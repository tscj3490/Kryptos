<?php

class Application_Model_Persons extends Muzyka_DataModel
{
    protected $_name = 'persons';

    public $memoProperties = array(
        'id',
        'is_locked',
        'unique_id',
        'type',
    );

    private $id;
    private $unique_id;
    private $is_locked;
    private $name;
    private $created_at;
    private $updated_at;

    public function getOne($id)
    {
        $sql = $this->select()
            ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }

    public function getAll()
    {
        $results = $this->select()
            ->order('name ASC')
            ->query()
            ->fetchAll();

        $this->resultsFilter($results);
        $this->addMemoObjects($results);

        return $results;
    }

    public function save($data)
    {
        if (empty($data['id'])) {
            $row = $this->createRow();
            $row->created_at = date('Y-m-d H:i:s');

            if (Application_Service_Utilities::getAppType() === 'hq_data') {
                do {
                    $unique_id = substr(md5(microtime(true)), 0, 12);
                    $present = $this->fetchRow($this->select()->where('unique_id = ?', $unique_id));
                } while ($present);

                $row->unique_id = $unique_id;
                $row->is_locked = true;
            }
        } else {
            $row = $this->getOne($data['id']);

            if (!is_null($row->unique_id) && $row->is_locked && Application_Service_Utilities::getAppType() !== 'hq_data') {
                Throw new Exception('Rekord jest zablokowany', 500);
            } else {
                $row->updated_at = date('Y-m-d H:i:s');
            }
        }

        $row->name = preg_replace('/\s+/', ' ', trim(mb_strtoupper($data['name'])));
        $id = $row->save();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }

    public function remove($id)
    {
        $row = $this->requestObject($id);

        if ($row->is_locked && Application_Service_Utilities::getAppType() !== 'hq_data') {
            Throw new Exception('Rekord jest zablokowany', 500);
        }

        $fielditemsfields = Application_Service_Utilities::getModel('Fielditemsfields');
        $fielditemsfields->delete(array('person_id = ?' => $id));
        $fielditemspersonjoines = Application_Service_Utilities::getModel('Fielditemspersonjoines');
        $fielditemspersonjoines->delete(array(
            'personjoinfrom_id = ?' => $id,
            'personjointo_id = ?' => $id,
        ));
        $fielditemspersons = Application_Service_Utilities::getModel('Fielditemspersons');
        $fielditemspersons->delete(array('person_id = ?' => $id));
        $fielditemspersontypes = Application_Service_Utilities::getModel('Fielditemspersontypes');
        $fielditemspersontypes->delete(array('person_id = ?' => $id));

        $zbioryfielditemsfields = Application_Service_Utilities::getModel('Zbioryfielditemsfields');
        $zbioryfielditemsfields->delete(array('person_id = ?' => $id));
        $zbioryfielditemspersonjoines = Application_Service_Utilities::getModel('Zbioryfielditemspersonjoines');
        $zbioryfielditemspersonjoines->delete(array(
            'personjoinfrom_id = ?' => $id,
            'personjointo_id = ?' => $id,
        ));
        $zbioryfielditemspersons = Application_Service_Utilities::getModel('Zbioryfielditemspersons');
        $zbioryfielditemspersons->delete(array('person_id = ?' => $id));
        $zbioryfielditemspersontypes = Application_Service_Utilities::getModel('Zbioryfielditemspersontypes');
        $zbioryfielditemspersontypes->delete(array('person_id = ?' => $id));

        $logData = $row->toArray();

        $row->delete();

        $this->addLog($this->_name, $logData, __METHOD__);
    }

    public function resultsFilter(&$results)
    {
        Application_Service_Zbiory::addLockedMetadata($results);
    }
}