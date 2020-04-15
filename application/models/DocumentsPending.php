<?php

class Application_Model_DocumentsPending extends Muzyka_DataModel
{
    protected $_name = "documents_pending";
    protected $_base_name = 'dp';
    protected $_base_order = 'dp.created_at ASC';

    const STATUS_REMOVED = 0;
    const STATUS_PENDING = 1;
    const STATUS_ACCEPTED = 2;
    const STATUS_CREATED = 3;

    public $id;
    public $user_id;
    public $documenttemplate_id;
    public $document_id;
    public $status;
    public $created_at;
    public $updated_at;

    public function getBaseQuery($conditions = array(), $limit = null, $order = null)
    {
        $select = $this->getSelect('dp')
            ->joinInner(array('o' => 'osoby'), 'dp.user_id = o.id', array('imie', 'nazwisko', 'stanowisko'))
            ->joinInner(array('dt' => 'documenttemplates'), 'dt.id = dp.documenttemplate_id', array('template_name' => 'dt.name', 'template_type' => 'dt.type'));

        $this->addBase($select, $conditions, $limit, $order);

        return $select;
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
        } else {
            $row = $this->requestObject($data['id']);
            $row->setFromArray($data);
            $row->updated_at = date('Y-m-d H:i:s');
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

    public function remove($id)
    {
        $row = $this->requestObject($id);

        $row->status = self::STATUS_REMOVED;
        $row->updated_at = date('Y-m-d H:i:s');
        $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);
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

    public function getActiveByUsers($osobyIds)
    {
        $select = $this->_db->select()
            ->from($this->_name, array('id'))
            ->where('user_id IN (?)', $osobyIds)
            ->where('status != ?', [Application_Model_DocumentsPending::STATUS_PENDING, Application_Model_DocumentsPending::STATUS_ACCEPTED]);

        return $select->query()->fetchAll(PDO::FETCH_COLUMN);
    }
}
