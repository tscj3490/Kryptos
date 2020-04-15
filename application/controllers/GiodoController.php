<?php

include_once('OrganizacjaController.php');

class GiodoController extends OrganizacjaController
{
    private $zbiory;

    public function init()
    {
        parent::init();
        $this->zbiory = Application_Service_Utilities::getModel('Zbiory');
        Zend_Layout::getMvcInstance()->assign('section', 'Rejestr zbiorów GIODO');
    }

    public static function getPermissionsSettings() {
        $settings = array(
            'modules' => array(
                'giodo' => array(
                    'label' => 'Zbiory/Rejestr zbiorów GIODO',
                    'permissions' => array(),
                ),
            ),
            'nodes' => array(
                'giodo' => array(
                    '_default' => array(
                        'permissions' => array('perm/giodo'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function indexAction()
    {
        if ($this->_getParam('showall', 0) == 1) {
            $this->itemsPerPage = 999999999;
        }

        $zbiory = $this->zbiory->getAll();

        $zbiory_res = array();
        foreach ($zbiory as $key => $zbior) {
            if ($zbior['status'] != Application_Model_Zbiory::STATUS_NIEPODLEGA) {
                $zbiory_res[] = $zbior;
            }
        }

        $this->view->paginator = $zbiory_res;
    }

    public function wniosekxmlAction()
    {
        $zbioryModel = Application_Service_Utilities::getModel('Zbiory');
        $id = $this->_getParam('id', 0);

        try {
            $zbior = $zbioryModel->get($id);
            $content = $zbioryModel->getxmlGiodo($id, $this->getCompanyInfo());
            Muzyka_File::displayFile(sprintf('wniosek_giodo_%s_%s.xml', $zbior['nazwa'], $this->getTimestampedDate()), 'xml');
            print($content);

        } catch (Exception $e) {
            throw new Exception('Próba odczytania danych nie powiodła się', 500, $e);
        }
        die();
    }
}