<?php

class PomieszczeniaController extends Muzyka_Admin
{
    /**
     *
     * Osoby model
     * @var Application_Model_Pomieszczenia
     *
     */

    private $pomieszczenia;
    private $budynki;

    public function init()
    {
        parent::init();
        $this->view->section = 'Pomieszczenia';
        $this->budynki = Application_Service_Utilities::getModel('Budynki');
        $this->pomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');
        $this->budynki = Application_Service_Utilities::getModel('Budynki');
        $this->pomieszczeniadozbiory = Application_Service_Utilities::getModel('Pomieszczeniadozbiory');
        $this->zbiory = Application_Service_Utilities::getModel('Zbiory');
        $this->klucze = Application_Service_Utilities::getModel('Klucze');
        $this->osoby = Application_Service_Utilities::getModel('Osoby');
        $this->zabezpieczenia = Application_Service_Utilities::getModel('Zabezpieczenia');
        Zend_Layout::getMvcInstance()->assign('section', 'Pomieszczenia');
    }

    public static function getPermissionsSettings() {
        $pomieszczeniaCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/pomieszczenia/create'),
                2 => array('perm/pomieszczenia/update')
            ),
        );

        $settings = array(
            'modules' => array(
                'pomieszczenia' => array(
                    'label' => 'Zbiory/Pomieszczenia',
                    'permissions' => array(
                        array(
                            'id' => 'create',
                            'label' => 'Tworzenie wpisów',
                        ),
                        array(
                            'id' => 'update',
                            'label' => 'Edycja własnych wpisów',
                        ),
                        array(
                            'id' => 'remove',
                            'label' => 'Usuwanie własnych wpisów',
                        )
                    ),
                ),
            ),
            'nodes' => array(
                'pomieszczenia' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    'index' => array(
                        'permissions' => array('perm/pomieszczenia'),
                    ),
                    'mini-add' => array(
                        'getPermissions' => array(),
                    ),

                    'update' => array(
                        'getPermissions' => array($pomieszczeniaCheck),
                    ),
                    'save' => array(
                        'getPermissions' => array($pomieszczeniaCheck),
                    ),

                    'del' => array(
                        'permissions' => array('perm/pomieszczenia/remove'),
                    ),

                    'profilespdf' => array(
                        'permissions' => array('perm/pomieszczenia'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function getTopNavigation()
    {
        $this->setSectionNavigation(array(
            array(
                'label' => 'Raporty',
                'path' => 'javascript:;',
                'icon' => 'fa icon-print-2',
                'rel' => 'reports',
                'children' => array(
                    array(
                        'label' => 'Wykaz budynków i pomieszczeń',
                        'path' => '/reports/wykazbudynkowprzetwdane',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin'
                    ),
                    array(
                        'label' => 'Wykaz budynków, pomieszczeń i zabezpieczeń',
                        'path' => '/reports/wykazbudynkowprzetwdane-zabezpieczenia',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin'
                    ),
                    array(
                        'label' => 'Wykaz kluczy',
                        'path' => '/reports/wykazkluczy',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin'
                    ),
                )
            ),
        ));
    }

    public function indexAction()
    {
        $this->view->paginator = $this->pomieszczenia->getAll();
        $this->view->budynki = $this->budynki->getAll();
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', $req->getParam('clone', 0));

        if ($id) {
            $row = $this->pomieszczenia->getForEdit($id);
            $this->view->data = $row;
            $this->view->zabezpieczeniaArray = array_merge(Application_Service_Utilities::getUniqueValues($row->safeguards, 'safeguard_id'), Application_Service_Utilities::getUniqueValues($row->safeguards_budynek, 'safeguard_id'));
            $this->view->zabezpieczeniaInherited = Application_Service_Utilities::getUniqueValues($row->safeguards_budynek, 'safeguard_id');

            $this->setDetailedSection('Edytuj pomieszczenie');
        } else {
            $this->setDetailedSection('Dodaj pomieszczenie');
        }
        $budynki = $this->budynki->getAll();
        $this->view->clone = $req->getParam('clone', 0);
        $this->view->budynki = $budynki;
        $this->view->budynkiCount = count($budynki->toArray());
        $this->view->t_zabezpieczenia = $this->zabezpieczenia->fetchAll(null, 'nazwa')->toArray();
    }
    
    public function saveAction()
    {
        try {
            $this->getRepository()->getOperation()->operationBegin(Application_Service_Repository::OPERATION_IMPORTANT);

            $req = $this->getRequest();
            $budynek_id = $req->getParam('budynki_id', 0);
            $budynek = $this->budynki->getOne($budynek_id);
            if (!($budynek instanceof Zend_Db_Table_Row)) {
                throw new Zend_Db_Exception('Blad zapisu. Budynek zostal usuniety');
            }

            $data = $req->getParams();
            $data['budynki_id'] = $budynek->id;
            if (!isset($data['zabezpieczenia'])) {
                $data['zabezpieczenia'] = [];
            }

            $id = $this->pomieszczenia->save($data);
            $this->getRepository()->getOperation()->operationComplete('pomieszczenia.update', $id);
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        if ($req->getParam('addAnother', 0) == "1"){
            $this->_redirect('/pomieszczenia/update');
        } else {
            $this->_redirect('/pomieszczenia');
        }
    }

    public function delAction()
    {
        try {
            $this->getRepository()->getOperation()->operationBegin(Application_Service_Repository::OPERATION_IMPORTANT);

            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->pomieszczenia->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));

            $this->getRepository()->getOperation()->operationComplete('pomieszczenia.remove', $id);
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/pomieszczenia');
    }

    public function profilespdfAction()
    {
        $this->view->ajaxModal = 1;

        $css = ('
            <style type="text/css">
               @page { margin:2cm 2cm 2cm 2cm!important;padding:0!important;line-height: 1; font-family: Arial; color: #000; background: none; font-size: 11pt; }
               *{ line-height: 1; font-family: Arial; color: #000; background: none; font-size: 11pt; }
               h1,h2,h3,h4,h5,h6 { page-break-after:avoid; }
               h1{ font-size:19pt; }
               h2{ font-size:17pt; }
               h3{ font-size:15pt; }
               h4,h5,h6{ font-size:14pt; }
               .break{ page-break-after: always; }
               p, h2, h3 { orphans: 3; widows: 3; }
               code { font: 12pt Courier, monospace; } 
               blockquote { margin: 1.2em; padding: 1em; font-size: 12pt; }
               hr { background-color: #ccc; }
               img { float: left; margin: 1em 1.5em 1.5em 0; max-width: 100% !important; }
               a img { border: none; }
               a:link, a:visited { background: transparent; font-weight: 700; text-decoration: underline;color:#333; }
               a:link[href^="http://"]:after, a[href^="http://"]:visited:after { content: " (" attr(href) ") "; font-size: 90%; }
               abbr[title]:after { content: " (" attr(title) ")"; }
               a[href^="http://"] { color:#000; }
               a[href$=".jpg"]:after, a[href$=".jpeg"]:after, a[href$=".gif"]:after, a[href$=".png"]:after { content: " (" attr(href) ") "; display:none; }
               a[href^="#"]:after, a[href^="javascript:"]:after { content: ""; }
               table { width:100%; }
               th { }
               td {vertical-align:top}
               th,td { padding: 4px 10px 4px 0; }
               tfoot { font-style: italic; }
               caption { background: #fff; margin-bottom:2em; text-align:left; }
               thead { display: table-header-group; }
               img,tr { page-break-inside: avoid; } 
            </style>
         ');

        require_once('mpdf60/mpdf.php');

        $mpdf = new mPDF('', 'A4', '', '', '0', '0', '0', '0', '', '', 'P');

        $i = 0;
        $paginator = $this->pomieszczenia->fetchAll(null, array('nazwa'))->toArray();
        foreach ($paginator AS $k => $v) {
            $i++;
            $t_budynek = $this->budynki->fetchRow(array('id = ?' => $v['budynki_id']));
            $content = ('
               <table cellspacing="0" style="width:100%;">
                  <tr>
                     <td style="text-align:center;font-size:18pt;">
                        Raport dla pomieszczenia ' . mb_strtoupper($v['nazwa']) . ' nr ' . mb_strtoupper($v['nr']) . '<br />znajdującego się w budynku ' . $t_budynek->nazwa . '<br /><br />wygenerowany dnia ' . date('Y-m-d') . '
                     </td>
                  </tr>
               </table>
               <br />
               <br />
               <br />
               <table cellspacing="0" style="width:100%;">
                  <tr>
                     <td style="width:50%;text-align:left;">
                        <strong>Osoby posiadające dostęp do pomieszczenia</strong><br />
                        <br />
                        <div style="text-align:left;">
                           <ul>
            ');

            $t_data = array();
            $t_klucze = $this->klucze->fetchAll(array('pomieszczenia_id = ?' => $v['id']));
            foreach ($t_klucze AS $klucz) {
                $t_osoba = $this->osoby->fetchRow(array('id = ?' => $klucz->osoba_id));

                $t_data[$t_osoba->imie . ' ' . $t_osoba->nazwisko] = $t_osoba->imie . ' ' . $t_osoba->nazwisko;
            }

            if (count($t_data) > 0) {
                ksort($t_data);
                foreach ($t_data AS $kx => $vx) {
                    $content .= ('
                     <li>' . mb_strtoupper($kx) . '</li>
                  ');
                }
            } else {
                $content .= ('
                  BRAK OSÓB POSIADAJĄCYCH DOSTĘP DO POMIESZCZENIA
               ');
            }

            $content .= ('
                           </ul>
                        </div>
                     </td>
                     <td style="width:50%;text-align:left;">
                        <strong>Zbiory przechowywane w pomieszczeniu</strong><br />
                        <br />
                        <div style="text-align:left;">
                           <ul>
            ');

            $t_data = array();
            $t_pomieszczeniadozbiory = $this->pomieszczeniadozbiory->fetchAll(array('pomieszczenia_id = ?' => $v['id']));
            foreach ($t_pomieszczeniadozbiory AS $zbiorin) {
                $t_zbior = $this->zbiory->fetchRow(array(
                    'id = ?' => $zbiorin->zbiory_id,
                    'usunieta <> ?' => 1,
                ));

                if ($t_zbior->id > 0) {
                    $t_data[$t_zbior->nazwa] = $t_zbior->nazwa;
                }
            }

            if (count($t_data) > 0) {
                ksort($t_data);
                foreach ($t_data AS $kx => $vx) {
                    $content .= ('
                     <li>
                        ' . $vx . '
                     </li>
                  ');
                }
            } else {
                $content .= ('
                  BRAK ZBIORÓW W POMIESZCZENIU
               ');
            }

            $content .= ('
                           </ul>
                        </div>
                     </td>
                  </tr>
               </table>
            ');

            if ($i > 1) {
                $mpdf->AddPage();
            }
            $mpdf->WriteHTML($css . '' . $content . '');
        }

        $mpdf->Output();

        die();
    }

    public function miniAddAction()
    {
        $this->view->ajaxModal = 1;
        $this->view->records = $this->pomieszczenia->getAllForTypeahead();
    }

}