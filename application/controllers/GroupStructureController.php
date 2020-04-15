<?php

class GroupStructureController extends Muzyka_Admin
{
    /** @var Application_Model_Groups */
    protected $groupsModel;

    protected $osobyGroupsModel;

    protected $baseUrl = '/groupsStructure';

    public function init()
    {
        parent::init();

        $this->groupsModel = Application_Service_Utilities::getModel('Groups');
        $this->osobyGroupsModel = Application_Service_Utilities::getModel('OsobyGroups');


        Zend_Layout::getMvcInstance()->assign('section', 'Struktura pracowników');
        $this->view->baseUrl = $this->baseUrl;
    }

    public static function getPermissionsSettings() {
        $baseIssetCheck = array(
            'function' => 'issetAccess',
            'params' => array('id'),
            'permissions' => array(
                1 => array('perm/groups/create'),
                2 => array('perm/groups/update'),
            ),
        );

        $settings = array(
            'modules' => array(
                'groups' => array(
                    'label' => 'Grupy użytkowników',
                    'permissions' => array(
                    ),
                ),
            ),
            'nodes' => array(
                'groups' => array(
                    '_default' => array(
                        'permissions' => array('user/superadmin'),
                    ),

                    'index' => array(
                        'permissions' => array('perm/groups'),
                    ),
                ),
            )
        );

        return $settings;
    }

    public function indexAction()
    {
        $this->setDetailedSection('Lista grup osób');

        $data = $this->groupsModel->getList();
        $osobyGroups = $this->osobyGroupsModel->getList();
        $this->osobyGroupsModel->loadData(['osoba'], $osobyGroups);

        $this->groupsModel->loadData(['group'], $data);
        $groups = array();
        foreach ($osobyGroups as $o){
            $groups[$o['group_id']] = $o;
        }

        foreach ($$data as $item) {
            $item['subs'] = array();
            $indexedItems[$item['id']] = (object) $item;
        }
        
        $topLevel = array();
        foreach ($indexedItems as $item) {
            if ($item->parent_id == 0) {
                $topLevel[] = $item;
            } else {
                $indexedItems[$item->parent_id]->subs[] = $item;
            }
        }

        $this->view->structure = $this->renderTree($data, $groups);
    }

    function renderTree($items, $groups)
    {
        $render = '<ul>';

        foreach ($items as $item) {
            $render .= '<li>' . $item->name;

            $nestedPeople = $groups[$item->id];
                $render .= '<ul>';
                foreach($nestedPeople as $np){
                    if($np->id){
                        $render .= '<li>'.$np->nazwisko.' '.$np->imie.'</li>';
                    }
                }

                $render .= "</ul>";

            if (!empty($item->subs)) {
                $render .= $this->renderTree($item->subs);
            }
            $render .= '</li>';
        }

        return $render . '</ul>';
    }

}