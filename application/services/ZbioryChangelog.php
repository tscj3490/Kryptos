<?php

class Application_Service_ZbioryChangelog {

    protected static $_instance = null;

    private function __clone() {
        
    }

    public static function getInstance() {
        return null === self::$_instance ? (self::$_instance = new self()) : self::$_instance;
    }

    public function saveUpowaznieniaDifferences($zbiorId, $newArray, $oldArray) {
        foreach ($newArray as $nak => $nav) {
            $r = $this->getRecordByPersonId($oldArray, $newArray[$nak]['osoby_id']);
            if ($r != null) {
                if ($this->compareUpowaznienia($newArray[$nak], $r)) {
                    $this->logToDb($zbiorId, 'Modyfikacja upoważnień dla ' . $this->resolvePerson($newArray[$nak]['osoby_id']), $this->serializeUpowaznienia($r), $this->serializeUpowaznienia($newArray[$nak]));
                }
            } else {
                $this->logToDb($zbiorId, 'Modyfikacja upoważnień dla ' . $this->resolvePerson($newArray[$nak]['osoby_id']), '', $this->serializeUpowaznienia($newArray[$nak]));
            }
        }

        foreach ($oldArray as $oak => $oav) {
            $r = $this->getRecordByPersonId($newArray, $oldArray[$oak]['osoby_id']);
            if ($r == null) {
                $this->logToDb($zbiorId, 'Modyfikacja upoważnień dla ' . $this->resolvePerson($oldArray[$oak]['osoby_id']), $this->serializeUpowaznienia($oldArray[$oak]), '');
            }
        }
    }

    private function serializeUpowaznienia($r) {
        $fields = array('czytanie', 'pozyskiwanie', 'wprowadzanie', 'modyfikacja', 'usuwanie');
        $result = '';
        foreach ($fields as $f) {
            if ($r[$f] == 1) {
                $result .= strtoupper($f . ' ');
            }
        }

        return $result;
    }

    private function getRecordByPersonId($array, $personId) {
        foreach ($array as $k => $v) {
            if ($array[$k]['osoby_id'] == $personId) {
                return $v;
            }
        }

        return null;
    }

    private function compareUpowaznienia($u1, $u2) {
        $fields = array('czytanie', 'pozyskiwanie', 'wprowadzanie', 'modyfikacja', 'usuwanie');

        foreach ($fields as $f) {
            if ($u1[$f] != $u2[$f]) {
                return true;
            }
        }

        return false;
    }

    public function saveZbioryDifferences($newData, $existingData, $modifiedFields) {
        $this->logModifiedFields($newData, $existingData, $modifiedFields);
    }

    public function saveOsobyOdpowiedzialneDifferences($zbiorId, $newArray, $oldArray) {
        $this->iterateDependencies($zbiorId, $newArray, $oldArray, 'Osoby odpowiedzialne', 'resolvePerson');
    }

    private function resolvePerson($v) {
        $model = Application_Service_Utilities::getModel('Osoby');
        $person = $model->getOne($v);

        return $person->imie . ' ' . $person->nazwisko;
    }

    private function iterateDependencies($zbiorId, $newArray, $oldArray, $description, $resolveFunc) {
        if(!is_array($newArray) OR !is_array($oldArray)){
            return;
        }
        $newOnes = array_diff($newArray, $oldArray);
        $toDeleteOnes = array_diff($oldArray, $newArray);

        foreach ($newOnes as $v) {
            $this->logToDb($zbiorId, $description, '', $this->{$resolveFunc}($v));
        }

        foreach ($toDeleteOnes as $v) {
            $this->logToDb($zbiorId, $description, $this->{$resolveFunc}($v), '');
        }
    }

    public function saveZbiorySafeguardsDifferences($zbiorId, $newArray, $oldArray) {
        $this->iterateDependencies($zbiorId, $newArray, $oldArray, 'Zabezpieczenie', 'resolveSafeguard');
    }

    private function resolveSafeguard($v) {
        $model = Application_Service_Utilities::getModel('Zabezpieczenia');

        $safeguard = $model->getOne($v);

        return $safeguard->nazwa . ' [' . $safeguard->giodo_field . ']';
    }

    public function savePomieszczeniaDifferences($zbiorId, $newArray, $oldArray) {
        $this->iterateDependencies($zbiorId, $newArray, $oldArray, 'Pomieszczenie', 'resolvePomieszczenie');
    }
    
    public function logCustomEditDate($zbiorId, $oldValue, $newValue){
        $this->logToDb($zbiorId, 'Własna data edycji' , $oldValue, $newValue);
    }

    private function resolvePomieszczenie($v) {
        $model = Application_Service_Utilities::getModel('Pomieszczenia');
        $model->loadData('pomieszcenia', $v);
        $pomieszczenie = $model->getOne($v);
        
        return $pomieszczenie->nazwa . ' ' . $pomieszczenie->nr.'['.$pomieszczenie->budynek->nazwa.']';
    }

    public function saveAplikacjeDifferences($zbiorId, $newArray, $oldArray) {
        $this->iterateDependencies($zbiorId, $newArray, $oldArray, 'Aplikacja', 'resolveApplication');
    }

    private function resolveApplication($v) {
        $model = Application_Service_Utilities::getModel('Applications');

        $row = $model->getOne($v);
        return $row->producent . ' ' . $row->nazwa;
    }

    private function logModifiedFields($newData, $existingData, $modifiedFields, $prefix = '') {
        foreach ($modifiedFields as $key => $v) {
            if ($key != 'data_edycji'){
                if($existingData[$key] != $newData[$key]){
                $this->logToDb($existingData['id'], $prefix . ' ' . $key, $existingData[$key], $newData[$key]);
                }            
            }
        }
    }

    private function logToDb($zbiorId, $field, $oldValue, $newValue) {
        $zbioryChangelog = Application_Service_Utilities::getModel('ZbioryChangelog');

        $dataChangelog = array(
            'zbior_id' => $zbiorId,
            'user_id' => Application_Service_Authorization::getInstance()->getUserId(),
            'date' => date("Y-m-d H:i:s"),
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => $newValue
        );
        $zbioryChangelog->save($dataChangelog);
    }

}
