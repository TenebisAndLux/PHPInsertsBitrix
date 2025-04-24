<?php

use Bitrix\Main\Loader;
use Bitrix\Disk;
use Bitrix\Iblock\Elements\ElementPaymentScheduleTable;
use Bitrix\Iblock\Elements\ElementPaymentTypeTable;

CModule::IncludeModule("crm"); // подключаем модуль инфоблоков
CModule::IncludeModule("bizproc");
CModule::IncludeModule('disk');

$rootActivity = $this->GetRootActivity();
$documentId = $rootActivity->GetDocumentId();
[
    $entityTypeName,
    $entityId
] = mb_split('_(?=[^_]*$)', $documentId[2]);

$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);

$crmId = \CCrmOwnerTypeAbbr::ResolveByTypeID(\CCrmOwnerType::ResolveID($entityTypeName)) . "_" . $entityId;

"crmId = " .     $crmId);
$this->WriteToTrackingService(
$dealId = $entityId;

$dbDeal = CCrmDeal::GetListEx(
    array("ID" => "ASC"),
    array("ID" => $dealId),
    false,
    false,
    array("*", "UF_CRM_PRICE_SQUARE_SPACE"),
    array()
);

$arDealFields = $dbDeal->fetch();
$productPrice = 0;

$arDealProducts = CCrmDeal::LoadProductRows($dealId);

if (is_array($arDealProducts) && count($arDealProducts) > 0) {
    foreach ($arDealProducts as $dealProduct) {
        $catalogObjectId = CatalogObject\House::getCatalogObjectSKUForProduct($dealProduct["PRODUCT_ID"]);

        if (empty($catalogObjectId)) {
            $catalogObjectId = CatalogObject\House::getCatalogObjectForProduct($dealProduct["PRODUCT_ID"]);
        }

        $ar_res = CPrice::GetBasePrice($catalogObjectId);

        if (isset($ar_res["PRICE"])) {
            $productPrice += round((float) $ar_res["PRICE"], 2);
        }
    }
}

$inforMessage = "[b][color=#18AEFF]Примите решение по цене сделки №[/color][/b]" . $arDealFields["ID"] . " (" . $arDealFields["TITLE"] . ")" . "[/B]\r\n\r\n";
$inforMessage .= "[B]Причина изменения стоимости: [/B]" . $this->GetVariable('Cause_line') . "\r\n";

$arRationale_document = $this->GetVariable('Rationale_document_disk');

$inforMessage .= str_repeat("-", 45) . "\r\n";
$inforMessage .= "\r\n" . "[I]Информация: [/I]" . "\r\n";
$inforMessage .= str_repeat("-", 45) . "\r\n";
$inforMessage .= "[B]Цена по прайс-листу: [/B]" . $productPrice . "\r\n";

$overstatement = round((float) $arDealFields["OPPORTUNITY"] - $productPrice, 2);
$inforMessage .= "[B]Завышение: [/B]" . (($overstatement != 0) ? $overstatement : "Отсутствует") . "\r\n";
$inforMessage .= "[B]Текущая цена сделки: [/B]" . $arDealFields["OPPORTUNITY"] . "\r\n";
$inforMessage .= str_repeat("-", 45) . "\r\n";

$otherAmount = round((float)$this->GetVariable('NEW_PRODUCT_PRICE') - (float)$this->GetVariable('LoanAmount') - (float)$this->GetVariable('Overheads') - (float) $arDealFields["OPPORTUNITY"], 2 );

$inforMessage .= "[B]Текущая цена сделки: [/B]" . $arDealFields["OPPORTUNITY"] . "\r\n";

if ($otherAmount != 0) {
    $inforMessage .= "[B]Прочее: [/B]" . $otherAmount . "\r\n";
}

if (! empty($this->GetVariable('Overheads'))) {
    $inforMessage .= "[B]Накладные расходы:[/B] " . $this->GetVariable('Overheads') . "\r\n";
}

if (! empty($this->GetVariable('LoanAmount'))) {
    $inforMessage .= "[B]Сумма займа:[/B] " . $this->GetVariable('LoanAmount') . "\r\n";
}

$inforMessage .= "[B]Предложение по цене:[/B] " . "[B]" . round((float)$this->GetVariable('NEW_PRODUCT_PRICE'), 2) . "[/B]" . "\r\n";
$inforMessage .= str_repeat("-", 45) . "\r\n";

$diffPrice = $arDealFields["OPPORTUNITY"] - $this->GetVariable('NEW_PRODUCT_PRICE');
$diffPercent = round(($diffPrice / $arDealFields["OPPORTUNITY"]) * 100, 3);

if ($diffPrice > 0) {
    $inforMessage .= "[b][color=RED]Цена повышена на %:[/b][/color] " . $diffPercent . "\r\n";
}

if ($diffPrice < 0) {
    $diffPercent = round(($diffPrice / $arDealFields["OPPORTUNITY"]) * -100, 3);
    $inforMessage .= "[b][color=GREEN]Цена сокращена на %:[/b][/color] " . $diffPercent . "\r\n";
}


// История цен
$arPriceHistory = $this->GetVariable('Price_history');
$strPriceHistory = "";

foreach ($arPriceHistory as $PriceHistory) {
    $strPriceHistory .= " * " . $PriceHistory . "; " . chr(13) . chr(10);
}

$inforMessage .= str_repeat("-", 45) . "\r\n";
$inforMessage .= "\r\n" . "[I]Справочно: [/I]" . "\r\n";
$inforMessage .= str_repeat("-", 45) . "\r\n";
if (!empty($strPriceHistory)) {
    $inforMessage .= "[B]История цен: [/B]" . "\r\n" . $strPriceHistory . "\r\n";
} else {
    $inforMessage .= "[B]История цен: [/B] Нет записей" . "\r\n";
}

// График платежей
$elementPaymentScheduleObject = ElementPaymentScheduleTable::query()->setSelect([
    "ID",
    "DEAL",
    "PAYMENT_DATE",
    "AMOUNT_PAYMENT",
    "TYPE_CASH.ELEMENT",
    "PAYMENT_TYPE.ELEMENT"
])
    ->where('DEAL.VALUE', $crmId)
    ->setOrder([
    "PAYMENT_DATE.VALUE" => "ASC"
])
    ->fetchCollection();

$arPaymentSchedule = array();

foreach ($elementPaymentScheduleObject as $PaymentScheduleElement) {
    $arPaymentSchedule[$PaymentScheduleElement->getId()] = [
        "ID" => $PaymentScheduleElement->getId(),
        "DEAL" => $PaymentScheduleElement->getDeal()->getValue(),
        "PAYMENT_DATE" => new \Bitrix\Main\Type\Date($PaymentScheduleElement->getPaymentDate()->getValue(), "Y-m-d"),
        "AMOUNT_PAYMENT" => round((float)$PaymentScheduleElement->getAmountPayment()->getValue(), 2),
        "TYPE_CASH" => ! is_null($PaymentScheduleElement->getTypeCash()) ? $PaymentScheduleElement->getTypeCash()
            ->getElement()
            ->getName() : "",
        "PAYMENT_TYPE" => ! is_null($PaymentScheduleElement->getPaymentType()) ? $PaymentScheduleElement->getPaymentType()
            ->getElement()
            ->getName() : ""
    ];
}

$inforMessage .= str_repeat("-", 45) . "\r\n";
$inforMessage .= "\r\n" . "[B]График платежей: [/B]" . "\r\n";
if (!empty($arPaymentSchedule)) {
    foreach ($arPaymentSchedule as $paymentItem) {
        $inforMessage .= $paymentItem["PAYMENT_DATE"] . " - " . $paymentItem["AMOUNT_PAYMENT"] . " - " . $paymentItem["TYPE_CASH"] . " - " . $paymentItem["PAYMENT_TYPE"] . "\r\n";
    }
} else {
    $inforMessage .= "Нет записей" . "\r\n";
}

$inforMessage .= str_repeat("-", 45) . "\r\n";

$inforMessage = $inforMessage . chr(13) . chr(10) . $this->GetVariable("Catalog_object_info");
$this->SetVariable('Infor_message', $inforMessage);

