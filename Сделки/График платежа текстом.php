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

$this->WriteToTrackingService("crmId = " .     $crmId);

$dealId = $entityId;

// График платежей
$elementPaymentScheduleObject = ElementPaymentScheduleTable::query()->setSelect([
    "ID",
    "DEAL",
    "PAYMENT_DATE",
    "AMOUNT_PAYMENT",
    "TYPE_CASH.ELEMENT",
    "PAYMENT_TYPE.ELEMENT",
    "NAZNACHENIE_PLATEZHA"
])
    ->where('DEAL.VALUE', $crmId)
    ->setOrder([
    "PAYMENT_DATE.VALUE" => "ASC"
])
    ->fetchCollection();

$arPaymentSchedule = array();

function getListElementName($value)
{
    $listItems = [
        72186 => 'Оплата квартиры',
        72187 => 'Оплата кладовки',
        72188 => 'Оплата машиноместа',
        72189 => 'Заказ ремонта',
        72191 => 'Платное бронирование объекта',
    ];

    return isset($listItems[$value]) ? $listItems[$value] : 'Неизвестный элемент';
}

foreach ($elementPaymentScheduleObject as $PaymentScheduleElement) {
    $naznacheniePlatezha = $PaymentScheduleElement->getNaznacheniePlatezha();
    $valueNaznachenie = !is_null($naznacheniePlatezha) ? $naznacheniePlatezha->getValue() : 'NULL';
    $this->WriteToTrackingService("value = " . $valueNaznachenie);
    
    $arPaymentSchedule[$PaymentScheduleElement->getId()] = [
        "ID" => $PaymentScheduleElement->getId(),
        "DEAL" => $PaymentScheduleElement->getDeal()->getValue(),
        "PAYMENT_DATE" => new \Bitrix\Main\Type\Date($PaymentScheduleElement->getPaymentDate()->getValue(), "Y-m-d"),
        "AMOUNT_PAYMENT" => round((float)$PaymentScheduleElement->getAmountPayment()->getValue(), 2),
        "TYPE_CASH" => !is_null($PaymentScheduleElement->getTypeCash()) ? $PaymentScheduleElement->getTypeCash()->getElement()->getName() : "",
        "PAYMENT_TYPE" => !is_null($PaymentScheduleElement->getPaymentType()) ? $PaymentScheduleElement->getPaymentType()->getElement()->getName() : "",
        "NAZNACHENIE_PLATEZHA" => getListElementName((int)$valueNaznachenie),
    ];
}

$inforMessage .= str_repeat("-", 45);
$inforMessage .= "\r\n" . "[B]График платежей: [/B]" . "\r\n";
if (!empty($arPaymentSchedule)) {
    foreach ($arPaymentSchedule as $paymentItem) {
        $this->WriteToTrackingService("NAZNACHENIE_PLATEZHA = " .     $paymentItem["NAZNACHENIE_PLATEZHA"]);
        $inforMessage .= $paymentItem["PAYMENT_DATE"] . " - " . $paymentItem["NAZNACHENIE_PLATEZHA"] . " - " . $paymentItem["AMOUNT_PAYMENT"] . " - " . $paymentItem["TYPE_CASH"] . " - " . $paymentItem["PAYMENT_TYPE"] . "\r\n";
    }
} else {
    $inforMessage .= "Нет записей" . "\r\n";
}

$inforMessage .= str_repeat("-", 45) . "\r\n";

$this->SetVariable('Infor_message', $inforMessage);