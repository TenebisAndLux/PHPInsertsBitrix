<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Crm\Service;
use Bitrix\Crm\ContactTable;

Loader::includeModule("iblock"); // подключаем модуль инфоблоков
Loader::includeModule("sale");
Loader::includeModule("crm");
Loader::includeModule("bizproc");

try {
    $rootActivity = $this->GetRootActivity();
    $documentId = $rootActivity->GetDocumentId();

    [
        $entityTypeName,
        $entityId
    ] = mb_split('_(?=[^_]*$)', $documentId[2]);
    
    $entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
    
    // Получаем переменные из активности
    $name = $rootActivity->GetVariable('name');
    $surname = $rootActivity->GetVariable('surname'); 
    $phone = $rootActivity->GetVariable('phone');

    // Проверяем наличие контакта по ФИО и телефону
    $contactExists = false;
    
    // Выполняем запрос на поиск контактов
    $contacts = ContactTable::getList([
        'filter' => [
            'NAME' => $name,
            'LAST_NAME' => $surname,
            '=PHONE.VALUE' => $phone,
        ],
        'select' => ['ID']
    ]);

    if ($contact = $contacts->fetch()) {
        $contactExists = true; // Контакт найден
    }

    // Устанавливаем переменную в зависимости от наличия контакта
    $this->SetVariable('CONTACT_EXISTS', $contactExists);
    
} catch (Exception $e) {
    // Обработка ошибок
    $this->SetVariable('CONTACT_EXISTS', false);
}