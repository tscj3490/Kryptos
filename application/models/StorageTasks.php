<?php

class Application_Model_StorageTasks extends Muzyka_DataModel
{
    const STATUS_PENDING = 0;
    const STATUS_ACCEPTED = 1;
    const STATUS_REJECTED = 2;
    const STATUS_PREPARED = 3;

    protected $_name = "storage_tasks";
    protected $_base_name = 'st';
    protected $_base_order = 'st.id ASC';

    public $injections = [
        'user' => ['Osoby', 'user_id', 'getList', ['o.id IN (?)' => null], 'id', 'user', false],
        'task' => ['Tasks', 'task_id', 'getList', ['id IN (?)' => null], 'id', 'task', false],
    ];

    private $id;
    private $type;
    private $procedure_step;
    private $task_id;
    private $status;
    private $user_id;
    private $author_osoba_id;
    private $object_id;
    private $deadline_date;
    private $title;
    private $description;
    private $signature_required;
    private $created_at;
    private $updated_at;
    private $confirmed_at;

    public function getAll($params = array())
    {
        $query = $this->_db->select();

        if (isset($params['countMode'])) {
            $query->from(array('st' => $this->_name), array('count' => 'COUNT(*)'));
        } else {
            $query->from(array('st' => $this->_name));
        }

        $query
            ->joinLeft(array('t' => 'tasks'), 't.id = st.task_id', array('task_title' => 'COALESCE(st.title, t.title)', 'task_type' => 'type'))
            ->joinLeft(array('o' => 'osoby'), 'o.id = t.author_osoba_id', array('author_name' => "CONCAT(o.nazwisko, ' ', o.imie)", 'author_login' => 'o.login_do_systemu'))
            ->joinLeft(array('oe' => 'osoby'), 'oe.id = st.user_id', array('employee_name' => "CONCAT(oe.nazwisko, ' ', oe.imie)", 'employee_login' => 'oe.login_do_systemu'))
            ->group('st.id')
            ->order(['st.created_at DESC']);

        if (!empty($params)) {
            if (isset($params['user_id'])) {
                $query->where('st.user_id = ?', $params['user_id']);
            }
            if (isset($params['task_id'])) {
                $query->where('st.task_id = ?', $params['task_id']);
            }
            if (isset($params['status'])) {
                $query->where('st.status = ?', $params['status']);
            }
            if (isset($params['limit'])) {
                $query->limit($params['limit']);
            }
            if (isset($params['active'])) {
                $query->where('t.status = ?', 1);
            }
        }

        $result = $query->query()
            ->fetchAll();

        if (isset($params['countMode'])) {
            if (!empty($result)) {
                return (int) $result[0]['count'];
            }
            return 0;
        }
        return $result;
    }

    public function getBaseQuery($conditions = array(), $limit = null, $order = null)
    {
        $select = $this->getSelect($this->_base_name);

        $select
            ->joinLeft(array('t' => 'tasks'), 't.id = st.task_id', array('task_title' => 'COALESCE(st.title, t.title)', 'task_type' => 'type'))
            ->joinLeft(array('o' => 'osoby'), 'o.id = t.author_osoba_id', array('author_name' => "CONCAT(o.nazwisko, ' ', o.imie)", 'author_login' => 'o.login_do_systemu'))
            ->joinLeft(array('oe' => 'osoby'), 'oe.id = st.user_id', array('employee_name' => "CONCAT(oe.nazwisko, ' ', oe.imie)", 'employee_login' => 'oe.login_do_systemu'))
            ->group('st.id')
            ->order('t.created_at DESC');

        $this->addBase($select, $conditions, $limit, $order);

        return $select;
    }

    public function getAllByIds($ids)
    {
        $sql = $this->select()
            ->where('id IN (?)', $ids);

        return $this->fetchAll($sql);
    }

    public function findUserTask($userId, $type, $objectId)
    {
        $select = $this->_db->select()
            ->from(array('st' => $this->_name))
            ->joinLeft(array('t' => 'tasks'), 't.id = st.task_id', array('task_title' => 'COALESCE(st.title, t.title)', 'task_type' => 'type'))
            ->where('st.user_id = ?', $userId)
            ->where('t.type = ?', $type)
            ->where('t.object_id = ?', $objectId);

        return $select->query()->fetch(PDO::FETCH_ASSOC);
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

        if ($row->status !== '1' && (int) $data['status'] === 1) {
            $row->confirmed_at = date('Y-m-d H:i:s');
        }

        $row->type = $this->getNullableInt($data['type']);
        $row->procedure_step = $this->getNullableInt($data['procedure_step']);
        $row->signature_required = $data['signature_required'];
        $row->task_id = $this->getNullableInt($data['task_id']);
        $row->title = $data['title'];
        $row->description = $data['description'];
        $row->status = (int) $data['status'];
        $row->user_id = (int) $data['user_id'];
        $row->author_osoba_id = (int) $data['author_osoba_id'];
        $row->object_id = (int) $data['object_id']; //przy dodawaniu powinno być zawsze puste, dopiero pozniej w skutet akceptacji wypalnia sie to pole $this->getNullableInt($data['object_id']);
        $row->deadline_date = (string) $data['deadline_date'];
        $row->comment = $this->escapeName($data['comments']);

        $id = $row->save();

        $row->description = str_replace('@@STORAGE_TASK_ID@@', $row->id, $row->description);
        $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $id;
    }

    public function remove($id)
    {
        $row = $this->requestObject($id);

        $row->delete();
        $this->addLog($this->_name, $row->toArray(), __METHOD__);
    }

    public function getSignatures($conditions, $limit = null, $order = null)
    {
        $select = $this->_db->select()
            ->from(array('st' => $this->_name), ['storage_task_object_id' => 'object_id'])
            ->joinLeft(['us' => 'user_signatures'], 'us.resource_id = st.id', ['*']);

        $this->addBase($select, $conditions, $limit, $order);

        $results = $select->query()->fetchAll(PDO::FETCH_ASSOC);

        Application_Service_Utilities::getModel('UserSignatures')->tryTofetchObjects($results);

        return $results;
    }
}
