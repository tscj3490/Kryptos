<?php

class Application_Model_DataTransfersZbioryFielditems extends Muzyka_DataModel
{
    protected $_name = "data_transfers_zbiory_fielditems";

    public $injections = [
        'zbior' => ['Zbiory', 'zbior_id', 'getList', ['z.id IN (?)' => null], 'id', 'zbior', false],
    ];

    public function resultsFilter(&$results)
    {
        $this->loadData('zbior', $results);
    }
}