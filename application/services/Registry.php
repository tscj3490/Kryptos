<?php

class Application_Service_Registry
{
    /** @var self */
    protected static $_instance = null;

    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? new self : self::$_instance; }

    /**
     * @param Application_Service_EntityRow $entry
     * @param $entryData
     */
    public function entrySave($entry, $entryData)
    {
     
        Application_Service_Utilities::getModel('RegistryEntries')->save($entry);
        $entry->loadData(['registry']);

        foreach ($entry->registry->entities as $registryEntity) {
            $entity = $registryEntity->entity;
            $configData = $entity->config_data;
            $fieldName = sprintf('element_%s', $registryEntity->id);
            $values = $entryData[$fieldName];
            $uniqueIndex = [
                'entry_id = ?' => $entry->id,
                'registry_entity_id = ?' => $registryEntity->id
            ];
            $uniqueIndexFields = [
                'entry_id' => $entry->id,
                'registry_entity_id' => $registryEntity->id
            ];

            switch ($configData->type) {
                case "int":
                    $model = Application_Service_Utilities::getModel('RegistryEntriesEntitiesInt');
                    break;
                case "text":
                    $model = Application_Service_Utilities::getModel('RegistryEntriesEntitiesText');
                    break;
                case "string":
                    $model = Application_Service_Utilities::getModel('RegistryEntriesEntitiesVarchar');
                    break;
                case "date":
                    $model = Application_Service_Utilities::getModel('RegistryEntriesEntitiesDate');
                    break;
                case "datetime":
                    $model = Application_Service_Utilities::getModel('RegistryEntriesEntitiesDateTime');
                    break;
                case "file":
                    $filesService = Application_Service_Files::getInstance();
                    $fieldNameUploaded = $fieldName . '_uploaded';
                    $uploadedValues = $entryData[$fieldNameUploaded];
                    if ($registryEntity->is_multiple) {
                        $values = [];
                    }

                    if (!empty($uploadedValues)) {
                        $uploadedValues = json_decode($uploadedValues, true);
                        $fileNames = array(); 
                        if (!empty($uploadedValues)) {
                            foreach ($uploadedValues as $file) {
                                $fileUri = sprintf('uploads/default/%s', $file['uploadedUri']);
                                
                                $params = array();
                                if($entry->registry->system_name != ''){
                                    $params['subdirectory'] = $entry->registry->system_name;
                                }   
                                
                                $file = $filesService->create(Application_Service_Files::TYPE_REGISTRY_ATTACHMENT, $fileUri, $file['name'], null, $params);

                                $fileNames[] = $file['name'];
                                if ($registryEntity->is_multiple) {
                                    $values[] = $file->id;
                                } else {
                                    $values = $file->id;
                                }
                            }
                        }
                        
                        $entry->title = implode (", ", $fileNames);
                        Application_Service_Utilities::getModel('RegistryEntries')->save($entry);
                    }

                    $model = Application_Service_Utilities::getModel('RegistryEntriesEntitiesInt');
                    break;
                case "dictionary":
                    $model = Application_Service_Utilities::getModel('RegistryEntriesEntitiesInt');
                    break;
                case "entry":
                    $model = Application_Service_Utilities::getModel('RegistryEntriesEntitiesInt');
                    break;
                case "checkbox":
                    $model = Application_Service_Utilities::getModel('RegistryEntriesEntitiesInt');
                    break;
                default:
                    Throw new Exception('Invalid entity type ' . $configData->type);
            }

            $model->replaceEntries($uniqueIndexFields, $values);
        }

//        vdie($entry, $entryData);
    }

    /**
     * @param Application_Service_EntityRow $entry[]
     */
    public function entriesGetEntities($entries)
    { 
        Application_Service_Utilities::getModel('RegistryEntries')->loadData(['registry'], $entries);
       
        foreach ($entries as $key => $entry) {
             $this->entryGetEntities($entry);
        }
      
    }

    /**
     * @param Application_Service_EntityRow $entry
     */
    public function entryGetEntities($entry)
    {
        $entry->loadData(['registry']);

        $entry->entities_named = [];
        $entry->entities = [];

        foreach ($entry->registry->entities as $registryEntity) {
            $entity = $registryEntity->entity;
            $configData = $entity->config_data;
            $fieldName = sprintf('element_%s', $registryEntity->id);
            $uniqueIndex = [
                'entry_id' => $entry->id,
                'registry_entity_id' => $registryEntity->id
            ];
            //    echo $configData->type;
            /** @var Muzyka_DataModel $model */
            switch ($configData->type) {
                case "int":
                    $model = Application_Service_Utilities::getModel('RegistryEntriesEntitiesInt');
                    break;
                case "text":
                    $model = Application_Service_Utilities::getModel('RegistryEntriesEntitiesText');
                    break;
                case "string":
                    $model = Application_Service_Utilities::getModel('RegistryEntriesEntitiesVarchar');
                    break;
                case "date":
                    $model = Application_Service_Utilities::getModel('RegistryEntriesEntitiesDate');
                    break;
                case "datetime":
                    $model = Application_Service_Utilities::getModel('RegistryEntriesEntitiesDateTime');
                    break;
                case "file":
                    $model = Application_Service_Utilities::getModel('RegistryEntriesEntitiesInt');
                    break;
                case "dictionary":
                    $model = Application_Service_Utilities::getModel('RegistryEntriesEntitiesInt');
                    break;
                case "entry":
                    $model = Application_Service_Utilities::getModel('RegistryEntriesEntitiesInt');
                    break;
                case "checkbox":
                    $model = Application_Service_Utilities::getModel('RegistryEntriesEntitiesInt');
                    break;
                default:
                    Throw new Exception('Invalid entity type ' . $configData->type);
            }

            $list = $model->getList($uniqueIndex);

            if (empty($list)) {
                continue;
            }

            if ($configData->baseModel) {
                $baseModel = Application_Service_Utilities::getModel($configData->baseModel);
                $baseModel->injectObjectsCustom('value', 'base_object', 'id', [$baseModel->getBaseName() . '.id IN (?)' => null], $list, 'getList');
            }

            foreach ($list as &$listItem) {
                $listItem['entity'] = $entry->registry->entities_indexed[$listItem['registry_entity_id']];
                
            }

            if ($registryEntity->is_multiple) {
                $entry->entities[$registryEntity->id] = $list;
                if ($registryEntity->system_name) {
                    $entry->entities_named[$registryEntity->system_name] = $list;
                }
            } else {
                $entry->entities[$registryEntity->id] = isset($list[0]) ? $list[0] : null;

               
                if ($registryEntity->system_name) {
                    $entry->entities_named[$registryEntity->system_name] = isset($list[0]) ? $list[0] : null;
                }
            }
            
             if($configData->type == "file"){
                    $entry->entities[$registryEntity->id]->value = $entry->title;
                }
        }
       // die;
    }

    function getTemplateReport()
    {

    }

    public function getEntityId($registryName, $entityName)
    {
        return Application_Service_Utilities::getModel('RegistryEntities')->getOneByName($registryName, $entityName);
    }

    public function addAssignee($registry, $user, $role)
    {
        $registryAssigneesModel = Application_Service_Utilities::getModel('RegistryAssignees');
        $assigneeData = [
            'registry_id' => $registry->id,
            'user_id' => $user->id,
        ];
        $existedAssignee = $registryAssigneesModel->getOne($assigneeData);

        if ($existedAssignee) {
            return false;
        }

        $assigneeData['registry_role_id'] = $role->id;

        $assignee = $registryAssigneesModel->save($assigneeData);

        Application_Service_Events::getInstance()->trigger('registry.assignee.add', $assignee);

        return true;
    }

    public function removeAssignee($registry, $assigneeId)
    {
        $registryAssigneesModel = Application_Service_Utilities::getModel('RegistryAssignees');
        $assigneeData = [
            'registry_id' => $registry->id,
            'id' => $assigneeId,
        ];
        $existedAssignee = $registryAssigneesModel->getOne($assigneeData);

        if (!$existedAssignee) {
            return false;
        }

        $assignee = clone $existedAssignee;
        $existedAssignee->delete();

        Application_Service_Events::getInstance()->trigger('registry.assignee.remove', $assignee);

        return true;
    }

    public function removeRole($registry, $roleId)
    {
        $registryRolesModel = Application_Service_Utilities::getModel('RegistryRoles');
        $roleData = [
            'registry_id' => $registry->id,
            'id' => $roleId,
        ];
        $existedRole = $registryRolesModel->getOne($roleData);

        if (!$existedRole) {
            return false;
        }

        $role = clone $existedRole;
        $existedRole->delete();

        Application_Service_Events::getInstance()->trigger('registry.role.remove', $role);

        return true;
    }

    public function removeDocumentTemplate($registry, $documentTemplateId)
    {
        $registryModel = Application_Service_Utilities::getModel('RegistryDocumentsTemplates');
        $data = [
            'registry_id' => $registry->id,
            'id' => $documentTemplateId,
        ];
        $existedObject = $registryModel->getOne($data);

        if (!$existedObject) {
            return false;
        }

        $object = clone $existedObject;
        $existedObject->delete();

        Application_Service_Events::getInstance()->trigger('registry.document_template.remove', $object);

        return true;
    }

    public function removePermission($registry, $permissionId)
    {
        $registryPermissionsModel = Application_Service_Utilities::getModel('RegistryPermissions');
        $permissionData = [
            'registry_id' => $registry->id,
            'id' => $permissionId,
        ];
        $existedPermission = $registryPermissionsModel->getOne($permissionData);

        if (!$existedPermission) {
            return false;
        }

        $permission = clone $existedPermission;
        $existedPermission->delete();

        Application_Service_Events::getInstance()->trigger('registry.permission.remove', $permission);

        return true;
    }

    public function entryCreateDocument($entryId, $documentTemplateId)
    {
        $dateString = date('Y-m-d');
        $entry = Application_Service_Utilities::getModel('RegistryEntries')->getFull($entryId, true);
        $this->entryGetEntities($entry);
        $registry = Application_Service_Utilities::getModel('Registry')->getFull($entry->registry_id, true);
        $documentTemplate = Application_Service_Utilities::arrayFindOne($registry['documents_templates'], 'id', $documentTemplateId);

        $documentData = Application_Service_Utilities::stempl($documentTemplate->template->data, $entry->entities_named);

        $number = Application_Service_Utilities::getModel('RegistryEntriesDocuments')->getNextNumberIncrement($documentTemplate->numbering_scheme, $dateString);

        Application_Service_Utilities::getModel('RegistryEntriesDocuments')->save([
            'entry_id' => $entry->id,
            'document_template_id' => $documentTemplate->id,
            'author_id' => $documentTemplate->default_author_id,
            'number' => Application_Service_Utilities::getDocumentNumber($documentTemplate->numbering_scheme, $dateString, $number),
            'numbering_scheme_ordinal' => $number,
            'data' => $documentData,
        ]);

        return true;
    }

    public function entryUpdateDocuments($entryId)
    {
        $documents = Application_Service_Utilities::getModel('RegistryEntriesDocuments')->getListFull(['entry_id' => $entryId]);
        Application_Service_Utilities::getModel('RegistryDocumentsTemplates')->loadData(['template'], Application_Service_Utilities::getValues($documents, 'document_template'));

        foreach ($documents as $document) {
            $documentData = Application_Service_Utilities::stempl($document->document_template->template->data, $document->entry->entities_named);
            $document->data = $documentData;
            $document->save();
        }

        return true;
    }

    public function entryUpdateDocument($documentId)
    {
        $document = Application_Service_Utilities::getModel('RegistryEntriesDocuments')->getFull($documentId, true);
        $documentData = Application_Service_Utilities::stempl($document->document_template->template->data, $document->entry->entities_named);
        $document->data = $documentData;
        $document->save();

        return true;
    }
}