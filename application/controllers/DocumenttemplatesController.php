<?php

class DocumenttemplatesController extends Muzyka_Admin
{
    /** @var Application_Model_Documenttemplates */
    private $documenttemplates;

    public function init()
    {
        parent::init();
        $this->view->section = 'Szablony dokumentów';
        $this->numberingschemes = Application_Service_Utilities::getModel('Numberingschemes');
        $this->documenttemplates = Application_Service_Utilities::getModel('Documenttemplates');
        $this->documents = Application_Service_Utilities::getModel('Documents');

        Zend_Layout::getMvcInstance()->assign('section', 'Szablony dokumentów');
    }

    public static function getPermissionsSettings()
    {
        $templateCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/documenttemplates/create'),
                2 => array('perm/documenttemplates/update'),
            ),
        );
        $templateCloneCheck = array(
            'function' => 'issetAccess',
            'params' => array('copy'),
            'permissions' => array(
                1 => array('perm/documenttemplates'),
                2 => array('perm/documenttemplates/update', 'perm/documenttemplates/create'),
            ),
        );

        $settings = array(
            'modules' => array(
                'documenttemplates' => array(
                    'label' => 'Dokumenty/Szablony dokumentów',
                    'permissions' => array(
                        array(
                            'id' => 'create',
                            'label' => 'Tworzenie wpisów',
                        ),
                        array(
                            'id' => 'update',
                            'label' => 'Edycja wpisów',
                        ),
                        array(
                            'id' => 'remove',
                            'label' => 'Usuwanie wpisów',
                        ),
                    ),
                ),
            ),
            'nodes' => array(
                'documenttemplates' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    // public
                    'icons' => array(
                        'permissions' => array(),
                    ),
                    'addmini' => array(
                        'permissions' => array(),
                    ),
                    'ajax-add-form-param' => [
                        'permissions' => array(),
                    ],

                    // base crud
                    'index' => array(
                        'permissions' => array('perm/documenttemplates'),
                    ),
                    'update' => array(
                        'getPermissions' => array(
                            $templateCheck,
                            $templateCloneCheck,
                        ),
                    ),
                    'save' => array(
                        'getPermissions' => array($templateCheck),
                    ),
                    'del' => array(
                        'permissions' => array('perm/documenttemplates/remove'),
                    ),
                    'delchecked' => array(
                        'permissions' => array('perm/documenttemplates/remove'),
                    ),

                ),
            )
        );

        return $settings;
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista szablonów dokumentów');
        $paginator = $this->documenttemplates->fetchAll(null, 'name')->toArray();
        foreach ($paginator AS $k => $v) {
            $t_numberingscheme = $this->numberingschemes->fetchRow(array('id = ?' => $paginator[$k]['numberingscheme_id']));

            $paginator[$k]['scheme'] = $t_numberingscheme->scheme;
            $paginator[$k]['schemetype'] = $t_numberingscheme->type;

            $paginator[$k]['used'] = 0;
            $i_docs = 0;
            $t_document = $this->documents->fetchRow(array(
                'documenttemplate_id = ?' => $paginator[$k]['id'],
                'active = ?' => 1,
            ));
            if ($t_document->id > 0) {
                $paginator[$k]['used'] = 1;
                $i_docs++;
            }
            $paginator[$k]['i_docs'] = $i_docs;
        }

        $this->view->paginator = $paginator;
    }

    public function addminiAction()
    {
        $this->view->ajaxModal = 1;
        $this->view->t_data = $this->documenttemplates->fetchAll(null, 'name')->toArray();
    }

    public function updateAction()
    {
        Zend_Layout::getMvcInstance()->setLayout('home');
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $copy = $req->getParam('copy', 0);

        $jsonoptions = '';

        $documenttemplatesosoby = Application_Service_Utilities::getModel('Documenttemplatesosoby');
        $osoby = Application_Service_Utilities::getModel('Osoby');
        if ($id) {
            $row = $this->documenttemplates->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();

            $i_docs = 0;
            $t_document = $this->documents->fetchRow(array(
                'documenttemplate_id = ?' => $id,
                'active = ?' => 1,
            ));
            if ($t_document->id > 0) {
                $i_docs++;
            }

            $this->view->i_docs = $i_docs;

            $t_documenttemplatesosoby = $documenttemplatesosoby->fetchAll(array('documenttemplate_id = ?' => $id));
            if (count($t_documenttemplatesosoby) > 0) {
                $t_options = new stdClass();
                $t_options->t_persons = array();
                $t_options->t_personsdata = new stdClass();

                foreach ($t_documenttemplatesosoby AS $opts) {
                    $t_osoba = $osoby->fetchRow(array('id = ?' => $opts->osoba_id));
                    $t_options->t_persons[] = $t_osoba->imie . ' ' . $t_osoba->nazwisko . ' (' . $t_osoba->login_do_systemu . ') - ' . $t_osoba->stanowisko;
                    $ob_osoba = $t_osoba->imie . ' ' . $t_osoba->nazwisko . ' (' . $t_osoba->login_do_systemu . ') - ' . $t_osoba->stanowisko;
                    $t_options->t_personsdata->$ob_osoba = 'id' . $t_osoba->id;
                }

                $jsonoptions = json_encode($t_options);
            }
            $this->setDetailedSection('Edytuj szablon dokumentu');

            $registryModel = Application_Service_Utilities::getModel('Registry');
            $registry = $registryModel->getFull([
                'type_id = ?' => Application_Service_RegistryConst::REGISTRY_TYPE_DOCUMENTTEMPLATE_FORM,
                'object_id = ?' => $id,
            ]);
            if ($registry) {
                $this->view->registry = $registry;
            }
        } else if ($copy) {
            $row = $this->documenttemplates->getOne($copy);
            if ($row instanceof Zend_Db_Table_Row) {
                $row = $row->toArray();
                unset($row['id']);
                $row['name'] = $row['name'] . ' KOPIA';
                $this->view->data = $row;

                $t_documenttemplatesosoby = $documenttemplatesosoby->fetchAll(array('documenttemplate_id = ?' => $copy));
                if (count($t_documenttemplatesosoby) > 0) {
                    $t_options = new stdClass();
                    $t_options->t_persons = array();
                    $t_options->t_personsdata = new stdClass();

                    foreach ($t_documenttemplatesosoby AS $opts) {
                        $t_osoba = $osoby->fetchRow(array('id = ?' => $opts->osoba_id));
                        $t_options->t_persons[] = $t_osoba->imie . ' ' . $t_osoba->nazwisko . ' (' . $t_osoba->login_do_systemu . ') - ' . $t_osoba->stanowisko;
                        $ob_osoba = $t_osoba->imie . ' ' . $t_osoba->nazwisko . ' (' . $t_osoba->login_do_systemu . ') - ' . $t_osoba->stanowisko;
                        $t_options->t_personsdata->$ob_osoba = 'id' . $t_osoba->id;
                    }

                    $jsonoptions = json_encode($t_options);
                }
            }
            $this->setDetailedSection('Dodaj nowy szablon dokumentu');
        } else {
            $this->setDetailedSection('Dodaj nowy szablon dokumentu');
        }

        $availableForms = [];
        $formsSelect = $this->db->select()
            ->from(['r1' => 'registry_entities'], [])
            ->joinInner(['r2' => 'registry_entities'], 'r1.registry_id = r2.registry_id AND r1.id <> r2.id', [])
            ->joinInner(['r' => 'registry'], 'r.id = r1.registry_id', ['*'])
            ->where('r1.entity_id = 6')
            ->where('r2.entity_id = 7');
        $formsList = Application_Service_Utilities::getModel('Registry')->getListFromSelect($formsSelect);
        foreach ($formsList as $registryForm) {
            $availableForms[] = [
                'id' => $registryForm->id,
                'name' => $registryForm->title,
            ];
        }

        $this->view->jsonoptions = $jsonoptions;
        $this->view->availableForms = $availableForms;

        $this->view->t_numberingschemes = $this->numberingschemes->fetchAll(null, 'name');
    }

    public function saveAction()
    {
        try {
            $this->getRepository()->getOperation()->operationBegin(Application_Service_Repository::OPERATION_IMPORTANT);
            $req = $this->getRequest();
            $id = $this->documenttemplates->save($req->getParams());
            $this->getRepository()->getOperation()->operationComplete('szablony_dokumentow.update', $id);
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect('/documenttemplates');
    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->documenttemplates->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/documenttemplates');
    }

    public function delcheckedAction()
    {
        foreach ($_POST AS $poster => $val) {
            $poster = str_replace('id', '', $poster) * 1;
            if ($poster > 0) {
                try {
                    $this->documenttemplates->remove($poster);
                } catch (Exception $e) {
                }
            }
        }

        $this->_redirect('/documenttemplates');
    }

    public function ajaxAddFormParamAction()
    {
        $documenttemplateId = $this->getParam('id');
        $registryModel = Application_Service_Utilities::getModel('Registry');
        $registryEntitiesModel = Application_Service_Utilities::getModel('RegistryEntities');
        $entitiesModel = Application_Service_Utilities::getModel('Entities');

        $documenttemplate = $this->documenttemplates->requestObject($documenttemplateId);

        $registry = $registryModel->getOne([
            'type_id = ?' => Application_Service_RegistryConst::REGISTRY_TYPE_DOCUMENTTEMPLATE_FORM,
            'object_id = ?' => $documenttemplateId,
        ]);

        if (!$registry) {
            $registry = $registryModel->createRow([
                'type_id' => Application_Service_RegistryConst::REGISTRY_TYPE_DOCUMENTTEMPLATE_FORM,
                'object_id' => $documenttemplateId,
                'title' => 'Formularz szablonu ' . $documenttemplate->name,
                'is_visible' => 0,
            ]);
            $registry->save();

            $registryEntitiesModel->save([
                'registry_id' => $registry->id,
                'entity_id' => $entitiesModel->getOne(['system_name' => 'employees'], true)->id,
                'system_name' => 'employee',
                'title' => 'Pracownik',
                'order' => 1,
            ]);

            $registryEntitiesModel->save([
                'registry_id' => $registry->id,
                'entity_id' => $entitiesModel->getOne(['system_name' => 'documents'], true)->id,
                'system_name' => 'document',
                'title' => 'Dokument',
                'order' => 2,
            ]);
        }

        $this->forward('ajax-add-param', 'registry', null, [
            'id' => $registry->id,
        ]);
    }

    public function iconsAction()
    {
        $this->view->ajaxModal = 1;

        $this->view->icons = array('fa-glass', 'fa-music', 'fa-search', 'fa-envelope-o', 'fa-heart', 'fa-star', 'fa-star-o', 'fa-user', 'fa-film', 'fa-th-large', 'fa-th', 'fa-th-list', 'fa-check', 'fa-remove', 'fa-search-plus', 'fa-search-minus', 'fa-power-off', 'fa-signal', 'fa-gear', 'fa-trash-o', 'fa-home', 'fa-file-o', 'fa-clock-o', 'fa-road', 'fa-download', 'fa-arrow-circle-o-down', 'fa-arrow-circle-o-up', 'fa-inbox', 'fa-play-circle-o', 'fa-rotate-right', 'fa-refresh', 'fa-list-alt', 'fa-lock', 'fa-flag', 'fa-headphones', 'fa-volume-off', 'fa-volume-down', 'fa-volume-up', 'fa-qrcode', 'fa-barcode', 'fa-tag', 'fa-tags', 'fa-book', 'fa-bookmark', 'fa-print', 'fa-camera', 'fa-font', 'fa-bold', 'fa-italic', 'fa-text-height', 'fa-text-width', 'fa-align-left', 'fa-align-center', 'fa-align-right', 'fa-align-justify', 'fa-list', 'fa-dedent', 'fa-indent', 'fa-video-camera', 'fa-photo', 'fa-pencil', 'fa-map-marker', 'fa-adjust', 'fa-tint', 'fa-edit', 'fa-share-square-o', 'fa-check-square-o', 'fa-arrows', 'fa-step-backward', 'fa-fast-backward', 'fa-backward', 'fa-play', 'fa-pause', 'fa-stop', 'fa-forward', 'fa-fast-forward', 'fa-step-forward', 'fa-eject', 'fa-chevron-left', 'fa-chevron-right', 'fa-plus-circle', 'fa-minus-circle', 'fa-times-circle', 'fa-check-circle', 'fa-question-circle', 'fa-info-circle', 'fa-crosshairs', 'fa-times-circle-o', 'fa-check-circle-o', 'fa-ban', 'fa-arrow-left', 'fa-arrow-right', 'fa-arrow-up', 'fa-arrow-down', 'fa-mail-forward', 'fa-expand', 'fa-compress', 'fa-plus', 'fa-minus', 'fa-asterisk', 'fa-exclamation-circle', 'fa-gift', 'fa-leaf', 'fa-fire', 'fa-eye', 'fa-eye-slash', 'fa-warning', 'fa-plane', 'fa-calendar', 'fa-random', 'fa-comment', 'fa-magnet', 'fa-chevron-up', 'fa-chevron-down', 'fa-retweet', 'fa-shopping-cart', 'fa-folder', 'fa-folder-open', 'fa-arrows-v', 'fa-arrows-h', 'fa-bar-chart-o', 'fa-twitter-square', 'fa-facebook-square', 'fa-camera-retro', 'fa-key', 'fa-gears', 'fa-comments', 'fa-thumbs-o-up', 'fa-thumbs-o-down', 'fa-star-half', 'fa-heart-o', 'fa-sign-out', 'fa-linkedin-square', 'fa-thumb-tack', 'fa-external-link', 'fa-sign-in', 'fa-trophy', 'fa-github-square', 'fa-upload', 'fa-lemon-o', 'fa-phone', 'fa-square-o', 'fa-bookmark-o', 'fa-phone-square', 'fa-twitter', 'fa-facebook-f', 'fa-github', 'fa-unlock', 'fa-credit-card', 'fa-rss', 'fa-hdd-o', 'fa-bullhorn', 'fa-bell', 'fa-certificate', 'fa-hand-o-right', 'fa-hand-o-left', 'fa-hand-o-up', 'fa-hand-o-down', 'fa-arrow-circle-left', 'fa-arrow-circle-right', 'fa-arrow-circle-up', 'fa-arrow-circle-down', 'fa-globe', 'fa-wrench', 'fa-tasks', 'fa-filter', 'fa-briefcase', 'fa-arrows-alt', 'fa-group', 'fa-chain', 'fa-cloud', 'fa-flask', 'fa-cut', 'fa-copy', 'fa-paperclip', 'fa-save', 'fa-square', 'fa-navicon', 'fa-list-ul', 'fa-list-ol', 'fa-strikethrough', 'fa-underline', 'fa-table', 'fa-magic', 'fa-truck', 'fa-pinterest', 'fa-pinterest-square', 'fa-google-plus-square', 'fa-google-plus', 'fa-money', 'fa-caret-down', 'fa-caret-up', 'fa-caret-left', 'fa-caret-right', 'fa-columns', 'fa-unsorted', 'fa-sort-down', 'fa-sort-up', 'fa-envelope', 'fa-linkedin', 'fa-rotate-left', 'fa-legal', 'fa-dashboard', 'fa-comment-o', 'fa-comments-o', 'fa-flash', 'fa-sitemap', 'fa-umbrella', 'fa-paste', 'fa-lightbulb-o', 'fa-exchange', 'fa-cloud-download', 'fa-cloud-upload', 'fa-user-md', 'fa-stethoscope', 'fa-suitcase', 'fa-bell-o', 'fa-coffee', 'fa-cutlery', 'fa-file-text-o', 'fa-building-o', 'fa-hospital-o', 'fa-ambulance', 'fa-medkit', 'fa-fighter-jet', 'fa-beer', 'fa-h-square', 'fa-plus-square', 'fa-angle-double-left', 'fa-angle-double-right', 'fa-angle-double-up', 'fa-angle-double-down', 'fa-angle-left', 'fa-angle-right', 'fa-angle-up', 'fa-angle-down', 'fa-desktop', 'fa-laptop', 'fa-tablet', 'fa-mobile-phone', 'fa-circle-o', 'fa-quote-left', 'fa-quote-right', 'fa-spinner', 'fa-circle', 'fa-mail-reply', 'fa-github-alt', 'fa-folder-o', 'fa-folder-open-o', 'fa-smile-o', 'fa-frown-o', 'fa-meh-o', 'fa-gamepad', 'fa-keyboard-o', 'fa-flag-o', 'fa-flag-checkered', 'fa-terminal', 'fa-code', 'fa-mail-reply-all', 'fa-star-half-empty', 'fa-location-arrow', 'fa-crop', 'fa-code-fork', 'fa-unlink', 'fa-question', 'fa-info', 'fa-exclamation', 'fa-superscript', 'fa-subscript', 'fa-eraser', 'fa-puzzle-piece', 'fa-microphone', 'fa-microphone-slash', 'fa-shield', 'fa-calendar-o', 'fa-fire-extinguisher', 'fa-rocket', 'fa-maxcdn', 'fa-chevron-circle-left', 'fa-chevron-circle-right', 'fa-chevron-circle-up', 'fa-chevron-circle-down', 'fa-html5', 'fa-css3', 'fa-anchor', 'fa-unlock-alt', 'fa-bullseye', 'fa-ellipsis-h', 'fa-ellipsis-v', 'fa-rss-square', 'fa-play-circle', 'fa-ticket', 'fa-minus-square', 'fa-minus-square-o', 'fa-level-up', 'fa-level-down', 'fa-check-square', 'fa-pencil-square', 'fa-external-link-square', 'fa-share-square', 'fa-compass', 'fa-toggle-down', 'fa-toggle-up', 'fa-toggle-right', 'fa-euro', 'fa-gbp', 'fa-dollar', 'fa-rupee', 'fa-cny', 'fa-ruble', 'fa-won', 'fa-bitcoin', 'fa-file', 'fa-file-text', 'fa-sort-alpha-asc', 'fa-sort-alpha-desc', 'fa-sort-amount-asc', 'fa-sort-amount-desc', 'fa-sort-numeric-asc', 'fa-sort-numeric-desc', 'fa-thumbs-up', 'fa-thumbs-down', 'fa-youtube-square', 'fa-youtube', 'fa-xing', 'fa-xing-square', 'fa-youtube-play', 'fa-dropbox', 'fa-stack-overflow', 'fa-instagram', 'fa-flickr', 'fa-adn', 'fa-bitbucket', 'fa-bitbucket-square', 'fa-tumblr', 'fa-tumblr-square', 'fa-long-arrow-down', 'fa-long-arrow-up', 'fa-long-arrow-left', 'fa-long-arrow-right', 'fa-apple', 'fa-windows', 'fa-android', 'fa-linux', 'fa-dribbble', 'fa-skype', 'fa-foursquare', 'fa-trello', 'fa-female', 'fa-male', 'fa-gittip', 'fa-sun-o', 'fa-moon-o', 'fa-archive', 'fa-bug', 'fa-vk', 'fa-weibo', 'fa-renren', 'fa-pagelines', 'fa-stack-exchange', 'fa-arrow-circle-o-right', 'fa-arrow-circle-o-left', 'fa-toggle-left', 'fa-dot-circle-o', 'fa-wheelchair', 'fa-vimeo-square', 'fa-turkish-lira', 'fa-plus-square-o', 'fa-space-shuttle', 'fa-slack', 'fa-envelope-square', 'fa-wordpress', 'fa-openid', 'fa-institution', 'fa-mortar-board', 'fa-yahoo', 'fa-google', 'fa-reddit', 'fa-reddit-square', 'fa-stumbleupon-circle', 'fa-stumbleupon', 'fa-delicious', 'fa-digg', 'fa-pied-piper', 'fa-pied-piper-alt', 'fa-drupal', 'fa-joomla', 'fa-language', 'fa-fax', 'fa-building', 'fa-child', 'fa-paw', 'fa-spoon', 'fa-cube', 'fa-cubes', 'fa-behance', 'fa-behance-square', 'fa-steam', 'fa-steam-square', 'fa-recycle', 'fa-automobile', 'fa-cab', 'fa-tree', 'fa-spotify', 'fa-deviantart', 'fa-soundcloud', 'fa-database', 'fa-file-pdf-o', 'fa-file-word-o', 'fa-file-excel-o', 'fa-file-powerpoint-o', 'fa-file-photo-o', 'fa-file-zip-o', 'fa-file-sound-o', 'fa-file-movie-o', 'fa-file-code-o', 'fa-vine', 'fa-codepen', 'fa-jsfiddle', 'fa-life-bouy', 'fa-circle-o-notch', 'fa-ra', 'fa-ge', 'fa-git-square', 'fa-git', 'fa-hacker-news', 'fa-tencent-weibo', 'fa-qq', 'fa-wechat', 'fa-send', 'fa-send-o', 'fa-history', 'fa-genderless', 'fa-header', 'fa-paragraph', 'fa-sliders', 'fa-share-alt', 'fa-share-alt-square', 'fa-bomb', 'fa-soccer-ball-o', 'fa-tty', 'fa-binoculars', 'fa-plug', 'fa-slideshare', 'fa-twitch', 'fa-yelp', 'fa-newspaper-o', 'fa-wifi', 'fa-calculator', 'fa-paypal', 'fa-google-wallet', 'fa-cc-visa', 'fa-cc-mastercard', 'fa-cc-discover', 'fa-cc-amex', 'fa-cc-paypal', 'fa-cc-stripe', 'fa-bell-slash', 'fa-bell-slash-o', 'fa-trash', 'fa-copyright', 'fa-at', 'fa-eyedropper', 'fa-paint-brush', 'fa-birthday-cake', 'fa-area-chart', 'fa-pie-chart', 'fa-line-chart', 'fa-lastfm', 'fa-lastfm-square', 'fa-toggle-off', 'fa-toggle-on', 'fa-bicycle', 'fa-bus', 'fa-ioxhost', 'fa-angellist', 'fa-cc', 'fa-shekel', 'fa-meanpath', 'fa-buysellads', 'fa-connectdevelop', 'fa-dashcube', 'fa-forumbee', 'fa-leanpub', 'fa-sellsy', 'fa-shirtsinbulk', 'fa-simplybuilt', 'fa-skyatlas', 'fa-cart-plus', 'fa-cart-arrow-down', 'fa-diamond', 'fa-ship', 'fa-user-secret', 'fa-motorcycle', 'fa-street-view', 'fa-heartbeat', 'fa-venus', 'fa-mars', 'fa-mercury', 'fa-transgender', 'fa-transgender-alt', 'fa-venus-double', 'fa-mars-double', 'fa-venus-mars', 'fa-mars-stroke', 'fa-mars-stroke-v', 'fa-mars-stroke-h', 'fa-neuter', 'fa-facebook-official', 'fa-pinterest-p', 'fa-whatsapp', 'fa-server', 'fa-user-plus', 'fa-user-times', 'fa-hotel', 'fa-viacoin', 'fa-train', 'fa-subway', 'fa-medium');
    }

}