<?php

use Bitrix\Main\Loader;
use Bitrix\Tasks\Internals\TaskTable;

$rootActivity = $this->GetRootActivity();
$substituteId = $rootActivity->GetVariable('substituteID');
$this->WriteToTrackingService("1");

Loader::includeModule('tasks');

$arTaskIds = $rootActivity->GetVariable('ID_TASK_LIST');

foreach ($arTaskIds as $taskId) {
    try {
        $task = new CTasks();
        $result = $task->Update($taskId, [
            'RESPONSIBLE_ID' => $substituteId
        ]);
        
        if ($result) {
        } else {
            $errors = $task->GetErrors();
            $errorMsg = implode(", ", array_column($errors, 'message'));
            $this->WriteToTrackingService("Ошибка при обновлении задачи ID $taskId: " . $errorMsg);
        }
    } catch (Exception $e) {
        $this->WriteToTrackingService("Ошибка при обработке задачи ID $taskId: " . $e->getMessage());
        continue;
    }
}

$this->WriteToTrackingService("Обработка завершена.");