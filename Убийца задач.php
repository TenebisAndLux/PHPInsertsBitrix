<?php
use Bitrix\Main\Loader;
CModule::IncludeModule("tasks");
CModule::IncludeModule("crm");
function completeTask($webhook, $taskId) {
$url = rtrim($webhook, '/') . "/tasks.task.complete/?taskId={$taskId}";

$curl = curl_init();

// Добавление заголовков, если это требуется
curl_setopt_array($curl, array(
     CURLOPT_URL => $url,
     CURLOPT_ENCODING => '',
     CURLOPT_MAXREDIRS => 10,
     CURLOPT_TIMEOUT => 0,
     CURLOPT_FOLLOWLOCATION => true,
     CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
     CURLOPT_CUSTOMREQUEST => 'POST',
     CURLOPT_RETURNTRANSFER => true, // Возвращать ответ в переменную вместо вывода
     CURLOPT_HTTPHEADER => array(
         'Content-Type: application/json',
     ),
));

$response = curl_exec($curl);

// Проверка ошибок cURL
if (curl_errno($curl)) {
     echo 'Ошибка cURL: ' . curl_error($curl);
     curl_close($curl);
     return false;
}

$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

// Проверка HTTP кода ответа
if ($httpCode !== 200) {
     echo "Ошибка ответа сервера: HTTP код $httpCode, ответ: $response";
     return false;
}

$jsonData = json_decode($response, true);

// Проверка на ошибки декодирования JSON
if (json_last_error() !== JSON_ERROR_NONE) {
     echo 'Ошибка при декодировании JSON: ' . json_last_error_msg();
     return false;
}

// Преобразование данных в читаемый вид
$jsonDataReadable = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Вывод данных в консоль отладки Bitrix24
echo "<script>console.log('Данные: " . $jsonDataReadable . "');</script>";

return $jsonData;
}

function getTaskList($webhook, $ufCrmTask){
    $url = rtrim($webhook, '/') . "/tasks.task.list/?taskId={$taskId}";
    $curl = curl_init();
    curl_setopt_array($curl, array(
    CURLOPT_URL => rtrim($webhook, '/') . "/tasks.task.list?filter[UF_CRM_TASK]=$ufCrmTask&select[0]=ID&select[1]=UF_CRM_TASK",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
    ),
));

    $response = curl_exec($curl);

// Проверка ошибок cURL
if (curl_errno($curl)) {
     echo 'Ошибка cURL: ' . curl_error($curl);
     curl_close($curl);
     return false;
}

$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

// Проверка HTTP кода ответа
if ($httpCode !== 200) {
     echo "Ошибка ответа сервера: HTTP код $httpCode, ответ: $response";
     return false;
}

$jsonData = json_decode($response, true);

// Проверка на ошибки декодирования JSON
if (json_last_error() !== JSON_ERROR_NONE) {
     echo 'Ошибка при декодировании JSON: ' . json_last_error_msg();
     return false;
}

// Преобразование данных в читаемый вид
$jsonDataReadable = json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// Вывод данных в консоль отладки Bitrix24
echo "<script>console.log('Данные: " . $jsonDataReadable . "');</script>";

return $jsonData;
}

$rootActivity = $this->GetRootActivity($webhook, );
    $documentId = $rootActivity->GetDocumentId();
    [
        $entityTypeName,
        $entityId
    ] = mb_split('_(?=[^_]*$)', $documentId[2]);
$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);
$id = \CCrmOwnerTypeAbbr::ResolveByTypeID($entityTypeId) . "_" . $entityId;
$arTask = getTaskList($webhook, $id);
foreach($arTask["result"]["tasks"] as $task){
    $this->writeToTrackingService("task" . $task['id']);      
    //$result = completeTask($webhook, $task['id']);
}


