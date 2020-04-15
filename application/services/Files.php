<?php

class Application_Service_Files
{
    const TYPE_MESSAGE_ATTACHMENT = 1;
    const TYPE_DOCUMENT_VERSIONED_VERSION = 2;
    const TYPE_COURSE = 3;
    const TYPE_EXAM = 4;
    const TYPE_NON_COMPILANCE = 5;
    const TYPE_DOCUMENT_ATTACHMENT = 6;
    const TYPE_REGISTRY_ATTACHMENT = 7;
    const TYPE_PUBLIC_PROCUREMENT = 8;

    /** @var self */
    protected static $_instance = null;

    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? new self : self::$_instance; }

    /** @var Application_Model_Files */
    protected $filesModel;

    /** finfo */
    protected $fileInfo;

    /** @var Muzyka_Admin */
    protected $controller;

    protected $directory;

    /** @var Zend_Session_Namespace */
    protected $session;

    /** @var Zend_Db_Adapter_Pdo_Mysql */
    protected $db;

    public function __construct()
    {
        self::$_instance = $this;

        $this->filesModel = Application_Service_Utilities::getModel('Files');
        $this->db = Zend_Registry::getInstance()->get('db');

        $this->typesConfig = array(
            self::TYPE_MESSAGE_ATTACHMENT => array(
               'storage' => 'messages'
            ),
            self::TYPE_DOCUMENT_VERSIONED_VERSION => array(
                'storage_type' => 'ftp',
                'storage' => 'default',
                'storage_dir' => 'documents_versioned_versions',
            ),
            self::TYPE_COURSE => array(
                'storage' => 'courses',
            ),
            self::TYPE_EXAM => array(
                'storage' => 'exams',
            ),
            self::TYPE_NON_COMPILANCE => array(
                'storage' => 'non_compilances',
            ),
            self::TYPE_DOCUMENT_ATTACHMENT => array(
                'storage_type' => 'ftp',
                'storage' => 'default',
                'storage_dir' => 'documents',
            ),
            self::TYPE_REGISTRY_ATTACHMENT => array(
                'storage_type' => 'ftp',
                'storage' => 'default',
                'storage_dir' => 'registry',
            ),
            self::TYPE_REGISTRY_ATTACHMENT => array(
                'storage_type' => 'ftp',
                'storage' => 'default',
                'storage_dir' => 'registry',
            ), 
            self::TYPE_PUBLIC_PROCUREMENT => array(
                'storage_type' => 'ftp',
                'storage' => 'default',
                'storage_dir' => 'zamowienia',
            ),
        );
        $this->directory = ROOT_PATH . 'files/';

        $this->session = new Zend_Session_Namespace('user');
    }

    public function getFileInfo()
    {
        if ($this->fileInfo) {
            return $this->fileInfo;
        }

        return new finfo(FILEINFO_MIME_TYPE);
    }

    public function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * @param int $type
     * @param string $uri
     * @param string $name
     * @param string|null $description
     * @return Application_Model_Files
     * @throws Exception
     */
    public function create($type, $uri, $name, $description = null, $params = [])
    {       
        if (!isset($this->typesConfig[$type])) {
            Throw new Exception('Invalid file type');
        }

        /*
         * to trzeba zrobić po transakcji, na razie nie obsługiwane
         * if (isset($this->session->uploaded[$uri])) {
            return $this->session->uploaded[$uri];
        }*/

        if (!is_file($uri)) {
            //Throw new Exception('File not exists');
        }

        $file_type = $this->getFileInfo()->file($uri);
        $size = filesize($uri);

        $config = $this->getConfig($type);

        $file = $this->filesModel->save(compact('type', 'uri', 'name', 'description', 'file_type', 'size'));

        $xpl = explode('.', $name);
        $ext = array_pop($xpl);

        switch ($config['storage_type']) {
            case "ftp":
                if ('default' === $config['storage']) {
                    $targetDirectory = !empty($params['storage_dir']) ? $params['storage_dir'] : $config['storage_dir'];
                    
                    $role = Application_Model_FileSources::ROLE_DEFAULT_SOURCE;
                    
                    if ($type == Application_Service_Files::TYPE_PUBLIC_PROCUREMENT){
                        $role = Application_Model_FileSources::ROLE_EXTRA_SOURCE;
                    }
                    
                    $source = Application_Service_Utilities::getModel('FileSources')->getOne([
                        'role' => $role,
                    ], true);
                    $sourceConfig = json_decode($source['config'], true);

                    $ftp = Application_Service_Ftp::getInstance($sourceConfig['host'], null, $sourceConfig['user'], $sourceConfig['pass']);

                    $appId = Application_Service_Utilities::getAppId();
                    $systemFolder = 'kryptos.' . $appId;
                    
                    if (isset($params['subdirectory'])){
                        $targetDirectory .= "/".$params['subdirectory'];
                    }

                    $newUri = sprintf('%s/%s/%s', $systemFolder, $targetDirectory, $file->name);

                    $newUri = $ftp->upload($newUri, $uri);
                } else {
                    Throw new Exception('Invalid storage', 500);
                }
                break;
            default:
                $newUri = sprintf('%s/%s.%s', $config['storage'], $file->id, $ext);
                $storageUri = $this->getFileRealPath($newUri);

                $storageDir = dirname($storageUri);
                if (!is_dir($storageDir)) {
                    mkdir($storageDir, 755, true);
                }

                rename($uri, $storageUri);
        }

        $file->uri = $newUri;
        $file->save();

        $this->session->uploaded[$uri] = $file->id;

        return $file;
    }

    public function removeFiles($files)
    {
        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function removeFilesById($ids)
    {
        $this->filesModel->delete(['id IN (?)' => $ids]);
    }

    public function getByToken($token)
    {
        $file = $this->filesModel->findOneBy(array('token = ?' => $token));

        if (!$file) {
            return false;
        }

        $config = $this->getConfig($file['type']);

        switch ($config['storage_type']) {
            case "ftp":
                if ('default' === $config['storage']) {
                    $source = Application_Service_Utilities::getModel('FileSources')->getOne([
                        'role' => Application_Model_FileSources::ROLE_DEFAULT_SOURCE,
                    ], true);
                    $sourceConfig = json_decode($source['config'], true);

                    $ftp = Application_Service_Ftp::getInstance($sourceConfig['host'], null, $sourceConfig['user'], $sourceConfig['pass']);

                    $file['binary_data'] = $ftp->download($file['uri']);
                } else {
                    Throw new Exception('Invalid storage', 500);
                }
                break;
            default:
                $file['real_path'] = $this->getFileRealPath($file['uri']);
        }

        return $file;
    }

    public function getFromApi($api, $token)
    {
        $file = Application_Service_Utilities::apiCall($api, 'api/get-file', ['token' => $token]);
        $binaryFile = Application_Service_Utilities::apiCall($api, 'api/get-file-binary', ['token' => $token], 'binary');

        if (!$file) {
            return false;
        }
        $file['binary_data'] = $binaryFile;

        return $file;
    }

    public function getFileRealPath($uri)
    {
        return sprintf('%s/files/%s', ROOT_PATH, $uri);
    }

    public function requestObject($params)
    {
        return $this->filesModel->requestObject($params);
    }

    public function confirmFile($id)
    {
        $status = false;

        try {
            $this->db->beginTransaction();

            $file = $this->requestObject($id);

            $tasksService = Application_Service_Tasks::getInstance();
            $userSignaturesModel = Application_Service_Utilities::getModel('UserSignatures');
            $idUser = Application_Service_Authorization::getInstance()->getUserId();
            $date = date('Y-m-d H:i:s');

            $storageTaskId = $tasksService->createStorageTask([
                'type' => Application_Service_Tasks::TYPE_TICKET_CONFIRM_ATTACHMENT,
                'object_id' => $id,
                'status' => 1,
                'author_osoba_id' => $idUser,
                'user_id' => $idUser,
                'title' => 'Potwierdzenie dokumentu',
                'signature_required' => 1,
            ]);

            $userSignaturesModel->save([
                'user_id' => $idUser,
                'resource_id' => $storageTaskId,
                'resource_view_date' => $date,
                'sign_date' => $date,
            ]);

            $file->status = 1;
            $file->save();

            $this->db->commit();

            $status = true;
        } catch (Exception $e) {
            $this->db->rollBack();

            Throw new Exception('Nie udało się potwierdzić pliku', 500, $e);
        }

        return $status;
    }

    private function getConfig($type)
    {
        return array_merge([
            'storage_type' => 'file',
        ], $this->typesConfig[$type]);
    }
}