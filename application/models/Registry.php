<?php

class Application_Model_Registry extends Muzyka_DataModel
{
    protected $_name = "registry";
    protected $_base_name = 'r';
    protected $_base_order = 'r.id ASC';

    public $injections = [
        'author' => ['Osoby', 'author_id', 'getList', ['o.id IN (?)' => null], 'id', 'author', false],
        'entities' => ['RegistryEntities', 'id', 'getListFull', ['re.registry_id IN (?)' => null], 'registry_id', 'entities', true],
        'documents_templates' => ['RegistryDocumentsTemplates', 'id', 'getListWithTemplate', ['rdt.registry_id IN (?)' => null], 'registry_id', 'documents_templates', true],
    ];

    public $id;
    public $title;
    public $author_id;
    public $type_id;
    public $object_id;
    public $system_name;
    public $label_schema;
    public $is_locked;
    public $is_visible;
    public $created_at;
    public $updated_at;

    /**
     * @return self|Zend_Db_Table_Row|Zend_Db_Table_Row_Abstract
     */
    public function save($data)
    {
        $defaultData = [
            'type' => 1,
            'object_id' => null,
            'is_locked' => 0,
            'is_visible' => 1,
        ];
        $data = array_merge($defaultData, (array) $data);

        if (empty($data['id'])) {
            unset($data['id']);
            $row = $this->createRow($data);
            $row->created_at = date('Y-m-d H:i:s');

        } else {
            $row = $this->requestObject($data['id']);
            $row->setFromArray($data);
            $row->updated_at = date('Y-m-d H:i:s');
        }

        $id = $row->save();

        $this->addLog($this->_name, $row->toArray(), __METHOD__);
        
        Application_Service_Events::getInstance()->trigger('registry.update', $row);

        return $row;
    }

    /**
     * @param array $conditions
     * @param bool $required
     * @return Application_Service_EntityRow|array
     */
    public function getFull($conditions = array(), $required = false)
    {
        $list = $this->getListFull($conditions);
        $this->resultsFilter($list);

        if (!empty($list)) {
            return $list[0];
        } elseif ($required) {
            Throw new Exception('Rekord nie istnieje lub został skasowany', 100);
        }

        return null;
    }

    public function resultsFilter(&$results)
    {
        foreach ($results as &$result) {
            if (!empty($result->entities)) {
                // code for clean debug
                $tmp = $result->entities;
                unset($result->entities);
                $result->entities_named = [];
                $result->entities = $tmp;

                foreach ($result->entities as $entity) {
                    if (!empty($entity->system_name)) {
                        $result->entities_named[$entity->system_name] = $entity;
                    }
                }

                $result->entities_indexed = $result->entities;
                Application_Service_Utilities::indexBy($result->entities_indexed, 'id');

                $result->display_name = $result->title;
            }
        }
    }

    public function getAllForTypeahead($conditions = array())
    {
        $select = $this->_db->select()
            ->from(array($this->_base_name => $this->_name), array('id', 'name' => 'title'))
            ->order('r.title ASC');

        $this->addConditions($select, $conditions);

        return $select
            ->query()
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cloneRegistrySettings($registryId, $cloneId)
    {
        $this->_db->query(sprintf('INSERT INTO registry_assignees SELECT null, %d, user_id, registry_role_id, NOW(), null FROM registry_assignees WHERE registry_id = %d', $registryId, $cloneId));
        $this->_db->query(sprintf('INSERT INTO registry_documents_templates SELECT null, %d, title, default_author_id, template_id, template_config, numbering_scheme, numbering_scheme_type_id, NOW(), null FROM registry_documents_templates WHERE registry_id = %d', $registryId, $cloneId));
        $this->_db->query(sprintf('INSERT INTO registry_entities SELECT null, %d, entity_id, system_name, title, is_multiple, config, `order`, NOW(), null FROM registry_entities WHERE registry_id = %d', $registryId, $cloneId));
        $this->_db->query(sprintf('INSERT INTO registry_permissions SELECT null, %d, system_name, title, NOW(), null FROM registry_permissions WHERE registry_id = %d', $registryId, $cloneId));
        $this->_db->query(sprintf('INSERT INTO registry_roles SELECT null, %d, system_name, title, NOW(), null FROM registry_roles WHERE registry_id = %d', $registryId, $cloneId));
        $this->_db->query(sprintf('INSERT INTO registry_roles_permissions SELECT null, rr2.id, rp2.id, NOW(), null
            FROM registry_roles_permissions rrp 
            LEFT JOIN registry_permissions rp ON rp.id = rrp.registry_permission_id
            LEFT JOIN registry_roles rr ON rr.id = rrp.registry_role_id
            LEFT JOIN registry_permissions rp2 ON rp2.system_name = rp.system_name AND rp2.registry_id = %d
            LEFT JOIN registry_roles rr2 ON rr2.system_name = rr.system_name AND rr2.registry_id = %d
            WHERE rr.registry_id = %d', $registryId, $registryId, $cloneId));
    }
	
	public function logregistry($text,$filename)
	{		
		file_put_contents($filename, $text . "\n", FILE_APPEND);
	}


    public function multi_diagrams($diagrams = array()){
        
        if(count($diagrams)==1){
            return $diagrams[0]['diagramj'];
        }

         $total_diagrams =  count($diagrams);
        $diagrams_arr= array();
        $diagrams_id_arr= array();
        $diagrams_y_arr= array();
        $doc = new DomDocument;
        $doc->preserveWhiteSpace = FALSE;


        foreach($diagrams as $key => $value): // Diagrams foreach loop start
        $mp_idx = 0;

        $doc->loadXML($value['diagramj']);

        $tot_attrs = (int)$doc->getElementsByTagName('mxCell')->length; 
        $mg_tal_attrs = (int)$doc->getElementsByTagName('mxGeometry')->length;

        $mg_diff = $tot_attrs - $mg_tal_attrs;
        // Diagram XML  Header tag Attributes
        if($key==0){
            $mxGraphModel = $doc->getElementsByTagName('mxGraphModel')->item($key);
            $diagrams_arr[$key]['header'] = $this->getHeader($mxGraphModel);
        }
        $arr = $this->getAttributes($doc, $key, $mp_idx, $mg_diff, $tot_attrs);
        $diagrams_id_arr[$key] = $arr['diagrams_id_arr'];
        $diagrams_y_arr[$key] = $arr['diagrams_y_arr'];
        $diagrams_arr[$key]['nodes'] = $arr['diagrams_arr'];

        endforeach;  // Diagrams foreach loop End
        //  echo '<pre>';print_r($diagrams_arr);echo '</pre>';die();


        $arr = $this->arrOrder($diagrams_arr, $diagrams_id_arr, $diagrams_y_arr);
        $diagrams_id_arr = $arr['diagrams_id_arr'];
        $diagrams_y_arr = $arr['diagrams_y_arr'];
        $diagrams_arr = $arr['diagrams_arr'];
       
       
       return $this->getXml($diagrams_arr);  
        die();    
       // return 'yes fine';
    }

    public function getXml($diagrams_arr, $diagramsj = '', $mxGraphModel = '', $mxGeometry = '', $mxPoint = '', $mxCell = '')
    {
        foreach($diagrams_arr as $key => $values ):
            if($key==0){
                $mxGraphModel .='<mxGraphModel ';
                    foreach($values['header'] as $index => $value){
                        $mxGraphModel .= ' '.$index.'="'.$value.'"';
                    }
                $mxGraphModel .=' ><root>';
            }
        foreach($values['nodes'] as $value)
        {
            $_is_mxGeometry = false;
            $_is_mxPoint = false;
            $mxCell .='<mxCell ';
            foreach($value['mxCell'] as $index => $val){
                $mxCell .= ' '.$index.'="'.htmlentities($val).'"';
            }
            if(count($value['mxGeometry'])>0 && !empty($value['mxGeometry'])){
                $_is_mxGeometry = true;
            }
            if(isset($value['mxPoint']) && count($value['mxPoint'])>0 && !empty($value['mxPoint'])){
                $_is_mxPoint = true;
            }
            $mxCell .= ($_is_mxGeometry==true)?' >':' />';
            $mxGeometry ='';
            if($_is_mxGeometry==true){
                $mxGeometry .='<mxGeometry ';
                foreach($value['mxGeometry'] as $index => $val){
                    $mxGeometry .= ' '.$index.'="'.$val.'"';
                }
                $mxGeometry .= ($_is_mxPoint==true)?' >':' />';
                $mxPoint = '';
                $mxG_end_tag = '';
                if($_is_mxPoint==true){
                    $mxPoint .='<mxPoint';
                    foreach($value['mxPoint'] as $index => $val){
                        $mxPoint .= ' '.$index.'="'.$val.'"';
                    }
                    $mxPoint .=' />';
                    $mxGeometry .=$mxPoint;
                    $mxGeometry .='</mxGeometry>';
                }
                $mxCell .=$mxGeometry;
                $mxCell .='</mxCell>';
                
            }
            
        }
        endforeach;
        $diagramsj .=$mxGraphModel;
        $diagramsj .=$mxCell;
        $diagramsj .='</root></mxGraphModel>';
        return $diagramsj;
    }

    public function arrOrder($diagrams_arr, $diagrams_id_arr, $diagrams_y_arr)
    {
        foreach($diagrams_arr as $keys => $arrs){
            $diagrams_id_arr[$keys];
            if($keys==0){
                continue;
            }
            rsort($diagrams_y_arr[$keys-1]);
            $highest_offset = $diagrams_y_arr[$keys-1][0];
            rsort($diagrams_id_arr[$keys-1]);
            $highest_id = $diagrams_id_arr[$keys-1][0];
            sort($diagrams_id_arr[$keys]);
            $arr_ids = array();
            $arr_ids = $diagrams_id_arr[$keys];
            $diagrams_id_arr[$keys] = array();
            $diagrams_y_arr[$keys] = array();
                
            for($i=0;$i<count($arr_ids);$i++):
                $diagrams_id_arr[$keys][] = $new_id = $highest_id+$i+1;
                foreach($arrs['nodes'] as $key => $values){
                    foreach($values['mxCell'] as $idx => $value):
                        if($value==$arr_ids[$i]){
                            $diagrams_arr[$keys]['nodes'][$key]['mxCell'][$idx] = $new_id;
                        }
                    endforeach;
                    foreach($values['mxGeometry'] as $idx => $value):
                        if($idx=='y' && !array_key_exists("relative", $values['mxGeometry'])){
                            $diagrams_y_arr[$keys][] = $value+$highest_offset;
                            $diagrams_arr[$keys]['nodes'][$key]['mxGeometry'][$idx] = $value+$highest_offset+80;
                        }
                    endforeach;
                }
            endfor;
        }
        $arr['diagrams_id_arr'] = $diagrams_id_arr;
        $arr['diagrams_y_arr'] = $diagrams_y_arr;
        $arr['diagrams_arr'] = $diagrams_arr;
        return $arr;
    }

    public function getAttributes($doc, $key, $mp_idx, $mg_diff, $tot_attrs)
    {
        for($i=0;$i<$tot_attrs;$i++): // For loop on # of mxCell tag
            $mxCell = $doc->getElementsByTagName('mxCell')->item($i);
            if($mxCell->hasAttributes()) {
                foreach ($mxCell->attributes as $attr) {  // mxCell tag attributes 
                    if($key>0 && $i<2){
                            continue;
                    }
                    if($attr->nodeName=='id'){
                        $diagrams_id_arr[] = $attr->nodeValue;
                    }
                    $diagrams_arr[$i]['mxCell'][$attr->nodeName] = $attr->nodeValue;
                    $diagrams_arr[$i]['mxGeometry'] = '';
                }
            } 
            if($i>1) {  // avoid first two mxCell to add mxGeometry tag 
                $k = $i-$mg_diff;  // add mxGeometry tag in 3rd mxCell
                $mxGeometry = $doc->getElementsByTagName('mxGeometry')->item($k);
                if($mxGeometry->hasAttributes()) { // mxGeometry tag attributes
                    foreach ($mxGeometry->attributes as $atttr) {
                        $diagrams_arr[$i]['mxGeometry'][$atttr->nodeName] = $atttr->nodeValue;
                        if($atttr->nodeName=='y'){
                            $diagrams_y_arr[] = $atttr->nodeValue;
                        }
                    }
                $diagrams_arr[$i]['mxPoint'] = '';
                if($mxGeometry->hasChildNodes()){
                    $mxPoint = $doc->getElementsByTagName('mxPoint')->item($mp_idx);
                    if($mxPoint->hasAttributes()) {
                        foreach ($mxPoint->attributes as $atttr){
                            $diagrams_arr[$i]['mxPoint'][$atttr->nodeName] = $atttr->nodeValue;
                        }
                        $mp_idx++;
                    }
                }
                }
            }
        endfor;
        $arr['diagrams_id_arr'] = $diagrams_id_arr;
        $arr['diagrams_y_arr'] = $diagrams_y_arr;
        $arr['diagrams_arr'] = $diagrams_arr;
        return $arr;
    }

    Public function getHeader($mxGraphModel)
    {
        $header = array();
        if($mxGraphModel->hasAttributes()) {
            foreach($mxGraphModel->attributes as $attr){
                $nodeValue = $attr->nodeValue;
                if($attr->nodeName=='background'){
                    $nodeValue = '#ffffff';
                }
                $header[$attr->nodeName] = $nodeValue;
            }
        }
        return $header;
    }
}
