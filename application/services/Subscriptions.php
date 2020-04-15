<?php

class Application_SubscriptionOverLimitException extends Exception
{
    // Redefine the exception so message isn't optional
    public function __construct($message, $code = 0, Exception $previous = null) {
        // some code
    
        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }

    // custom string representation of object
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}

class Application_Service_Subscriptions
{
    /** @var self */
    protected static $_instance = null;

    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? new self : self::$_instance; }

    private function __construct()
    {
        self::$_instance = $this;

        $this->boxes = Application_Service_Utilities::getModel('UiBoxes');
        $this->directives = Application_Service_Utilities::getModel('UiDirectives');
        $this->sections = Application_Service_Utilities::getModel('UiSections');
    }
    
    public function checkLimit($moduleName, $requestedValue) {
        $systemsModel = Application_Service_Utilities::getModel('Systems');
        $appId = Zend_Registry::getInstance()->get('config')->production->app->id;
        $system = $systemsModel->getOne(array('bq.subdomain = ?' => $appId));

        //var_dump('Checking limit for module... '.$moduleName);
        //var_dump('Requested value... '.$requestedValue);
       // var_dump($system);

        if (!$system){
            return true;
        }
        $limitsModel = Application_Service_Utilities::getModel('SubscriptionLevelLimits');
        $limit = $limitsModel->getOne(array('name = ?' => $moduleName, 'type' => $system->type));

        if (!$limit){
            return true;
        }

        return ($requestedValue < ($limit->limit));
    }
}