<?php
class CsvImportController extends Muzyka_Admin {

    protected $Importexport;

    private $xlsx_array;

    Protected $registryModel; 

    Protected $Osoby_Model; 

    protected $registryEntriesModel;

    protected $reg_varchar_Model;

    protected $reg_text_Model; protected $reg_date_Model; protected $reg_datetime_Model;

    protected $reg_int_Model;
    
    public function init() {
        parent::init();
        $this->Osoby_Model = Application_Service_Utilities::getModel('Osoby');
        $this->entitiesModel = Application_Service_Utilities::getModel('Entities');
        $this->Importexport = Application_Service_Utilities::getModel('Importexport');
        $this->registryModel = Application_Service_Utilities::getModel('Registry');
        $this->registryEntriesModel = Application_Service_Utilities::getModel('RegistryEntries');
        $this->reg_varchar_Model = Application_Service_Utilities::getModel('RegistryEntriesEntitiesVarchar');
        $this->reg_text_Model = Application_Service_Utilities::getModel('RegistryEntriesEntitiesText');
        $this->reg_date_Model = Application_Service_Utilities::getModel('RegistryEntriesEntitiesDate');
        $this->reg_datetime_Model = Application_Service_Utilities::getModel('RegistryEntriesEntitiesDateTime');
        $this->reg_int_Model = Application_Service_Utilities::getModel('RegistryEntriesEntitiesInt');
        $this->_helper->layout->setLayout('csvimport');
        $this->view->section = 'CsvImport';
        Zend_Layout::getMvcInstance()->assign('section', 'CsvImport');
    }

    public function indexAction() {  
       $this->_helper->layout->setLayout('admin');
    }

    public function processAction() { 
            
            $registries =  $this->registryModel->getList();


    //  try {
            $defaultNamespace = new Zend_Session_Namespace('Default');

            $upload = new Zend_File_Transfer_Adapter_Http();

           // print_r($_FILES);die('upload');

            if (!$upload->receive()) {
                $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Error uploading file'));
                return false;
            }
            $objPHPExcel = PHPExcel_IOFactory::load($upload->getFileName());
            $date_col = '';
            $datetime_col = '';
            foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
               
                $highestRow = $worksheet->getHighestRow();
                $highestColumn = $worksheet->getHighestColumn();
                $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
                $rowvalue = array();
                $i = 0;
                for ($row = 1; $row <= $highestRow; ++$row) {
                    for ($col = 0; $col < $highestColumnIndex; ++$col) {
                        $cell = $worksheet->getCellByColumnAndRow($col, $row);
                        $val = $cell->getValue();
                        if($i==0){
                          if(strtolower($val)=='date'){
                            $date_col = $col;
                          }elseif(strtolower($val)=='datetime'){
                            $datetime_col = $col;
                          }
                        }
                        
                      if(is_numeric($date_col) && $date_col==$col && $row>1){
                          $date = str_replace(",","-",$val);
                          $val = date('Y-m-d',strtotime($date));
                       }elseif(is_numeric($datetime_col) && $datetime_col==$col && $row>1){
                          $datetime = str_replace(",","-",$val);
                          $val = date('Y-m-d H:i:s',strtotime($datetime));
                       }
                        
                        $rowvalue[$i][] = $val;
                     }
                     $i++;
                    
                   // $this->Importexport->importData($rowvalue);
                }
                
            }
           // echo "<pre>";print_r($rowvalue);echo "</pre>";die();
            if(count($rowvalue)>0){
                $defaultNamespace->test_xlsx = $rowvalue;
            }

            $reg_HTML = '';
            $reg_HTML .= '<div class="row">';
            $reg_HTML .= '<div class="col-sm-6">';
            $reg_HTML .='<div class ="form-group">';
            $reg_HTML .='<select class="form-control maping_events">';
                foreach ($registries as $value){
               // {if $auth->isGranted('node/registry-entries/index', ['registry_id' => $value['id']])}
             $reg_HTML .='<option value="'.$value['id'].'">'.$value['title'].'</option>';
               // {/if}
                }
            $reg_HTML .='</select>';
            $reg_HTML .='</div>';
            $reg_HTML .='</div>';
            $reg_HTML .='</div>';
            $reg_HTML .='<div class="registry_maping"></div>';
            $this->outputJson(array('status'=>1,'html'=>$reg_HTML,'message'=>'Data Imported Successfully'));
      
    }

    public function geteventrntriesAction(){
       // $test_dd = $this->entitiesModel->getAllForTypeahead();
       // echo '<pre>';print_r($test_dd);echo "</pre>";die();
        $defaultNamespace = new Zend_Session_Namespace('Default');
        $xlxs_data = $defaultNamespace->test_xlsx;
        $select_options = $xlxs_data[0];
        $total = count($select_options);
        $select_options[$total] = "None";
      //  echo "<pre>";print_r($xlxs_data);echo "</pre>";die();
        $event_id = $this->_request->getPost('event_id');
        $data = $this->Importexport->getEntites($event_id);
        $html .= '<form method="post" id="maped_form" action="csvimport/insertion">';
        foreach($data as $key => $value){
        $html .= '<div class="row">';

        $html .= '<div class="col-sm-6">';
        $html .= '<div class="form-group"><select name="xlsx['.$i.']" class="form-control">';
            $option = '';
            foreach($select_options as $idx => $val){
                $selected = ($idx==$key)?'selected="selected"':'';
                $option .= '<option value="'.$idx.'" '.$selected.'>'.$val.'</option>'; 
            }
        $html .= $option;    
        $html .= '</select></div>';
        $html .= '</div>';

        $html .= '<div class="col-sm-6">';
        $html .= '<div class="form-group pull-left">';
        $name_id = $value['system_name'].'_'.$value['id'];
        $element = 'element_'.$value['id'];;
        $id = $value['id'];
        $entity_id = $value['entity_id'];
        $html .= '<label for='.$name_id.'><strong>'.$value['title'].'</strong></label>';
        $html .= '<input type="hidden" id='.$element.' name='.$element.' value='.$id.' data-entity_id='.$entity_id.'>';
        $html .= '</div>';
        $html .= '</div>';        
        
        $html .= '</div>';
       }
       $html .='<div class="row">';
       $html .='<div class="col-sm-12">';
       $html .='<input type="hidden" id="event_id" name="event_id" value="'.$event_id.'">';
       $html .= '<div class="form-group"><input type="submit" id="append_form" value="Import" class="btn btn-info"></div>';
       $html .='</div>';
       $html .='</div>';
       
       $html .= '</form>';
       $this->outputJson(array('status'=>1,'html'=>$html,'message'=>'Data Imported Successfully'));
       die();
    } 

    public function insertionAction(){
        
        $defaultNamespace = new Zend_Session_Namespace('Default');
        $entry_id = '';
        $event_id = '';
        $data = $this->_request->getPost();
        $xlxs_data = $defaultNamespace->test_xlsx;
        $event_id = $data['event_id'];
        $map_sheet = $data['xlsx_arr'];
        $map_entity = $data['map_arr']; 
        
        $result=array_intersect($xlxs_data[0],$map_sheet);
        for($row = 1; $row<count( $xlxs_data ); $row++){
            $field = array();
            $i =0;
            foreach($xlxs_data[$row] as $key => $value){
               foreach($map_sheet as $ms_key => $ms_value){
                    if($ms_value==$key){
                        if($i==0){
                           $entry_id = $this->getentityid($event_id);
                        }
                        if(is_numeric($entry_id) && $entry_id != false && $entry_id != ''){
                            $field['entry_id'] =  $entry_id;
                            $field['registry_entity_id'] =  $map_entity[$ms_key][0];
                            $field['value'] =  $this->valueformat($value,$map_entity[$ms_key][1]);
                            $field['created_at'] =  date('Y-m-d H:i:s');
                            if(count($field)>0){
                              $this->saveEntityEntries($field,$map_entity[$ms_key][1]);
                            }
                        }
                        $i++;
                    }
                    }
                }
            }
       // return true;
            $this->outputJson(array('status'=>1,'message'=>'Data Imported Successfully'));
        die();
    }

    public function saveEntityEntries($field, $type){
      switch ($type) {
            case 1:
                return $this->reg_varchar_Model->save($field);
                break;
            case 2:
                return $this->reg_text_Model->save($field);
                break;
            case 4:
                return $this->reg_date_Model->save($field);
                break;
            case 5:
                return $this->reg_datetime_Model->save($field);
                break;
            case 6:
              $osoby_aar['status'] = 1;
              $osoby_aar['type'] = 1;
              $osoby_aar['imie'] = $field['value'];
              $osoby_aar['nazwisko'] = '';
              $osoby_aar['stanowisko'] = '';
              $osoby_aar['umowa'] = '';
              $osoby_aar['dzial'] = '';
              $osoby_aar['email'] = '';
              $osoby_aar['notification_email'] = '';
              $osoby_aar['telefon_stacjonarny'] = '';
              $osoby_aar['telefon_komorkowy'] = '';
              $osoby_aar['generate_documents'] = 1;
              $osoby_aar['login_do_systemu'] = false;
              //$osoby_aar['nazwisko'] = $field['value'];
             // $osoby_aar['login_do_systemu'] = strtoupper(substr($field['value'],0,5)).'1';
            //  $osoby_aar['created_at'] = $field['created_at'];
               $Osoby_id = '';         
               $Osoby_id  =  $this->Osoby_Model->save($osoby_aar);
               if(is_numeric($Osoby_id)){
                $field['value'] = $Osoby_id;
                return $this->reg_int_Model->save($field);
               }
               return ;
               break;
            case 7:
                return $this->reg_varchar_Model->save($field);
                break;
            case 9:
                return $this->reg_varchar_Model->save($field);
                break;
            case 10:
                return $this->reg_varchar_Model->save($field);
                break;
            case 11:
                return $this->reg_varchar_Model->save($field);
                break;
            case 12:
                return $this->reg_varchar_Model->save($field);
                break;
            case 13:
                return $this->reg_varchar_Model->save($field);
                break;
            case 14:
                return $this->reg_int_Model->save($field);
                break;
            default:
                return $this->reg_varchar_Model->save($field);
        }
      
      // if(is_numeric($type) && ($type==1 || $type==9)){
      //   $this->reg_varchar_Model->save($field);
      // }
      
    }
                                




    public function exportAction(){

    $localizations = $this->Importexport->getLocalization();
    
     $objPHPExcel = new PHPExcel();
     $objPHPExcel->setActiveSheetIndex(0);
     $objPHPExcel->getActiveSheet()->setCellValue('A1', "ID");
     $objPHPExcel->getActiveSheet()->setCellValue('B1', "Name");
     $objPHPExcel->getActiveSheet()->setCellValue('C1', "Street");
     $objPHPExcel->getActiveSheet()->setCellValue('D1', "Number");
     $objPHPExcel->getActiveSheet()->setCellValue('E1', "Address");
     $objPHPExcel->getActiveSheet()->setCellValue('F1', "Country");
     $objPHPExcel->getActiveSheet()->setCellValue('G1', "Computer Name");


    $count =2;
    foreach($localizations as $localization){
     $objPHPExcel->getActiveSheet()->setCellValue('A'.$count, $localization['id']);
     $objPHPExcel->getActiveSheet()->setCellValue('B'.$count, $localization['name']);
     $objPHPExcel->getActiveSheet()->setCellValue('C'.$count, $localization['street']);
     $objPHPExcel->getActiveSheet()->setCellValue('D'.$count, $localization['number']);
     $objPHPExcel->getActiveSheet()->setCellValue('E'.$count,$localization['address']);
     $objPHPExcel->getActiveSheet()->setCellValue('F'.$count, $localization['country']);
     $objPHPExcel->getActiveSheet()->setCellValue('G'.$count, $localization['computername']);
     $count++;
    }

            
    
    unset($styleArray);

    $objPHPExcel->setActiveSheetIndex(0);
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="Localization.xlsx"');
    header('Cache-Control: max-age=0');
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    
    ob_end_clean();
    $objWriter->save('php://output');
    $objPHPExcel->disconnectWorksheets();
    unset($objPHPExcel);
    return;
    die();
    // $documentTemplateIds = Application_Service_Utilities::getValues($registry->documents_templates, 'id');
  }

    public function getentityid($reg_id = ''){
      
      $author_ID = Application_Service_Authorization::getInstance()->getUserId();
      if(is_numeric($reg_id) && $reg_id != ''){
          $entity['author_id'] = $author_ID;
          $entity['registry_id'] = $reg_id;
          $row = $this->registryEntriesModel->save($entity);
          if(is_numeric($row->id)){
                return $row->id;
          }
      }
      return false;
    }

    public function valueformat($val, $type){

        switch ($type) {
            case 1:
                return $val;
                break;
            case 2:
                return $val;
                break;
            case 4:
                return date("Y-m-d", strtotime($val));
                break;
            case 5:
                return date("Y-m-d H:i:s", strtotime($val));
                break;
            case 6:
                return $val;
                break;
            case 7:
                return $val;
                break;
            case 9:
                return $val;
                break;
            case 10:
                return $val;
                break;
            case 11:
                return $val;
                break;
            case 12:
                return $val;
                break;
            case 13:
                return $val;
                break;
            case 14:
                if((is_numeric($val) && $val !=0) || $val==1){
                  return 1;
                }else{
                  return 0;
                }
                break;
            default:
                return $val;
        }

    } 
  


}
// <textarea 
// name="element_53" widget="" label="Editor test" multiple="0" tag="textarea" 
// class="ckeditor-default processed-ckeditor-default" 
// style="visibility: hidden; display: none;"></textarea>

// <textarea name="element_53" widget="" label="Editor test" multiple="0" tag="textarea" 
// value="&amp;lt;p&amp;gt;Lorem Ipsum is simply dummy text of the printing and typesetting industry.
//  Lorem Ipsum has been the industry's standard dummy text ever since the 1500s,
//  when an unknown printer took a galley of type and scrambled 
//  it to make a type specimen book.&amp;lt;/p&amp;gt;
// " class="ckeditor-default processed-ckeditor-default" 
// style="visibility: hidden; display: none;"></textarea>