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

$root->SetVariable('ALL_LUNCH_BID', $allLunchBid);