<?php

class DocumentsController extends Muzyka_Admin
{
    /** @var Application_Model_Documenttemplates */
    protected $documenttemplates;

    /** @var Application_Model_DocumentsPending */
    protected $documentsPendingModel;

    /** @var Application_Model_Documenttemplatesosoby */
    protected $documenttemplatesosoby;

    /** @var Application_Model_Numberingschemes */
    protected $numberingschemes;

    /** @var Application_Service_Messages */
    private $messagesService;

    /** @var Application_Service_Documents */
    private $documentsService;

    /** @var Application_Model_Osoby */
    protected $osoby;

    /** @var Application_Model_Documents */
    private $documentsModel;

    protected $pending_document_status_display;

    protected $baseUrl = '/documents';

    public function init()
    {
        parent::init();
        $this->view->section = 'Typy osób';
        $this->view->baseUrl = $this->baseUrl;
        $this->osoby = Application_Service_Utilities::getModel('Osoby');
        $this->documentsModel = Application_Service_Utilities::getModel('Documents');
        $this->documentsPendingModel = Application_Service_Utilities::getModel('DocumentsPending');
        $this->documenttemplates = Application_Service_Utilities::getModel('Documenttemplates');
        $this->documenttemplatesosoby = Application_Service_Utilities::getModel('Documenttemplatesosoby');
        $this->numberingschemes = Application_Service_Utilities::getModel('Numberingschemes');
        $this->klucze = Application_Service_Utilities::getModel('Klucze');
        $this->upowaznienia = Application_Service_Utilities::getModel('Upowaznienia');
        $this->settings = Application_Service_Utilities::getModel('Settings');
        $this->budynki = Application_Service_Utilities::getModel('Budynki');
        $this->pomieszczenia = Application_Service_Utilities::getModel('Pomieszczenia');
        $this->zbiory = Application_Service_Utilities::getModel('Zbiory');
        $this->messagesService = Application_Service_Messages::getInstance();
        $this->documentsService = Application_Service_Documents::getInstance();

        Zend_Layout::getMvcInstance()->assign('section', 'Dokumentacja');

        $this->baseUrl = '/documents';

        $this->pending_document_status_display = array(
            array(
                'label' => 'Usunięty',
                'type' => 'button',
                'class' => 'label label-info',
            ),
            array(
                'label' => 'Oczekuje',
                'type' => 'button',
                'class' => 'label label-warning',
            ),
            array(
                'label' => 'Zaakceptowany',
                'type' => 'button',
                'class' => 'label label-success',
            ),
            array(
                'label' => 'Utworzony',
                'type' => 'button',
                'class' => 'label label-info',
            ),
        );
    }

    public function preDispatch()
    {
        $template_type_display = array(
            array(
                'label' => 'Inne',
                'type' => 'text',
            ),
            array(
                'label' => 'Oświadczenie',
                'type' => 'text',
            ),
            array(
                'label' => 'Upoważnienie do pomieszczeń',
                'type' => 'text',
            ),
            array(
                'label' => 'Upoważnienie do zbiorów',
                'type' => 'text',
            ),
        );
        $this->view->template_type_display = $template_type_display;

        $this->view->pending_document_status_display = $this->pending_document_status_display;

        parent::preDispatch();
    }

    public static function getPermissionsSettings() {
        $ownerCheck = array(
            'function' => 'getDocumentsAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/documents'),
                2 => array('perm/documents/all'),
            ),
        );

        $recallCheck = array(
            'function' => 'getDocumentsRecallAccess',
            'params' => array('id'),
            'permissions' => array(
                0 => false,
                1 => array(),
            ),
        );

        $settings = array(
            'modules' => array(
                'documents' => array(
                    'label' => 'Dokumenty/Dokumentacja osobowa',
                    'permissions' => array(
                        array(
                            'id' => 'all',
                            'label' => 'Dostęp do wszystkich dokumentów',
                        ),
                        array(
                            'id' => 'update',
                            'label' => 'Aktualizacja dokumentacji',
                        ),
                        array(
                            'id' => 'remove-all',
                            'label' => 'Usuwanie całej dokumentacji',
                        ),
                    ),
                ),
            ),
            'nodes' => array(
                'documents' => array(
                    '_default' => array(
                        'permissions' => array('perm/documents'),
                    ),

                    // public
                    'user-documents' => array(
                        'permissions' => array(),
                    ),
                    'mini-add' => array(
                        'permissions' => array(),
                    ),

                    'index' => array(
                        'permissions' => array('perm/documents'),
                    ),
                    'all' => array(
                        'permissions' => array('perm/documents'),
                    ),

                    'print' => array(
                        'getPermissions' => array($ownerCheck),
                    ),
                    'getpdf' => array(
                        'getPermissions' => array($ownerCheck),
                    ),
                    'user-archive' => array(
                        'getPermissions' => array($ownerCheck),
                    ),
                    'get-choice' => array(
                        'getPermissions' => array($ownerCheck),
                    ),

                    'update-all' => array(
                        'permissions' => array('perm/documents/update'),
                    ),
                    'update-all-go' => array(
                        'permissions' => array('perm/documents/update'),
                    ),

                    'delete-all' => array(
                        'permissions' => array('perm/documents/remove-all'),
                    ),
                    'delete-all-go' => array(
                        'permissions' => array('perm/documents/remove-all'),
                    ),

                    'recall-document' => array(
                        'permissions' => array('perm/documents/update'),
                        'getPermissions' => array($recallCheck),
                    ),
                    'recall-document-go' => array(
                        'permissions' => array('perm/documents/update'),
                        'getPermissions' => array($recallCheck),
                    ),

                    'dialog-choose' => array(
                        'permissions' => array('user/anyone'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function bulkOperationsAction()
    {
        $rowAction = $_POST['rowsAction'];

        $rowSelect = $this->_getParam('id');
        $rowSelect = array_keys(Application_Service_Utilities::removeEmptyValues($rowSelect));

        switch ($rowAction) {
            case "print":
                $_GET['ids'] = implode(',', $rowSelect);
                return $this->printAction();
                break;
            case "download":
                $_GET['ids'] = implode(',', $rowSelect);
                return $this->getpdfAction();
                break;
            case "users-print":
                $_GET['ids'] = implode(',', $this->documentsModel->getActiveByUsers($rowSelect));
                return $this->printAction();
                break;
            case "users-download":
                $_GET['ids'] = implode(',', $this->documentsModel->getActiveByUsers($rowSelect));
                return $this->getpdfAction();
                break;
            case "users-actualize":
                $this->forcePermission('perm/documents/update');
                $dateupdate = $_GET['dateupdate'];
                $documenttemplateIds = $_POST['documenttemplate_id'];

                $documentsService = new Application_Service_Documents();
                $documentsService->createDocuments($dateupdate, [
                    'osobyIds' => $rowSelect,
                    'documenttemplateIds' => $documenttemplateIds,
                ]);
                break;
        }
        
        $this->redirectBack();
    }

    public function getTopNavigation()
    {
        $this->setSectionNavigation(array(
            array(
                'label' => 'Dokumenty',
                'path' => 'javascript:;',
                'icon' => 'fa icon-print-2',
                'rel' => 'reports',
                'children' => array(
                    array(
                        'label' => 'Wydrukuj wszystkie',
                        'path' => '/documents/print/',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin'
                    ),
                    array(
                        'label' => 'Pobierz wszystkie w PDF',
                        'path' => '/documents/getpdf/',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin'
                    ),
                )
            ),
            array(
                'label' => 'Operacje',
                'path' => 'javascript:;',
                'icon' => 'fa icon-tools',
                'rel' => 'operations',
                'children' => array(
                    array(
                        'label' => 'Aktualizuj dokumentację',
                        'path' => '/documents/update-all',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin',
                        'nohref' => true,
                        'onclick' => "showDial('/documents/update-all/','',''); return false;",
                    ),
                    array(
                        'label' => 'Usuń całą dokumentację',
                        'path' => '/documents/delete-all',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin',
                        'nohref' => true,
                        'onclick' => "showDial('/documents/delete-all/','',''); return false;",
                    ),
                )
            ),
        ));
    }

    public function indexAction()
    {
        $t_templates = array();
        $t_documenttemplates = $this->documenttemplates->fetchAll()->toArray();
        foreach ($t_documenttemplates AS $documenttemplate) {
            $t_templates[$documenttemplate['id']] = $documenttemplate;
        }
        $this->view->t_templates = $t_templates;

        $listParams = array();
        $listParams['d.active <> ?'] = 0;

        if (!$this->isGranted('perm/documents/all')) {
            $listParams['d.osoba_id = ?'] = Application_Service_Authorization::getInstance()->getUserId();
        }

        $documentsList = $this->documentsModel->getList($listParams);
        $paginator = array();
        $users = Application_Service_Utilities::getModel('Osoby')->getList(['o.id IN (?)' => Application_Service_Utilities::getUniqueValues($documentsList, 'osoba_id')]);
        Application_Service_Utilities::indexBy($users, 'id');

        foreach ($documentsList as $document) {
            if (!isset($paginator[$document['osoba_id']])) {
                $paginator[$document['osoba_id']] = array(
                    'id' => $document['osoba_id'],
                    'imie' => $document['osoba_imie'],
                    'nazwisko' => $document['osoba_nazwisko'],
                    'login' => $document['osoba_login'],
                    'stanowisko' => $document['osoba_stanowisko'],
                    't_documents' => array(),
                    'has_archive' => 0,
                );
            }
            $userRow = &$paginator[$document['osoba_id']];

            if (empty($userRow['nazwisko']) && !empty($document['osoba_nazwisko'])) {
                $userRow = array_merge($userRow, [
                    'imie' => $document['osoba_imie'],
                    'nazwisko' => $document['osoba_nazwisko'],
                    'login' => $document['osoba_login'],
                    'stanowisko' => $document['osoba_stanowisko'],
                ]);
            }

            if ($document['has_archive'] && !$userRow['has_archive']) {
                $paginator[$document['osoba_id']]['has_archive'] = 1;
            }

            $userRow['t_documents'][] = $document;
        }


        foreach ($paginator as &$userRow) {
            if (empty($userRow['nazwisko'])) {
                $user = $users[$userRow['id']];
                $userRow = array_merge($userRow, [
                    'imie' => $user['imie'],
                    'nazwisko' => $user['nazwisko'],
                    'login' => $user['login_do_systemu'],
                    'stanowisko' => $user['stanowisko'],
                ]);
            }
        }

        /*
        $paginator = $this->osoby->fetchAll('usunieta <> \'1\'', array('imie', 'nazwisko', 'stanowisko'))->toArray();
        foreach ($paginator AS $k => $v) {
            $paginator[$k]['t_documents'] = $this->documents->fetchAll('osoba_id = \'' . $paginator[$k]['id'] . '\' AND active != 0')->toArray();
            $paginator[$k]['t_documentshistory'] = $this->documents->fetchAll('osoba_id = \'' . $paginator[$k]['id'] . '\' AND active = \'0\'')->toArray();
        }*/
        $this->view->paginator = $paginator;
    }

    public function pendingAction()
    {
        $this->setDetailedSection('Dokumenty oczekujące');

        $rowAction = $this->_getParam('rowsAction');
        switch ($rowAction) {
            case "print":
                if (!empty($_POST['rowSelect'])) {
                    $_GET['ids'] = implode(',', $this->documentsPendingModel->getActiveByUsers($_POST['rowSelect']));
                }
                $this->printAction();
                return null;
                break;
            case "download":
                if (!empty($_POST['rowSelect'])) {
                    $_GET['ids'] = implode(',', $this->documentsPendingModel->getActiveByUsers($_POST['rowSelect']));
                }
                $this->getpdfAction();
                return null;
                break;
            case "actualize":
                $this->forcePermission('perm/documents/update');
                $dateupdate = $_POST['dateupdate'];
                $mode = !empty($_POST['mode']) ? $_POST['mode'] : 'actualize';
                $ids = !empty($_POST['rowSelect']) ? explode(',', $_POST['rowSelect']) : null;

                $pendingDocumentsToPrint = $this->documentsPendingModel->getList([
                    'dp.id IN (?)' => $ids,
                    'dp.status IN (?)' => [Application_Model_DocumentsPending::STATUS_ACCEPTED, Application_Model_DocumentsPending::STATUS_PENDING],
                ]);

                $documentsService = new Application_Service_Documents();
                $newDocumentsCounter = $documentsService->createDocuments($dateupdate, [
                    'documents' => $pendingDocumentsToPrint,
                ], $mode);

                $this->outputJson([
                    'status' => 1,
                    'app' => [
                        'notification' => [
                            'type' => 'success',
                            'title' => 'Aktualizacja dokumentów',
                            'text' => sprintf('Utworzono %d nowych dokumentów', $newDocumentsCounter),
                        ],
                        'redirect' => '/documents/pending'
                    ]
                ]);
                return null;
                break;
        }

        $documentsService = new Application_Service_Documents();
        $documentsService->updatePendingDocuments();

        $paginator = $this->documentsPendingModel->getList([
            'dp.status IN (?)' => [Application_Model_DocumentsPending::STATUS_ACCEPTED, Application_Model_DocumentsPending::STATUS_PENDING]
        ]);

        $this->view->assign(compact('paginator'));
    }

    public function pendingBulkOperationsAction()
    {

    }

    public function pendingPreviewAction()
    {
        $this->view->ajaxModal = 1;
        $id = $this->_getParam('id');

        $pendingDocument = $this->documentsPendingModel->requestObject($id)->toArray();

        $this->view->documentContent = Application_Service_DocumentsPrinter::getInstance()->getPendingDocumentPreview($pendingDocument['id']);
        $this->view->document = $pendingDocument;
    }

    public function pendingProcessAction()
    {
        $this->setDialogAction();
        $id = $this->_getParam('id');

        $pendingDocument = $this->documentsPendingModel->requestObject($id)->toArray();

        $comment = [
            'type' => Application_Service_Messages::TYPE_PENDING_DOCUMENT,
            'object_id' => $id,
            'recipient_id' => 0,
            'topic' => 'asd',
        ];

        $messages = $this->messagesService->getMessages(array(
            'type = ?' => Application_Service_Messages::TYPE_PENDING_DOCUMENT,
            'object_id = ?' => $id,
        ));

        $this->view->comments = $messages;

        $this->view->documentContent = Application_Service_DocumentsPrinter::getInstance()->getPendingDocumentPreview($pendingDocument['id']);
        $this->view->document = $pendingDocument;
        $this->view->comment = $comment;
    }

    public function pendingChangeStatusAction()
    {
        $this->view->ajaxModal = 1;
        $id = $this->_getParam('id');
        $status = $this->_getParam('status');

        try {
            if (!isset($this->pending_document_status_display[$status])) {
                Throw new Exception('Invalid status');
            }

            $pendingDocument = $this->documentsPendingModel->requestObject($id);

            $pendingDocument->status = $status;
            $pendingDocument->save();
        } catch (Exception $e) {
            Throw new Exception('Próba zapisu nie powiodła się', 500, $e);
        }

        $this->_redirect('/documents/pending');
    }

    public function pendingCommentSaveAction()
    {
        $this->view->ajaxModal = 1;
        $id = $this->_getParam('id');
        $status = $this->_getParam('status');

        try {
            if (!isset($this->pending_document_status_display[$status])) {
                Throw new Exception('Invalid status');
            }

            $pendingDocument = $this->documentsPendingModel->requestObject($id);

            $pendingDocument->status = $status;
            $pendingDocument->save();
        } catch (Exception $e) {
            Throw new Exception('Próba zapisu nie powiodła się', 500, $e);
        }

        $this->_redirect('/documents/pending');
    }

    public function getChoiceAction()
    {
        $this->view->ajaxModal = 1;
        $mode = 'table';

        $id = $this->_getParam('id');
        if ($id) {
            $ids = array($id);
            $mode = 'document';

            $document = $this->documentsModel->requestObject($id);
            $document->loadData(['attachments', 'signature']);

            $this->view->document = $document;
            if ($document['is_recalled']) {
                $this->view->recallAuhtor = $this->osoby->getOne($document['recall_author']);
            }

            $this->view->documentContent = Application_Service_DocumentsPrinter::getInstance()->getDocumentPreview($id);
            $this->documentsService->loadForm($document);
            vd($document);
        } else {
            $ids = $_GET['ids'];

            $params = array(
                'd.id IN (?)' => $ids,
                'd.active != ?' => Application_Service_Documents::VERSION_ARCHIVE,
            );

            $documents = $this->documentsModel->getList($params);

            $this->view->documents = $documents;
        }

        $this->view->mode = $mode;
        $this->view->ids = implode(',', $ids);
    }

    public function printAction()
    {
        $this->_helper->getHelper('viewRenderer')->setScriptAction('print');
        $this->view->ajaxModal = 1;
        $type = $this->getParam('type', 'real');


        $ids = !empty($_GET['ids']) ? array_filter(explode(',', $_GET['ids'])) : null;
        $requestedId = $this->_getParam('id');
        if (!$ids && $requestedId) {
            $ids = array($requestedId);
        }

        $documenttemplateId = null;

        if ($type === 'pending') {
            $documentsModel = Application_Service_Utilities::getModel('DocumentsPending');
            $tableAlias = 'dp';
        } else {
            $documentsModel = Application_Service_Utilities::getModel('Documents');
            $tableAlias = 'd';
        }

        if ($type === 'pending') {
            $params = [$tableAlias . '.status IN (?)' => [Application_Model_DocumentsPending::STATUS_PENDING, Application_Model_DocumentsPending::STATUS_ACCEPTED]];
            $order = [$tableAlias . '.created_at DESC'];
        } else {
            $params = [$tableAlias . '.active != ?' => Application_Service_Documents::VERSION_ARCHIVE];
            $order = ['roz.nazwisko ASC', 'roi.imie ASC', 'd.date DESC'];
        }
        if ($documenttemplateId) {
            $params[$tableAlias . '.documenttemplate_id = ?'] = $documenttemplateId;
        }

        $params = empty($ids)
            ? $params
            : [$tableAlias . '.id IN (?)' => $ids];

        $documents = $documentsModel->getList($params, null, $order);

        $ids = [];
        foreach ($documents as $document) {
            $ids[] = $document['id'];
        }

        $documents = array();
        foreach ($ids as $id) {
            if (!$this->isGranted('node/documents/print', array('id' => $id))) {
                continue;
            }

            Application_Service_RepositoryRetreiver::getInstance()->clearCache();
            $documentsPrinter = Application_Service_DocumentsPrinter::reloadInstance();
            if ($type === 'pending') {
                $documents = array_merge($documents, $documentsPrinter->printPendingDocuments(array($id)));
            } else {
                $documents = array_merge($documents, $documentsPrinter->printDocuments(array($id)));
            }
        }

        if (empty($documents)) {
            $this->_redirect($this->baseUrl);
        }

        $this->view->paginator = $documents;
    }

    public function getpdfAction() {
        $this->printAction();

        $this->_helper->layout->setLayout('report');
        $layout = $this->_helper->layout->getLayoutInstance();
        $layout->assign('content', $this->view->render('documents/print-pdf.html'));
        $htmlResult = $layout->render();

        $date = new DateTime();
        $time = $date->format('\TH\Hi\M');
        $timeDate = new DateTime();
        $timeDate->setTimestamp(0);
        $timeInterval = new DateInterval('P0Y0D' . $time);
        $timeDate->add($timeInterval);
        $timeTimestamp = $timeDate->format('U');

        $filename = 'dokumenty_' . date('Y-m-d') . '_' . $timeTimestamp . '.pdf';

        $this->outputHtmlPdf($filename, $htmlResult);
    }

    public function printAction_old()
    {
        $this->view->ajaxModal = 1;

        $t_setting = $this->settings->fetchRow(array('id = ?' => 1));

        $ids = $_GET['ids'];

        $params = array(
            'active = ?' => 1,
        );

        if ($ids <> '') {
            $t_ids = explode(',', $ids);
            unset($t_ids[(count($t_ids) - 1)]);
            $params['id IN (?)'] = $t_ids;
        }

        $t_templates = array();
        $t_documenttemplates = $this->documenttemplates->fetchAll()->toArray();
        foreach ($t_documenttemplates AS $documenttemplate) {
            $t_templates[$documenttemplate['id']] = $documenttemplate;
        }

        $paginator = $this->osoby->fetchAll(
            array('usunieta <> ?' => 1),
            array('imie', 'nazwisko', 'stanowisko'))
            ->toArray();

        foreach ($paginator AS $k => $v) {
            $params['osoba_id = ?'] = $paginator[$k]['id'];
            $t_documents = $this->documentsModel->fetchAll($params)->toArray();
            if (count($t_documents) == 0) {
                unset($paginator[$k]);
            } else {
                foreach ($t_documents AS $k2 => $v2) {
                    $date = $v2['date'];
                    $number = $v2['number'];
                    $numbertxt = $v2['numbertxt'];
                    $keys = unserialize($v2['keys']);
                    $access = unserialize($v2['access']);
                    $personal = unserialize($v2['personal']);
                    $content = $t_templates[$v2['documenttemplate_id']]['content'];
                    $content = str_replace('{imie}', mb_strtoupper($personal['i']), $content);
                    $content = str_replace('{nazwisko}', mb_strtoupper($personal['n']), $content);
                    $content = str_replace('{login_do_systemu}', mb_strtoupper($personal['l']), $content);
                    $content = str_replace('{stanowisko}', mb_strtoupper($personal['s']), $content);
                    $content = str_replace('{data}', $date, $content);
                    $content = str_replace('{nr}', $numbertxt, $content);
                    $content = str_replace('{nazwa_firmy}', mb_strtoupper($t_setting->value), $content);
                    $content = str_replace('{zbiory}', $v2['zbiorynames'], $content);
                    $content = str_replace('{pomieszczenia}', $v2['roomsnames'], $content);
                    $t_documents[$k2]['content'] = $content;
                }

                $paginator[$k]['t_documents'] = $t_documents;
                $paginator[$k]['i_documents'] = count($t_documents);
            }
        }
        $this->view->paginator = $paginator;
        $this->view->i_paginator = count($paginator);
    }

    public function pendingUpdateAllAction()
    {
        $rowSelect = $this->_getParam('rowSelect');
        $rowSelect = array_keys(Application_Service_Utilities::removeEmptyValues($rowSelect));
        $mode = !empty($_POST['mode']) ? $_POST['mode'] : 'actualize';

        $this->view->mode = $mode;
        $this->view->rowSelect = $rowSelect;
        $this->view->ajaxModal = 1;
        $this->view->datetoupdate = date('Y-m-d');
    }

    public function updateAllAction()
    {
        $userId = $this->_getParam('userId');

        if ($userId) {
            $osoba = $this->osoby->requestObject($userId);
            if (!$osoba->generate_documents) {
                $this->view->ajaxModal = 1;
                echo '<well>Nie można wygenerować dokumentacji dla wybranego pracownika.<br>Pracownik nie ma zaznaczonej opcji generowania dokumentów.</well>';
                exit;
            }
        }

        $this->view->documenttemplates = $this->documenttemplates->getAllForTypeahead(['active = 1', 'type <> 4']);
        $this->view->userId = $userId;
        $this->view->ajaxModal = 1;
        $this->view->datetoupdate = date('Y-m-d');
    }

    public function updateAllGoAction()
    {
        $this->view->ajaxModal = 1;

        $dateupdate = $_POST['dateupdate'];
        $mode = !empty($_POST['mode']) ? $_POST['mode'] : 'actualize';
        $documenttemplateIds = $_POST['documenttemplate_id'];
        $ids = !empty($_POST['rowSelect']) ? explode(',', $_POST['rowSelect']) : array();

        $documentsService = new Application_Service_Documents();
        $documentsService->createDocuments($dateupdate, [
            'osobyIds' => $ids,
            'documenttemplateIds' => $documenttemplateIds,
        ], $mode);

        $this->_redirect('/documents');
    }

    public function getpdfAction_old()
    {
        $this->view->ajaxModal = 1;

        $css = ('
            <style type="text/css">
               @page { margin:1cm 2cm 1cm 2cm!important;padding:0!important;line-height: 1; font-family: Arial; color: #000; background: none; font-size: 11pt; }
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
               td { }
               th,td { padding: 4px 10px 4px 0; }
               tfoot { font-style: italic; }
               caption { background: #fff; margin-bottom:2em; text-align:left; }
               thead { display: table-header-group; }
               img,tr { page-break-inside: avoid; }
            </style>
         ');

        $t_setting = $this->settings->fetchRow(array('id = ?' => 1));

        $ids = $_GET['ids'];

        $params = array(
            'active = ?' => 1,
        );

        if ($ids <> '') {
            if ((string)((int)$ids) === $ids) {
                $params['id = ?'] = $ids;
            } else {
                $t_ids = explode(',', $ids);
                unset($t_ids[(count($t_ids) - 1)]);
                $params['id IN (?)'] = $t_ids;
            }
        }

        $t_templates = array();
        $t_documenttemplates = $this->documenttemplates->fetchAll()->toArray();
        foreach ($t_documenttemplates AS $documenttemplate) {
            $t_templates[$documenttemplate['id']] = $documenttemplate;
        }

        require_once('mpdf60/mpdf.php');

        $mpdf = new mPDF('', 'A4', '', '', '0', '0', '0', '0', '', '', 'P');

        $i = 0;
        $paginator = $this->osoby->fetchAll(
            array('usunieta <> ?' => 1),
            array('imie', 'nazwisko', 'stanowisko'))
            ->toArray();

        foreach ($paginator AS $k => $v) {
            $params['osoba_id = ?'] = $paginator[$k]['id'];
            $t_documents = $this->documentsModel->fetchAll($params)->toArray();
            if (count($t_documents) == 0) {
                unset($paginator[$k]);
            } else {
                foreach ($t_documents AS $k2 => $v2) {
                    $i++;
                    $date = $v2['date'];
                    $number = $v2['number'];
                    $numbertxt = $v2['numbertxt'];
                    $keys = unserialize($v2['keys']);
                    $access = unserialize($v2['access']);
                    $personal = unserialize($v2['personal']);
                    $content = $t_templates[$v2['documenttemplate_id']]['content'];
                    $content = str_replace('{imie}', $personal['i'], $content);
                    $content = str_replace('{nazwisko}', $personal['n'], $content);
                    $content = str_replace('{login_do_systemu}', mb_strtoupper($personal['l']), $content);
                    $content = str_replace('{stanowisko}', mb_strtoupper($personal['s']), $content);
                    $content = str_replace('{data}', $date, $content);
                    $content = str_replace('{nr}', $numbertxt, $content);
                    $content = str_replace('{nazwa_firmy}', mb_strtoupper($t_setting->value), $content);
                    $content = str_replace('{zbiory}', $v2['zbiorynames'], $content);
                    $content = str_replace('{pomieszczenia}', mb_strtoupper($v2['roomsnames']), $content);
                    $newnum = preg_replace("/[^A-Za-z0-9 ]/", '', $v2['numbertxt']);
                    $content = str_replace('{barcode}', '<barcode code="' . $newnum . '" type="C39" height="2" text="1" /><br />' . $newnum . '', $content);
                    $t_documents[$k2]['content'] = $content;

                    if ($i > 1) {
                        $mpdf->AddPage();
                    }
                    $mpdf->WriteHTML($css . '' . $content . '');
                }

                $paginator[$k]['t_documents'] = $t_documents;
                $paginator[$k]['i_documents'] = count($t_documents);
            }
        }

        $mpdf->Output();

        die();
    }

    public function deleteAllAction()
    {
        $this->view->ajaxModal = 1;
    }

    public function deleteAllGoAction()
    {
        $this->view->ajaxModal = 1;

        $this->db->query('TRUNCATE TABLE documents');
        $this->db->query('TRUNCATE TABLE documents_repo_objects');

        $this->_redirect('/documents');
        die();
    }

    public function allAction()
    {
        $this->setDetailedSection('Lista wszystkich dokumentów');
        $req = $this->getRequest();
        $active = $req->getParam('active');
        if ($active == '') {
            $active = 'all';
        }
        $this->view->p_active = $active;

        $listParams = array();

        if (!$this->isGranted('perm/documents/all')) {
            //$listParams['d.osoba_id = ?'] = $this->idUsers;
        }

        $documents = $this->documentsModel->getList($listParams);

        $this->view->paginator = $documents;
    }

    public function userArchiveAction()
    {
        $userId = $this->_getParam('id');
        $this->setDetailedSection('Archiwum użytkownika');

        $documents = $this->documentsModel->getList(array(
            'd.osoba_id = ?' => $userId,
            'd.active = ?' => Application_Service_Documents::VERSION_ARCHIVE,
        ));

        $this->view->paginator = $documents;
    }

    public function userDocumentsAction()
    {
        $userId = $this->_getParam('id');
        $this->setDetailedSection('Dokumenty użytkownika');

        $this->setSectionNavigation(array(
            array(
                'label' => 'Operacje',
                'path' => 'javascript:;',
                'icon' => 'fa icon-tools',
                'rel' => 'operations',
                'children' => array(
                    array(
                        'label' => 'Aktualizuj dokumentację dla pracownika',
                        'path' => 'javascript:;',
                        'icon' => 'icon-align-justify',
                        'rel' => 'admin',
                        'nohref' => true,
                        'onclick' => "showDial('/documents/update-all/?userId=".$userId."','',''); return false;",
                    ),
                )
            ),
        ));

        $documents = $this->documentsModel->getList(array(
            'd.osoba_id = ?' => $userId,
        ));

        $this->view->paginator = $documents;
    }

    public function recallDocumentAction()
    {
        $this->setDialogAction();
        $id = $this->_getParam('id');

        $this->view->id = $id;
        $this->view->document = $this->documentsModel->requestObject($id);
        $this->view->data = [
            'recall_date' => date('Y-m-d'),
        ];
    }

    public function recallDocumentGoAction()
    {
        $id = $this->_getParam('id');
        $reason = $this->_getParam('recall_reason');
        $date = $this->_getParam('recall_date');
        $status = false;

        try {
            $this->db->beginTransaction();

            Application_Service_Documents::getInstance()->recallDocument($id, $reason, $date);

            $this->db->commit();
            $status = true;
        } catch (Exception $e) {
            Throw $e;
        }

        $this->outputJson([
            'status' => 1,
            'app' => [
                'notification' => [
                    'type' => $status ? 'success' : 'danger',
                    'title' => 'Przenoszenie zbiorów',
                    'text' => $status ? 'Wycofano dokument' : 'Nieudane wycofanie dokumentu',
                ],
                'redirect' => '/documents'
        ]
        ]);
    }

    public function attachmentsUploadAction()
    {
        $documentId = $this->_getParam('id');
        $uploadedFiles = $this->_getParam('uploadedFiles');
        $uploadedFiles = json_decode($uploadedFiles, true);
        $filesService = Application_Service_Files::getInstance();
        $documentsAttachments = Application_Service_Utilities::getModel('DocumentsAttachments');

        $document = $this->documentsModel->getOne(['d.id = ?' => $documentId], true);

        try {
            if (!empty($uploadedFiles)) {
                foreach ($uploadedFiles as $file) {
                    $fileUri = sprintf('uploads/documents/%s', $file['uploadedUri']);
                    $file = $filesService->create(Application_Service_Files::TYPE_DOCUMENT_ATTACHMENT, $fileUri, $file['name'], null, [
                        'storage_dir' => sprintf('uploaded/%s/%s',
                            Application_Service_Utilities::standarizeName($document['template_name']),
                            Application_Service_Utilities::standarizeName($document['numbertxt'])
                        ),
                    ]);

                    $documentsAttachments->save([
                        'document_id' => $documentId,
                        'file_id' => $file->id,
                    ]);
                }
            }

        } catch (Exception $e) {
            Throw new Exception('Nie udało się wysłać plików', 500, $e);
        }

        $this->outputJson([
            'status' => true,
            'app' => [
                'reload' => true,
            ],
        ]);

    }

    public function dialogChooseAction()
    {
        $this->setDialogAction();

        $this->view->t_data = $this->documentsModel->getList();
    }

    public function miniAddAction()
    {
        $this->view->ajaxModal = 1;
        $this->view->records = $this->documentsModel->getAllForTypeahead();
    }
}