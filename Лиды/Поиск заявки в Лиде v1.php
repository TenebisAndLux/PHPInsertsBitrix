<?php
use Bitrix\Main\Loader;
use Bitrix\Crm\LeadTable;
use Bitrix\Crm\ActivityTable;

Loader::IncludeModule("crm");

$rootActivity = $this->GetRootActivity();
$documentId = $rootActivity->GetDocumentId();

list($entityTypeName, $entityId) = explode('_', $documentId[2], 2);
$leadId = $entityId;

// Запрос к ActivityTable с фильтрацией по необходимым параметрам
try {
    $res = ActivityTable::getList([
        'filter' => [
            "OWNER_ID" => $leadId,
            "OWNER_TYPE_ID" => CCrmOwnerType::Lead,
            "TYPE_ID" => 6 // Убедитесь, что это корректное значение типа действия
        ],
        'select' => ['ID'],
        'limit' => 1
    ]);

    $this->WriteToTrackingService("Выполнен запрос к ActivityTable с фильтром: OWNER_ID = {$leadId}, OWNER_TYPE_ID = Lead, TYPE_ID = 5");

    // Проверяем наличие результата
    if ($activity = $res->fetch()) {
        $this->SetVariable('LEAD_HISTORY_FLAG', 'Y');
        $this->WriteToTrackingService("Найдено действие: " . print_r($activity, true));
    } else {
        $this->WriteToTrackingService("Событие 'Создано действие' не найдено в истории лида.");
    }
} catch (Exception $e) {
    $this->WriteToTrackingService("Ошибка при выполнении запроса: " . $e->getMessage());
}