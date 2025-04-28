<?php

use Bitrix\Main\Loader;
use Bitrix\Tasks\TaskTable;

$rootActivity = $this->GetRootActivity();
$substituteId = $rootActivity->GetVariable('substituteID');
$this->WriteToTrackingService("1");

$arTaskIds = $rootActivity->GetVariable('ID_TASK_LIST');

foreach ($arTaskIds as $taskId) {
    try {
        $task = TaskTable::getById($taskId)->fetch();

        if (!$task) {
            $this->WriteToTrackingService("Ошибка: задача с ID $taskId не найдена");
            continue;
        }
        TaskTable::update($taskId, ['RESPONSIBLE_ID' => $substituteId]);
        $this->WriteToTrackingService("Исполнитель для задачи ID $taskId изменен на ID $substituteId");
    } catch (Exception $e) {
        $this->WriteToTrackingService("Ошибка при обработке  задачи ID $taskId: " . $e->getMessage());
        return;
    }
}

$this->WriteToTrackingService("Обработка завершена.");