<?php

class Application_Service_Arrivals
{
    /** @var self */
    protected static $_instance = null;
    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? new self : self::$_instance; }

    /** @var Application_Model_Arrivals */
    protected $arrivalsModel;

    /** @var Application_Model_Osoby */
    private $osobyModel;
    /** @var Application_Model_Companiesnew */
    protected $companiesModel;


    private function __construct()
    {
        self::$_instance = $this;

        $this->arrivalsModel = Application_Service_Utilities::getModel('Arrivals');

        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');
        $this->companiesModel = Application_Service_Utilities::getModel('Companiesnew');
    }

    public function savePhoneCall($data)
    {
        $arrivalData = array_merge($data['arrival'], array(
            'status' => Application_Model_Arrivals::STATUS_NEW,
            'type' => Application_Model_Arrivals::TYPE_PHONE_CALL,
        ));

        $arrival = $this->save($arrivalData);

        return $arrival;
    }

    public function save($data)
    {
        $dateNow = date('Y-m-d H:i:s');

        $data = array_merge(array(
            'status' => 1,
            'type' => 1,
            'date' => $dateNow,
        ), $data);

        if ($data['date'] === 'CURRENT_DATE' || empty($data['date'])) {
            $data['date'] = $dateNow;
        }

        $arrival = $this->arrivalsModel->save($data);

        return $arrival;
    }
}