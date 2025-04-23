<?php
use BitrixMainLoader;
use BitrixBizproc;

Loader::includeModule('bizproc');

$rootActivity = $this->GetRootActivity();
$substituteId = $rootActivity->GetVariable('substituteID');
$userId = $rootActivity->GetVariable('userID');

$this->WriteToTrackingService("Searching for workflows with user ID: " . $userId);

// Получаем ID документа
$documentId = $rootActivity->GetDocumentId();
[
    $entityTypeName,
    $entityId
] = mb_split('_(?=[^_]*$)', $documentId[2]);

$entityTypeId = CCrmOwnerType::ResolveID($entityTypeName);

// Фильтр для поиска активностей
$arFilter = [
    "DOCUMENT_ID" => $entityTypeName . '_' . $entityId,
];

$arSelectFields = [
    "ID",
    "WORKFLOW_ID",
];

// Получаем список активностей
$dbResultList = CBPTaskService::GetList([], $arFilter, false, false, $arSelectFields);

while ($arBizproc = $dbResultList->Fetch()) {
    $workflowId = $arBizproc["WORKFLOW_ID"];
    
    // Получаем текущие параметры бизнес-процесса
    $workflowParameters = CBPWorkflowTemplateLoader::GetList(
        [],
        ["ID" => $workflowId],
        false,
        false,
        ["ID", "NAME", "DOCUMENT_TYPE"]
    )->Fetch();

    if ($workflowParameters) {
        // Получаем все активности для данного бизнес-процесса
        $workflowActivities = CBPWorkflowTemplateLoader::GetActivities($workflowId);
        
        foreach ($workflowActivities as &$activity) {
            // Проверяем, есть ли у активности исполнители
            if (isset($activity['Properties']['User'])) {
                // Если исполнитель совпадает с userId, заменяем его на substituteId
                if (in_array($userId, $activity['Properties']['User'])) {
                    $this->WriteToTrackingService("Replacing user ID: " . $userId . " with substitute ID: " . $substituteId);
                    // Заменяем userId на substituteId
                    $activity['Properties']['User'] = array_map(function($id) use ($userId, $substituteId) {
                        return ($id == $userId) ? $substituteId : $id;
                    }, $activity['Properties']['User']);
                }
            }
        }
        
        // Сохраняем изменения в бизнес-процессе (если это необходимо)
        CBPDocument::UpdateWorkflow($workflowId, $workflowActivities);
    }
}
