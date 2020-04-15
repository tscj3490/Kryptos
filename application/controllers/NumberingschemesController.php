<?php

class NumberingschemesController extends Muzyka_Admin
{
    /** @var Application_Model_Numberingschemes */
    private $numberingschemes;

    public function init()
    {
        parent::init();
        $this->view->section = 'Schematy numeracji';
        $this->numberingschemes = Application_Service_Utilities::getModel('Numberingschemes');
        $this->documenttemplates = Application_Service_Utilities::getModel('Documenttemplates');
        $this->documents = Application_Service_Utilities::getModel('Documents');

        Zend_Layout::getMvcInstance()->assign('section', 'Schematy numeracji');
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/numberingschemes/create'),
                2 => array('perm/numberingschemes/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'numberingschemes' => array(
                    'label' => 'Schematy numeracji',
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
                'numberingschemes' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),
                    'index' => array(
                        'permissions' => array('perm/numberingschemes'),
                    ),
                    'update' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),
                    'save' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),
                    'del' => array(
                        'permissions' => array('perm/numberingschemes/remove'),
                    ),
                    'delchecked' => array(
                        'permissions' => array('perm/numberingschemes/remove'),
                    ),

                ),
            )
        );

        return $settings;
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista schematów numeracji');
        $paginator = $this->numberingschemes->fetchAll(null, 'name')->toArray();
        foreach ($paginator AS $k => $v) {
            $paginator[$k]['used'] = 0;
            $paginator[$k]['usednames'] = 0;
            $i_docs = 0;
            $t_documenttemplates = $this->documenttemplates->fetchAll(array('numberingscheme_id = ?' => $v['id']));
            foreach ($t_documenttemplates AS $documenttemplate) {
                $paginator[$k]['used'] = 1;
                $paginator[$k]['usednames'] .= $documenttemplate->name . '<br />';
                $t_document = $this->documents->fetchRow(array(
                    'documenttemplate_id = ?' => $documenttemplate->id,
                    'active = ?' => 1,
                ));
                if ($t_document->id > 0) {
                    $i_docs++;
                }
            }
            $paginator[$k]['i_docs'] = $i_docs;
        }

        $this->view->paginator = $paginator;
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $copy = $req->getParam('copy', 0);

        if ($id) {
            $row = $this->numberingschemes->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();

            $i_docs = 0;
            $t_documenttemplates = $this->documenttemplates->fetchAll(array('numberingscheme_id = ?' => $id));
            foreach ($t_documenttemplates AS $documenttemplate) {
                $t_document = $this->documents->fetchRow(array(
                    'documenttemplate_id = ?' => $documenttemplate->id,
                    'active = ?' => 1,
                ));
                if ($t_document->id > 0) {
                    $i_docs++;
                }
            }

            $this->view->i_docs = $i_docs;
            $this->setDetailedSection('Edytuj schamat numeracji');
        } else if ($copy) {
            $row = $this->numberingschemes->getOne($copy);
            if ($row instanceof Zend_Db_Table_Row) {
                $row = $row->toArray();
                unset($row['id']);
                $row['name'] = $row['name'] . ' KOPIA';
                $this->view->data = $row;
            }
            $this->setDetailedSection('Dodaj schamat numeracji');
        } else {
            $this->setDetailedSection('Dodaj schamat numeracji');
        }
    }

    public function saveAction()
    {
        try {
            $this->getRepository()->getOperation()->operationBegin(Application_Service_Repository::OPERATION_IMPORTANT);

            $req = $this->getRequest();
            $id = $this->numberingschemes->save($req->getParams());

            $this->getRepository()->getOperation()->operationComplete('schematy_numeracji.update', $id);
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie', 500, $e);
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        $this->_redirect('/numberingschemes');
    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->numberingschemes->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/numberingschemes');
    }

    public function delcheckedAction()
    {
        foreach ($_POST AS $poster => $val) {
            $poster = str_replace('id', '', $poster) * 1;
            if ($poster > 0) {
                try {
                    $this->numberingschemes->remove($poster);
                } catch (Exception $e) {
                }
            }
        }

        $this->_redirect('/numberingschemes');
    }
}