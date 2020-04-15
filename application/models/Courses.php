<?php

class Application_Model_Courses extends Muzyka_DataModel
{
    protected $_name = "courses";
    protected $_base_name = 'c';
    protected $_base_order = 'c.created_at ASC';

    public $memoProperties = array(
        'id',
        'unique_id',
        'type',
        'status',
    );
    
    public $id;
    public $type;
    public $status;
    public $unique_id;
    public $topic;
    public $author_id;
    public $category_id;
    public $exam_id;
    public $description;
    public $global_author_name;
    public $pages_count;
    public $created_at;
    public $updated_at;

    public function getBaseQuery($conditions = array(), $limit = null, $order = null)
    {
        $select = $this->getSelect('c')
            ->joinLeft(array('cc' => 'course_categories'), 'cc.id = c.category_id', array('category_name' => 'name'))
            ->joinLeft(array('cs' => 'courses_sessions'), 'cs.course_id = c.id', array('sessions_count' => 'count(distinct cs.id)'))
            ->joinLeft(array('csd' => 'courses_sessions'), 'csd.course_id = c.id AND csd.is_done = 1', array('sessions_count_done' => 'count(distinct csd.id)'))
            ->joinLeft(array('e' => 'exams'), 'e.id = c.exam_id', array('exam_id' => 'id', 'exam_name' => 'name'))
            ->group('c.id');

        if (isset($conditions['user'])) {
            $select->joinInner(array('csus' => 'courses_sessions'), 'csus.course_id = c.id')
                ->where('csus.user_id = ?', $conditions['user']);
            unset($conditions['user']);
        }

        if (isset($conditions['api_courses'])) {
            $select
                ->joinLeft(array('o' => 'osoby'), 'o.id = c.author_id', ['global_author_name' => "UPPER(CONCAT(o.nazwisko, ' ', o.imie))"])
                ->where('c.status = 1')
                ->where('c.type = ?', Application_Service_Courses::TYPE_GLOBAL);
            unset($conditions['api_courses']);
        } elseif (Application_Service_Utilities::getAppType() !== 'hq_data') {
            $select->having('c.unique_id IS NULL OR c.status = 1 OR sessions_count > 0');
        }

        $this->addBase($select, $conditions, $limit, $order);

        return $select;
    }

    public function getAllForTypeahead()
    {
        return $this->_db->select()
            ->from(array('c' => $this->_name), array('id', 'name' => 'topic'))
            ->order('c.topic ASC')
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @return self|Zend_Db_Table_Row|Zend_Db_Table_Row_Abstract
     */
    public function save($data)
    {
        $isHqApp = Application_Service_Utilities::getAppType() === 'hq_data';
        $row = $this->tryImportRow($data);

        if (empty($data['id'])) {
            unset($data['id']);
            $row = $this->createRow($data);
            $row->created_at = date('Y-m-d H:i:s');
        } else {
            if (null === $row) {
                $row = $this->requestObject($data['id']);
                $row->setFromArray($data);
            }
            $row->updated_at = date('Y-m-d H:i:s');
        }

        if (empty($row->exam_id)) {
            $row->exam_id = null;
        }

        if ($isHqApp && empty($row->unique_id) && $row->type == Application_Service_Courses::TYPE_GLOBAL) {
            $row->unique_id = $this->generateUniqueId(12);
        }

        $row->save();

        if ($isHqApp || $row->type == Application_Service_Courses::TYPE_LOCAL) {
            $pagesCount = 0;

            if (!empty($data['files'])) {
                $filesService = Application_Service_Files::getInstance();
                $coursesPagesModel = Application_Service_Utilities::getModel('CoursesPages');
                $filesExternal = Application_Service_Utilities::getModel('FilesExternal');

                $this->_db->update('courses_pages', array('order' => 0), array('course_id = ?' => $row->id));
                $order = 1;
                foreach ($data['files'] as $file) {
                    if (is_numeric($file)) {
                        $this->getAdapter()->update('courses_pages', array(
                            'order' => $order,
                        ), array(
                            'id = ?' => $file,
                            'course_id = ?' => $row->id,
                        ));
                    } else {
                        $pageData = [
                            'course_id' => $row->id,
                            'order' => $order,
                        ];
                        $file = json_decode($file, true);

                        switch ($file['type']) {
                            case 1:
                                $fileUri = sprintf('uploads/courses/%s', $file['uploadedUri']);
                                $savedFile = $filesService->create(Application_Service_Files::TYPE_COURSE, $fileUri, $file['name']);
                                $pageData['object_id'] = $savedFile->id;
                                $pageData['type'] = Application_Model_CoursesPages::TYPE_FILE;
                                break;
                            case 2:
                                $savedFile = $filesExternal->save([
                                    'type' => Application_Model_FilesExternal::TYPE_YOUTUBE_VIDEO,
                                    'uri' => $file['uri']
                                ]);
                                $pageData['object_id'] = $savedFile->id;
                                $pageData['type'] = Application_Model_CoursesPages::TYPE_FILE_EXTERNAL;
                                break;
                            default:
                                Throw new Exception('Invalid file type', 500);
                        }

                        $coursesPagesModel->save($pageData);
                    }

                    $order++;
                    $pagesCount++;
                }

                $this->_db->delete('courses_pages', array(
                    'course_id = ?' => $row->id,
                    '`order` = ?' => 0
                ));

            } else {
                $this->_db->delete('courses_pages', array('course_id = ?' => $row->id));
            }

            $row->pages_count = $pagesCount;
            $row->save();
        }

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

    public function disable($id)
    {
        $row = $this->getOne($id);
        if ($row instanceof Zend_Db_Table_Row) {
            $row->enabled = false;
            $row->save();
        }
    }

    public function enable($id)
    {
        $row = $this->getOne($id);
        if ($row instanceof Zend_Db_Table_Row) {
            $row->enabled = true;
            $row->save();
        }
    }

    public function resultsFilter(&$results)
    {
        foreach ($results as &$row) {
            $icon = null;
            if ($row['unique_id']) {
                if ((int) $row['type'] === Application_Service_Courses::TYPE_GLOBAL) {
                    $icon = 'ico-img ico-kryptos-small';
                }
            }

            $row['icon'] = $icon;
        }
    }
}
