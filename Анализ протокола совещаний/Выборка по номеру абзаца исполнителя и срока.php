<?php
use Bitrix\Main\Loader;

Loader::includeModule('tasks');
Loader::includeModule('crm');

$rootActivity = $this->GetRootActivity();
$this->SetVariable('USER', 'user_' . (int)$rootActivity->GetVariable('EXECUTORS')[(int)$rootActivity->GetVariable('STEP')]);
$this->SetVariable('currentDeadLine', $rootActivity->GetVariable('DEAD_LINES')[(int)$rootActivity->GetVariable('STEP')]);