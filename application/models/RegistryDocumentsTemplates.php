<?php

class Application_Model_RegistryDocumentsTemplates extends Muzyka_DataModel
{
    protected $_name = "registry_documents_templates";
    protected $_base_name = 'rdt';
    protected $_base_order = 'rdt.id ASC';

    public $injections = [
        'template' => ['RegistryTemplates', 'template_id', 'getList', ['rt.id IN (?)' => null], 'id', 'template', false],
        'registry' => ['Registry', 'registry_id', 'getListFull', ['r.id IN (?)' => null], 'id', 'registry', false],
    ];

    public $id;
    public $registry_id;
    public $title;
    public $default_author_id;
    public $template_id;
    public $template_config;
    public $flag_auto_create;
    public $numbering_scheme;
    public $numbering_scheme_type_id;
    public $created_at;
    public $updated_at;

    /**
     * @param array $conditions
     * @param int|null $limit
     * @param mixed $order
     * @return Application_Service_EntityRow[]|array
     */
    public function getListWithTemplate($conditions = array(), $limit = null, $order = null)
    {
        $select = $this->getBaseQuery($conditions, $limit, $order);

        $results = $this->getListFromSelect($select, $conditions, $limit, $order);

        $this->loadData(['template'], $results);

        return $results;
    }

    /**
     * @return self|Zend_Db_Table_Row|Zend_Db_Table_Row_Abstract
     */
    public function save($data)
    {
        if (is_array($data) && empty($data['id'])) {
            unset($data['id']);
            $row = $this->createRow($data);
            $row->created_at = date('Y-m-d H:i:s');
        } else {
            if ($data instanceof Application_Service_EntityRow) {
                $row = $data;
            } else {
                $row = $this->requestObject($data['id']);
                $row->setFromArray($data);
            }
            $row->updated_at = date('Y-m-d H:i:s');
        }

        if (empty($row->template_id)) {
            $registryTemplate = Application_Service_Utilities::getModel('RegistryTemplates')->save([
                'type_id' => Application_Model_RegistryTemplates::TYPE_HTML_EDITOR,
                'aspect_id' => Application_Model_RegistryTemplates::ASPECT_OBJECT,
                'name' => null,
                'data' => $data['template_string'],
            ]);
            $row->template_id = $registryTemplate->id;
        } else {
            Application_Service_Utilities::getModel('RegistryTemplates')
                ->update(['data' => $data['template_string']], ['id = ?' => $row->template_id]);
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);

        return $row;
    }

    public function resultsFilter(&$results)
    {
    }

    public function getAllForTypeahead()
    {
        return $this->_db->select()
            ->from(['rdt' => $this->_name], ['rdt.id', 'name' => "CONCAT_WS(', ', rdt.title, rt.name)"])
            ->joinLeft(['rt' => 'registry_templates'], 'rt.id = rdt.template_id')
            ->order('rdt.title ASC')
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);
    }
}
