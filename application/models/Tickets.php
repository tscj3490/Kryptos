<?php
use Zend\Db\Sql\Sql;

class Application_Model_Tickets extends Muzyka_DataModel
{
    protected $_name = "tickets";
    protected $_base_name = 't';
    protected $_base_order = 't.id ASC';

    public $memoProperties = [
        'id',
        'assignees',
    ];

    public $injections = [
        'statuses' => ['TicketsStatuses', 'type_id', 'getList', ['type_id IN (?)' => null], 'type_id', 'statuses', true],
        'status' => ['TicketsStatuses', 'status_id', 'getList', ['id IN (?)' => null], 'id', 'status', false],
        'assignees' => ['TicketsAssignees', 'id', 'getList', ['ticket_id IN (?)' => null], 'ticket_id', 'assignees', true],
        'type' => ['TicketsTypes', 'type_id', 'getList', ['id IN (?)' => null], 'id', 'type', false],
        'roles' => ['TicketsRoles', 'type_id', 'getList', ['type_id IN (?)' => null], 'type_id', 'roles', true],
    ];

    public $id;
    public $type_id;
    public $status_id;
    public $object_id;
    public $author_id;
    public $solver_id;
    public $topic;
    public $content;
    public $deadline_date;
    public $created_at;
    public $updated_at;

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

        $row->content = Application_Service_UtilityPurifier::purify($row->content);
        $row->deadline_date = $this->getNullableString($row->deadline_date);

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

    public function updateTypStatus($data)
    {
        $row = $this->requestObject($data['id']);

        $row->status_id = $data["status_id"];
        $row->type_id = $data["type_id"];

        $row->save();
    }

    public function resultsFilter(&$results)
    {
        Application_Service_Utilities::getModel('TicketsAssignees')->injectObjectsCustom('id', 'assignees', 'ticket_id', ['ticket_id IN (?)' => null], $results, 'getList', true);
        Application_Service_Utilities::getModel('Osoby')->injectObjectsCustom('author_id', 'author', 'id', ['o.id IN (?)' => null], $results, 'getList', false);
        Application_Service_Utilities::getModel('TicketsTypes')->injectObjectsCustom('type_id', 'type', 'id', ['tt.id IN (?)' => null], $results, 'getList', false);
        Application_Service_Utilities::getModel('TicketsStatuses')->injectObjectsCustom('status_id', 'status', 'id', ['ts.id IN (?)' => null], $results, 'getList', false);

        foreach ($results as &$result) {
            if (empty($result['statuses'])) {
                continue;
            }
            $statusesNamed = [];
            foreach ($result['statuses'] as $status) {
                if (!empty($status['system_name'])) {
                    $statusesNamed[$status['system_name']] = $status;
                }
            }
            $result['statuses_named'] = $statusesNamed;
        }

        return $results;
    }

    public function getListWithType($conditions = array(), $limit = null, $order = null)
    {
        $select = $this->getBaseQuery($conditions, $limit, $order);
        $select->joinLeft(['tt' => 'tickets_types'], 't.type_id = tt.id', []);

        return $this->getListFromSelect($select, $conditions, $limit, $order);
    }

    public function getGroupsDetails()
    {
   
         $select = $this->getAdapter()->select()
                        ->from(array('grp' => 'groups'), array('id', 'name'));

         $results = $select->query()->fetchAll();
                 return $results;
    }
}
