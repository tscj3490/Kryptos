<?php

class Application_Model_ExamsQuestionsAnswers extends Muzyka_DataModel
{
    protected $_name = "exams_questions_answers";
    protected $_base_name = 'eqa';
    protected $_base_order = 'eqa.id ASC';

    public $id;
    public $question_id;
    public $answer;
    public $is_correct;
    public $order;
    public $created_at;
    public $updated_at;

    public function getList($conditions = array(), $limit = null, $order = null)
    {
        $select = $this->getBaseQuery($conditions);

        return $select->query()->fetchAll();
    }

    public function getOne($conditions = array())
    {
        $select = $this->getBaseQuery($conditions);

        return $select->query()->fetch(PDO::FETCH_ASSOC);
    }

    public function getBaseQuery($conditions = array(), $limit = NULL, $order = NULL)
    {
        $select = $this->getSelect('eqa');

        $this->addBase($select, $conditions, $limit, $order);

        return $select;
    }

    /**
     * @return self|Zend_Db_Table_Row|Zend_Db_Table_Row_Abstract
     */
    public function save($data)
    {
        if (empty($data['id'])) {
            unset($data['id']);
            $row = $this->createRow($data);
            $row->created_at = date('Y-m-d H:i:s');
        } else {
            $row = $this->validateExists($this->getOne($data['id']));
            $row->setFromArray($data);
            $row->updated_at = date('Y-m-d H:i:s');
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }
}
