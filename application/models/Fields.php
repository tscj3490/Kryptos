<?php

class Application_Model_Fields extends Muzyka_DataModel
{
    protected $_name = 'fields';

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
    private $fielditemscategory_id;
    private $giodofield;
    private $created_at;
    private $updated_at;

    public function getOne($id)
    {
        $sql = $this->select()
            ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }

    public function getAll($where, $order, $limit, $start, $columns = array(), $joinalldata = 1)
    {
        $useJoin = false;
        if (empty($columns)) {
            $columns = array('*', 'usedInElements' => 'IF(ff.id IS NOT NULL, 1, 0)');
            $useJoin = true;
        }

        $sql = $this->select()
            ->from(['f' => $this->_name], $columns);

        if ($useJoin) {
            $sql->setIntegrityCheck(false)
                ->joinLeft(['ff' => 'fielditemsfields'], 'ff.field_id = f.id', [])
                ->group('f.id');
        }

        $sql->order($order);
        if ($joinalldata == 1) {
            $sql->setIntegrityCheck(false)
                ->joinLeft(array('fc' => 'fieldscategories'), 'fc.id = f.fieldscategory_id', array('fc.name AS categoryname'));
        }

        if ($limit > 0) {
            $sql->limit($limit, $start);
        }
        if ($where <> '') {
            $sql->where($where);
        }

        if ($useJoin) {
            $results = $this->fetchAll($sql)->toArray();
            $this->resultsFilter($results);
            $this->addMemoObjects($results);
        } else {
            $results = $this->fetchAll($sql);
        }

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

        $row->giodofield = $data['giodofield'] * 1;
        $row->name = preg_replace('/\s+/', ' ', trim(mb_strtoupper($data['name'])));
        $row->fieldscategory_id = $data['fieldscategory_id'] * 1;
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
        $fielditemsfields->delete(array('field_id = ?' => $id));

        $zbioryfielditemsfields = Application_Service_Utilities::getModel('Zbioryfielditemsfields');
        $zbioryfielditemsfields->delete(array('field_id = ?' => $id));

        $logData = $row->toArray();

        $row->delete();

        $this->addLog($this->_name, $logData, __METHOD__);
    }

    public function removeandmove($fromId, $toId)
    {
        $row = $this->requestObject($fromId);
        $row2 = $this->requestObject($toId);

        if ($row->is_locked && Application_Service_Utilities::getAppType() !== 'hq_data') {
            Throw new Exception('Rekord jest zablokowany', 500);
        }

        $t_data = array(
            'field_id' => $toId
        );

        $fielditemsfields = Application_Service_Utilities::getModel('Fielditemsfields');
        $fielditemsfields->update($t_data, ['field_id = ?' => $fromId]);

        $zbioryfielditemsfields = Application_Service_Utilities::getModel('Zbioryfielditemsfields');
        $zbioryfielditemsfields->update($t_data, ['field_id = ?' => $fromId]);

        $logData = $row->toArray();

        $row->delete();

        $this->addLog($this->_name, $logData, __METHOD__);
    }

    public function resultsFilter(&$results)
    {
        Application_Service_Zbiory::addLockedMetadata($results);
    }
}