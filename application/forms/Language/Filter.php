<?php

class Application_Form_Language_Filter extends Zend_Form
{
  public function init()
  {
    $this
      ->setMethod('GET')
      ->setAttrib('class', 'global_form_box')
      ->addDecorator('FormElements')
      ->addDecorator('Form')
      ->setAction(Zend_Controller_Front::getInstance()->getRouter()->assemble(array()))
      ;

    // Init search
    $this->addElement('Text', 'search', array(
      'decorators' => array(
        'ViewHelper',
        array('HtmlTag', array('tag' => 'div')),
      ),
    ));

    // Init show
    $this->addElement('Select', 'show', array(
      'multiOptions' => array(
        'all' => 'All',
        'missing' => 'Missing',
        'translated' => 'Translated',
      ),
      'decorators' => array(
        'ViewHelper',
        array('HtmlTag', array('tag' => 'div')),
      ),
    ));

    // Init submit
    $this->addElement('Button', 'submit', array(
      'label' => 'Search',
      'type' => 'submit',
      'decorators' => array(
        'ViewHelper',
        array('HtmlTag', array('tag' => 'div')),
      ),
    ));
  }
}