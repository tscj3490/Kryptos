<?php

class Application_Model_OsobyGroups extends Muzyka_DataModel
{
    protected $_name = "osoby_groups";
    protected $_base_name = 'og';
    protected $_base_order = 'og.id ASC';

    public $id;
    public $osoba_id;
    public $group_id;

    public $injections = [
        'osoba' => ['Osoby', 'osoba_id', 'getList', ['o.id IN (?)' => null], 'id', 'osoba', false],
    ];

    /**
     * @return self|Zend_Db_Table_Row|Zend_Db_Table_Row_Abstract
     */
    public function save($data)
    {
        if (empty($data['id'])) {
            unset($data['id']);
            $row = $this->createRow($data);
        } else {
            $row = $this->requestObject($data['id']);
            $row->setFromArray($data);
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

    public function saveUserGroups($userId, $groups)
    {
        $this->delete(['osoba_id = ?' => $userId, 'group_id NOT IN (?)' => $groups]);
        $currentGroups = $this->getUserGroups($userId);

        foreach ($groups as $groupId)
        {
            if (!in_array($groupId, $currentGroups)) {
                $this->save([
                    'osoba_id' => $userId,
                    'group_id' => $groupId
                ]);
            }
        }
    }

    public function getUserGroups($userId)
    {
        return $this->getSelect(null, ['group_id'])
            ->joinInner(['g' => 'groups'], 'g.id = og.group_id', ['name'])
            ->where('og.osoba_id = ?', $userId)
            ->query()
            ->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
