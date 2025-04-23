<?php

use Bitrix\Main\Loader;
use Bitrix\Tasks\TaskTable;

Loader::includeModule('tasks');

$rootActivity = $this->GetRootActivity();
$userId = $rootActivity->GetVariable('userID');
$this->WriteToTrackingService("1");

// Проверяем, существует ли пользователь
if (!$userId) {
    $this->WriteToTrackingService("Ошибка: userId не найден");
    return;
}

$stages = ['-3', '-2', '-1', '0', '1', '2', '3']; // Определяем массив статусов

// Загружаем задачи с фильтрацией по статусам
try {
    $dbResult = TaskTable::getList([
        'filter' => [
            'RESPONSIBLE_ID' => $userId,
            '@STATUS' => $stages // Используем массив для фильтрации статусов
        ],
        'select' => ['ID']
    ]);
} catch (Exception $e) {
    $this->WriteToTrackingService("Ошибка при получении задач: " . $e->getMessage());
    return;
}

$this->WriteToTrackingService("2");

$arTasks = []; // Инициализируем массив для хранения ID задач
while ($task = $dbResult->fetch()) {
    $arTasks[] = $task['ID']; // Добавляем ID задачи в массив
}
$this->WriteToTrackingService("3");

$this->SetVariable('ID_TASK_LIST', $arTasks); // Устанавливаем переменную с ID задач