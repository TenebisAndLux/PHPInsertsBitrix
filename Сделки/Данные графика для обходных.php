<?php
use Bitrix\Main\Loader;
use Bitrix\Iblock\Elements\ElementPaymentScheduleTable;

CModule::IncludeModule("crm"); // подключаем модуль инфоблоков

$rootActivity = $this->GetRootActivity();
$documentId = $rootActivity->GetDocumentId();
[
    $entityTypeName,
    $entityId
] = mb_split('_(?=[^_]*$)', $documentId[2]);

$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);

$crmId = \CCrmOwnerTypeAbbr::ResolveByTypeID(\CCrmOwnerType::ResolveID($entityTypeName)) . "_" . $entityId;

$dealId = $entityId;

// График платежей
$elementPaymentScheduleObject = ElementPaymentScheduleTable::query()->setSelect([
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

$paymentDates = [];
$amountPayments = [];
$typeCashes = [];
$isLoanForFirstPayment = false; // По умолчанию "Нет"

foreach ($elementPaymentScheduleObject as $PaymentScheduleElement) {
    $paymentDates[] = new \Bitrix\Main\Type\Date($PaymentScheduleElement->getPaymentDate()->getValue(), "Y-m-d");
    $amountPayments[] = round((float)$PaymentScheduleElement->getAmountPayment()->getValue(), 2);
    $typeCashes[] = !is_null($PaymentScheduleElement->getTypeCash()) ? $PaymentScheduleElement->getTypeCash()->getElement()->getName() : "";

    // Проверяем, есть ли "Займ для первого платежа" в PAYMENT_TYPE
    $paymentType = !is_null($PaymentScheduleElement->getPaymentType()) ? $PaymentScheduleElement->getPaymentType()->getElement()->getName() : "";
    if (strpos($paymentType, "Займ для первого платежа") !== false) {
        $isLoanForFirstPayment = true;
    }
}

// Устанавливаем переменные
$this->SetVariable('PaymentDates', $paymentDates);
$this->SetVariable('AmountPayments', $amountPayments);
$this->SetVariable('TypeCashes', $typeCashes);
$this->SetVariable('IsLoanForFirstPayment', $isLoanForFirstPayment);