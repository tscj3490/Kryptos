<?php
// This file is not a CODE, it makes no sense and won't run or validate
// Its AST serves IDE as DATA source to make advanced type inference decisions.
// hint from https://confluence.jetbrains.com/display/PhpStorm/PhpStorm+Advanced+Metadata

namespace PHPSTORM_META {
    $STATIC_METHOD_TYPES = [ // we make sections for scopes
        \Application_Service_Utilities::getModel('') => [ // STATIC call key to make static (1) & dynamic (2) calls work
            "AllUsers" instanceof Application_Model_AllUsers,
            "Applications" instanceof Application_Model_Applications,
            "ApplicationsModules" instanceof Application_Model_ApplicationsModules,
            "AplikacjeZabezpieczenia" instanceof Application_Model_ApplicationsZabezpieczenia,
            "Arrivals" instanceof Application_Model_Arrivals,
            "Articles" instanceof Application_Model_Articles,
            "AuditMethods" instanceof Application_Model_AuditMethods,
            "Audits" instanceof Application_Model_Audits,
            "AuditsZbiory" instanceof Application_Model_AuditsZbiory,
            "Banks" instanceof Application_Model_Banks,
            "Budynki" instanceof Application_Model_Budynki,
            "ChatMessages" instanceof Application_Model_ChatMessages,
            "ChatRooms" instanceof Application_Model_ChatRooms,
            "ChatRoomsUsers" instanceof Application_Model_ChatRoomsUsers,
            "ChatUsers" instanceof Application_Model_ChatUsers,
            "Companies" instanceof Application_Model_Companies,
            "Companiesnew" instanceof Application_Model_Companiesnew,
            "CompanyEmployees" instanceof Application_Model_CompanyEmployees,
            "Computer" instanceof Application_Model_Computer,
            "Contacts" instanceof Application_Model_Contacts,
            "CourseCategories" instanceof Application_Model_CourseCategories,
            "Courses" instanceof Application_Model_Courses,
            "CoursesServer" instanceof Application_Model_CoursesServer,
            "CoursesPages" instanceof Application_Model_CoursesPages,
            "CoursesSessions" instanceof Application_Model_CoursesSessions,
            "Cron" instanceof Application_Model_Cron,
            "DataTransfers" instanceof Application_Model_DataTransfers,
            "DataTransfersFielditemsfields" instanceof Application_Model_DataTransfersFielditemsfields,
            "DataTransfersFielditemspersonjoines" instanceof Application_Model_DataTransfersFielditemspersonjoines,
            "DataTransfersFielditemspersons" instanceof Application_Model_DataTransfersFielditemspersons,
            "DataTransfersFielditemspersontypes" instanceof Application_Model_DataTransfersFielditemspersontypes,
            "DataTransfersZbioryFielditems" instanceof Application_Model_DataTransfersZbioryFielditems,
            "Doc" instanceof Application_Model_Doc,
            "DocHistory" instanceof Application_Model_DocHistory,
            "DocSerie" instanceof Application_Model_DocSerie,
            "DocSzablony" instanceof Application_Model_DocSzablony,
            "DocumentationLogs" instanceof Application_Model_DocumentationLogs,
            "Documents" instanceof Application_Model_Documents,
            "DocumentsAttachments" instanceof Application_Model_DocumentsAttachments,
            "DocumentsFiles" instanceof Application_Model_DocumentsFiles,
            "DocumentsFilesUsers" instanceof Application_Model_DocumentsFilesUsers,
            "DocumentsFilesVersions" instanceof Application_Model_DocumentsFilesVersions,
            "DocumentsPending" instanceof Application_Model_DocumentsPending,
            "DocumentsRepoObjects" instanceof Application_Model_DocumentsRepoObjects,
            "DocumentsVersioned" instanceof Application_Model_DocumentsVersioned,
            "DocumentsVersionedVersions" instanceof Application_Model_DocumentsVersionedVersions,
            "Documenttemplates" instanceof Application_Model_Documenttemplates,
            "Documenttemplatesosoby" instanceof Application_Model_Documenttemplatesosoby,
            "Dokzszab" instanceof Application_Model_Dokzszab,
            "DokzszabSzablony" instanceof Application_Model_DokzszabSzablony,
            "Entities" instanceof Application_Model_Entities,
            "Events" instanceof Application_Model_Events,
            "Eventscars" instanceof Application_Model_Eventscars,
            "Eventscompanies" instanceof Application_Model_Eventscompanies,
            "Eventsnumbers" instanceof Application_Model_Eventsnumbers,
            "Eventsnumberstypes" instanceof Application_Model_Eventsnumberstypes,
            "Eventspersons" instanceof Application_Model_Eventspersons,
            "Eventspersonstypes" instanceof Application_Model_Eventspersonstypes,
            "EwdZDOsoby" instanceof Application_Model_EwdZDOsoby,
            "EwidencjaZrodelDanych" instanceof Application_Model_EwidencjaZrodelDanych,
            "ExamCategories" instanceof Application_Model_ExamCategories,
            "Exams" instanceof Application_Model_Exams,
            "ExamsQuestions" instanceof Application_Model_ExamsQuestions,
            "ExamsQuestionsAnswers" instanceof Application_Model_ExamsQuestionsAnswers,
            "ExamsSessions" instanceof Application_Model_ExamsSessions,
            "Fieldcategories" instanceof Application_Model_Fieldcategories,
            "Fieldgroups" instanceof Application_Model_Fieldgroups,
            "Fielditems" instanceof Application_Model_Fielditems,
            "Fielditemscategories" instanceof Application_Model_Fielditemscategories,
            "Fielditemsfields" instanceof Application_Model_Fielditemsfields,
            "Fielditemspersonjoines" instanceof Application_Model_Fielditemspersonjoines,
            "Fielditemspersons" instanceof Application_Model_Fielditemspersons,
            "Fielditemspersontypes" instanceof Application_Model_Fielditemspersontypes,
            "Fields" instanceof Application_Model_Fields,
            "Fieldscategories" instanceof Application_Model_Fieldscategories,
            "Files" instanceof Application_Model_Files,
            "FilesExternal" instanceof Application_Model_FilesExternal,
            "FileSources" instanceof Application_Model_FileSources,
            "Groups" instanceof Application_Model_Groups,
            "GeneratorValues" instanceof Application_Model_GeneratorValues,
            "Incident" instanceof Application_Model_Incident,
            "Inspections" instanceof Application_Model_Inspections,
            "InspectionsActivities" instanceof Application_Model_InspectionsActivities,
            "InspectionsNonCompilances" instanceof Application_Model_InspectionsNonCompilances,
            "InspectionsNonCompilancesFiles" instanceof Application_Model_InspectionsNonCompilancesFiles,
            "Klucze" instanceof Application_Model_Klucze,
            "KomunikatOsoba" instanceof Application_Model_KomunikatOsoba,
            "KomunikatRola" instanceof Application_Model_KomunikatRola,
            "Komunikaty" instanceof Application_Model_Komunikaty,
            "KontaBankowe" instanceof Application_Model_KontaBankowe,
            "KontaBankoweOsoby" instanceof Application_Model_KontaBankoweOsoby,
            "Kopiezapasowe" instanceof Application_Model_Kopiezapasowe,
            "Legalacts" instanceof Application_Model_Legalacts,
            "Logi" instanceof Application_Model_Logi,
            "Messages" instanceof Application_Model_Messages,
            "MessagesAttachments" instanceof Application_Model_MessagesAttachments,
            "MessagesTags" instanceof Application_Model_MessagesTags,
            "MessageTag" instanceof Application_Model_MessageTag,
            "Notes" instanceof Application_Model_Notes,
            "Notifications" instanceof Application_Model_Notifications,
            "NotificationsServer" instanceof Application_Model_NotificationsServer,
            "Numberingschemes" instanceof Application_Model_Numberingschemes,
            "OperationalSystems" instanceof Application_Model_OperationalSystems,
            "Osoby" instanceof Application_Model_Osoby,
            "Osobydorole" instanceof Application_Model_Osobydorole,
            "Osobyzbiory" instanceof Application_Model_Osobyzbiory,
            "OsobyGroups" instanceof Application_Model_OsobyGroups,
            "OsobyPermissions" instanceof Application_Model_OsobyPermissions,
            "OtherActivities" instanceof Application_Model_OtherActivities,
            "OperationalSystems" instanceof Application_Model_OperationalSystems,
            "Pages" instanceof Application_Model_Pages,
            "Persons" instanceof Application_Model_Persons,
            "Persontypes" instanceof Application_Model_Persontypes,
            "Permissions" instanceof Application_Model_Permissions,
            "Pliki" instanceof Application_Model_Pliki,
            "PlikOsoba" instanceof Application_Model_PlikOsoba,
            "PublicRegistry" instanceof Application_Model_PublicRegistry,
            "Podpisy" instanceof Application_Model_Podpisy,
            "PodpisyOsoby" instanceof Application_Model_PodpisyOsoby,
            "Pomieszczenia" instanceof Application_Model_Pomieszczenia,
            "Pomieszczeniadozbiory" instanceof Application_Model_Pomieszczeniadozbiory,
            "PomieszczeniaZabezpieczenia" instanceof Application_Model_PomieszczeniaZabezpieczenia,
            "Proposals" instanceof Application_Model_Proposals,
            "ProposalsItems" instanceof Application_Model_ProposalsItems,
            "Registry" instanceof Application_Model_Registry,
            "RegistryAssignees" instanceof \Application_Model_RegistryAssignees,
            "RegistryDocumentsTemplates" instanceof \Application_Model_RegistryDocumentsTemplates,
            "RegistryEntities" instanceof \Application_Model_RegistryEntities,
            "RegistryEntitiesDictionary" instanceof \Application_Model_RegistryEntitiesDictionary,
            "RegistryEntries" instanceof \Application_Model_RegistryEntries,
            "RegistryEntriesAssignees" instanceof \Application_Model_RegistryEntriesAssignees,
            "RegistryEntriesDocuments" instanceof \Application_Model_RegistryEntriesDocuments,
            "RegistryEntriesEntities" instanceof \Application_Model_RegistryEntriesEntities,
            "RegistryEntriesEntitiesDate" instanceof \Application_Model_RegistryEntriesEntitiesDate,
            "RegistryEntriesEntitiesDateTime" instanceof \Application_Model_RegistryEntriesEntitiesDateTime,
            "RegistryEntriesEntitiesInt" instanceof \Application_Model_RegistryEntriesEntitiesInt,
            "RegistryEntriesEntitiesText" instanceof \Application_Model_RegistryEntriesEntitiesText,
            "RegistryEntriesEntitiesVarchar" instanceof \Application_Model_RegistryEntriesEntitiesVarchar,
            "RegistryPermissions" instanceof \Application_Model_RegistryPermissions,
            "RegistryRoles" instanceof \Application_Model_RegistryRoles,
            "RegistryRolesPermissions" instanceof \Application_Model_RegistryRolesPermissions,
            "RegistryTemplates" instanceof \Application_Model_RegistryTemplates,
            "RegistryService" instanceof \Application_Service_Registry,
            "RepoBudynekNazwa" instanceof Application_Model_RepoBudynekNazwa,
            "RepoDocumenttemplate" instanceof Application_Model_RepoDocumenttemplate,
            "Repohistory" instanceof Application_Model_Repohistory,
            "RepoKlucz" instanceof Application_Model_RepoKlucz,
            "RepoNumberingscheme" instanceof Application_Model_RepoNumberingscheme,
            "Repooperations" instanceof Application_Model_Repooperations,
            "RepoOsobaImie" instanceof Application_Model_RepoOsobaImie,
            "RepoOsobaLogin" instanceof Application_Model_RepoOsobaLogin,
            "RepoOsobaNazwisko" instanceof Application_Model_RepoOsobaNazwisko,
            "RepoOsobaStanowisko" instanceof Application_Model_RepoOsobaStanowisko,
            "RepoPomieszczenie" instanceof Application_Model_RepoPomieszczenie,
            "RepoSet" instanceof Application_Model_RepoSet,
            "RepoSetData" instanceof Application_Model_RepoSetData,
            "RepoUpowaznienie" instanceof Application_Model_RepoUpowaznienie,
            "RepoZbiorNazwa" instanceof Application_Model_RepoZbiorNazwa,
            "RodzajeDanychOsobowych" instanceof Application_Model_RodzajeDanychOsobowych,
            "Role" instanceof Application_Model_Role,
            "Session" instanceof Application_Model_Session,
            "Settings" instanceof Application_Model_Settings,
            "Share" instanceof Application_Model_Share,
            "SharedUsersServer" instanceof \Application_Model_SharedUsersServer,
            "SharedUsersGroupsServer" instanceof \Application_Model_SharedUsersGroupsServer,
            "SharedUsersSessionsServer" instanceof \Application_Model_SharedUsersSessionsServer,
            "Sites" instanceof Application_Model_Sites,
            "StorageDocuments" instanceof Application_Model_StorageDocuments,
            "StorageTasks" instanceof Application_Model_StorageTasks,
            "Substitutions" instanceof Application_Model_Substitutions,
            "Szablony" instanceof Application_Model_Szablony,
            "SZbioryPola" instanceof Application_Model_SZbioryPola,
            "Tasks" instanceof Application_Model_Tasks,
            "TasksUsers" instanceof Application_Model_TasksUsers,
            "Tickets" instanceof Application_Model_Tickets,
            "TicketsOperations" instanceof Application_Model_TicketsOperations,
            "TicketsRoles" instanceof Application_Model_TicketsRoles,
            "TicketsRolesPermissions" instanceof Application_Model_TicketsRolesPermissions,
            "TicketsStatuses" instanceof Application_Model_TicketsStatuses,
            "TicketsTypes" instanceof Application_Model_TicketsTypes,
            "TicketsAssignees" instanceof Application_Model_TicketsAssignees,
            "TicketsGroupsAssignees" instanceof Application_Model_TicketsGroupsAssignees,
            "Transfers" instanceof Application_Model_Transfers,
            "Transferszbiory" instanceof Application_Model_Transferszbiory,
            "UiBoxes" instanceof Application_Model_UiBoxes,
            "UiBoxesDirectives" instanceof Application_Model_UiBoxesDirectives,
            "UiDirectives" instanceof Application_Model_UiDirectives,
            "UiSections" instanceof Application_Model_UiSections,
            "UiSectionsBoxes" instanceof Application_Model_UiSectionsBoxes,
            "UpdateDatabases" instanceof Application_Model_UpdateDatabases,
            "Upowaznienia" instanceof Application_Model_Upowaznienia,
            "Users" instanceof Application_Model_Users,
            "UserSignatures" instanceof Application_Model_UserSignatures,
            "Zabezpieczenia" instanceof Application_Model_Zabezpieczenia,
            "ZabezpieczeniaObjects" instanceof Application_Model_ZabezpieczeniaObjects,
            "Zastepstwa" instanceof Application_Model_Zastepstwa,
            "Zbiory" instanceof Application_Model_Zbiory,
            "ZbioryApplications" instanceof Application_Model_ZbioryApplications,
            "Zbioryfielditems" instanceof Application_Model_Zbioryfielditems,
            "Zbioryfielditemsfields" instanceof Application_Model_Zbioryfielditemsfields,
            "Zbioryfielditemspersonjoines" instanceof Application_Model_Zbioryfielditemspersonjoines,
            "Zbioryfielditemspersons" instanceof Application_Model_Zbioryfielditemspersons,
            "Zbioryfielditemspersontypes" instanceof Application_Model_Zbioryfielditemspersontypes,
            "ZbioryGroupItems" instanceof Application_Model_ZbioryGroupItems,
            "ZbioryOsobaPerson" instanceof Application_Model_ZbioryOsobaPerson,
            "ZbioryPersonFields" instanceof Application_Model_ZbioryPersonFields,
            "ZbioryPersonGroupType" instanceof Application_Model_ZbioryPersonGroupType,
            "ZbioryPersonTemplateType" instanceof Application_Model_ZbioryPersonTemplateType,
            "ZbioryPola" instanceof Application_Model_ZbioryPola,
            "ZbioryZabezpieczenia" instanceof Application_Model_ZbioryZabezpieczenia,
        ],
    ];
}