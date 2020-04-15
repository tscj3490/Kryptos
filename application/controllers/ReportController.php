<?php
class ReportController extends Muzyka_Admin
{
    public function init()
    {
        parent::init();
        $this->_helper->layout->setLayout('report');
        $this->view->section = 'Raporty';
    }

    public function generateAction()
    {
        $req = $this->getRequest();
        $reportType = $req->getParam('type');
    }
}
