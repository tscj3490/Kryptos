<?php

class Application_Service_Substitutions
{
    /** @var self */
    protected static $_instance = null;

    /** @var Application_Model_Upowaznienia */
    protected $upowaznieniaModel;

    /** @var Application_Model_Osoby */
    protected $osobyModel;

    /** @var Application_Model_Klucze */
    protected $kluczeModel;

    /** @var Application_Service_Repository */
    protected $repositoryService;

    /** @var Application_Model_Substitutions */
    protected $substitutionModel;

    /** @var Application_Model_Repohistory */
    protected $repohistoryModel;

    /** @var Application_Model_RepoUpowaznienie */
    protected $repoUpowaznienieModel;

    /** @var Application_Model_RepoKlucz */
    protected $repoKluczeModel;

    private function __clone() {}

    public static function getInstance() { return null === self::$_instance ? (self::$_instance = new self()) : self::$_instance; }
    private function __construct()
    {
        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');
        $this->upowaznieniaModel = Application_Service_Utilities::getModel('Upowaznienia');
        $this->kluczeModel = Application_Service_Utilities::getModel('Klucze');
        $this->repositoryService = Application_Service_Repository::getInstance();
        $this->substitutionModel = Application_Service_Utilities::getModel('Substitutions');
        $this->repohistoryModel = Application_Service_Utilities::getModel('Repohistory');
        $this->repoUpowaznienieModel = Application_Service_Utilities::getModel('RepoUpowaznienie');
        $this->repoKluczeModel = Application_Service_Utilities::getModel('RepoKlucz');
    }

    /**
     * @param Application_Model_Substitutions $substitution
     */
    public function activateSubstitution($substitution)
    {
        $typyUpowaznien = array('czytanie', 'pozyskiwanie', 'wprowadzanie', 'modyfikacja', 'usuwanie');
        $sourceUser = $this->osobyModel->findOne($substitution->user);
        $substituteUser = $this->osobyModel->findOne($substitution->substitute);

        $upowaznieniaDb = $this->upowaznieniaModel->findBy(array('osoby_id IN (?)' => array($substitution->user, $substitution->substitute)));

        $upowaznienia = array();
        $modifiedUpowaznienia = array();
        foreach ($upowaznieniaDb as $upowaznienie) {
            $upowaznienia[$upowaznienie['osoby_id']][$upowaznienie['zbiory_id']] = array(
                'czytanie' => $upowaznienie['czytanie'],
                'pozyskiwanie' => $upowaznienie['pozyskiwanie'],
                'wprowadzanie' => $upowaznienie['wprowadzanie'],
                'modyfikacja' => $upowaznienie['modyfikacja'],
                'usuwanie' => $upowaznienie['usuwanie'],
            );
        }
        $sourceUpowaznienia = $upowaznienia[$substitution->user];
        $substituteUpowaznienia = $upowaznienia[$substitution->substitute];
        foreach ($sourceUpowaznienia as $zbiorId => $sourceUpowaznienie) {
            if (!isset($substituteUpowaznienia[$zbiorId])) {
                $modifiedUpowaznienia[$zbiorId] = $sourceUpowaznienie;
            } else {
                $modifiedUpowaznienia[$zbiorId] = $substituteUpowaznienia[$zbiorId];
                foreach ($typyUpowaznien as $typ) {
                    if ($sourceUpowaznienie[$typ]) {
                        $modifiedUpowaznienia[$zbiorId][$typ] = 1;
                    }
                }
            }
        }
    }

    /**
     * @param Application_Model_Substitutions $substitution
     */
    public function deactivateSubstitution($substitution)
    {
        $typyUpowaznien = array('czytanie', 'pozyskiwanie', 'wprowadzanie', 'modyfikacja', 'usuwanie');
        $sourceUser = $this->osobyModel->findOne($substitution->user);
        $substituteUser = $this->osobyModel->findOne($substitution->substitute);

        $otherSubstitutionsUsers = array();
        $userOtherSubstitutions = $this->substitutionModel->findBy(array(
            'substitute = ?' => $substitution->substitute,
            'status = ?' => Application_Model_Substitutions::STATUS_ACTIVE,
            'id <> ?' => $substitution->id,
        ));
        foreach ($userOtherSubstitutions as $otherSubstitution) {
            $otherSubstitutionsUsers[] = $otherSubstitution->user;
        }

        /**
         * KLUCZE START
         */

        $kluczeVersions = array();
        $kluczeToRemove = array();

        /** @var Application_Model_Repohistory[] $substitutionChangesKlucze */
        $substitutionChangesKlucze = $this->repohistoryModel->findBy(array(
            'operation_id = ?' => $substitution->operation,
            'object_id IN = ?' => array(5),
        ));
        foreach ($substitutionChangesKlucze as $repohistoryEntry) {
            $kluczeVersions[] = $repohistoryEntry->version_id;
        }
        $kluczeToDeactivate = $this->repoKluczeModel->find($kluczeVersions);

        $otherKluczeDb = $this->kluczeModel->findBy(array(
            'osoby_id = ?' => $otherSubstitutionsUsers,
        ));
        $currentKlucze = $this->getMergedKlucze($otherKluczeDb, $substitution->substitute);

        foreach ($kluczeToDeactivate as $repoKlucz) {
            if (!in_array($repoKlucz['pomieszczenia_id'], $currentKlucze)) {
                $kluczeToRemove[] = $repoKlucz['pomieszczenia_id'];
            }
        }

        /**
         * KLUCZE END
         */

        /**
         * UPOWAZNIENIA START
         */

        $upowaznieniaVersions = array();
        $currentUpowaznienia = array();

        $upowaznieniaDb = $this->upowaznieniaModel->findBy(array('osoby_id IN (?)' => array($substitution->substitute)));
        foreach ($upowaznieniaDb as $upowaznienieDb) {
            $currentUpowaznienia[$upowaznienieDb['zbiory_id']] = array(
                'czytanie' => $upowaznienieDb['czytanie'],
                'pozyskiwanie' => $upowaznienieDb['pozyskiwanie'],
                'wprowadzanie' => $upowaznienieDb['wprowadzanie'],
                'modyfikacja' => $upowaznienieDb['modyfikacja'],
                'usuwanie' => $upowaznienieDb['usuwanie'],
            );
        }

        /** @var Application_Model_Repohistory[] $substitutionChangesUpowaznienia */
        $substitutionChangesUpowaznienia = $this->repohistoryModel->findBy(array(
            'operation_id = ?' => $substitution->operation,
            'object_id IN = ?' => array(8),
        ));
        foreach ($substitutionChangesUpowaznienia as $repohistoryEntry) {
            $upowaznieniaVersions[] = $repohistoryEntry->version_id;
        }
        $upowaznienia = $this->repoUpowaznienieModel->find($upowaznieniaVersions);

        $otherUpowaznieniaDb = $this->upowaznieniaModel->findBy(array(
            'osoby_id = ?' => $otherSubstitutionsUsers,
        ));
        $otherUpowaznienia = $this->getMergedUpowaznienia($otherUpowaznieniaDb, $substitution->substitute);

        foreach ($upowaznienia as $repoUpowaznienie) {
            $zbiorId = $repoUpowaznienie['zbiory_id'];
            if (!in_array($zbiorId, $otherUpowaznienia)) {
                $currentUpowaznienia[$zbiorId] = array(
                    'czytanie' =>  0,
                    'pozyskiwanie' =>  0,
                    'wprowadzanie' => 0,
                    'modyfikacja' => 0,
                    'usuwanie' => 0,
                );
            } else {
                foreach ($typyUpowaznien as $typ) {
                    if (!$repoUpowaznienie[$typ]) {
                        $currentUpowaznienia[$zbiorId][$typ] = 0;
                    }
                }
            }
        }

        /**
         * UPOWAZNIENIA END
         */
    }

    protected function getMergedKlucze($kluczeDb)
    {
        $klucze = array();

        foreach ($kluczeDb as $klucz) {
            $klucze[$klucz['pomieszczenie_id']] = true;
        }

        return array_keys($klucze);
    }

    protected function getMergedUpowaznienia($upowaznieniaDb/*, $userId*/)
    {
        $typyUpowaznien = array('czytanie', 'pozyskiwanie', 'wprowadzanie', 'modyfikacja', 'usuwanie');
        $defaultRow = array('czytanie' => 0, 'pozyskiwanie' => 0, 'wprowadzanie' => 0, 'modyfikacja' => 0, 'usuwanie' => 0);
        $upowaznienia = array();
        $result = array();

        foreach ($upowaznieniaDb as $upowaznienie) {
            $zbiorId = $upowaznienie['zbiory_id'];
            if (!isset($upowaznienia[$zbiorId])) {
                $upowaznienia[$zbiorId] = $defaultRow;
            }
            foreach ($typyUpowaznien as $typ) {
                if ($upowaznienie[$typ]) {
                    $upowaznienia[$zbiorId][$typ] = 1;
                }
            }
        }

        return $upowaznienia;

        /*foreach ($upowaznienia as $zbiorId => $upowaznienieRow) {
            $result[] = array_merge($upowaznienieRow, array(
                'zbiory_id' => $zbiorId,
                'user_id' => $userId,
            ));
        }

        return $result;*/
    }
}