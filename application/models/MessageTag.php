<?php

class Application_Model_MessageTag extends Muzyka_DataModel
{
    const TYPE_FAVOURITE = 1;
    const TYPE_URGENT = 2;
    const TYPE_KOMUNIKAT = 3;
    const TYPE_NOTIFY = 4;
    const TYPE_TASK = 5;

    protected $_name = "message_tag";

    public $id;

    /**
     * owner of tag, global tag if null
     * @var int|null
     */
    public $user_id;

    public $name;
    public $color;

    public function save($data)
    {
        if (empty($data['id'])) {
            $row = $this->createRow();
        } else {
            $row = $this->findOne($data['id']);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Zmiana rekordu zakonczona niepowiedzenie. Rekord zostal usuniety');
            }
        }

        $row->user_id = $this->getNullableInt($data['user_id']);
        $row->name = trim($data['name']);
        $row->color = $data['color'];

        $row->save();

        return $row;
    }
}
