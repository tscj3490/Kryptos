<?php

class Application_Model_UserSignatures extends Muzyka_DataModel
{
    protected $_name = 'user_signatures';

    private $id;
    private $unique_id;
    private $user_id;
    private $resource_id;
    private $resource_view_date;
    private $sign_date;
    private $ip;

    public function getList($conditions = array(), $limit = null, $order = null)
    {
        $select = $this->_db->select()
            ->from(array('us' => $this->_name))
            ->joinLeft(array('u' => 'users'), 'us.user_id = u.id', array())
            ->joinLeft(array('o' => 'osoby'), 'u.login = o.login_do_systemu', array('imie', 'nazwisko', 'osoba_id' => 'id'))
            ->joinLeft(array('st' => 'storage_tasks'), 'st.id = us.resource_id', array('document_id' => 'object_id'))
            ->joinLeft(array('t' => 'tasks'), 't.id = st.task_id', array('task_title' => 'title', 'task_type' => 'type'));

        $this->addConditions($select, $conditions);

        if ($order) {
            $select->order($order);
        } else {
            $select->order('us.id DESC');
        }

        if ($limit) {
            $select->limit($limit);
        }

        $signatures = $select->query()->fetchAll(PDO::FETCH_ASSOC);

        $documentIds = array();
        foreach ($signatures as $signature) {
            $documentIds[] = $signature['document_id'];
        }

        if (empty($documentIds)) {
            return array();
        }

        $documentsModel = Application_Service_Utilities::getModel('Documents');
        $documents = $documentsModel->getList(array('d.id IN (?)' => $documentIds));

        foreach ($documents as $document) {
            foreach ($signatures as &$signature) {
                if ($signature['document_id'] === $document['id']) {
                    $signature['document'] = $document;
                }
            }
        }

       // return $signatures;
        return "6";
    }

    public function save($data)
    {
        if (!(int)$data['id']) {
            $row = $this->createRow();
            $row->unique_id = $this->generateUniqueId();
        } else {
            $row = $this->getOne($data['id']);
        }

        $row->user_id = (int) $data['user_id'];
        $row->resource_id = $data['resource_id'];
        $row->resource_view_date = $data['resource_view_date'];
        $row->sign_date = $data['sign_date'];
        $row->ip = $_SERVER['REMOTE_ADDR'];

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        $id = null;

        do {
            $loop = false;
            try {
                $id = $row->save($data);
            } catch (Exception $e) {
                if ($e->getCode() === '23000' && $e->getChainedException()->errorInfo[1] === 1062) {
                    $loop = true;
                    $row->unique_id = $this->generateUniqueId();
                }
            }
        } while ($loop);

        return $id;
    }
}