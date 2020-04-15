<?php

class Application_Model_CourseCategories extends Muzyka_DataModel
{
    protected $_name = "course_categories";
    protected $_base_name = 'cc';
    protected $_base_order = 'cc.id ASC';

    public $memoProperties = array(
        'id',
        'unique_id',
    );

    public $id;
    public $unique_id;
    public $name;
    public $created_at;
    public $updated_at;

    public function getBaseQuery($conditions = array(), $limit = null, $order = null)
    {
        $select = $this->getSelect('cc');

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

    public function resultsFilter(&$results)
    {
        foreach ($results as &$row) {
            $icon = null;
            if (!empty($row['unique_id'])) {
                $icon = 'ico-img ico-kryptos-small';
            }

            $row['icon'] = $icon;
        }
    }
}
