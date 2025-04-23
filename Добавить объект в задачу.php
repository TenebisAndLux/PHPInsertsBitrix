<?php
\Bitrix\Main\Loader::includeModule('tasks');

$rootActivity = $this->GetRootActivity();

$idTask = $rootActivity->TASK_ID;

$arUserFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("TASKS_TASK", $idTask);

if (array_key_exists("UF_OBJECT_LIST", $arUserFields)) {
    $result = $GLOBALS["USER_FIELD_MANAGER"]->Update("TASKS_TASK", $idTask, ["UF_OBJECT_LIST"=>$rootActivity->GetVariable("OBJECT_LISTS")]);
}