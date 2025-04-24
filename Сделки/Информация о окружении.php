<?php
/* */
CModule::IncludeModule("iblock"); // подключаем модуль инфоблоков
CModule::IncludeModule("sale");
CModule::IncludeModule("crm"); // подключаем модуль инфоблоков
CModule::IncludeModule("bizproc");

$rootActivity = $this->GetRootActivity();
$documentId = $rootActivity->GetDocumentId();

[
$entityTypeName,
$entityId
] = mb_split('_(?=[^_]*$)', $documentId[2]);

$dealId = $entityId;

$dbDeal = CCrmDeal::GetListEx(array(
    "ID" => "ASC"
), array(
    "ID" => $dealId
), false, false, array(
    "*",
), array());

$arDealFields = $dbDeal->fetch();

ob_start();

// Информация о квартирах на этаже
$arDealProducts = CCrmDeal::LoadProductRows($dealId);

$object_info .= "[b][color=#2268b1]Информация о квартирах(объектах) на этаже: [/color][/b]".chr(13).chr(10); 
$object_info .= str_repeat("[b][color=#2268b1]┈[/color][/b]", 23) . "\n"; 

if (is_array($arDealProducts) && count($arDealProducts) > 0) {
    
    foreach ($arDealProducts as $dealProduct) {
        
        $arFilter = array(
            "ID" => $dealProduct["PRODUCT_ID"]
        );
        
        $arSelectFields = array(
            "ID",
            "XML_ID",
            "NAME",
            "PROPERTY_260",
            "PROPERTY_166"
        );
        
        $rsProduct = CCrmProduct::GetList(array(), $arFilter, $arSelectFields);
        $arProduct = $rsProduct->Fetch();
        
        unset($arFilter, $arSelectFields);
        
        if (! empty($arProduct["PROPERTY_260_VALUE"])) {
            
            $ID_product_catalog = $arProduct["PROPERTY_260_VALUE"];
        } else {
            
            if (! empty($arProduct["PROPERTY_166_VALUE"])) {
                
                $arSelectFields = Array(
                    "ID",
                    "XML_ID",
                    "PROPERTY_CML2_LINK"
                );
                $arFilter = Array(
                    "ID" => IntVal($arProduct["PROPERTY_166_VALUE"])
                );
                $res = CIBlockElement::GetList(Array(), $arFilter, false, Array(
                    "nPageSize" => 50
                ), $arSelectFields);
                while ($ob = $res->GetNextElement()) {
                    $arFields = $ob->GetFields();
                    
                    if (empty($ID_product_catalog)) {
                        $ID_product_catalog = $arFields["PROPERTY_CML2_LINK_VALUE"];
                    }
                }
                unset($arFilter, $arSelectFields);
            }
        }
        $arFilter = array(
            "ID" => $ID_product_catalog
        );
        $res_groups = CIBlockElement::GetElementGroups($ID_product_catalog);
        while ($ar_group = $res_groups->Fetch()) {
            $ar_groups[] = $ar_group;
        }
        $depth_level = array_column($ar_groups, 'DEPTH_LEVEL');
        array_multisort($depth_level, SORT_DESC, $ar_groups);
        
        $this->SetVariable('Catalog_object_infor', serialize($ar_new_groups));
        
        $arSelectFields = array(
            "ID",
            "XML_ID",
            "NAME",
            "PROPERTY_OBJECT_TYPE",
            "PROPERTY_BUSINESS_STATUS"
        );
        
        $arFilter = Array(
            "ID" => $ID_product_catalog
        );
        
        $res = CIBlockElement::GetList(Array(), $arFilter, false, Array(), $arSelectFields);
        while ($ob = $res->GetNextElement()) {
            $arCatalogObjectFields = $ob->GetFields();
        }

        $arFilter = Array(
            "SECTION_ID" => current($ar_groups)["IBLOCK_SECTION_ID"],
            "IBLOCK_ID" => current($ar_groups)["IBLOCK_ID"],
            "PROPERTY_OBJECT_TYPE_VALUE" => $arCatalogObjectFields["PROPERTY_OBJECT_TYPE_VALUE"]
        );
        $res = CIBlockElement::GetList(Array(), $arFilter, false, Array(), $arSelectFields);
        // Получаем статистику объектов
        $arCatalogObjectStatistic = array(); 
        while ($ob = $res->GetNextElement()) { 
            $arCatalogObjectFields = $ob->GetFields(); 
            $arCatalogObjectStatistic[$arCatalogObjectFields["PROPERTY_BUSINESS_STATUS_VALUE"]] = ($arCatalogObjectStatistic[$arCatalogObjectFields["PROPERTY_BUSINESS_STATUS_VALUE"]] ?? 0) + 1; 
        }

        // Проверяем, есть ли объекты в окружении
        if (empty($arCatalogObjectStatistic)) {
            $object_info = $object_info . chr(13) . chr(10) . "[B]" . $arCatalogObjectFields["PROPERTY_OBJECT_TYPE_VALUE"] . ":" . "[/B] \r\n Нет информации о окружении \r\n"; 
        } else {
            $object_info = $object_info . chr(13) . chr(10) . "[B]" . $arCatalogObjectFields["PROPERTY_OBJECT_TYPE_VALUE"] . ":" . "[/B]"; 
            foreach ($arCatalogObjectStatistic as $keyStatistic => $CatalogObjectStatisticValue) { 
                $object_info = $object_info . chr(13) . chr(10) . "[B]" . $keyStatistic . " - " . "[/B]" . $CatalogObjectStatisticValue; 
            }
        }
    }
    $this->SetVariable('Catalog_object_info', $object_info);
}

$OP_chat_text = ob_get_contents(); 
ob_end_clean(); 
$OP_chat_text = $OP_chat_text . chr(13) . chr(10) . $this->GetVariable("Catalog_object_info"); 
$this->SetVariable('OP_chat_text', $OP_chat_text 
); 