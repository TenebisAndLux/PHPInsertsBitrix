<?php
use Bitrix\Crm\Service;
$rootActivity = $this->GetRootActivity();
$documentId = $rootActivity->GetDocumentId();    
[
    $entityTypeName,
    $entityId
] = mb_split('_(?=[^_]*$)', $documentId[2]);

$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
$obj = new \GG\CRM\SigurDocumentAdder();
$sigur_employer = $this->GetVariable('SIGUR_EMPLOYER');
$result = $obj->execute($entityTypeId, $entityId, $sigur_employer);

if ($result->isSuccess()) {
    $this->WriteToTrackingService("результат = успешный");
    $res = $result->getData();
    $str = implode($res);
    $this->WriteToTrackingService("данные = $str");
} else {
    $this->WriteToTrackingService("результат = не успешный");
    $res = $result->getErrors();
    $str = implode($res);
    $this->WriteToTrackingService("данные = $str");
}