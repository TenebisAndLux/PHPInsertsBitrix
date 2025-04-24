<?php
$rootActivity = $this->GetRootActivity();
$documentId = $rootActivity->GetDocumentId();

$this->WriteToTrackingService("Начало обработки смарт-процесса. DocumentID: " . print_r($documentId, true));

[$entityTypeName, $entityId] = mb_split('_(?=[^_]*$)', $documentId[2]);
$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);

$this->WriteToTrackingService("Тип сущности: $entityTypeName, ID элемента: $entityId");

// Получаем ID сотрудника из переменной
$employeeId = $rootActivity->GetVariable('Employee');
$this->WriteToTrackingService("Получен Employee ID: " . $employeeId);

if (empty($employeeId)) {
    $this->WriteToTrackingService("Ошибка: Employee ID не задан!", 0, \CBPTrackingType::Error);
    return;
}

// Получаем фабрику и элемент смарт-процесса
$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
if (!$factory) {
    $this->WriteToTrackingService("Ошибка: Не удалось получить фабрику для типа $entityTypeName", 0, \CBPTrackingType::Error);
    return;
}

$item = $factory->getItem($entityId);
if (!$item) {
    $this->WriteToTrackingService("Ошибка: Не найден элемент с ID $entityId", 0, \CBPTrackingType::Error);
    return;
}

$this->WriteToTrackingService("Текущий CREATED_BY: " . $item->get('CREATED_BY'));

// Устанавливаем нового создателя документа
$item->set('CREATED_BY', $employeeId);

// Сохраняем изменения
$saveResult = $item->save();

if ($saveResult->isSuccess()) {
    $this->WriteToTrackingService("Успешно обновлено! Новый CREATED_BY: $employeeId");
} else {
    $errorMessages = $saveResult->getErrorMessages();
    $this->WriteToTrackingService(
        "Ошибка при сохранении: " . implode(", ", $errorMessages),
        0,
        \CBPTrackingType::Error
    );
}