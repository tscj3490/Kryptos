<?php

class Application_Service_DocumentsPrinter
{

    /** Singleton */
    protected static $_instance = null;
    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? (self::$_instance = new self()) : self::$_instance; }
    public static function reloadInstance() { return self::$_instance = new self(); }

    /** @var Application_Model_Documents */
    protected $documentsModel;

    /** @var Application_Model_DocumentsPending */
    protected $documentsPendingModel;

    /** @var Application_Model_Documenttemplates */
    protected $documenttemplatesModel;

    /** @var Application_Model_Osoby */
    protected $osobyModel;

    /** @var Application_Model_Documenttemplatesosoby */
    protected $documenttemplatesosobyModel;

    /** @var Application_Model_DocumentsRepoObjects */
    protected $documentsRepoObjectsModel;

    /** @var Application_Service_RepositoryRetreiver */
    protected $repositoryRetreiver;

    /** @var Application_Model_Settings */
    protected $settingsModel;

    public function __construct()
    {
        $this->documentsModel = Application_Service_Utilities::getModel('Documents');
        $this->documentsPendingModel = Application_Service_Utilities::getModel('DocumentsPending');
        $this->documenttemplatesModel = Application_Service_Utilities::getModel('Documenttemplates');
        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');
        $this->documenttemplatesosobyModel = Application_Service_Utilities::getModel('Documenttemplatesosoby');
        $this->documentsRepoObjectsModel = Application_Service_Utilities::getModel('DocumentsRepoObjects');
        $this->repositoryRetreiver = Application_Service_RepositoryRetreiver::reloadInstance();
        $this->settingsModel = Application_Service_Utilities::getModel('Settings');

        $this->templateVariables = array(
            'imie' => array('osoba.imie'),
            'nazwisko' => array('osoba.nazwisko'),
            'stanowisko' => array('osoba.stanowisko'),
            'login_do_systemu' => array('osoba.login'),
            'nazwa_firmy' => array('???'),
            'zbiory' => array('zbiory.nazwa', 'upowaznienie'),
            'pomieszczenia' => array('klucz', 'pomieszczenie.nazwa', 'budynek.nazwa'),
            'dokument' => array('object.dokument'),
        );
    }

    public function getTemplateVariables()
    {
        return $this->templateVariables;
    }

    function printDocuments($ids = array())
    {
        // TODO wczytywnie t_setting z objectow
        $t_setting = $this->settingsModel->fetchRow('id = 1');

        if (!empty($ids)) {
            $documents = $this->documentsModel->find($ids)->toArray();
        } else {
            $documents = $this->documentsModel->fetchAll(array('active != ?' => Application_Service_Documents::VERSION_ARCHIVE))->toArray();
            foreach ($documents as $document) {
                $ids[] = $document['id'];
            }
        }

        if (empty($documents)) {
            return null;
        }

        $objectsByDocument = array();
        $documentObjects = $this->documentsRepoObjectsModel->findByDocument($ids);
        foreach ($documentObjects as $object) {
            if (!isset($objectsByDocument[$object['document_id']])) {
                $objectsByDocument[$object['document_id']] = array();
            }
            $objectsByDocument[$object['document_id']][] = $object->toArray();
        }

        $this->repositoryRetreiver->loadByVersion($documentObjects);
        $rr = $this->repositoryRetreiver;

        foreach ($documents as &$document) {
            $osobaId = $document['osoba_id'];
            $szablonId = $document['documenttemplate_id'];

            $date = $document['date'];
            $number = $document['number'];
            $numbertxt = $document['numbertxt'];
            $newnum = preg_replace("/[^A-Za-z0-9 ]/", '', $numbertxt);

            $content = $rr->fetch('documenttemplate', array('documenttemplate_id' => $szablonId))['content'];
            $content = str_replace('{imie}', $rr->fetch('osoba.imie', array('osoby_id' => $osobaId))['imie'], $content);
            $content = str_replace('{nazwisko}', $rr->fetch('osoba.nazwisko', array('osoby_id' => $osobaId))['nazwisko'], $content);
            $content = str_replace('{login_do_systemu}', $rr->fetch('osoba.login', array('osoby_id' => $osobaId))['login_do_systemu'], $content);
            $content = str_replace('{stanowisko}', $rr->fetch('osoba.stanowisko', array('osoby_id' => $osobaId))['stanowisko'], $content);
            $content = str_replace('{data}', sprintf('<span class="nowrap">%s</span>', $date), $content);
            $content = str_replace('{nr}', $numbertxt, $content);
            $content = str_replace('{nazwa_firmy}', $t_setting->value, $content);
            $content = str_replace('{zbiory}', $this->getZbiory(), $content);
            $content = str_replace('{pomieszczenia}', $this->getPomieszczenia(), $content);
            $content = str_replace('{formularz}', $this->getDocumentFormSummary($document), $content);
            $content = str_replace('{barcode}', '<barcode code="' . $newnum . '" type="C39" height="2" text="1" /><br />' . $newnum . '', $content);

            $documentFind = Application_Service_Utilities::arrayFind($objectsByDocument[$document['id']], 'object_id', 14);
            if (!empty($documentFind)) {
                $content = str_replace('{dokument.numer}', $rr->fetch('object.document', array('id' => $documentFind[0]['version_id']))['numbertxt'], $content);
            }

            $document['content'] = $content;
        }

        return $documents;
    }

    function printPendingDocuments($ids = array())
    {
        // TODO wczytywnie t_setting z objectow
        $t_setting = $this->settingsModel->fetchRow('id = 1');

        if (!empty($ids)) {
            $documents = $this->documentsPendingModel->find($ids)->toArray();
        } else {
            $documents = $this->documentsPendingModel->fetchAll(array('status IN (?)' => [Application_Model_DocumentsPending::STATUS_PENDING, Application_Model_DocumentsPending::STATUS_ACCEPTED]))->toArray();
            foreach ($documents as $document) {
                $ids[] = $document['id'];
            }
        }

        if (empty($documents)) {
            return null;
        }

        $documenttemplateIds = Application_Service_Utilities::getValues($documents, 'documenttemplate_id');
        $osobyIds = Application_Service_Utilities::getValues($documents, 'user_id');

        $t_documenttemplates = $this->documenttemplatesModel->fetchAll(['id IN (?)' => $documenttemplateIds]);

        $numberingschemeIds = array();
        foreach ($t_documenttemplates as $documenttemplate) {
            $documenttemplateIds[] = $documenttemplate->id;
            $numberingschemeIds[] = $documenttemplate->numberingscheme_id;
        }

        $objectsRepository = new Application_Service_RepositoryObjects();
        $rr = $objectsRepository->prepareRetreiver($osobyIds, $documenttemplateIds, $numberingschemeIds);

        /*$objectsByDocument = array();
        $documentObjects = $this->documentsRepoObjectsModel->findByDocument($ids);
        foreach ($documentObjects as $object) {
            if (!isset($objectsByDocument[$object['document_id']])) {
                $objectsByDocument[$object['document_id']] = array();
            }
            $objectsByDocument[$object['document_id']][] = $object->toArray();
        }*/

        foreach ($documents as &$document) {
            $osobaId = $document['user_id'];
            $szablonId = $document['documenttemplate_id'];

            $date = $document['date'];
            $number = $document['number'];
            $numbertxt = $document['numbertxt'];
            $newnum = preg_replace("/[^A-Za-z0-9 ]/", '', $numbertxt);

            $content = $rr->fetch('documenttemplate', array('documenttemplate_id' => $szablonId))['content'];
            $content = str_replace('{imie}', $rr->fetch('osoba.imie', array('osoby_id' => $osobaId))['imie'], $content);
            $content = str_replace('{nazwisko}', $rr->fetch('osoba.nazwisko', array('osoby_id' => $osobaId))['nazwisko'], $content);
            $content = str_replace('{login_do_systemu}', $rr->fetch('osoba.login', array('osoby_id' => $osobaId))['login_do_systemu'], $content);
            $content = str_replace('{stanowisko}', $rr->fetch('osoba.stanowisko', array('osoby_id' => $osobaId))['stanowisko'], $content);
            $content = str_replace('{data}', sprintf('<span class="nowrap">%s</span>', $date), $content);
            $content = str_replace('{nr}', $numbertxt, $content);
            $content = str_replace('{nazwa_firmy}', $t_setting->value, $content);
            $content = str_replace('{zbiory}', $this->getZbiory(), $content);
            $content = str_replace('{pomieszczenia}', $this->getPomieszczenia(), $content);
            $content = str_replace('{formularz}', $this->getDocumentFormSummary($document), $content);
            $content = str_replace('{barcode}', '<barcode code="' . $newnum . '" type="C39" height="2" text="1" /><br />' . $newnum . '', $content);

            $document['content'] = $content;
        }

        return $documents;
    }

    private function getZbiory()
    {
        $rr = $this->repositoryRetreiver;
        $objects = $rr->fetchCategorized();

        if (empty($objects['upowaznienie'])) {
            return '';
        }

        $t_zbiory_names = array();
        foreach ($objects['upowaznienie'] as $upowaznienie) {
            if ($upowaznienie['czytanie'] == 0 && $upowaznienie['pozyskiwanie'] == 0 && $upowaznienie['wprowadzanie'] == 0 && $upowaznienie['modyfikacja'] == 0 && $upowaznienie['usuwanie'] == 0) {
                continue;
            }

            $t_zbiory_names[$upowaznienie['zbiory_id']] = $objects['zbior.nazwa'][$upowaznienie['zbiory_id']]['nazwa'] . ' ( ';
            if ($upowaznienie['czytanie'] == 1) {
                $t_zbiory_names[$upowaznienie['zbiory_id']] .= ' C ';
            }
            if ($upowaznienie['pozyskiwanie'] == 1) {
                $t_zbiory_names[$upowaznienie['zbiory_id']] .= ' P ';
            }
            if ($upowaznienie['wprowadzanie'] == 1) {
                $t_zbiory_names[$upowaznienie['zbiory_id']] .= ' W ';
            }
            if ($upowaznienie['modyfikacja'] == 1) {
                $t_zbiory_names[$upowaznienie['zbiory_id']] .= ' M ';
            }
            if ($upowaznienie['usuwanie'] == 1) {
                $t_zbiory_names[$upowaznienie['zbiory_id']] .= ' U ';
            }
            $t_zbiory_names[$upowaznienie['zbiory_id']] .= ' ) ';
        }

        $zbiorynames = '<ul>';
        foreach ($t_zbiory_names AS $zbioryname) {
            $zbiorynames .= '<li>' . $zbioryname . '</li>';
        }
        $zbiorynames .= '</ul>';

        return $zbiorynames;
    }

    private function getPomieszczenia()
    {
        $rr = $this->repositoryRetreiver;
        $objects = $rr->fetchCategorized();

        if (empty($objects['klucz'])) {
            return '';
        }

        $roomsnames = '<ul>';
        foreach ($objects['budynek.nazwa'] AS $budynek) {
            $roomsnames .= '<li>' . $budynek['nazwa'] . '<ul>';
            foreach ($objects['pomieszczenie.nazwa'] AS $pomieszczenie) {
                if ($pomieszczenie['budynki_id'] === $budynek['budynki_id']) {
                    $roomsnames .= '<li>' . $pomieszczenie['nazwa'] .' '.$pomieszczenie['nr'].' '. $pomieszczenie['wydzial'] . '</li>';
                }
            }
            $roomsnames .= '</ul></li>';
        }
        $roomsnames .= '</ul>';

        return $roomsnames;
    }

    public function getDocumentBinaryData($documentId)
    {
        $content = $this->getDocumentPreview($documentId);
        $paginator = [['content' => $content]];

        require_once('mpdf60/mpdf.php');

        $mpdf = new mPDF('', 'A4', '', '', '0', '0', '0', '0', '', '', 'P');
        $mpdf->WriteHTML(Application_Service_Utilities::renderView('documents/print-pdf.html', compact('paginator')));

        $pdfBinary = $mpdf->Output('', 'S');

        return $pdfBinary;
    }

    public function getDocumentPreview($documentId)
    {
        $documentsPrinterService = Application_Service_DocumentsPrinter::getInstance();
        $print = $documentsPrinterService->printDocuments(array($documentId));

        if (!empty($print)) {
            return $print[0]['content'];
        }

        return 'Brak dokumentu';
    }

    public function getPendingDocumentPreview($documentId)
    {
        $documentsPrinterService = Application_Service_DocumentsPrinter::getInstance();
        $print = $documentsPrinterService->printPendingDocuments(array($documentId));

        if (!empty($print)) {
            return $print[0]['content'];
        }

        return 'Brak dokumentu';
    }

    private function getDocumentFormSummary($document)
    {
        $summary = '';
        $registryModel = Application_Service_Utilities::getModel('Registry');
        $registryEntriesModel = Application_Service_Utilities::getModel('RegistryEntries');

        $documenttemplateFormRegistry = $registryModel->getFull([
            'type_id = ?' => Application_Service_RegistryConst::REGISTRY_TYPE_DOCUMENTTEMPLATE_FORM,
            'object_id = ?' => $document['documenttemplate_id'],
        ]);

        if ($documenttemplateFormRegistry) {
            $select = $registryEntriesModel->getSelect()
                ->joinLeft(['po' => 'registry_entries_entities_int'], 'po.entry_id = re.id AND po.registry_entity_id = ' . $documenttemplateFormRegistry->entities_named['document']->id, [])
                ->where('po.value = ?', $document['id']);

            $result = $registryEntriesModel->getListFromSelect($select);
            if (!empty($result)) {
                $result = $result[0];
                Application_Service_Registry::getInstance()->entryGetEntities($result);

                $disabledEntities = [];
                foreach ($documenttemplateFormRegistry->entities as $entity) {
                    if (in_array($entity->system_name, ['employee', 'document'])) {
                        $disabledEntities[] = $entity->id;
                    }
                }

                $entitiesToPrint = [];
                foreach ($result->entities as $entity) {
                    if (in_array($entity->registry_entity_id, $disabledEntities)) {
                        continue;
                    }
                    $entitiesToPrint[] = $entity;
                }

                $summary = '<table class="document-user-form-table">';
                foreach ($documenttemplateFormRegistry->entities as $registryEntity) {
                    if (in_array($registryEntity->system_name, ['employee', 'document'])) {
                        continue;
                    }

                    $entity = Application_Service_Utilities::arrayFindOne($result->entities, 'registry_entity_id', $registryEntity->id);

                    if ($entity) {
                        $summary .= '<tr><td>'.$registryEntity->title.'</td><td>'.($entity ? 'tak' : 'nie').'</td></tr>';
                    }
                }
                $summary .= '</table>';
            }
        }

        return $summary;
    }
}