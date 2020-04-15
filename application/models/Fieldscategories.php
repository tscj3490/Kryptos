<?php

class Application_Model_Fieldscategories extends Muzyka_DataModel
{
    protected $_name = 'fieldscategories';

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

    public function getAllMini()
    {
        $t_data = $this->fetchAll(null, 'name');
        $t_sel = array();
        foreach ($t_data AS $data) {
            $t_sel[$data->id] = $data->name;
        }

        return $t_sel;
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

        $row->name = trim(mb_strtoupper($data['name']));
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

        $fields = Application_Service_Utilities::getModel('Fields');
        $t_data = array(
            'fieldscategory_id' => 0,
        );
        $fields->update($t_data, 'fieldscategory_id = \'' . $id . '\'');

        $logData = $row->toArray();

        $row->delete();

        $this->addLog($this->_name, $logData, __METHOD__);
    }

    public function resultsFilter(&$results)
    {
        Application_Service_Zbiory::addLockedMetadata($results);
    }
}