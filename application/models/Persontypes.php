<?php

class Application_Model_Persontypes extends Muzyka_DataModel
{
    protected $_name = 'persontypes';

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
    private $description;
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

        $row->description = $data['description'];
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

        $fielditemspersontypes = Application_Service_Utilities::getModel('Fielditemspersontypes');
        $fielditemspersontypes->delete(array('persontype_id = ?' => $id));

        $zbioryfielditemspersontypes = Application_Service_Utilities::getModel('Zbioryfielditemspersontypes');
        $zbioryfielditemspersontypes->delete(array('persontype_id = ?' => $id));

        $logData = $row->toArray();

        $row->delete();

        $this->addLog($this->_name, $logData, __METHOD__);
    }

    public function resultsFilter(&$results)
    {
        Application_Service_Zbiory::addLockedMetadata($results);
    }
}