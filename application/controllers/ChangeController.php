<?php

include_once('OrganizacjaController.php');

class ChangeController extends OrganizacjaController {

    /**
     * 
     * Osoby model
     * @var Application_Model_Zbiory
     *
     */
    private $zbiory;
    private $zbioryPomieszczenia;
    private $matchers;

    public function init() {
        parent::init();
        $this->zbiory = Application_Service_Utilities::getModel('Zbiory');
        $this->fieldgroups = Application_Service_Utilities::getModel('Fieldgroups');
        $this->fielditems = Application_Service_Utilities::getModel('Fielditems');
        Zend_Layout::getMvcInstance()->assign('section', 'Zbiory');
    }
    
    public function indexAction() {
       $t_zbiory = $this->zbiory->fetchAll();
       foreach ( $t_zbiory AS $zbior ) {
          if ( $zbior->options <> '' ) {
             $opts = json_decode($zbior->options);
             foreach ( $opts->t_data AS $el ) {
                $el->name = mb_strtoupper($el->name);
                foreach ( $el->groupssettings AS $el2 ) {
                  $el2->name = mb_strtoupper($el2->name);
                  
                  while(list($k,$v) = each($el2->opts)) {
                     $el2->opts[$k] = mb_strtoupper($el2->opts[$k]);
                  }
                  
                  $t_data = array();
                  while(list($k,$v) = each($el2->selects)) {
                     $t_data[mb_strtoupper($k)] = $v;
                     unset($el2->selects->$k);
                  }
                  
                  while(list($k,$v) = each($t_data)) {
                     $el2->selects->$k = $v;
                  }
                }
             }
             
             foreach ( $opts->itemssettings AS $el ) {
                $el->name = mb_strtoupper($el->name);
                foreach ( $el->t_data->t_data AS $el2 ) {
                  $el2->name = mb_strtoupper($el2->name);
                  
                  while(list($k,$v) = each($el2->opts)) {
                     $el2->opts[$k] = mb_strtoupper($el2->opts[$k]);
                  }
                  
                  $t_data = array();
                  while(list($k,$v) = each($el2->checked)) {
                     $t_data[mb_strtoupper($k)] = $v;
                     unset($el2->checked->$k);
                  }
                  
                  while(list($k,$v) = each($t_data)) {
                     $el2->checked->$k = $v;
                  }
                }
             }
          }
          
          $t_data = array(
            'options' => json_encode($opts)
          );
          $this->zbiory->update($t_data,'id = \''.$zbior->id.'\'');
       }
       
       $t_fieldgroups = $this->fieldgroups->fetchAll();
       foreach ( $t_fieldgroups AS $fieldgroup ) {
          if ( $fieldgroup->options <> '' ) {
             $opts = json_decode($fieldgroup->options);
             
             while(list($k,$v) = each($opts)) {
                $opts[$k] = mb_strtoupper($opts[$k]);
             }
          }
          
          $t_data = array(
            'options' => json_encode($opts)
          );
          $this->fieldgroups->update($t_data,'id = \''.$fieldgroup->id.'\'');
       }
       
       $t_fielditems = $this->fielditems->fetchAll();
       foreach ( $t_fielditems AS $fielditem ) {
          if ( $fielditem->options <> '' ) {
             $opts = json_decode($fielditem->options);
             
             foreach ( $opts->t_data AS $el2 ) {
               $el2->name = mb_strtoupper($el2->name);
               
               while(list($k,$v) = each($el2->opts)) {
                  $el2->opts[$k] = mb_strtoupper($el2->opts[$k]);
               }
               
               $t_data = array();
               while(list($k,$v) = each($el2->checked)) {
                  $t_data[mb_strtoupper($k)] = $v;
                  unset($el2->checked->$k);
               }
               
               while(list($k,$v) = each($t_data)) {
                  $el2->checked->$k = $v;
               }
             }
             
             print_r($opts);
          }
          
          $t_data = array(
            'options' => json_encode($opts)
          );
          $this->fielditems->update($t_data,'id = \''.$fielditem->id.'\'');
       }
       echo('ok');
       die();
    }
    
}