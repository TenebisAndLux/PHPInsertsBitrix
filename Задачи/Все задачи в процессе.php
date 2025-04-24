<?php

use Bitrix\Main\Loader;
use Bitrix\Tasks\TaskTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Activity\Provider\Tasks\Task;
Loader::includeModule('tasks');
Loader::includeModule('crm');
$rootActivity = $this->GetRootActivity();
$documentId = $rootActivity->GetDocumentId();
   [
       $entityTypeName,
       $entityId
   ] = mb_split('_(?=[^_]*$)', $documentId[2]);




$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
$dbResult = \CCrmActivity::GetList(
   [],
   [
       'TYPE_ID' => \CCrmActivityType::Provider,
       'PROVIDER_ID' => Task::getId(),
       'PROVIDER_TYPE_ID' => Task::getProviderTypeId(),
       'COMPLETED' => 'N',
       'CHECK_PERMISSIONS' => 'N',
       'OWNER_ID' => $entityId,
       'OWNER_TYPE_ID' => $entityTypeId
   ],
   false,
   false,
   ['ID', 'ASSOCIATED_ENTITY_ID', 'SETTINGS']
);
$arTasks = []; // Инициализируем массив для хранения ID задач
for ($activity = $dbResult->Fetch(); $activity; $activity = $dbResult->Fetch()) {
    if (is_array($activity['SETTINGS'])) {
        $arTasks[] = $activity['ASSOCIATED_ENTITY_ID']; // Добавляем ID задачи в массив
    }
}

$this->SetVariable('ID_TASK_LIST', $arTasks); // Устанавливаем переменную с ID задач