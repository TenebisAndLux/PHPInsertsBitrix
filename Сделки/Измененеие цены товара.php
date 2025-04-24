<?php
use Bitrix\Crm\Discount;

CModule::IncludeModule("iblock");
CModule::IncludeModule("sale");
CModule::IncludeModule("crm");
CModule::IncludeModule("bizproc");

// получить текущий бизнес-процесс

$rootActivity = $this->GetRootActivity();

$documentId = $rootActivity->GetDocumentId();

[
    $entityTypeName,
    $entityId
] = mb_split('_(?=[^_]*$)', $documentId[2]);

$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);

// Получаем список товаров сделки

$productRows = \CCrmDeal::LoadProductRows($entityId);

$this->WriteToTrackingService(serialize($productRows));

$newProductPrice = $this->GetVariable('NEW_PRODUCT_PRICE');

$matches = null;
$returnValue = preg_match('#^-?\\d+.\\d+#', $newProductPrice, $matches);

if (!empty($matches)) {
    $newProductPrice = (float)$matches[0];
} else {
    return;
}

if (is_array($productRows) && count($productRows) > 0) {

    foreach ($productRows as &$dealProduct) {

        $discountSum =  $dealProduct["PRICE_NETTO"] - $newProductPrice;
        $discountPrice = $discountSum / $dealProduct["QUANTITY"];

        $dealProduct["DISCOUNT_SUM"] = $discountSum;

        $dealProduct["DISCOUNT_PRICE"] = $discountPrice;

        $dealProduct["PRICE_EXCLUSIVE"] = $dealProduct["PRICE_NETTO"] - $discountPrice;
        $dealProduct["PRICE_ACCOUNT"] = $dealProduct["PRICE_EXCLUSIVE"];
        $dealProduct["PRICE"] = $dealProduct["PRICE_EXCLUSIVE"];

        if ($discountTypeID != Discount::MONETARY) {
            $dealProduct["DISCOUNT_TYPE_ID"] = \Bitrix\Crm\Discount::MONETARY;
        }

        $discountTypeID = (int) $dealProduct['DISCOUNT_TYPE_ID'];

        $dealProduct['DISCOUNT_TYPE_ID'] = Discount::MONETARY;
    }

    unset($dealProduct);

    try {

        foreach ($productRows as $productRow) {

            $result = CCrmProductRow::Update($productRow["ID"], $productRow, $checkPerms = false, $regEvent = true);
        }
    } catch (\Throwable $e) {
        $this->WriteToTrackingService($e->getMessage());
    }
}