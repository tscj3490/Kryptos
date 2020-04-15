<?php

class Application_Service_Ui
{
    /** @var self */
    protected static $_instance = null;

    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? new self : self::$_instance; }

    /** @var Application_Model_UiBoxes */
    protected $boxes;
    /** @var Application_Model_UiDirectives */
    protected $directives;
    /** @var Application_Model_UiSections*/
    protected $sections;

    protected $boxesCache = [];
    protected $directivesCache = [];
    protected $sectionCache = [];

    private function __construct()
    {
        self::$_instance = $this;

        $this->boxes = Application_Service_Utilities::getModel('UiBoxes');
        $this->directives = Application_Service_Utilities::getModel('UiDirectives');
        $this->sections = Application_Service_Utilities::getModel('UiSections');
    }

    public function getBox($id, $context = [])
    {
        if (isset($this->boxesCache[$id])) {
            return $this->boxesCache[$id];
        }

        $box = $this->boxes->getOne(['id = ?' => $id]);
        $boxParser = $this->getParser($box['template']);
        return $this->boxesCache = $this->startParse($boxParser, $context);
    }

    public function getDirective($id, $context = [])
    {
        if (isset($this->directivesCache[$id])) {
            return $this->directivesCache[$id];
        }

        $directive = $this->directives->getOne(['id = ?' => $id]);
vd($directive);
        if (!$directive->template_id) {
            $parameters = $this->getParameters((array) json_decode($directive->parameters, true), $context);
            return Application_Service_Utilities::getModel($directive->model)->{$directive->function}($parameters);
        }
        Throw new Exception('Errro');
        $directiveParser = $this->getParser($directive->template->content);
        return $this->directivesCache[$id] = $this->startParse($directiveParser, $context);
    }

    public function getSection($id, $context = [])
    {
        if (isset($this->sectionCache[$id])) {
            return $this->sectionCache[$id];
        }

        $section = $this->sections->getOne(['id = ?' => $id]);
        $sectionParser = $this->getParser($section['template']);
        return $this->sectionCache = $this->startParse($sectionParser, $context);
    }

    public function getSectionByName($name, $context = [])
    {
        $section = $this->sections->getOne(['name = ?' => $name]);
        vd($section);
        if (!$section) {
            return '';
        }

        $sectionParser = $this->getParser($section->template->content);
        return $this->sectionCache[$section->id] = $this->startParse($sectionParser, $context);
    }

    /**
     * @param Application_Service_UiParser $parser
     */
    public function startParse($parser, $context)
    {
        try {
            foreach ($parser->find("//div[contains(@class, 'cke-kryptoscustomtags-element-block')]") as $elementBlock) {
                if ('ui' === $elementBlock->getAttribute('data-type')) {
                    $elementId = $elementBlock->getAttribute('data-ui-id');
                    $elementType = $elementBlock->getAttribute('data-ui-type');

                    switch ($elementType) {
                        case "section":
                            $result = $this->getSection($elementId, $context);
                            break;
                        case "block":
                            $result = $this->getBox($elementId, $context);
                            break;
                        case "directive":
                            $result = $this->getDirective($elementId, $context);
                            break;
                    }

                    if (!empty($result)) {
                        $elementBlock->parentNode->replaceChild($parser->dom->importNode(Application_Service_Utilities::getDomElement($result), true), $elementBlock);
                    }
                }
            }

            return $parser->getResult();
        } catch (Exception $e) {
             vdie($e);
        }
    }

    public function getParser($template)
    {
        return new Application_Service_UiParser($template);
    }

    private function getParameters($parameters, $context)
    {
        foreach ($parameters as $k => $v) {
            if ($v[0] === ':') {
                $key = substr($v, 1);

                $value = Application_Service_Utilities::getValue($context, $key);
                if (!$value) {
                    // disabled cause: errors on create pages
                    // Throw new Exception('Error');
                }

                $parameters[$k] = $value;
            }
        }

        return $parameters;
    }
}