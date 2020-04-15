<?php

class Application_Service_Events extends Zend_EventManager_EventManager
{
    /** Singleton */
    protected static $_instance = null;
    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? (self::$_instance = new self()) : self::$_instance; }

    static private $initialized = false;

    static public function initEventManager()
    {
        if (self::$initialized) {
            // problem with forward
            return false;

            Throw new Exception('Cannot initialize twice');
        }

        $manager = self::getInstance();

        $manager->attach('registry.param.create', 'Application_Service_RegistryEvents::onParamCreate');
        $manager->attach('registry.param.update', 'Application_Service_RegistryEvents::onParamUpdate');
        $manager->attach('registry.param.delete', 'Application_Service_RegistryEvents::onParamDelete');
        $manager->attach('registry.update', 'Application_Service_RegistryEvents::onRegistryUpdate');
        $manager->attach('registry.assignee.add', 'Application_Service_RegistryEvents::onRegistryAssigneeAdd');
        $manager->attach('registry.assignee.remove', 'Application_Service_RegistryEvents::onRegistryAssigneeRemove');

        $manager->attach('osoby.permissions.add', 'Application_Service_RegistryEvents::onOsobyPermissionsAdd');
        $manager->attach('osoby.permissions.remove', 'Application_Service_RegistryEvents::onOsobyPermissionsRemove');

        //$manager->attach('proposal.status.change', 'Application_Service_ProcedureProposalEmployeeAdd::onStatusChange');
        $manager->attach('proposal.create', 'Application_Service_ProcedureProposalEmployeeAdd::onProposalCreate');
        $manager->attach('proposal.item.status.change', 'Application_Service_ProcedureProposalEmployeeAdd::onProposalItemStatusChange');

        //$manager->attach('ticket.create', 'Application_Service_ProcedureProposalEmployeeAdd::onStatusChange');

        $manager->attach('task.complete', 'Application_Service_ProcedureProposalEmployeeAdd::onTaskComplete');
        $manager->attach('ticket.create', 'Application_Service_ProcedureProposalEmployeeAdd::onTicketCreate');
        $manager->attach('ticket.status.change', 'Application_Service_ProcedureProposalEmployeeAdd::onTicketStatusChange');

        self::$initialized = true;
    }

}