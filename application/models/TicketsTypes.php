<?php

class Application_Model_TicketsTypes extends Muzyka_DataModel {

    protected $_name = "tickets_types";
    protected $_base_name = 'tt';
    protected $_base_order = 'tt.name ASC';

    public $memoProperties = [
        'id',
        'statuses',
        'roles',
    ];

    public $id;
    public $type;
    public $object_id;
    public $name;
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

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

    public function resultsFilter(&$results)
    {
        Application_Service_Utilities::getModel('TicketsStatuses')->injectObjectsCustom('id', 'statuses', 'type_id', ['type_id IN (?)' => null], $results, 'getList', true);
        foreach ($results as &$result) {
            foreach ($result['statuses'] as $status) {
                if ($status['state'] === '1') {
                    $result['status_new'] = $status;
                    break;
                }
            }
        }

        Application_Service_Utilities::getModel('TicketsRoles')->injectObjectsCustom('id', 'roles', 'type_id', ['type_id IN (?)' => null], $results, 'getList', true);
        foreach ($results as &$result) {
            foreach ($result['roles'] as $role) {
                if ($role['aspect'] === '1') {
                    $result['role_author'] = $role;
                    break;
                }
            }
        }
    }

}
