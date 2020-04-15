<?php

class Application_Model_Komunikaty extends Muzyka_DataModel
{
    protected $_name = "komunikaty";

    public function getAll()
    {
        return $this->fetchAll()->toArray();
    }

    public function save($data)
    {

        $row = $this->createRow();
        $row->temat = $data["temat"];
        $row->tresc = $data["tresc"];
        $row->save();
    }

    public function delKom($id)
    {
        $row = $this->requestObject($id);

        $row->delete();
        $komunikatOsoba = Application_Service_Utilities::getModel('KomunikatOsoba');
        $komunikatOsoba->delAllById($id);
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
    }
}