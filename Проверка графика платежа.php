<?php
use Bitrix\Main\Loader;
use Bitrix\Iblock\Elements\ElementPaymentScheduleTable;

CModule::IncludeModule("crm");
CModule::IncludeModule("bizproc");

// Получаем ID сделки
$rootActivity = $this->GetRootActivity();
$documentId = $rootActivity->GetDocumentId();
[$entityTypeName, $entityId] = mb_split('_(?=[^_]*$)', $documentId[2]);
$crmId = \CCrmOwnerTypeAbbr::ResolveByTypeID(\CCrmOwnerType::ResolveID($entityTypeName)) . "_" . $entityId;

// Сумма всех платежей
$totalPaymentAmount = 0;

// Получаем все платежи по сделке
$payments = ElementPaymentScheduleTable::query()
    ->setSelect(["AMOUNT_PAYMENT"])
    ->where('DEAL.VALUE', $crmId)
    ->fetchCollection();

foreach ($payments as $payment) {
    $totalPaymentAmount += round((float)$payment->getAmountPayment()->getValue(), 2);
}

// Получаем сумму сделки
$dealSum = round((float)$this->getVariable("dealSum"), 2);
$totalPaymentAmount = round($totalPaymentAmount, 2);
$difference = $dealSum - $totalPaymentAmount;

// Проверяем точное соответствие
$isValid = ($totalPaymentAmount === $dealSum);

// Формируем комментарий
$comment = sprintf(
    "Проверка платежей: сумма сделки %s, сумма платежей %s, результат %s, разница в суммах = %s",
    number_format($dealSum, 2, '.', ' '),
    number_format($totalPaymentAmount, 2, '.', ' '),
    $isValid ? "OK" : "ERROR",
    number_format($difference, 2, '.', ' ')
);

// Устанавливаем результаты
$this->SetVariable('isPaymentScheduleValid', $isValid ? 'Y' : 'N');
$this->SetVariable('comment', $comment);
$this->WriteToTrackingService($comment);