<?php
use Bitrix\Crm\Service;
use Bitrix\Crm\Item;

$rootActivity = $this->GetRootActivity();  
$documentId = $rootActivity->GetDocumentId();  

[  
    $entityTypeName,  
    $entityId  
] = mb_split('_(?=[^_]*$)', $documentId[2]);  

$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);  

// Получаем переменные из активности  
$inputString  = $rootActivity->GetVariable('Codifier');  

// Разделяем строку по разделителю " - "
$parts = explode(' - ', $inputString);  

// Инициализируем переменные
$Number = isset($parts[0]) ? trim($parts[0]) : null;
$Denomination = isset($parts[1]) ? trim($parts[1]) : null;
$Units = isset($parts[2]) ? trim($parts[2]) : null;
$FeePerUnit = isset($parts[3]) ? trim($parts[3]) : null;
$FeePerDay = isset($parts[4]) ? trim($parts[4]) : null;

// Игнорируем "Плата за День", если это не число
if (!is_numeric($FeePerDay)) {
    $FeePerDay = null;
}

// Записываем переменные с использованием SetVariable
$this->SetVariable('Number', $Number);
$this->SetVariable('Denomination', $Denomination);
$this->SetVariable('Units', $Units);
$this->SetVariable('FeePerUnit', $FeePerUnit);

if ($FeePerDay !== null) {
    $this->SetVariable('FeePerDay', $FeePerDay);
} else {
    // Если "Плата за День" игнорируется, можно установить значение null
    $this->SetVariable('FeePerDay', null);
}