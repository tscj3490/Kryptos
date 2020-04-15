<?php

class FilesController extends Muzyka_Admin
{

    /** @var Application_Service_Files */
    private $filesService;

    protected $baseUrl = '/files';

    public function init()
    {
        parent::init();
        Zend_Layout::getMvcInstance()->assign('section', 'Pliki');
        Zend_Layout::getMvcInstance()->setLayout('home');
        $this->view->baseUrl = $this->baseUrl;

        $this->filesService = Application_Service_Files::getInstance();
    }

    public static function getPermissionsSettings() {
        $settings = array(
            'nodes' => array(
                'files' => array(
                    '_default' => array(
                        'permissions' => array(),
                    ),
                ),
            )
        );

        return $settings;
    }

    protected function throw404()
    {
        header('HTTP/1.0 404 Not Found');
        exit;
    }

    public function viewAction()
    {
        $token = $this->_getParam('t');
        $api = $this->_getParam('api');

        if (!$token) {
            $this->throw404();
        }

        if ($api) {
            $file = $this->filesService->getFromApi($api, $token);
        } else {
            $file = $this->filesService->getByToken($token);
        }

        if (false === $file) {
            $this->throw404();
        }

        header(sprintf('Content-type: %s', $file['file_type']));

        if (isset($file['binary_data'])) {
            echo $file['binary_data'];
        } else {
            echo file_get_contents($file['real_path']);
        }

        exit;
    }

    public function downloadAction()
    {
        $token = $this->_getParam('t');
        $api = $this->_getParam('api');

        $file = $this->filesService->getByToken($token);

        if (false === $file) {
            $this->throw404();
        }

        header("Content-Transfer-Encoding: Binary");
        header(sprintf('Content-disposition: attachment; filename="%s"', $file['name']));
        header(sprintf('Content-type: %s', $file['file_type']));
        header(sprintf('Content-Length: %s', $file['size']));

        if (isset($file['binary_data'])) {
            echo $file['binary_data'];
        } else {
            echo file_get_contents($file['real_path']);
        }

        exit;
    }

    public function thumbnailAction()
    {
        $token = $this->_getParam('t');
        $typesDefinition = array(
            'image/jpeg' => true,
            'image/jpg' => true,
            'image/png' => true,
            'image/gif' => true,
        );

        if (!$token) {
            $this->throw404();
        }

        $file = $this->filesService->getByToken($token);

        if (false === $file) {
            $this->throw404();
        }

        if (isset($typesDefinition[$file['file_type']])) {
            if ($typesDefinition[$file['file_type']] === true) {
                return $this->viewAction();
            }
        }

        $extension = array_pop(explode('.', $file['name']));

        if (is_file(sprintf('assets/plugins/roxyFileman/fileman/images/filetypes/big/file_extension_%s.png', $extension))) {
            $this->_redirect(sprintf('/assets/plugins/roxyFileman/fileman/images/filetypes/big/file_extension_%s.png', $extension));
        }

        $this->throw404();
    }

    public function previewAction()
    {
        $this->setDialogAction();
        $token = $this->_getParam('t');
        $api = $this->_getParam('api');

        if (!$token) {
            $this->throw404();
        }

        if ($api) {
            $file = $this->filesService->getFromApi($api, $token);
        } else {
            $file = $this->filesService->getByToken($token);
        }

        $this->view->file = $file;
    }
}
