<?php

class Application_Service_DocumentsVersioned
{
    const TYPE_GENERAL = 1;
    const TYPE_TASK = 2;
    const TYPE_KOMUNIKAT = 3;

    const STATUS_ACTIVE = 1;
    const STATUS_TRASH = 0;

    /** @var Application_Service_DocumentsVersioned */
    protected static $_instance = null;

    private function __clone() {}

    public static function getInstance() { return null === self::$_instance ? (self::$_instance = new self()) : self::$_instance; }
    /** @var Application_Model_Files */
    protected $filesModel;

    /** @var Zend_Db_Adapter_Abstract */
    protected $db;

    /** @var Application_Service_Files */
    protected $filesService;

    /** @var Muzyka_Admin */
    protected $controller;

    /** @var Application_Model_DocumentsVersioned */
    private $documentsVersionedModel;

    /** @var Application_Model_DocumentsVersionedVersions */
    private $documentsVersionedVersionsModel;

    /** @var Application_Model_Tasks */
    private $tasksModel;

    /** @var Application_Service_Tasks */
    private $tasksService;

    protected $directory;

    public function __construct()
    {
        $this->filesModel = Application_Service_Utilities::getModel('Files');
        $this->filesService = Application_Service_Files::getInstance();
        $this->documentsVersionedModel = Application_Service_Utilities::getModel('DocumentsVersioned');
        $this->documentsVersionedVersionsModel = Application_Service_Utilities::getModel('DocumentsVersionedVersions');
        $this->tasksModel = Application_Service_Utilities::getModel('Tasks');
        $this->tasksService = Application_Service_Tasks::getInstance();

        $this->directory = ROOT_PATH . 'files/';

        $this->db = $this->filesModel->getAdapter();
    }

    public function setController($controller)
    {
        $this->controller = $controller;
    }

    public function getVersionStatusDisplaySettings()
    {
        return array(
            Application_Model_DocumentsVersionedVersions::VERSION_ACTUAL => array(
                'label' => 'Aktualny',
                'color' => 'green',
            ),
            Application_Model_DocumentsVersionedVersions::VERSION_SCHEDULE => array(
                'label' => 'Zaplanowany',
                'color' => 'orange',
            ),
            Application_Model_DocumentsVersionedVersions::VERSION_OUTDATED => array(
                'label' => 'Przedawniony',
                'color' => 'red',
            ),
            Application_Model_DocumentsVersionedVersions::VERSION_ARCHIVE => array(
                'label' => 'Archiwalny',
                'color' => 'grey',
            ),
        );
    }

    /**
     * @param array $documentData
     * @param array $versionData
     * @return Application_Model_DocumentsVersioned
     * @throws Exception
     */
    public function createDocument($documentData, $versionData)
    {
        $documentData = array_merge($documentData,
            array(
                'status' => Application_Model_DocumentsVersioned::STATUS_ACTIVE,
            )
        );

        try {
            $document = $this->documentsVersionedModel->save($documentData);
            $versionData['document_id'] = $document->id;

            $this->createVersion($versionData);
        } catch (Exception $e) {
            Throw $e;
        }

        return $document;
    }

    /**
     * @param array $versionData
     * @return Application_Model_DocumentsVersionedVersions
     * @throws Exception
     */
    public function createVersion($versionData)
    {
        $versionData = array_merge($versionData,
            array(
                'status' => Application_Model_DocumentsVersionedVersions::VERSION_SCHEDULE,
            )
        );

        try {
            if (!empty($versionData['uploadedFile'])) {
                $file = $versionData['uploadedFile'];
                $fileUri = sprintf('uploads/documents/%s', $file['uploadedUri']);

                $file = $this->filesService->create(Application_Service_Files::TYPE_DOCUMENT_VERSIONED_VERSION, $fileUri, $file['name']);
                $versionData['file_id'] = $file->id;
            }

            $version = $this->documentsVersionedVersionsModel->save($versionData);
        } catch (Exception $e) {
            Throw $e;
        }

        return $version;
    }

    /**
     * @param array $versionData
     * @return Application_Model_DocumentsVersionedVersions
     * @throws Exception
     */
    public function updateVersion($versionData)
    {
        try {
            $currentData = $this->documentsVersionedVersionsModel->getOne(array('dv.id = ?' => $versionData['id']));

            if (!empty($versionData['uploadedFile'])) {
                $this->filesService->removeFilesById([$currentData['file_id']]);

                $file = $versionData['uploadedFile'];
                $fileUri = sprintf('uploads/documents/%s', $file['uploadedUri']);

                $file = $this->filesService->create(Application_Service_Files::TYPE_DOCUMENT_VERSIONED_VERSION, $fileUri, $file['name']);
                $versionData['file_id'] = $file->id;
            }

            $version = $this->documentsVersionedVersionsModel->save($versionData);
        } catch (Exception $e) {
            Throw $e;
        }

        return $version;
    }

    public function updateVersionsStatus($document)
    {
        $newActualVersion = null;
        $today = date('Y-m-d');

        $scheduledForTodayResult = $this->documentsVersionedVersionsModel->getList(array(
            'dv.document_id = ?' => $document->id,
            'dv.date_from <= ?' => $today,
            'dv.date_to >= ? OR dv.date_to IS NULL' => $today,
        ), 1, ['dv.date_from DESC']);
        $scheduledForTodayVersion = $scheduledForTodayResult ? $scheduledForTodayResult[0] : null;

        $actualVersion = $this->documentsVersionedVersionsModel->getOne(array(
            'dv.document_id = ?' => $document->id,
            'dv.status IN (?)' => array(Application_Model_DocumentsVersionedVersions::VERSION_ACTUAL)
        ));

        //vdie($scheduledForTodayVersion, $actualVersion);

        if (!$scheduledForTodayVersion && $actualVersion) {
            $actualVersion['status'] = Application_Model_DocumentsVersionedVersions::VERSION_OUTDATED;
            $this->documentsVersionedVersionsModel->save($actualVersion);
        }
        elseif ($scheduledForTodayVersion && !$actualVersion) {
            $scheduledForTodayVersion['status'] = Application_Model_DocumentsVersionedVersions::VERSION_ACTUAL;
            $newActualVersion = $this->documentsVersionedVersionsModel->save($scheduledForTodayVersion);
        }
        elseif ($scheduledForTodayVersion && $actualVersion) {
            if ($scheduledForTodayVersion['id'] !== $actualVersion['id']) {
                if ((int) $actualVersion['status'] === Application_Model_DocumentsVersionedVersions::VERSION_ACTUAL) {
                    //$actualVersion['date_to'] = $scheduledForTodayVersion['date_from'];
                }
                $actualVersion['status'] = Application_Model_DocumentsVersionedVersions::VERSION_ARCHIVE;
                $scheduledForTodayVersion['status'] = Application_Model_DocumentsVersionedVersions::VERSION_ACTUAL;
                $this->documentsVersionedVersionsModel->save($actualVersion);
                $newActualVersion = $this->documentsVersionedVersionsModel->save($scheduledForTodayVersion);
            }
        }

        if ($newActualVersion) {
            $this->eventNewActualVersion($newActualVersion);
        }
    }

    public function removeVersion($versionId)
    {
        try {
            $version = $this->documentsVersionedVersionsModel->requestObject($versionId);
            $version->delete();
        } catch (Exception $e) {
            Throw $e;
        }

        return true;
    }

    private function eventNewActualVersion($newActualVersion)
    {
        $task = $this->tasksModel->findOneBy(array(
            'type = ?' => Application_Service_Tasks::TYPE_DOCUMENT_VERSIONED,
            'object_id = ?' => $newActualVersion->document_id
        ));

        if ($task) {
            $this->tasksService->eventDocumentVersionCreate($task, $newActualVersion->id);
        }
    }

    public function eventNewTask($task)
    {
        $actualVersion = $this->documentsVersionedVersionsModel->findOneBy(array(
            'document_id = ?' => $task['object_id'],
            'status = ?' => Application_Model_DocumentsVersionedVersions::VERSION_ACTUAL,
        ));

        if ($actualVersion) {
            $this->tasksService->eventDocumentVersionCreate($task, $actualVersion['id']);
        }
    }
}
