<?php
use Bitrix\Crm\Service;
use Bitrix\Crm\Item;

$rootActivity = $this->GetRootActivity();
$documentId = $rootActivity->GetDocumentId();
$cadastralNumber = $this->getVariable("cadastralNumber");
foreach ($cadastralNumber as $number){
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://rosreestr.gov.ru/fir_lite_rest/api/gkn/fir_lite_object/' . implode(',', $number),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
    ));
    $rootActivity->SetVariable("url", 'https://rosreestr.gov.ru/fir_lite_rest/api/gkn/fir_lite_object/' . implode(',', $number));
    $response = curl_exec($curl);

    $data = json_decode($response, true);

    $cadastralCost = $cadastralCost + $data['objectData']['parcelData']['cadCostValue'];
    $area = $area + $data['objectData']['parcelData']['area']['value'];

    if (!empty($data['objectData']['parcelData']['utilByDoc'])) {
        $category = $data['objectData']['parcelData']['utilByDoc'];
    }

    if (!empty($data['objectData']['address']['note'])) {
        $address = $data['objectData']['address']['note'];
    }

    if (!empty($data)) {
        $rootActivity->SetVariable("data", $data);
    }
       
    $rootActivity->SetVariable("address", $address);
    $rootActivity->SetVariable("cadastralCost", $cadastralCost);
    $rootActivity->SetVariable("area", $area);
    $rootActivity->SetVariable("category", $category);
    
    curl_close($curl);
}