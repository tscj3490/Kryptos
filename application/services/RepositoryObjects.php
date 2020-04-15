<?php

class Application_Service_RepositoryObjects
{
    /** @var Application_Model_RepoSet */
    protected $repoSetModel;

    function __construct()
    {
        $repoKluczModel = new Application_Model_RepoKlucz;
        $repoUpowaznienieModel = Application_Service_Utilities::getModel('RepoUpowaznienie');

        $this->repoSetModel = new Application_Model_RepoSet;

        $this->_db = $repoUpowaznienieModel->getAdapter();

        $this->versionedObjects = array(
            'documenttemplate' => array(
                'id' => 1,
                'name' => 'documenttemplate',
                'versionModel' => new Application_Model_RepoDocumenttemplate,
                'updatedOn' => array(
                    'Application_Model_Documenttemplates',
                ),
                'categorize_key' => 'documenttemplate_id',
            ),
            'numberingscheme' => array(
                'id' => 13,
                'name' => 'numberingscheme',
                'versionModel' => new Application_Model_RepoNumberingscheme,
                'updatedOn' => array(
                    'Application_Model_Numberingschemes',
                ),
                'categorize_key' => 'numberingscheme_id',
            ),
            'osoba.imie' => array(
                'id' => 2,
                'name' => 'osoba.imie',
                'versionModel' => new Application_Model_RepoOsobaImie,
                'updatedOn' => array(
                    'Application_Model_Osoby',
                ),
                'categorize_key' => 'osoby_id',
            ),
            'osoba.nazwisko' => array(
                'id' => 3,
                'name' => 'osoba.nazwisko',
                'versionModel' => new Application_Model_RepoOsobaNazwisko,
                'updatedOn' => array(
                    'Application_Model_Osoby',
                ),
                'categorize_key' => 'osoby_id',
            ),
            'osoba.login' => array(
                'id' => 10,
                'name' => 'osoba.login',
                'versionModel' => new Application_Model_RepoOsobaLogin,
                'updatedOn' => array(
                    'Application_Model_Osoby',
                ),
                'categorize_key' => 'osoby_id',
            ),
            'osoba.stanowisko' => array(
                'id' => 4,
                'name' => 'osoba.stanowisko',
                'versionModel' => new Application_Model_RepoOsobaStanowisko,
                'updatedOn' => array(
                    'Application_Model_Osoby',
                ),
                'categorize_key' => 'osoby_id',
            ),
            'klucz' => array(
                'id' => 5,
                'name' => 'klucz',
                'versionModel' => $repoKluczModel,
                'updatedOn' => array(
                    'Application_Model_Klucze',
                ),
                'categorize_key' => 'id',
            ),
            'pomieszczenie.nazwa' => array(
                'id' => 6,
                'name' => 'pomieszczenie.nazwa',
                'versionModel' => new Application_Model_RepoPomieszczenie,
                'updatedOn' => array(
                    'Application_Model_Pomieszczenia',
                ),
                'categorize_key' => 'pomieszczenia_id',
            ),
            'budynek.nazwa' => array(
                'id' => 7,
                'name' => 'budynek.nazwa',
                'versionModel' => new Application_Model_RepoBudynekNazwa,
                'updatedOn' => array(
                    'Application_Model_Budynki',
                ),
                'categorize_key' => 'budynki_id',
            ),
            'upowaznienie' => array(
                'id' => 8,
                'name' => 'upowaznienie',
                'versionModel' => $repoUpowaznienieModel,
                'updatedOn' => array(
                    'Application_Model_Upowaznienia',
                ),
                'categorize_key' => 'id',
            ),
            'zbior.nazwa' => array(
                'id' => 9,
                'name' => 'zbior.nazwa',
                'versionModel' => new Application_Model_RepoZbiorNazwa,
                'updatedOn' => array(
                    'Application_Model_Zbiory',
                ),
                'categorize_key' => 'zbiory_id',
            ),
            'set.upowaznienia' => array(
                'id' => 11,
                'name' => 'set.upowaznienia',
                'versionModel' => $this->repoSetModel,
                'type' => 'set',
                'function' => 'recalculateSet',
                'config' => array(
                    'baseModel' => $repoUpowaznienieModel,
                    'baseIndex' => 'osoby_id',
                    'basedOn' => 'upowaznienie',
                    'baseQuery' => array($this, 'getRepoSetUpowaznieniaBaseQuery'),
                    'subjectsQuery' => array($this, 'getRepoSetUpowaznieniaSubjectsQuery'),
                ),
                'updatedOnComplete' => true,
                'categorize_key' => 'id',
                'updatedOnRemove' => array(
                    'Application_Model_Zbiory',
                ),
            ),
            'set.klucze' => array(
                'id' => 12,
                'name' => 'set.klucze',
                'versionModel' => new Application_Model_RepoSet,
                'type' => 'set',
                'function' => 'recalculateSet',
                'config' => array(
                    'realModel' => new Application_Model_Klucze,
                    'baseModel' => $repoKluczModel,
                    'baseIndex' => 'osoby_id',
                    'objectIndex' => 'zbiory_id',
                    'basedOn' => 'klucz',
                    'baseQuery' => array($this, 'getRepoSetKluczeBaseQuery'),
                    'subjectsQuery' => array($this, 'getRepoSetSubjectsQuery'),
                ),
                'updatedOnRemove' => array(
                    'Application_Model_Budynki',
                    'Application_Model_Pomieszczenia',
                ),
                'updatedOnComplete' => true,
                'categorize_key' => 'id',
            ),
            'object.document' => array(
                'id' => 14,
                'name' => 'object.document',
                'versionModel' => new Application_Model_Documents,
                'type' => 'object',
                'function' => 'recalculateSet',
                'config' => array(
                    'realModel' => new Application_Model_Klucze,
                    'baseModel' => $repoKluczModel,
                    'baseIndex' => 'osoby_id',
                    'objectIndex' => 'zbiory_id',
                    'basedOn' => 'klucz',
                    'baseQuery' => array($this, 'getRepoSetKluczeBaseQuery'),
                    'subjectsQuery' => array($this, 'getRepoSetSubjectsQuery'),
                ),
                'updatedOnRemove' => array(
                    'Application_Model_Budynki',
                    'Application_Model_Pomieszczenia',
                ),
                'updatedOnComplete' => true,
                'categorize_key' => 'id',
            ),
        );
    }

    public function getRepoSetUpowaznieniaBaseQuery($subjectIds)
    {
        return $this->_db->select()
            ->from(array('u' => 'upowaznienia'), array('subject_id' => 'osoby_id', 'subject_object_id' => 'zbiory_id'))
            ->joinInner(array('z' => 'zbiory'), 'z.id = u.zbiory_id AND z.usunieta = 0', array())
            ->where('u.osoby_id IN (?)', $subjectIds)
            ->where('u.czytanie = 1 || u.pozyskiwanie = 1 || u.wprowadzanie = 1 || u.modyfikacja = 1 || u.usuwanie = 1')
            ->order('u.zbiory_id')
            ->query();
    }

    public function getRepoSetSubjectsQuery($objectConfig, $params)
    {
        $select = $this->_db->select()
            ->from(array('u' => $objectConfig['config']['realModel']->info('name')), array('subject_id' => $objectConfig['config']['baseIndex']))
            ->where(sprintf('u.%s IN (?)', $objectConfig['objectIndex']), $params);

        return $select->query();
    }

    public function getRepoSetKluczeBaseQuery($subjectIds)
    {
        return $this->_db->select()
            ->from(array('u' => 'klucze'), array('subject_id' => 'osoba_id', 'subject_object_id' => 'pomieszczenia_id'))
            ->joinInner(array('p' => 'pomieszczenia'), 'p.id = u.pomieszczenia_id', array())
            ->where('u.osoba_id IN (?)', $subjectIds)
            ->order('u.pomieszczenia_id')
            ->query();
    }

    public function findByTableName($name)
    {
        foreach ($this->versionedObjects as $object) {
            if ($object['versionModel']->info('name') === $name) {
                return $object;
            }
        }

        return false;
    }

    public function findById($id)
    {
        foreach ($this->versionedObjects as $object) {
            if ($object['id'] === (int) $id) {
                return $object;
            }
        }

        return false;
    }

    /**
     * @param $osobyIds
     * @param $documenttemplateIds
     * @param $numberingschemeIds
     * @return Application_Service_RepositoryRetreiver
     * @throws Exception
     */
    public function prepareRetreiver($osobyIds, $documenttemplateIds, $numberingschemeIds)
    {
        $repositoryRetreiver = Application_Service_RepositoryRetreiver::getInstance();

        $repositoryRetreiver->load('documenttemplate', array('documenttemplate_id' => $documenttemplateIds));
        $repositoryRetreiver->load('numberingscheme', array('numberingscheme_id' => $numberingschemeIds));

        $repositoryRetreiver->load('osoba.imie', array('osoby_id' => $osobyIds));
        $repositoryRetreiver->load('osoba.nazwisko', array('osoby_id' => $osobyIds));
        $repositoryRetreiver->load('osoba.stanowisko', array('osoby_id' => $osobyIds));
        $repositoryRetreiver->load('osoba.login', array('osoby_id' => $osobyIds));
        $repositoryRetreiver->load('set.upowaznienia', array('subject_id' => $osobyIds, 'object_id' => 11));
        $repositoryRetreiver->load('set.klucze', array('subject_id' => $osobyIds, 'object_id' => 12));

        $repositoryRetreiver->load('klucz', array('osoby_id' => $osobyIds));
        $pomieszczeniaIds = array();
        $klucze = $repositoryRetreiver->fetchAll('klucz');
        foreach ($klucze as $klucz) {
            $pomieszczeniaIds[] = $klucz['pomieszczenia_id'];
        }
        $repositoryRetreiver->load('pomieszczenie.nazwa', array('pomieszczenia_id' => $pomieszczeniaIds));
        $budynkiIds = array();
        $pomieszczenia = $repositoryRetreiver->fetchAll('pomieszczenie.nazwa');
        foreach ($pomieszczenia as $pomieszczenie) {
            $budynkiIds[] = $pomieszczenie['budynki_id'];
        }
        $repositoryRetreiver->load('budynek.nazwa', array('budynki_id' => $budynkiIds));
        $repositoryRetreiver->load('upowaznienie', array('osoby_id' => $osobyIds));
        $zbioryIds = array();
        $upowaznienia = $repositoryRetreiver->fetchAll('upowaznienie');
        foreach ($upowaznienia as $upowaznienie) {
            $zbioryIds[] = $upowaznienie['zbiory_id'];
        }
        $repositoryRetreiver->load('zbior.nazwa', array('zbiory_id' => $zbioryIds));

        return $repositoryRetreiver;
    }
}