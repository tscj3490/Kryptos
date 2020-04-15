<?php

class Application_Model_CoursesSessions extends Muzyka_DataModel
{
    protected $_name = "courses_sessions";
    protected $_base_name = 'cs';
    protected $_base_order = 'cs.id ASC';

    public $id;
    public $type;
    public $unique_id;
    public $app_id;
    public $user_id;
    public $course_id;
    public $last_page = 0;
    public $is_done;
    public $done_date;
    public $created_at;
    public $updated_at;

    public function getBaseQuery($conditions = array(), $limit = null, $order = null)
    {
        $examSessionsSelect = $this->getAdapter()->select()
            ->from('exams_sessions')
            ->order('done_date DESC');

        $select = $this->getSelect($this->_base_name)
            ->joinInner(array('o' => 'osoby'), 'o.id = cs.user_id', array('imie', 'nazwisko', 'login' => 'login_do_systemu'))
            ->joinInner(array('c' => 'courses'), 'c.id = cs.course_id', array())
            ->joinLeft(array('es' => $examSessionsSelect), 'es.exam_id = c.exam_id AND es.user_id = cs.user_id', array('exam_date' => 'es.done_date', 'exam_done' => 'IFNULL(es.is_done, 0)', 'exam_result' => 'IFNULL(es.result, 0)'))
            ->group('cs.id');

        $this->addBase($select, $conditions, $limit, $order);

        return $select;
    }

    public function getAllByCourse($courseId)
    {
        return $this->_db->select()
            ->from(array('cs' => 'courses_sessions'), array('is_done', 'done_date'))
            ->joinInner(array('o' => 'osoby'), 'o.id = cs.user_id', array('o.*'))
            ->where('cs.course_id = ?', $courseId)
            ->query()
            ->fetchAll();
    }

    public function getAllByCourseArray($course)
    {
        $sql = $this->select()->where('course_id = ?', $course);

        $sql->setIntegrityCheck(false);

        $coursesSessions = $this->fetchAll($sql)->toArray();
        $osoby = array();
        foreach ($coursesSessions as $course) {
            $osoby[] = $course['user_id'];
        }

        return $osoby;
    }

    public function save($data)
    {
        $isHqApp = Application_Service_Utilities::getAppType() === 'hq_data';
        $row = $this->tryImportRow($data);

        if (empty($data['id'])) {
            unset($data['id']);
            $row = $this->createRow($data);
            $row->created_at = date('Y-m-d H:i:s');

            if ($isHqApp) {
                $row->unique_id = $this->generateUniqueId(12);
            }
        } else {
            if (null === $row) {
                $row = $this->requestObject($data['id']);
                $row->setFromArray($data);
            }
            $row->updated_at = date('Y-m-d H:i:s');
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

    public function removeBySzkolenie($szkolenie)
    {
        $db = $this->getAdapter();
        $db->delete($this->_name, $db->quoteInto('course_id = ?', $szkolenie));
    }
}