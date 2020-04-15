<?php
include_once('OrganizacjaController.php');

class StaticController extends OrganizacjaController
{
    public function init()
    {
        parent::init();
        Zend_Layout::getMvcInstance()->assign('section', 'Informacje');
        //$this->_helper->layout->setLayout('podstrona');
    }

    public static function getPermissionsSettings() {
        $settings = [
            'nodes' => [
                'static' => [
                    '_default' => [
                        'permissions' => [],
                    ],
                ],
            ]
        ];

        return $settings;
    }

    public function indexAction()
    {
        $id = (int)$this->_getParam('id', 0);
        $this->view->pid = $id;

        $pages = Application_Service_Utilities::getModel('Pages');
        $page = $pages->get($id);
        $this->view->page = $page;
        Zend_Layout::getMvcInstance()->assign('section', $page['nazwa']);
    }

    public function napiszAction()
    {

    }

    public function contactAction()
    {
        $this->getLayout()->setLayout('home');
    }

    public function savenapiszAction()
    {
        $tresc = $this->_getParam('tresc', '');
        $email = $this->_getParam('email', '');
        $body = $tresc;

        $this->sendMail($body, $email . ' [zapytanie]: ' . $_SERVER['HTTP_HOST'], 'bok@kryptos24.pl');

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wysłano'));
        $this->_redirect('/admin');
    }

    public function sendAjaxAction()
    {
        $this->setAjaxAction();

        $tresc = $this->_getParam('tresc', '');
        $email = $this->_getParam('email', '');
        $body = $tresc;

        $this->sendMail($body, $email . ' [zapytanie]: ' . $_SERVER['HTTP_HOST'], 'bok@kryptos24.pl');

        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Wysłano'));

        echo json_encode(array('status' => 1));
        exit;
    }

    /*
    public function indexAction()
    {
        $id = (int)$this->_getParam('id', 0);
        $this->view->pid = $id;
        $lng =  $this->_getParam('lang', 0);
        if($lng  === 'pl') $this->session->lang = 'pl';
        else if ($lng === 'en') $this->session->lang = 'en';

        $dataLockerIds = array(18,19,20,21,22,23,24,25);
        if(in_array($id, $dataLockerIds))
            $this->view->dataLockerSubPath = 1;
        if($id)
        {
            $meta_desc = "";
            $pages = Application_Service_Utilities::getModel('Pages');
            $page = $pages->get($id);

            $page['content'] = str_replace('[contact_form]',$this->view->render('box/contact.html'),stripslashes(str_replace("{url}", $this->url, $page['content'])));
            $page['content_en'] = str_replace('[contact_form]',$this->view->render('box/contact.html'),stripslashes(str_replace("{url}", $this->url, $page['content_en'])));
            $page['content_de'] = str_replace('[contact_form]',$this->view->render('box/contact.html'),stripslashes(str_replace("{url}", $this->url, $page['content_de'])));

            $page['content'] = str_replace('[menu_left_products]',$this->view->render('box/menu_products.html'), $page['content']);
            $page['content_en'] = str_replace('[menu_left_products]',$this->view->render('box/menu_products.html'), $page['content_en']);
            $page['content_de'] = str_replace('[menu_left_products]',$this->view->render('box/menu_products.html'), $page['content_de']);

            //[interview_apply]
            $page['content'] = str_replace('[interview_apply]',$this->view->render('box/interview_apply.html'), $page['content']);
            $page['content_en'] = str_replace('[interview_apply]',$this->view->render('box/interview_apply.html'), $page['content_en']);
            $page['content_de'] = str_replace('[interview_apply]',$this->view->render('box/interview_apply.html'), $page['content_de']);

            //navapply
            $page['content'] = str_replace('[nav_products]',$this->view->render('box/nav_products.html'), $page['content']);
            $page['content_en'] = str_replace('[nav_products]',$this->view->render('box/nav_products.html'), $page['content_en']);
            $page['content_de'] = str_replace('[nav_products]',$this->view->render('box/nav_products.html'), $page['content_de']);

            $this->view->page = $page;
            $this->view->tab = $id;
            $title = null;
            if($this->session->lang == 'pl' || $lng =='pl')
            {
                $this->view->title  = $title = 	$page['name'] ? $page['name'] : "Brak tytułu strony";
                $meta_desc = $page['meta_pl'];
            }
            else if($this->session->lang == 'en' || $lng =='en')
            {
                $this->view->title  = $title = 	$page['name_en'] ? $page['name_en'] : "There's no title set for this site";
                $meta_desc = $page['meta_en'];
            }
            else if($this->session->lang == 'de' || $lng =='de')
            {
                $this->view->title  = $title = 	$page['name_de'] ? $page['name_de'] : "Es gibt keinen Titel für diese Seite setzen";
                $meta_desc = $page['meta_de'];
            }
            else if($this->session->lang == 'ua' || $lng =='ua')
            {
                $this->view->title  = $title = 	$page['name_de'] ? $page['name_de'] : "відсутність змісту на вашій мові";
                $meta_desc = $page['meta_de'];
            }
            else
            {
                $this->session->lang = 'pl';
                $this->view->title  = $title = 	$page['name'] ? $page['name'] : "Brak tytułu strony";
                $meta_desc = $page['meta_de'];
            }
            $dataLockerIds = array(18,19,20,21,22,23,24,25);
            if(in_array($id, $dataLockerIds))
            {
                $this->view->sitepath = array(array(url => 'datalocker,14.html', 'name' => 'DataLocker' ),array(url => ''.str_replace(',','_',$title).','.$page['id'].'.html', 'name' => $title ));
                $this->view->dataLockerSubPath = "ok";
            }
            else
            {
                $this->view->sitepath = array(array(url => ''.str_replace(',','_',$title).','.$page['id'].'.html', 'name' => $title ));
                $this->view->dataLockerSubPath = "false";
            }
            $this->view->lang = $this->session->lang;
            $this->view->meta_desc = $meta_desc;
            //$this->view->sitepath = array(array(url => ''.str_replace(',','_',$title).','.$page['id'].'.html', 'name' => $title ));

            //$menu = Application_Service_Utilities::getModel('Menu');
            //$this->view->menu = $menu->getmenu($id);
        }
        else
        {
            $this->view->title = ($this->session->lang =='pl') ? "Błąd 404" : "Error 404";
            $this->view->page = ($this->session->lang =='pl') ? "Taka strona nie istnieje" : "Page does not exists";
        }
    }

    public function setlangAction()
    {
        if($this->_getParam('lang', 0))
        {
            $lang = trim(strip_tags(strtolower($this->_getParam('lang'))));
            switch($lang)
            {
                case "en":
                    $this->session->lang = "en";
                break;
                case "pl":
                    $this->session->lang = "pl";
                break;
                case "de":
                    $this->session->lang = "de";
                break;
                case "ua":
                    $this->session->lang = "ua";
                break;
                default:
                    $this->session->lang = "pl";
                break;
            }
        }
        if($this->_getParam('ajax', 0) == 1)
        {

        }
        else
        {
            $this->_redirect($this->url);
        }
        exit;
    }

    public function sitemapAction()
    {
        $pages = Application_Service_Utilities::getModel('Pages');
        $page = $pages->getAll();
        $this->view->pages = $page;
        //Zend_Debug::dump($page);exit;
    }

    public function xmlsitemapAction()
    {
        $pages = Application_Service_Utilities::getModel('Pages');
        $page = $pages->getAll();
        $lng = $this->_getParam('lng', 0);
        header('Content-type: application/xml; charset="utf-8"');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
                <urlset
                      xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
                      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                      xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
                            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';


        foreach($page as $p)
        {
            if($lng === 'en')
            {
                $xml .= '<url>
                      <loc>http://kryptos.co/'.urlencode(str_replace(",",'',$p['name_en'] != '' ? $p['name_en'] : $p['name'])).','.$p['id'].',en.html</loc>
                      <changefreq>daily</changefreq>
                      <priority>1</priority>
                    </url>
                    ';
            }
            else
            {
                $xml .= '<url>
                      <loc>http://kryptos.co/'.urlencode(str_replace(",",'',$p['name'])).','.$p['id'].'.html</loc>
                      <changefreq>daily</changefreq>
                      <priority>1</priority>
                    </url>
                    ';
            }
        }
        $xml .= '</urlset>';
        echo $xml;exit;
    }

    public function istorageAction()
    {
        $this->_helper->layout->setLayout('istorage');
    }

    public function konkursAction()
    {
        $this->view->sitepath = array(array(url => '/konkurs.html', 'name' => 'Konkurs' ));
        $this->_helper->layout->setLayout('podstrona');
    }

    public function konkursuploadAction()
    {
        $error = "";
        $msg = "";
        $fileElementName = 'fileToUpload';
        if(!empty($_FILES[$fileElementName]['error']))
        {
            switch($_FILES[$fileElementName]['error'])
            {

                case '1':
                case '2':
                    $error = 'Zbyt duży rozmiar pliku';
                    break;
                case '3':
                    $error = 'Plik został wgrany w części';
                    break;
                case '4':
                    $error = 'Plik nie został wgrany';
                    break;

                case '6':
                    $error = 'Brak folderu tmp na serwerze';
                    break;
                case '7':
                    $error = 'Błąd zapisu';
                    break;
                case '8':
                    $error = 'Złe rozszerzenie';
                    break;
                case '999':
                default:
                    $error = 'Brak kodu błędu';
            }
            }
            elseif(empty($_FILES['fileToUpload']['tmp_name']) || $_FILES['fileToUpload']['tmp_name'] == 'none')
            {
                $error = 'Plik nie został wgrany.';
            }
            else
            {
                    $fi = pathinfo ($_FILES['fileToUpload']['name']);
                    $name = strtolower(md5($_FILES['fileToUpload']['tmp_name']).uniqid().'.'.$fi['extension']);
                    $new_path = APPLICATION_PATH.'/../konkurs_prace/'.$name;
                    move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $new_path);
                    @unlink($_FILES['fileToUpload']);
            }
            $response = array('error' => $error, 'msg' => $_FILES['fileToUpload']['name'], 'newfile' => $new_path);
            echo json_encode($response);
            exit;
    }

    public function dosendkonkursAction()
    {
        try
        {
            $konkurs_model = Application_Service_Utilities::getModel('Zgloszeniakonkurs');

            $data = $this->_getParam('data', 0);
            //Zend_Debug::Dump($data);

            //$data['filelocation'] = str_replace('/application/..','', $data['filelocation']);

            $arr = array(

                        'namesurname' => $data['name'],
                        'adress' => $data['adress'],
                        'email' => $data['mail'],
                        'birthday' => $data['birth'],
                        'phone' => $data['phone'],
                        'file' => str_replace('/application/..','', $data['filelocation']),

            );
            $konkurs_model->insert($arr);
        }
        catch(Exception $e)
        {
            echo $e->getMessage();
        }
        $this->_redirect('/potwierdzenie.html');
    }

    public function potwierdzenieAction()
    {
        $this->view->sitepath = array(array(url => '/konkurs.html', 'name' => 'Konkurs' ), array(url => '/potwierdzenie.html', 'name' => 'Potwierdzenie' ));
    }

    public function kontaktAction()
    {
        $meta = array();
        $meta['title'] = "Kontakt z serwisem certyfikatbezpieczenstwa.pl";
        $meta['desc'] = "Skontaktuj się z nami. Zadaj pytania dotyczące certyfikatubezpieczeństwa i nie tylko";
        $this->view->meta = $meta;
    }

    public function dlakonsumentaAction()
    {
        $teksty_rotator = array('W tym roku odnotowano już bowiem 4,5 tysiąca prób wyłudzenia kredytów z
wykorzystaniem skradzionej tożsamości. Tylko dzięki zabezpieczeniom
stosowanym przez BIK udało się udaremnić wyłudzenie w ten sposób 280
milionów złotych – informacja podana przez dra Mariusza Cholewę, prezesa
Zarządu Biura Informacji Kredytowej (BIK)', 'Bezpieczne hasło to takie, które nie jest „hasłem słownikowym”, posiada
powyżej 8 znaków zawierające cyfry, litery (małe i wielkie) oraz znaki
specjalne. Aby  uchronić się przed konsekwencjami kradzieży haseł należy
używać różnych haseł do logowania na różnych stronach internetowych.', 'Osoba użytkująca komputer przenośny zawierający dane osobowe musi zachować szczególną ostrożność podczas jego transportu oraz stosować środki ochrony kryptograficznej wobec przetwarzanych danych osobowych');
       $this->view->rotator = $teksty_rotator[rand(0,count($teksty_rotator)-1)];

       $meta = array();
        $meta['desc'] = "Sprawdź autorytet firmy/instytucji. Zobacz czy ktoś dba o bezpieczeństwo";
        $meta['title'] = "Certyfikat Bezpieczeństwa dla konsumenta";
        $this->view->meta = $meta;
    }

    public function dlafirmAction()
    {
        $meta = array();
        $meta['desc'] = "Zdobądź certyfikat bezpieczeństwa dla Twojej firmy. Podnieś prestiż i bezpieczeństwo swojej strony oraz autorytet firmy.";
        $meta['title'] = "Certyfikat Bezpieczeństwa dla firm";
        $this->view->meta = $meta;
    }

    public function faqAction()
    {
        $meta = array();
        $meta['desc'] = "Przeczytaj najczęściej zadawane pytania dotyczące certyfikatu bezpieczeństwa";
        $meta['title'] = "Najczęściej zadawane pytania o certyfikacie bezpieczeństwa";
        $this->view->meta = $meta;
    }

    public function dlamediowAction()
    {
        $meta = array();
        $meta['desc'] = "Zobacz informacje dla mediów na temat certyfikatu bezpieczeństwa";
        $meta['title'] = "Certyfikat bezpieczeństwa informacje dla mediów";
        $this->view->meta = $meta;
    }

    public function partnerzyAction()
    {
        $meta = array();
        $meta['title'] = "Partnerzy wspierający certyfikat bezpieczeństwa";
        $meta['desc'] = "Zobacz partnerów wspierających certyfikat bezpieczeństwa";
        $this->view->meta = $meta;
    }

    public function ocertyfikacieAction()
    {
        $meta = array();
        $meta['title'] = "O certyfikacie bezpieczeństwa";
        $meta['desc'] = "Zobacz informacje dotyczące uzyskania certyfikatu bezpieczeństwa, a także korzyści posiadania certyfikatu.";
        $this->view->meta = $meta;
    }

    public function dlaosobzpAction()
    {
        $meta = array();
        $meta['title'] = "Certyfikat bezpieczeństwa dla osób zaufania publicznego";
        $meta['desc'] = "Jesteś osobą zaufania publicznego? Certyfikat bezpieczeństwa to idealne rozwiązanie dla Ciebie.";
        $this->view->meta = $meta;
    }

    public function dlainstytucjiAction()
    {
        $meta = array();
        $meta['title'] = "Certyfikat bezpieczeństwa dla instytucji";
        $meta['desc'] = "Dla instytucji przygotowaliśmy Certyfikat bezpieczeństwa, który znacząco podniesie prestiż Twojej instytucji.";
        $this->view->meta = $meta;
    }

    public function politykaprywatnosciAction()
    {
        $meta = array();
        $meta['title'] = "Polityka prywatności";
        $meta['desc'] = "Dbamy o prywatność naszych użytkowników. Przeglądnij politykę prywatności, aby dowiedzieć się więcej.";
        $this->view->meta = $meta;

    }

    public function regulaminAction()
    {
        $meta = array();
        $meta['title'] = "Regluamin serwisu ceryfikatbezpieczenstwa.pl";
        $meta['desc'] = "Przeglądnij regulamin serwisu. Regulamin pozwala uniknąć nieporozumień oraz wątpliwości związanych z naszymi działaniami.";
        $this->view->meta = $meta;
    }
    */
}