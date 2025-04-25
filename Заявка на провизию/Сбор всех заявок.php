<?php
use Bitrix\Crm\Service;
use Bitrix\Crm\Item;

$rootActivity = $this->GetRootActivity();  
$documentId = $rootActivity->GetDocumentId();  

[$entityTypeName, $entityId] = mb_split('_(?=[^_]*$)', $documentId[2]);  
$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);

$container = Service\Container::getInstance();
$factory = $container->getFactory($entityTypeId);
if (!$factory) {
    die('Factory not found');
}

$targetStageId = 'DT1062_151:PREPARATION';

// Получаем все элементы на целевой стадии
$items = $factory->getItems([
    'select' => ['ID', 'UF_CRM_60_LUNCH_TABLE', 'STAGE_ID'],
    'filter' => [
        '=STAGE_ID' => $targetStageId,
        '!UF_CRM_60_LUNCH_TABLE' => false // Только элементы с заполненным полем
    ]
]);

$allLunchBid = [];
foreach ($items as $item) {
    $lunchTableData = $item->get('UF_CRM_60_LUNCH_TABLE');
    if ($lunchTableData) {
        $allLunchBid[] = $lunchTableData;
    }
}

//Переменная типа lunch table
$rootActivity->SetVariable('ALL_LUNCH_BID', $allLunchBid);