<?php

class Application_Model_Tasks extends Muzyka_DataModel
{
    protected $_name = "tasks";

    private $id;
    private $status;
    private $type;
    private $users_type;
    private $object_id;
    private $author_osoba_id;
    private $title;
    private $trigger_type;
    private $trigger_config;
    private $activate_before_days;
    private $message_template;
    private $send_notification_message;
    private $send_notification_email;
    private $send_notification_sms;
    private $created_at;
    private $updated_at;

    public function getAll($params = array())
    {
        $query = $this->_db->select()
            ->from(array('t' => $this->_name))
            ->joinLeft(array('o' => 'osoby'), 'o.id = t.author_osoba_id', array('author_name' => "UPPER(CONCAT(o.nazwisko, ' ', o.imie))", 'author_login' => 'login_do_systemu'))
            ->joinLeft(array('st' => 'storage_tasks'), 'st.task_id = t.id', array('tasks_count' => 'count(st.id)'))
            ->where('t.status = 1')
            ->group('t.id')
            ->order('t.updated_at DESC');

        if (!empty($params)) {
            if (isset($params['zbior_id'])) {
                if (!empty($params['zbior_id'])) {
                    $query->joinInner(array('dtzf' => 'data_transfers_zbiory_fielditems'), 'dtzf.data_transfer_id = t.id AND '.sprintf('dtzf.zbior_id = %d', $params['zbior_id']), array());
                } else {
                    $query->where('1 <> 1'); // NO RESULTS
                }
            }
            if (!empty($params['id'])) {
                $query->where('t.id = ?', $params['id']);
            }
            if (!empty($params['not_system'])) {
                $query->where('t.type < 1000');
            }
            if (!empty($params['type'])) {
                $query->where('t.type = ?', $params['type']);
            }
            if (!empty($params['getAdressess'])) {
                $query->columns(array(
                    'source_company_street' => 'street',
                    'source_company_house' => 'house',
                    'source_company_locale' => 'locale',
                    'source_company_postal_code' => 'postal_code',
                    'source_company_city' => 'city',
                    'source_company_country' => 'country',
                ), 'sp');
                $query->columns(array(
                    'source_employee_first_name' => 'first_name',
                    'source_employee_last_name' => 'last_name',
                ), 'se');
            }
            $query->group('t.id');
        }

        return $query->query()
            ->fetchAll();
    }

    public function getAllByIds($ids)
    {
        $sql = $this->select()
            ->where('id IN (?)', $ids);

        return $this->fetchAll($sql);
    }

    public function getFull($id)
    {
        $documenttemplatesModel = Application_Service_Utilities::getModel('Documenttemplates');
        $coursesModel = Application_Service_Utilities::getModel('Courses');
        $usersModel = Application_Service_Utilities::getModel('Users');

        $sql = $this->_db->select()
            ->from(array('t' => $this->_name))
            ->joinLeft(array('o' => 'osoby'), 'o.id = t.author_osoba_id', array('author_name' => "UPPER(CONCAT(o.nazwisko, ' ', o.imie))", 'author_login' => 'login_do_systemu'))
            ->where('t.id = ?', $id);

        $task = $sql->query()->fetch(PDO::FETCH_ASSOC);

        $task['task_users'] = $this->_db->select()
            ->from(array('tu' => 'tasks_users'), array('id' => 'tu.user_id'))
            ->joinLeft(array('o' => 'osoby'), 'tu.user_id = o.id', array('name' => "UPPER(CONCAT(o.nazwisko, ' ', o.imie, ' ', o.login_do_systemu))"))
            ->where('tu.task_id = ?', $id)
            ->query()
            ->fetchAll(PDO::FETCH_KEY_PAIR);

        $task['trigger_config_data'] = json_decode($task['trigger_config'], true);

        switch ($task['type']) {
            case Application_Service_Tasks::TYPE_DOCUMENT:
                if ($task['object_id']) {
                    $task['object'] = $documenttemplatesModel->get($task['object_id']);
                }
                break;
            case Application_Service_Tasks::TYPE_COURSE:
                if ($task['object_id']) {
                    $task['object'] = $coursesModel->get($task['object_id']);
                }
                break;
        }

        return $task;
    }

    public function getUsersWithoutTask($task)
    {
        $select = $this->_db->select()
            ->from(array('u' => 'osoby'), array('id'))
            ->where('u.type = 1')
            ->where('u.status IN (?)', [Application_Model_Osoby::STATUS_ACTIVE, Application_Model_Osoby::STATUS_PENDING_ACTIVATION])
            ->where('u.usunieta = 0');

        if ((int) $task['users_type'] === 2) {
            $select->joinInner(array('tu' => 'tasks_users'), 'tu.user_id = u.id', array());
            $select->where('tu.task_id = ?', $task['id']);
        }

        $select
            ->joinLeft(array('st' => 'storage_tasks'), sprintf('st.user_id = u.id AND st.task_id = %d', $task['id']), array())
            ->where('st.id IS NULL')
            ->where('u.type = ?', Application_Model_Osoby::TYPE_EMPLOYEE);

        $users = $select
            ->query()
            ->fetchAll(PDO::FETCH_COLUMN);

        return $users;
    }

    public function findTasksToSend($dateStr)
    {
        $date = new DateTime($dateStr);
        $tasksToSend = array();

        $tasks = $this->fetchAll('status = 1')->toArray();
        foreach ($tasks as $task) {
            $triggerConfig = json_decode($task['trigger_config'], true);
            $daysBefore = $task['activate_before_days'];
            switch ($task['trigger_type']) {
                case "1":
                    $day = $triggerConfig['date'];
                    if ($day) {
                        $date->modify('+'. $daysBefore .' day');
                        $dayDate = new DateTime($day);
                        if ($dayDate == $date) {
                            $tasksToSend[] = $task['id'];
                        }
                    }
                    break;
                case "2":
                    $day = $triggerConfig['day'];
                    if ($day) {
                        $date->modify('+'. $daysBefore .' day');
                        $currentDay = $date->format('j');
                        $currentMonthMaxDay = $date->format('t');
                        if ($currentMonthMaxDay < $day) {
                            $day = $currentMonthMaxDay;
                        }
                        if ((int) $currentDay === (int) $day) {
                            $tasksToSend[] = $task['id'];
                        }
                    }
                    break;
                case "3":
                    $tasksToSend[] = $task['id'];
                    break;
                case "4":
                    // nie wysyłaj poprzez cron, zadanie wysyłane ręcznie
                    break;
                case "5":
                    // nie wysyłaj poprzez cron, zadanie wysyłane podczas utworzenia dokumentu
                    break;
            }
        }

        return $tasksToSend;
    }

    public function save($data)
    {
        if (empty($data['id'])) {
            $row = $this->createRow();
            $row->created_at = date('Y-m-d H:i:s');
        } else {
            $row = $this->requestObject($data['id']);
            $row->updated_at = date('Y-m-d H:i:s');
        }
        $row->status = (int) $data['status'];
        $row->type = (int) $data['type'];
        $row->users_type = (int) $data['users_type'];
        $row->object_id = $this->getNullableInt($data['object_id']);
        $row->author_osoba_id = (int) $data['author_osoba_id'];
        $row->title = $this->escapeName($data['title']);
        $row->trigger_type = (int) $data['trigger_type'];
        $row->activate_before_days = (int) $data['activate_before_days'];
        $row->message_template = (string) $data['message_template'];
        $row->send_notification_message = (int) $data['send_notification_message'];
        $row->send_notification_email = (int) $data['send_notification_email'];
        $row->send_notification_sms = (int) $data['send_notification_sms'];

        $row->trigger_config = json_encode($data['trigger_mode_' . $row->trigger_type]);

        $id = $row->save();

        $taskUsersModel = Application_Service_Utilities::getModel('TasksUsers');
        $taskUsersModel->delete(array('task_id = ?' => $id));
        if (!empty($data['task_users'])) {
            foreach ($data['task_users'] as $userId => $isSelected) {
                if ($isSelected === '1') {
                    $taskUsersModel->save(array(
                        'task_id' => $id,
                        'user_id' => $userId,
                    ));
                }
            }
        }

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

    public function remove($id)
    {
        $row = $this->requestObject($id);

        $row->delete();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
    }

    public function findUsersWithoutTask($taskId, $objectId = null)
    {
        $select = $this->getAdapter()->select()
            ->from(array('tu' => 'tasks_users'), array('user_id'))
            ->joinLeft(array('to' => 'osoby'), 'tu.id = to.id', array())
            ->joinLeft(array('st' => 'storage_tasks'), 'tu.user_id = st.user_id AND tu.task_id = st.task_id', array())
            ->where('tu.task_id = ?', $taskId)
            ->where('to.status IN (?)', [Application_Model_Osoby::STATUS_ACTIVE, Application_Model_Osoby::STATUS_PENDING_ACTIVATION])
            ->where('st.id IS NULL');

        if ($objectId) {
            $select->where('st.object_id = ?', $objectId);
        }

        return $select->query()
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    public function findAllUsersWithoutTask($taskId, $objectId = null)
    {
        $storageJoin = array('tu.id = st.user_id');
        $storageJoin[] = sprintf('st.task_id = %d', $taskId);

        if ($objectId) {
            $storageJoin[] = sprintf('st.object_id = %d', $objectId);
        }

        $select = $this->getAdapter()->select()
            ->from(array('tu' => 'users'), array('user_id' => 'id'))
            ->joinLeft(array('to' => 'osoby'), 'tu.id = to.id', array())
            ->joinLeft(array('st' => 'storage_tasks'), implode(' AND ', $storageJoin), array())
            ->where('st.id IS NULL')
            ->where('to.status IN (?)', [Application_Model_Osoby::STATUS_ACTIVE, Application_Model_Osoby::STATUS_PENDING_ACTIVATION]);

        return $select->query()
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    public function findTasksForToday($date)
    {
        return $this->getAdapter()->select()
            ->from(array('st' => 'storage_tasks'))
            ->where('st.deadline_date >= ?', sprintf('%s 00:00:00', $date))
            ->where('st.deadline_date <= ?', sprintf('%s 23:59:59', $date))
            ->where('st.status = ?', Application_Model_StorageTasks::STATUS_PENDING)
            ->query()
            ->fetchAll();
    }
}
