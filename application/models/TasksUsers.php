<?php

class Application_Model_TasksUsers extends Muzyka_DataModel
{
    protected $_name = "tasks_users";

    private $id;
    private $task_id;
    private $user_id;
    private $created_at;
    private $updated_at;

    public function save($data)
    {
        if (empty($data['id'])) {
            $row = $this->createRow();
            $row->created_at = date('Y-m-d H:i:s');
        } else {
            $row = $this->get($data['id']);
            $row->updated_at = date('Y-m-d H:i:s');
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Zmiana rekordu zakonczona niepowiedzenie. Rekord zostal usuniety');
            }
        }

        $row->task_id = (int) $data['task_id'];
        $row->user_id = (int) $data['user_id'];

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }
}
