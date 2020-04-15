<?php

include_once('OrganizacjaController.php');

class LoghistoryController extends OrganizacjaController {

    public function init() {
        parent::init();
    }

    public function indexAction() {
        $logs = file(APPLICATION_PATH . '/../log_history.log');
        $logs = array_reverse($logs);
        $new_logs = array();
        if (is_array($logs) && count($logs) > 0) {
            foreach ($logs as &$log) {
                $tmp = explode('||', $log);
                $tmp[0] = date("d.m.Y h:i:s", $tmp[0]);
                $new_logs[] = $tmp;
            }
        }
        $this->view->logs = $new_logs;
    }

}
