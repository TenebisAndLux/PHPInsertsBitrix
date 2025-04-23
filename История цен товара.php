<?php

CModule::IncludeModule("iblock"); // подключаем модуль инфоблоков
CModule::IncludeModule("sale");
CModule::IncludeModule("crm"); // подключаем модуль инфоблоков
CModule::IncludeModule("bizproc");

$arPriceHistory = array();

$arPriceHistory = $this->GetVariable('Price_history');

if (count($arPriceHistory) == 0) {
    // получить текущий бизнес-процесс

    $rootActivity = $this->GetRootActivity();

    $documentId = $rootActivity->GetDocumentId();

    $dealId = str_replace("DEAL_", "", $documentId[2]);

    $dbDeal = CCrmDeal::GetListEx(array(
        "ID" => "ASC"
    ), array(
        "ID" => $dealId
    ), false, false, array(), array());

    $arDealFields = $dbDeal->fetch();

    $arPriceHistory[] = $arDealFields["OPPORTUNITY"];
}

$arPriceHistory[] = $this->GetVariable('NEW_PRODUCT_PRICE');

$this->SetVariable('Price_history', $arPriceHistory);