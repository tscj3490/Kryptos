<?php

class Application_Service_Proposals
{
    const TYPE_TECHNICAL_SUPPORT = 1;
    const TYPE_DOCUMENTS_VERSIONED_CORRECTION = 2;

    const STATUS_NEW = 1;
    const STATUS_CANCELLED = 2;
    const STATUS_CLOSED = 3;
    const STATUS_WAITING = 4;

    /** @var self */
    protected static $_instance = null;

    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? (self::$_instance = new self()) : self::$_instance; }

    /** @var Application_Model_Tickets */
    protected $ticketsModel;

    /** @var Application_Model_TicketsTypes */
    protected $ticketsTypesModel;

    /** @var Application_Model_TicketsOperations */
    protected $ticketsOperationsModel;

    /** @var Zend_Db_Adapter_Abstract */
    protected $db;

    /** @var Application_Model_Osoby */
    protected $osobyModel;

    /** @var Application_Model_Proposals */
    protected $proposalsModel;

    /** @var Application_Model_ProposalsItems */
    protected $proposalsItemsModel;

    protected $directory;

    public function __construct()
    {
        $this->ticketsModel = Application_Service_Utilities::getModel('Tickets');
        $this->ticketsTypesModel = Application_Service_Utilities::getModel('TicketsTypes');
        $this->ticketsOperationsModel = Application_Service_Utilities::getModel('TicketsOperations');

        $this->ticketsService = Application_Service_Tickets::getInstance();
        $this->osobyModel = Application_Service_Utilities::getModel('Osoby');
        $this->proposalsModel = Application_Service_Utilities::getModel('Proposals');
        $this->proposalsItemsModel = Application_Service_Utilities::getModel('ProposalsItems');

        $this->db = $this->ticketsModel->getAdapter();
    }

    /**
     * @param [] $data
     * @throws Exception
     */
    public function create($data)
    {
        $defaultData = array(
            'status_id' => Application_Service_ProposalsConst::STATUS_CREATED,
            'author_id' => Application_Service_Authorization::getInstance()->getUserId(),
        );
        $data = array_merge($defaultData, $data);

        try {
            $tasksModel = Application_Service_Utilities::getModel('Tasks');
            $tasksService = Application_Service_Tasks::getInstance();

            $ticketTypeProposal = $this->getTicketType($data['type_id']);

            $data['title'] = $ticketTypeProposal->name;

            $proposal = $this->proposalsModel->save($data);

            $itemData = [
                'object_id' => $data['object_id'],
            ];
            $proposalItem = $this->addItem($proposal, $itemData);

            $proposal['items'] = [$proposalItem];

            $ticket = $this->ticketsService->create([
                'topic' => sprintf('%s: %s %s', $ticketTypeProposal->name, $data['_item_data']['nazwisko'], $data['_item_data']['imie']),
                'content' => '',
                'type_id' => $ticketTypeProposal->id,
                'object_id' => $proposal->id,
                'context_user' => $data['object_id'],
            ]);
            $proposal['ticket'] = $ticket;

            Application_Service_Events::getInstance()->trigger('proposal.create', $proposal);
        } catch (Exception $e) {
            Throw $e;
        }

        return $proposal;
    }

    public function addItem($proposal, $itemData, $authorId = null)
    {
        if (!$authorId) {
            $authorId = Application_Service_Authorization::getInstance()->getUserId();
        }

        $itemData = array_merge([
            'type_id' => Application_Service_ProposalsConst::ITEM_TYPE_EMPLOYEE,
            'status_id' => Application_Service_ProposalsConst::ITEM_STATUS_PENDING,
            'proposal_id' => $proposal->id,
            'author_id' => $authorId,
        ], $itemData);

        $proposalItem = $this->proposalsItemsModel->save($itemData);

        return $proposalItem;
    }

    public function getTicketType($typeId)
    {
        return $this->ticketsTypesModel->getOne([
            'type = ?' => Application_Service_TicketsConst::TYPE_PROPOSAL,
            'object_id = ?' => Application_Service_ProposalsConst::TYPE_TYPES[$typeId]['ticket_object_id'],
        ]);
    }

    public function acceptItem($proposalItem)
    {
        $this->changeItemStatus($proposalItem, Application_Service_ProposalsConst::ITEM_STATUS_ACCEPTED);
    }

    public function rejectItem($proposalItemCurrent, $proposalObjectNew, $comment = null)
    {
        $proposalItemNew = $this->addItem($proposalItemCurrent->proposal, ['object_id' => $proposalObjectNew['id']]);

        $proposalItemCurrent->status_id = Application_Service_ProposalsConst::ITEM_STATUS_REJECTED;
        $proposalItemCurrent->save();

        Application_Service_Events::getInstance()->trigger('proposal.item.status.change', $proposalItemCurrent, ['new_item' => $proposalItemNew]);

        return $proposalItemNew;

        /*$ticketsService->addMessage($ticket, [
            'content' => 'Powód odrzucenia: ' . $comment
        ]);*/
    }

    public function forwardItem($proposalItemCurrent, $proposalObjectNew)
    {
        $proposalItemNew = $this->addItem($proposalItemCurrent->proposal, ['object_id' => $proposalObjectNew['id']]);

        $proposalItemCurrent->status_id = Application_Service_ProposalsConst::ITEM_STATUS_ACCEPTED;
        $proposalItemCurrent->save();

        Application_Service_Events::getInstance()->trigger('proposal.item.status.change', $proposalItemCurrent, ['new_item' => $proposalItemNew]);

        return $proposalItemNew;
    }

    public function changeItemStatus($proposalItem, $statusId)
    {
        try {
            $proposalItem->status_id = $statusId;
            $proposalItem->save();

            Application_Service_Events::getInstance()->trigger('proposal.item.status.change', $proposalItem);
        } catch (Exception $e) {
            Throw $e;
        }
    }
}
