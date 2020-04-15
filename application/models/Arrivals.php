<?php

class Application_Model_Arrivals extends Muzyka_DataModel
{
    protected $_name = "arrivals";
    protected $_base_name = 'a';
    protected $_base_order = 'a.date DESC';

    const TYPE_PHONE_CALL = 1;
    const TYPE_MEDICAL_SHARE = 2;

    const STATUS_NEW = 1;

    const DIRECTION_INCOMING = 1;
    const DIRECTION_OUTGOING = 2;

    public $id;
    public $author_id;
    public $status = 1;
    public $type;
    public $object_id;
    public $direction;
    public $receive_user_id;
    public $source_user_id;
    public $destination_user_id;
    public $destination_company_id;
    public $date;
    public $topic;
    public $comment;
    public $created_at;
    public $updated_at;

    public function getListFromFilters($filters = array())
    {
        $params = array('*having' => array());

        if (!empty($filters['date_from'])) {
            $params['a.date >= ?'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $params['a.date <= ?'] = $filters['date_to'];
        }

        if (!empty($filters['direction'])) {
            $params['a.direction = ?'] = $filters['direction'];
        }

        if (!empty($filters['receive_user_name'])) {
            $params['*having']['receive_user_name LIKE ?'] = sprintf('%%%s%%', $filters['receive_user_name']);
        }
        if (!empty($filters['source_user_name'])) {
            $params['*having']['source_user_name LIKE ?'] = sprintf('%%%s%%', $filters['source_user_name']);
        }
        if (!empty($filters['destination_name'])) {
            $params['*having']['destination_name LIKE ?'] = sprintf('%%%s%%', $filters['destination_name']);
        }

        if (!empty($filters['topic'])) {
            $params['topic LIKE ?'] = sprintf('%%%s%%', $filters['topic']);
        }
        if (!empty($filters['comment'])) {
            $params['comment LIKE ?'] = sprintf('%%%s%%', $filters['comment']);
        }

        return $this->getList($params);
    }

    public function getBaseQuery($conditions = array(), $limit = null, $order = null)
    {
        $select = $this->getSelect('a')
            /* RECEIVE */
            ->joinLeft(array('ru' => 'osoby'), 'a.receive_user_id = ru.id', array('receive_user_name' => "UPPER(CONCAT(ru.nazwisko, ' ', ru.imie))"))

            /* SOURCE */
            ->columns(array('source_user_name' => "IF(su.company_id IS NULL, CONCAT_WS(' ', su.nazwisko, su.imie), CONCAT(su.nazwisko, ' ', su.imie, ' - ', suc.name))"))
            ->joinLeft(array('su' => 'osoby'), 'a.source_user_id = su.id', array())
            ->joinLeft(array('suc' => 'companiesnew'), 'su.company_id = suc.id', array())

            /* DESTINATION */
            ->columns(array('destination_name' => "IF(a.destination_user_id IS NULL, dc.name, IF(du.company_id IS NULL, CONCAT_WS(' ', du.nazwisko, du.imie), CONCAT(du.nazwisko, ' ', du.imie, ' - ', duc.name)))"))
            ->joinLeft(array('du' => 'osoby'), 'a.destination_user_id = du.id', array())
            ->joinLeft(array('duc' => 'companiesnew'), 'du.company_id = duc.id', array())
            ->joinLeft(array('dc' => 'companiesnew'), 'a.destination_company_id = du.id', array())

            ->group('a.id');

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
