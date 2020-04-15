<?php

class Application_Service_Operation
{
    const OPERATION_IMPORTANT = 1;
    const OPERATION_IRREVELANT = 2;

    /** @var int|null */
    protected $operationId;

    /** @var int|null */
    protected $operationType;

    /** @var Zend_Db_Adapter_Abstract */
    protected $db;

    public function __construct()
    {
        $this->repoOperationsModel = Application_Service_Utilities::getModel('Repooperations');
        $this->db = $this->repoOperationsModel->getAdapter();
    }

    /**
     * Should be called at first repository change
     *
     * @return int|null
     */
    public function getOperationId() {
        if ($this->operationId === null) {
            $this->operationId = $this->createOperation();
            $this->operation = $this->repoOperationsModel->find($this->operationId);
        }

        return $this->operationId;
    }

    public function getOperationType()
    {
        if ($this->operationType === null) {
            Throw new Exception('Repository critical error');
        }

        return $this->operationType;
    }

    public function operationBegin($operationType, $transaction = true)
    {
        if ($this->operationId !== null) {
            Throw new Exception('Repository critical error');
        }
        $this->operationType = (int) $operationType;

        if ($transaction) {
            $this->db->beginTransaction();
        }
    }

    /**
     * @param $subjectType
     * @param $subjectId
     */
    public function operationComplete($subjectType, $subjectId, $transaction = true)
    {
        Application_Service_Repository::getInstance()->eventOperationComplete();
        Application_Service_Documents::getInstance()->eventOperationComplete();

        $operationId = null;

        // any changes made to versioned objects
        if ($this->operationId) {
            $operationId = $this->operationId;

            $this->repoOperationsModel->update(array(
                'subject_operation' => $subjectType,
                'subject_id' => $subjectId,
            ), array('id = ?' => $operationId));

            $this->operationId = null;
        }

        if ($transaction) {
            $this->db->commit();
        }

        return $operationId;
    }

    public function operationFailed($subjectType)
    {
        $this->repoOperationsModel->update(array(
            'subject_operation' => $subjectType .' FAILED',
            'subject_id' => null,
        ), array('id = ?' => $this->operationId));

        $this->operationId = null;

        $this->db->rollBack();
    }

    protected function createOperation()
    {
        return $this->repoOperationsModel->insert(array(
            'type' => $this->getOperationType(),
            'author_id' => Application_Service_Repository::getInstance()->getCurrentUserId(),
            'subject_operation' => 'operation.unfinished',
            'subject_id' => null,
            'date' => date('Y-m-d H:i:s'),
        ));
    }
}