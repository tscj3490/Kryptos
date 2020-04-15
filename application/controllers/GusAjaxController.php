<?php
    use GusApi\GusApi;
    use GusApi\RegonConstantsInterface;
    use GusApi\Exception\InvalidUserKeyException;
    use GusApi\ReportTypes;

class GusAjaxController extends Muzyka_Admin
{
    public function init()
    {
        parent::init();
    }

    public static function getPermissionsSettings() {
        $settings = [
            'modules' => [
                'gus-ajax' => [
                    'label' => 'Dostęp do danych GUS',
                    'permissions' => [],
                ],
            ],
            'nodes' => [
                'gus-ajax' => [
                    '_default' => [
                        'permissions' => ['user/anyone'],
                    ],
                    'get' => [
                        'permissions' => ['user/anyone'],
                    ]
                ],
            ]
        ];

        return $settings;
    }




    function getAction()
    {
      //  $this->disableLayout();
      $nip = $this->getParam('nip');
        $gus = new GusApi(
            'f35c6081009f433ab2b2', // <--- your user key / twój klucz użytkownika
            new \GusApi\Adapter\Soap\SoapAdapter(
                RegonConstantsInterface::BASE_WSDL_URL,
                RegonConstantsInterface::BASE_WSDL_ADDRESS //<--- production server / serwer produkcyjny
                //for test serwer use RegonConstantsInterface::BASE_WSDL_ADDRESS_TEST
                //w przypadku serwera testowego użyj: RegonConstantsInterface::BASE_WSDL_ADDRESS_TEST
            )
        );

        try {
            $gus->login();
        } catch (InvalidUserKeyException $e) {
            $this->outputJson(array('error' => 'invalid-key' ));
        }

        if ($gus->serviceStatus() === RegonConstantsInterface::SERVICE_AVAILABLE) {
        try {
            if (!isset($_SESSION['sid']) || !$gus->isLogged($_SESSION['sid'])) {
                $_SESSION['sid'] = $gus->login();
            }
            if (isset($nip)) {

                try {
                    $gusReport = $gus->getByNip($_SESSION['sid'], $nip);

                    $name = @$gusReport[0]->name;
                    $regon = $gusReport[0]->regon;
                    $this->outputJson(array('name'=> $name, 'nip' => $nip, 'regon' => $regon));
                   // echo $gusReport[0]->getName();
                } catch (\GusApi\Exception\NotFoundException $e) {

                    $this->outputJson(array('error'=> 'notFound'));
                }
            }
            } catch (InvalidUserKeyException $e) {
                $this->outputJson(array('error' => 'invalid-key' ));
            }
            } else if ($gus->serviceStatus() === RegonConstantsInterface::SERVICE_UNAVAILABLE) {
                $this->outputJson(array('error'=> 'notAvailable'));
            } else {
                $this->outputJson(array('error'=> 'technicalBreak'));
            }
                    

    }

}