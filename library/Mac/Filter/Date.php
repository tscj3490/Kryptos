<?php

class Mac_Filter_Date implements Zend_Filter_Interface {

    protected $options = array(
        'format' => 'Y-m-d',
    );

    public function __construct($options = array()) {
        $this->setOptions($options);
    }

    public function getOptions() {
        return $this->options;
    }

    public function setOptions( $options = array() ) {
        if ($options instanceof Zend_Config) {
            $options = $options->toArray();
        }

        if(!empty($options))
            $this->options = $options;

        return $this;
    }

    public function filter($date) {
        $date = strtotime( $date );

        return date($this->options['format'], $date);
    }

}