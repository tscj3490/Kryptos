<?php

class Application_Model_Cron extends Muzyka_DataModel
{
    protected $_name = "cron";

    public $id;
    public $function;
    public $name;
    public $interval;
    public $last_run;

    public function getOutdatedJobs($allJobs = false)
    {
        $select = $this->select();

        if ($allJobs === false) {
            $select->where('NOW() - INTERVAL `interval` SECOND > last_run');
        }

        return $this->fetchAll($select);
    }

    public function save($data)
    {
        if (empty($data['id'])) {
            $row = $this->createRow();
        } else {
            $row = $this->find($data['id']);
        }

        $row->function = $data['function'];
        $row->name = $data['name'];
        $row->interval = $data['interval'];
        $row->last_run = $data['last_run'];

        $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row->id;
    }
}