<?php

class LegalactsController extends Muzyka_Admin
{
    /**
     *
     * Osoby model
     * @var Application_Model_Legalacts
     *
     */

    private $legalacts;

    public function init()
    {
        parent::init();
        $this->view->section = 'Akty prawne';
        $this->legalacts = Application_Service_Utilities::getModel('Legalacts');

        Zend_Layout::getMvcInstance()->assign('section', 'Akty prawne');
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/legalacts/create'),
                2 => array('perm/legalacts/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'legalacts' => array(
                    'label' => 'Zbiory/Akty prawne',
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
                'legalacts' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    // public
                    'addmini' => array(
                        'permissions' => array(),
                    ),
                    'mini-choose' => array(
                        'permissions' => array(),
                    ),
                    'checkexist' => array(
                        'permissions' => array(),
                    ),

                    'index' => array(
                        'permissions' => array('perm/legalacts'),
                    ),
                    'ajax-get-list' => array(
                        'permissions' => array('perm/legalacts'),
                    ),

                    'savemini' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),
                    'update' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),
                    'save' => array(
                        'getPermissions' => array($baseIssetCheck),
                    ),

                    'del' => array(
                        'permissions' => array('perm/legalacts/remove'),
                    ),
                    'delchecked' => array(
                        'permissions' => array('perm/legalacts/remove'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function addminiAction()
    {
        $this->view->ajaxModal = 1;
        $req = $this->getRequest();
        $num = $req->getParam('num', 0);
        $this->view->num = $num;


        $columns = [
            'type' => $this->getParam('type'),
            'name' => $this->getParam('name'),
            'year' => $this->getParam('year'),
            'is_obligatory' => $this->getParam('is_obligatory', ''),
        ];

        $conditions = [];
        foreach ($columns as $columnName => $columnValue) {
            if (!empty($columnValue) || $columnValue === '0') {
                $columnValue = '%' . $columnValue . '%';
                if ($columnName === 'name') {
                    $conditions[] = implode(' OR ', [$this->db->quoteInto('name LIKE ?', $columnValue), $this->db->quoteInto('symbol LIKE ?', $columnValue)]);
                } else {
                    $conditions[$columnName . ' LIKE ?'] = $columnValue;
                }
            }
        }

        $this->view->t_data = $this->legalacts->getList($conditions, 1000, 'id');
        $this->view->types = $this->legalacts->getAllTypes();
    }

    public function miniChooseAction()
    {
        $this->view->ajaxModal = 1;
        $req = $this->getRequest();
        $num = $req->getParam('num', 0);
        $this->view->num = $num;
        $this->view->t_data = $this->legalacts->fetchAll(null, 'name');
    }

    public function saveminiAction()
    {
        $this->view->ajaxModal = 1;

        $req = $this->getRequest();
        $t_data = $req->getParams();
        $name = mb_strtoupper(trim($t_data['name']));
        $l_ids = '';
        if ($name <> '') {
            $t_name = explode(';', $name);
            foreach ($t_name AS $nm) {
                $nm = trim($nm);
                if ($nm <> '') {
                    try {
                        if ($nm <> '') {
                            $t_field = $this->legalacts->fetchRow(array('name = ?' => $nm));

                            if (!$t_field->id > 0) {
                                $t_toins = array(
                                    'name' => $nm,
                                    'type' => $t_data['fieldscategory_id'],
                                );
                                $l_ids .= $this->legalacts->save($t_toins) . ',' . $nm . ';';
                            } else {
                                $l_ids .= $t_field->id . ',' . $nm . ';';
                            }
                        }
                    } catch (Exception $e) {
                    }
                }
            }
        }
        echo($l_ids);
        die();
    }

    public function ajaxGetListAction()
    {
        $params = $this->getAllParams();
        $columns = $params['columns'];
        $order = $params['order'][0];
        $orderColumn = $columns[$order['column']]['data'];
        $orderDir = $order['dir'];

        $conditions = ['*return_counter' => true];
        $allowColumns = ['name', 'type', 'symbol', 'year'];
        foreach ($columns as $column) {
            if (!empty($column['search']['value']) && in_array($column['data'], $allowColumns)) {
                $conditions[$column['data'] . ' LIKE ?'] = '%' . $column['search']['value'] . '%';
            }
        }
        if (!in_array($orderColumn, $allowColumns)) {
            $orderColumn = 'id';
        }

        list($list, $counter) = $this->legalacts->getList($conditions, [$params['length'], $params['start']], [$orderColumn .' '. $orderDir]);

        $results = [];
        $result = [
            'draw' => $params['draw'],
            'recordsTotal' => $this->legalacts->countAll(),
            'recordsFiltered' => $counter,
        ];

        foreach ($list as $item) {
            $operations = [];

            if ($this->isGranted('node/legalacts/update', ['id' => $item->id])) {
                $operations[] = sprintf('<a class="glyphicon glyphicon-pencil" href="/legalacts/update/id/%d" data-toggle="tooltip" data-title="EDYTUJ"></a>', $item->id);
            }
            if ($this->isGranted('node/legalacts/update/copy')) {
                $operations[] = sprintf('<a class="glyphicon glyphicon-star" href="/legalacts/update/copy/%d" data-toggle="tooltip" data-title="DUPLIKUJ"></a>', $item->id);
            }
            if ($this->isGranted('node/legalacts/del')) {
                $operations[] = sprintf('<a class="glyphicon glyphicon-trash modal-confirm" href="/legalacts/del/id/%d" data-confirmation-class="singleDelete" data-toggle="tooltip" title="USUŃ"></a>', $item->id);
            }

            $results[] = [
                'checkbox' => sprintf('<input type="checkbox" name="id%d" id="id%d" value="1"/>', $item->id, $item->id),
                'id' => $item->id,
                'name' => $item->name,
                'type' => $item->type,
                'symbol' => $item->symbol,
                'year' => $item->year,
                'operations' => implode(' ', $operations),
            ];
        }

        $result['data'] = $results;

        return $this->outputJson($result);
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista aktów prawnych');
        $this->view->paginator = $this->legalacts->getList(null, 10);
    }

    public function updateAction()
    {
        $req = $this->getRequest();
        $id = $req->getParam('id', 0);
        $copy = $req->getParam('copy', 0);

        if ($id) {
            $row = $this->legalacts->getOne($id);
            if (!($row instanceof Zend_Db_Table_Row)) {
                throw new Exception('Podany rekord nie istnieje');
            }
            $this->view->data = $row->toArray();
            $this->setDetailedSection('Edytuj akt prawny');
        } else if ($copy) {
            $row = $this->legalacts->getOne($copy);
            if ($row instanceof Zend_Db_Table_Row) {
                $row = $row->toArray();
                unset($row['id']);
                $row['name'] = $row['name'] . ' KOPIA';
                $this->view->data = $row;
            }
            $this->setDetailedSection('Dodaj akt prawny');
        } else {
            $this->setDetailedSection('Dodaj akt prawny');
        }
    }

    public function checkexistAction()
    {
        $this->view->ajaxModal = 1;

        $req = $this->getRequest();
        $name = $req->getParam('name', 0);
        $id = $req->getParam('id', 0) * 1;

        $row = $this->legalacts->fetchRow(array(
            'id <> ?' => $id,
            'name LIKE ?' => addslashes(preg_replace('/\s+/', ' ', trim($name))),
        ));
        if ($row->id > 0) {
            echo('0');
        } else {
            echo('1');
        }

        die();
    }

    public function hqImportNewAction()
    {
        $remoteUniqueIds = Application_Service_Utilities::apiCall('hq_data', 'api/unique-legal-acts', null);
        
        $uniqueIds = $this->legalacts->getList(['unique_id IS NOT NULL']);

        $uniqueIds = array_unique(Application_Service_Utilities::getValues($uniqueIds, 'unique_id'));

        $elementsToImport = (array_diff($remoteUniqueIds, $uniqueIds));
        $toImport = array_chunk($elementsToImport, 10000);
        foreach ($toImport as $import){
            $result = Application_Service_Utilities::apiCall('hq_data', 'api/export-legal-acts', ['uniqueIds' => $import]);
            $this->legalacts->importFromJson($result);
        } 
        
        $this->flashMessage('success', 'Zaktualizowano o nowe elementy');

        $this->_redirect('/legalacts');
    }

    public function saveAction()
    {
        try {

            $req = $this->getRequest();
            $this->legalacts->save($req->getParams());
        } catch(Application_SubscriptionOverLimitException $x){
            $this->_redirect('subscription/limit');
        } catch (Exception $e) {
            throw new Exception('Proba zapisu danych nie powiodla sie');
        }
        $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        if ($req->getParams()['addAnother'] == 1) {
            $this->_redirect('/legalacts/update');
        } else {
            $this->_redirect('/legalacts');
        }
    }

    public function delAction()
    {
        try {
            $req = $this->getRequest();
            $id = $req->getParam('id', 0);
            $this->legalacts->remove($id);
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Zmiany zostały poprawnie zapisane'));
        } catch (Exception $e) {
            $this->_helper->getHelper('flashMessenger')->addMessage($this->showMessage('Proba skasowania zakonczyla sie bledem', 'danger'));
        }

        $this->_redirect('/legalacts');
    }

    public function delcheckedAction()
    {
        foreach ($_POST AS $poster => $val) {
            $poster = str_replace('id', '', $poster) * 1;
            if ($poster > 0) {
                try {
                    $this->legalacts->remove($poster);
                } catch (Exception $e) {
                }
            }
        }

        $this->_redirect('/legalacts');
    }
}