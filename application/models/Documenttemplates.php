<?php

class Application_Model_Documenttemplates extends Muzyka_DataModel
{
    protected $_name = 'documenttemplates';
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

    public function getAllForTypeahead($conditions = [])
    {
        $select = $this->_db->select()
            ->from(array('dt' => $this->_name), array('id', 'name'))
            ->order('name ASC');

        $this->addConditions($select, $conditions);

        return $select->query()
            ->fetchAll(PDO::FETCH_ASSOC);
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
        $row->type = $data['type'] * 1;
        $row->numberingscheme_id = $data['numberingscheme_id'] * 1;
        $row->content = $data['content'];
        $row->active = $data['active'] * 1;
        $row->icon = $data['icon'];
        $row->signature_required = (int) $data['signature_required'];
        $id = $row->save();

        $this->getRepository()->eventObjectChange($row, $historyCompare);

        $documenttemplatesosoby = Application_Service_Utilities::getModel('Documenttemplatesosoby');
        $documenttemplatesosoby->delete(array('documenttemplate_id = ?' => $id));
        $t_options = json_decode($data['persons']);
        if (is_object($t_options->t_personsdata)) {
            foreach ($t_options->t_personsdata AS $option) {
                $iden = str_replace('id', '', $option);
                $t_data = array(
                    'documenttemplate_id' => $id,
                    'osoba_id' => $iden,
                );
                $documenttemplatesosoby->insert($t_data);
            }
        }

        $tasksModel = Application_Service_Utilities::getModel('Tasks');
        $tasksService = Application_Service_Tasks::getInstance();
        $task = $tasksModel->getOne([
            'type = ?' => Application_Service_Tasks::TYPE_DOCUMENT,
            'object_id = ?' => $row->id,
        ]);

        if ($row->signature_required) {
            if (!$task) {
                $task = $tasksService->create([
                    'type' => Application_Service_Tasks::TYPE_DOCUMENT,
                    'object_id' => $row->id,
                    'status' => 1,
                    'users_type' => 1,
                    'author_osoba_id' => Application_Service_Authorization::getInstance()->getUserId(),
                    'title' => 'Potwierdź dokument: ' . $row->name,
                    'trigger_type' => 4,
                    'trigger_config' => '{"day":"7"}',
                    'activate_before_days' => 6,
                    'send_notification_message' => 1,
                ]);
            } else {
                $task->title = 'Potwierdź dokument: ' . $row->name;
                $task->status = 1;
                $task->save();
            }
        } else {
            if ($task) {
                $task->status = 0;
                $task->save();
            }
        }

        /*if ( $data['active'] == 1 AND $data['type'] > 0 ) {
           $documenttemplates = Application_Service_Utilities::getModel('Documenttemplates');
           $documents = Application_Service_Utilities::getModel('Documents');
           $t_documenttemplates = $documenttemplates->fetchAll('active = \'1\' AND type = \''.$data['type'].'\' AND id <> \''.$id.'\'');
           foreach ( $t_documenttemplates AS $documenttemplate ) {
              $t_data = array(
                 'active' => 0,
                 'countingactive' => 0,
              );
              $documents->update($t_data,'documenttemplate_id = \''.$documenttemplate->id.'\'');

              $t_data = array(
                 'active' => 0
              );
              $documenttemplates->update($t_data,'active = \'1\' AND type = \''.$data['type'].'\' AND id <> \''.$id.'\'');
           }
        }

        $documents = Application_Service_Utilities::getModel('Documents');

        $t_data = array(
           'active' => 0,
           'countingactive' => 0,
        );

        $documents->update($t_data,'documenttemplate_id = \''.$id.'\'');*/

        $this->addLog($this->_name, $row->toArray(), __METHOD__);
        return $id;
    }

    public function remove($id)
    {
        $row = $this->getOne($id);
        if (!($row instanceof Zend_Db_Table_Row)) {
            throw new Exception('Rekord nie istnieje lub zostal skasowany');
        }

        $documents = Application_Service_Utilities::getModel('Documents');

        $t_data = array(
            'active' => 0,
            'countingactive' => 0,
        );

        $documents->update($t_data, 'documenttemplate_id = \'' . $id . '\'');

        $history = clone $row;

        $row->delete();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        $this->getRepository()->eventObjectChange($this->createRow(), $history);
    }
}