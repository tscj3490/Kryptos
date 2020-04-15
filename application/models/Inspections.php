<?php

class Application_Model_Inspections extends Muzyka_DataModel
{
    protected $_name = "inspections";
    protected $_base_name = 'i';
    protected $_base_order = 'i.date DESC';

    const TYPE_BASIC = 1;
    const TYPE_ZBIOR = 2;

    public $id;
    public $type;
    public $object_id;
    public $author_id;
    public $title;
    public $date;
    public $comment;
    public $created_at;
    public $updated_at;

    public function resultsFilter(&$results)
    {
        $osobyModel = Application_Service_Utilities::getModel('Osoby');
        $zbioryModel = Application_Service_Utilities::getModel('Zbiory');

        $osobyModel->injectObjectsCustom('author_id', 'author', 'id', ['o.id IN (?)' => null], $results, 'getList', false);
        $zbioryModel->injectObjectsCustom('zbior_id', 'zbior', 'id', ['z.id IN (?)' => null], $results, 'getList', false);

        return $results;
    }

    /**
     * @return self|Zend_Db_Table_Row|Zend_Db_Table_Row_Abstract
     */
    public function save($data)
    {
        if (empty($data['id'])) {
            unset($data['id']);
            $row = $this->createRow($data);
            $row->created_at = date('Y-m-d H:i:s');
            $row->author_id = Application_Service_Authorization::getInstance()->getUserId();
        } else {
            $row = $this->requestObject($data['id']);
            $row->setFromArray($data);
            $row->updated_at = date('Y-m-d H:i:s');
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }
}
