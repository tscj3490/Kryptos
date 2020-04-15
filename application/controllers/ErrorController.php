<?php

class ErrorController extends Muzyka_Action
{

    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');
        $this->_helper->layout->setLayout('admin');
        $message = '';
        $logMessage = '';

        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:

                // 404 error -- controller or action not found
                $this->getResponse()->setHttpResponseCode(404);
                $message = '404 Nie znalaziono strony';
                if (isset($errors->exception)) {
                    /** @var Exception $exception */
                    $exception = $errors->exception;

                    if ($exception->getCode() === 100) {
                        $message = $exception->getMessage();
                    }
                    if ($exception->getCode() === 403) {
                        $message = $exception->getMessage();
                        $responseCode = 403;
                    }

                    $logMessage = $this->getExceptionReport($exception);
                } else {
                    $logMessage = $message;
                }
                break;

            // application error
            default:
                // defaults
                $message = 'Błąd aplikacji';
                $responseCode = 500;
                var_dump($errors->exception);
                exit;

                if (isset($errors->exception)) {
                    /** @var Exception $exception */
                    $exception = $errors->exception;

                    if ($exception->getCode() === 100) {
                        $message = $exception->getMessage();
                    }
                    if ($exception->getCode() === 403) {
                        $message = $exception->getMessage();
                        $responseCode = 403;
                    }

                    $logMessage = $this->getExceptionReport($exception);
                }

                $this->view->message = $message;

                if ($this->getRequest()->isXmlHttpRequest()) {
                    $this->_helper->layout->setLayout('json');
                    $this->view->content = [
                        'status' => false,
                        'app' => [
                            'notification' => [
                                'type' => 'danger',
                                'title' => 'Wystąpił błąd',
                                'text' => $message,
                            ],
                        ],
                    ];
                    $this->getResponse()->setHeader('Content-Type', 'application/json', true);
                    $responseCode = 200;
                } else {
                    $this->_helper->layout->setLayout('blank');
                    $this->view->content = $this->view->render('error/'.$responseCode.'.html');
                }

                $this->getResponse()->setHttpResponseCode($responseCode);
        }

        $this->view->message = $message;

        // Log exception, if logger available
        if ($log = $this->getLog()) {
            $log->crit($logMessage);
        }

        // conditionally display exceptions
        if ($this->getInvokeArg('displayExceptions') == true) {
            $this->view->exception = $errors->exception;
        }
        $this->view->request = $errors->request;

        $this->view->sitepath = array(array(url => '#', 'name' => "Zle miejsce"));
    }

    public function getLog()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        if (!$bootstrap->hasPluginResource('Log')) {
            return false;
        }
        $log = $bootstrap->getResource('Log');
        return $log;
    }

    /** @var Exception $exception */
    public function getExceptionReport($exception, $previousMode = false)
    {
        $logMessage = '';

        if (false === $previousMode) {
            $logMessage .= sprintf("\n[Request URI] %s\n[Data] %s\n[User] id:%s ip: %s\n[Agent] %s\n",
                $_SERVER['REQUEST_URI'],
                json_encode(['get' => $_GET, 'post' => $_POST]),
                Application_Service_Authorization::getInstance()->getUserLogin(),
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT']
            );
        }

        $logMessage .= sprintf("[Error code] %s\n[Error message] %s\n[inFile] %s:%d\n[Trace]\n%s\n", $exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine(), $exception->getTraceAsString());

        $previous = $exception->getPrevious();
        if ($previous) {
            $logMessage .= "[Previous exception]\n" . $this->getExceptionReport($previous, true);
        }

        return $logMessage . "\n\n";
    }
}

