<?php

// Подключаем необходимые модули
use Bitrix\Main\Loader;
use Bitrix\Crm\LeadTable;
use Bitrix\Crm\ActivityTable;

Loader::IncludeModule("crm");

$rootActivity = $this->GetRootActivity();
$documentId = $rootActivity->GetDocumentId();

[
$entityTypeName,
$entityId
] = mb_split('_(?=[^_]*$)', $documentId[2]);

$leadId = $entityId;

// Получаем историю лида
$leadHistory = CCrmActivity::GetList(
    [],
    ['TYPE' => 'Создано действие', 'BIND_TYPE' => 'LEAD', 'BIND_ID' => $leadId],
    false,
    false,
    []
);

// Проверяем наличие события "Создано действие"
$foundActionCreated = false;

if ($leadHistory) {
    while ($event = $leadHistory->Fetch()) {
        $foundActionCreated = true;
        break;
    }
}

// Если событие найдено, обновляем поле типа Да/Нет
if ($foundActionCreated) {
    $this->SetVariable('LEAD_HISTORY_FLAG', 'Y'); // Устанавливаем значение "Да
} else {
    $this->WriteToTrackingService("Событие 'Создано действие' не найдено в истории лида.");
}