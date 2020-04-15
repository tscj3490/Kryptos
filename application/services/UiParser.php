<?php

class Application_Service_UiParser
{
    /** @var Application_Model_UiBoxes */
    protected $boxes;
    /** @var Application_Model_UiSections*/
    protected $sections;
    /** @var Application_Model_UiDirectives */
    protected $directives;

    /** @var DOMDocument */
    public $dom;

    /** @var DOMXPath */
    public $xpath;

    function __construct($template)
    {
        $this->dom = new DOMDocument('1.0', 'UTF-8');
        $this->dom->preserveWhiteSpace = false;
        $this->dom->strictErrorChecking = false;
        $this->dom->substituteEntities = false;
        $this->dom->formatOutput = false;

        $this->xpath = new DOMXPath($this->dom);

        $this->boxes = Application_Service_Utilities::getModel('UiBoxes');
        $this->sections = Application_Service_Utilities::getModel('UiSections');
        $this->directives = Application_Service_Utilities::getModel('UiDirectives');

        $this->load($template);
    }

    public function load($template)
    {
        $this->dom->loadHTML('<?xml encoding="utf-8" ?><div id="ui_parser_wrapper">' . $template, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD . '<div>');
        $this->xpath = new DOMXPath($this->dom);
    }

    public function find($param)
    {
        return $this->xpath->query($param);
    }

    public function getResult()
    {
        //vdie($this->dom->saveHTML());
        $result = '';
        foreach ($this->dom->getElementById('ui_parser_wrapper')->childNodes as $node) {
            $result .= $this->dom->saveHTML($node);
        }
        return $result;
    }
}