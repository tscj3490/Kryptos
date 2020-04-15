<?php

class Application_Model_TicketsGroupsAssignees extends Muzyka_DataModel
{
    protected $_name = "tickets_groups_assignees";
    protected $_base_name = 'tga';
    protected $_base_order = 'tga.id ASC';

    public $id;
    public $group_id;
    public $role_id;
    public $assignee_id;

    /**
     * @return self|Zend_Db_Table_Row|Zend_Db_Table_Row_Abstract
     */
    public function save($data)
    {
        if (empty($data['id'])) {
            unset($data['id']);
            $row = $this->createRow($data);
        } else {
            $row = $this->requestObject($data['id']);
            $row->setFromArray($data);
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

    public function saveAssignees($ticketTypeId, $groupAssignees)
    {
        $ticketType = Application_Service_Utilities::getModel('TicketsTypes')->getOne($ticketTypeId, true);
        $roleIds = Application_Service_Utilities::getValues($ticketType, 'roles.id');

        $this->delete(['role_id IN (?)' => $roleIds]);

        foreach ($groupAssignees as $groupId => $roles) {
            foreach ($roles as $roleId => $assignees) {
                foreach ($assignees as $assigneeId) {
                    $this->save([
                        'group_id' => $groupId,
                        'role_id' => $roleId,
                        'assignee_id' => $assigneeId,
                    ]);
                }
            }
        }
    }

    public function getGroupsAssignees($groupsIds)
    {
        $mainAssignees = $this->getList(['group_id = 0']);

        if (!$groupsIds) {
            return $mainAssignees;
        }

        $groupAssignees = $this->getList(['group_id IN (?)' => $groupsIds]);

        if (empty($groupAssignees)) {
            return $mainAssignees;
        }

        Application_Service_Utilities::indexBy($mainAssignees, 'group_id', true);
        Application_Service_Utilities::indexBy($groupAssignees, 'group_id', true);
        $resultAssignees = [];
        $groupIds = array_unique(array_merge(array_keys($mainAssignees), array_keys($groupAssignees)));

        foreach ($groupIds as $groupId) {
            if (isset($groupAssignees[$groupId])) {
                $resultAssignees[] = $groupAssignees[$groupId];
            } elseif (isset($mainAssignees[$groupId])) {
                $resultAssignees[] = $mainAssignees[$groupId];
            }
        }

        return Application_Service_Utilities::ungroup($resultAssignees);
    }
}
