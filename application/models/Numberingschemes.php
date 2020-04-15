<?php

class Application_Model_Numberingschemes extends Muzyka_DataModel
{
    protected $_name = 'numberingschemes';
    private $id;
    private $name;

    public function getOne($id)
    {
        $sql = $this->select()
            ->where('id = ?', $id);

        return $this->fetchRow($sql);
    }

    public function getAll()
    {
        return $this->select()
            ->order('name ASC')
            ->query()
            ->fetchAll();
    }

    public function save($data)
    {
        if (!(int)$data['id']) {
            $row = $this->createRow();
            $row->created_at = date('Y-m-d H:i:s');
        } else {
            $row = $this->getOne($data['id']);
            $row->updated_at = date('Y-m-d H:i:s');
        }

        $historyCompare = clone $row;

        $row->name = mb_strtoupper($data['name']);
        $row->scheme = $data['scheme'];
        $row->type = $data['type'] * 1;
        $id = $row->save();

        $this->getRepository()->eventObjectChange($row, $historyCompare);

        /*$documenttemplates = Application_Service_Utilities::getModel('Documenttemplates');
        $documents = Application_Service_Utilities::getModel('Documents');

        $t_documenttemplates = $documenttemplates->fetchAll('numberingscheme_id = \''.$id.'\'');
        foreach ( $t_documenttemplates AS $documenttemplate ) {
           $t_data = array(
              'active' => 0,
              'countingactive' => 0,
           );
           $documents->update($t_data,'documenttemplate_id = \''.$documenttemplate->id.'\'');
        }*/

        $this->addLog($this->_name, $row->toArray(), __METHOD__);
        return $id;
    }

    public function remove($id)
    {
        $row = $this->getOne($id);
        if (!($row instanceof Zend_Db_Table_Row)) {
            throw new Exception('Rekord nie istnieje lub zostal skasowany');
        }

        $documenttemplates = Application_Service_Utilities::getModel('Documenttemplates');
        $documents = Application_Service_Utilities::getModel('Documents');

        $t_documenttemplates = $documenttemplates->fetchAll(array('numberingscheme_id = ?' => $id));
        foreach ($t_documenttemplates AS $documenttemplate) {
            $t_data = array(
                'active' => 0,
                'countingactive' => 0,
            );
            $documents->update($t_data, 'documenttemplate_id = \'' . $documenttemplate->id . '\'');
        }

        $t_data = array(
            'numberingscheme_id' => 0
        );
        $documenttemplates->update($t_data, 'numberingscheme_id = \'' . $id . '\'');

        $row->delete();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
    }
}