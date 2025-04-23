<?php

use Bitrix\Main\Loader;

Loader::IncludeModule("crm");

$rootActivity = $this->GetRootActivity();
$documentId = $rootActivity->GetDocumentId();
$dealId = str_replace("DEAL_", "", $documentId[2]);

try {
    $eventFilter = array(
        "OWNER_ID" => $dealId,
        "OWNER_TYPE_ID" => \CCrmOwnerType::Deal,
        "CHECK_PERMISSIONS" => false,
        "COMPLETED" => 'Y'
    );

    $eventSelect = array("ID");

    $eventList = \CCrmActivity::GetList(array(), $eventFilter, false, false, $eventSelect);
    
    $activity_list_id = []; // Инициализация массива для хранения ID активити

    while ($event = $eventList->Fetch()) {
        $activity_list_id[] = $event['ID']; // Добавление ID в массив
    }

    $rootActivity->SetVariable("activity_list_id", $activity_list_id);
} catch (\Exception $e) {
    // Обработка ошибок
    echo "Произошла ошибка: " . $e->getMessage();
}