<?php
use Bitrix\Main\Loader;

Loader::IncludeModule("crm");

// Получаем ID сделки
$rootActivity = $this->GetRootActivity();
$documentId = $rootActivity->GetDocumentId();

list($entityTypeName, $entityId) = explode('_', $documentId[2], 2);
$DealId = $entityId;

// Получаем историю изменений сделки
$history = CCrmHistory::GetList(
    array('ID' => 'DESC'), // Сортировка по ID в обратном порядке
    array('ENTITY_TYPE' => 'DEAL', 'ENTITY_ID' => $DealId), // Фильтр по сделке
    false, // Не использовать навигацию
    false, // Не использовать группировку
    array('ID', 'ENTITY_TYPE', 'ENTITY_ID', 'TYPE_ID', 'CREATED_TIME') // Выбираемые поля
);

// Выводим даты и время с секундами
while ($record = $history->Fetch()) {
    $createdTime = $record['CREATED_TIME'];
    $formattedTime = FormatDate("d.m.Y H:i:s", MakeTimeStamp($createdTime));
    echo $formattedTime . "<br>";
}