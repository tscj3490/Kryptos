<?php

class Application_Model_Exams extends Muzyka_DataModel
{
    protected $_name = "exams";
    protected $_base_name = 'e';
    protected $_base_order = 'e.id ASC';

    public $memoProperties = array(
        'id',
        'unique_id',
        'type',
        'status',
    );

    public $id;
    public $unique_id;
    public $type;
    public $status;
    public $name;
    public $category_id;
    public $questions_count;
    public $required_to_pass;
    public $created_at;
    public $updated_at;

    public function getBaseQuery($conditions = array(), $limit = NULL, $order = NULL)
    {
        $select = $this->getSelect('e')
            ->joinLeft(array('ec' => 'exam_categories'), 'ec.id = e.category_id', array('category_name' => 'name'))
            ->joinLeft(array('es' => 'exams_sessions'), 'es.exam_id = e.id', array('sessions_count' => 'count(distinct es.id)'))
            ->joinLeft(array('esd' => 'exams_sessions'), 'esd.exam_id = e.id AND esd.result = 1', array('sessions_count_completed' => 'count(distinct esd.id)'))
            ->group('e.id');

        if (isset($conditions['api_courses'])) {
            $select
                ->where('e.status = 1')
                ->where('e.type = ?', Application_Service_Courses::TYPE_GLOBAL);
            unset($conditions['api_courses']);
        } elseif (Application_Service_Utilities::getAppType() !== 'hq_data') {
            $select->having('e.unique_id IS NULL OR e.status = 1 OR sessions_count > 0');
        }

        $this->addBase($select, $conditions, $limit, $order);

        return $select;
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

            if (!$isHqApp) {
                $row->type = Application_Service_Courses::TYPE_LOCAL;
            }
        } else {
            if (null === $row) {
                $row = $this->requestObject($data['id']);
                $row->setFromArray($data);
            }
            $row->updated_at = date('Y-m-d H:i:s');
        }

        if ($isHqApp && empty($row->unique_id) && $row->type == Application_Service_Courses::TYPE_GLOBAL) {
            $row->unique_id = $this->generateUniqueId(12);
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
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
