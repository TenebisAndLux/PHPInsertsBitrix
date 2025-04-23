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

$ID_product_catalog = "";

$ProductSum = 0;

$DealTitleProduct = "";

$newProductPrice = $this->GetVariable('NEW_PRODUCT_PRICE');



if (is_array($productRows) && count($productRows) > 0) {

    foreach ($productRows as &$dealProduct) {

        $discountSum = $dealProduct["PRICE"] - $newProductPrice;
        $discountPrice = $discountSum / $dealProduct["QUANTITY"];

        $dealProduct["DISCOUNT_SUM"] = $dealProduct["PRICE"] - $newProductPrice;

        $dealProduct["DISCOUNT_PRICE"] = $dealProduct["DISCOUNT_SUM"] / $dealProduct["QUANTITY"];

        // $dealProduct["PRICE"] = $newProductPrice;
        $dealProduct["PRICE_EXCLUSIVE"] = $dealProduct["PRICE"] - $discountPrice;
        $dealProduct["PRICE_ACCOUNT"] = $dealProduct["PRICE_EXCLUSIVE"];
        $dealProduct["PRICE"] = $dealProduct["PRICE_EXCLUSIVE"];

        if ($discountTypeID != Discount::MONETARY) {
            $dealProduct["DISCOUNT_TYPE_ID"] = \Bitrix\Crm\Discount::MONETARY;
        }

        $discountTypeID = (int) $dealProduct['DISCOUNT_TYPE_ID'];
    }

    $this->WriteToTrackingService(serialize($productRows));

    unset($dealProduct);

    try {

        $arPriceSave = array_map(function ($n) {
            $res = [
                "ID" => $n["ID"],
                "PRICE" => $n["PRICE"]
            ];
            return $res;
        }, $productRows);

        $totalInfo = [];
        $result = CCrmProductRow::SaveRows("D", $entityId, $productRows, null, false, true, false, $totalInfo);
        $result = CCrmProductRow::SaveRows("D", $entityId, $productRows, null, false, true, false, $totalInfo);

    } catch (\Throwable $e) {
        $this->WriteToTrackingService($e->getMessage());
    }
}