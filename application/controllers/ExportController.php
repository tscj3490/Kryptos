<?php
require_once 'PHPExcel/IOFactory.php';
include_once('OrganizacjaController.php');
class ExportController extends OrganizacjaController
{
    private $budynki;
    private $pomieszczenia;
    private $aplikacje;
    private $osobyList;
    private $exportPath;
    protected $subDomain;

    public function init()
    {
        parent::init();
        $this->view->section = 'Export';

        $server = $_SERVER ['HTTP_HOST'];
        $serverArray = explode('.', $server);
        $this->subDomain = $serverArray [0];
    }
    public function indexAction()
    {
        $exportFolder = realpath(dirname(APPLICATION_PATH)).'/export';
        $this->exportPath = $exportFolder.'/'.$this->subDomain.'/';
        if (!file_exists($exportFolder)) {
            mkdir($exportFolder, 0777, true);
            mkdir($this->exportPath);
        }



    }
    public function beginAction()
    {
        try {
            $objPHPExcel = PHPExcel_IOFactory::load(realpath(dirname(APPLICATION_PATH)).'/templates/export-template.xls');
            $sheets = $objPHPExcel->getAllSheets();

            $objPHPExcel->setActiveSheetIndex(3);
            $objWorksheet = $objPHPExcel->getActiveSheet();
//            $this->addOrganizacja();
//            $this->addBudynki();
//            $this->addPomieszczenia();
//            $this->addUzytkownicy();
//            $this->addAplikacje();
            $zbioryItems = $this->processZbiory();
            foreach ($zbioryItems as $col => $items) {
                $i = 1;
               foreach ($items as $item) {
                 $objWorksheet->setCellValueByColumnAndRow($col, $i++, $item);
               }
            }

            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.date('Y-m-d').'-'.$this->subDomain.'.xls"');
            header('Cache-Control: max-age=0');
            $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $writer->save('php://output');
            exit();
        } catch (Exception $e) {
            print($e->getMessage());
            exit();
        }

    }



    private function addOrganizacja()
    {
        $organizacjaModel = Application_Service_Utilities::getModel('Settings');
        $items = $organizacjaModel->getAll();
    }

    private function addBudynki()
    {
       $budynkiModel = Application_Service_Utilities::getModel('Budynki');
       $items = $budynkiModel->getAll();
    }

    private function addPomieszczenia()
    {
       $pomieszczeniaModel = Application_Service_Utilities::getModel('Pomieszczenia');
       $items = $pomieszczeniaModel->getAll();
    }

    private function addUzytkownicy()
    {
       $usersModel = Application_Service_Utilities::getModel('Osoby');
       $users = $usersModel->getAll();
    }

    private function addAplikacje()
    {
       $aplikacjeModel = Application_Service_Utilities::getModel('Applications');
       $aplikacje = $aplikacjeModel->getAll();
    }

    private function processZbiory()
    {
       $zbioryModel = Application_Service_Utilities::getModel('Zbiory');
       $zbiory = $zbioryModel->getAll();

       foreach ($zbiory as $key => $zbior) {
          $items[$key][0] = $zbior['nazwa'];
          $items[$key][1] = mb_strtoupper($zbior['formaGromadzeniaDanych']);
          $items[$key] = array_merge($items[$key], json_decode($zbior['opis_pol_zbioru']));
          $items[$key][count($items[$key])] =  $zbior['opis_zbioru'];
       }
       return $items;
    }


}
