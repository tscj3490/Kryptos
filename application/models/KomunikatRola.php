<?php

class Application_Model_KomunikatRola extends Muzyka_DataModel {

    protected $_name = "komunikat_rola";

    public function save($data) {

        foreach ($data as $rol) {
            $row = $this->createRow();
            $row->rola_id = $rol;
            $row->save();
        }
    }

}
