<?php

class Application_Form_Language_Delete extends Zend_Form
{
  public function init()
  {
    $router = Zend_Controller_Front::getInstance()->getRouter();
    $action = $router->assemble(array(
        'action' => 'delete',
        'locale' => $this->getAttrib('locale')
    ));
    $this->setAction($action); 
    $this
      //->setTitle('Delete Language Pack')
      //->setDescription('You are about to delete the language pack "%s".  Are you sure you want to do this?  This action cannot be undone.')
      ->setAttrib('class', 'global_form_popup')
      ;

    // Init submit
    $this->addElement('Button', 'submit', array(
      'label' => 'Delete',
      'id' => 'delete-language-btn',
      'ignore'=>true,
      'decorators' => array(
        'ViewHelper',
      ),
    ));

    $this->addElement('Button', 'cancel', array(
      'prependText' => ' or ',
      'link' => true,
      'label' => 'cancel',
      'onclick' => '$.colorbox.close();',
      'decorators' => array(
        'ViewHelper'
      )
    ));

    $this->addDisplayGroup(array(
      'submit',
      'cancel'
    ), 'buttons', array(
      'decorators' => array(
        'FormElements'
      )
    ));


    $this->addDisplayGroup(array('submit', 'cancel'), 'buttons');
    $button_group = $this->getDisplayGroup('buttons');

  }
}