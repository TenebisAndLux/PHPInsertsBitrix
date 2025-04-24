<?php
use Bitrix\Main\Loader;
Loader::includeModule('bizproc');

        $rootActivity = $this->GetRootActivity();
        $documentId = $rootActivity->GetDocumentId();
        [
            $entityTypeName,
            $entityId
        ] = mb_split('_(?=[^_]*$)', $documentId[2]);

        $entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);

$arSelectFields = array(
            "ID",
            "WORKFLOW_ID",
        );


        $arFilter["DOCUMENT_ID"] = $entityTypeName . '_' . $entityId;

        $this->writeToTrackingService("arFilter =" . $arFilter["DOCUMENT_ID"]);

        $dbResultList = \CBPTaskService::GetList(array(
            $by => $order
        ), $arFilter, false, false, $arSelectFields);

        $arBPTask = [];

        while ($arBizproc = $dbResultList->Fetch()) {
            $arBPTask[] = $arBizproc;
        }

     foreach ($arBPTask as $bpTask) {
            $this->writeToTrackingService("arBPTask =" . implode(', ', $bpTask));
            $result = CBPDocument::TerminateWorkflow($bpTask["WORKFLOW_ID"], null, $arErrorsTmp);
     }