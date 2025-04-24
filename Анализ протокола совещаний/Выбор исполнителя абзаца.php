<?php

use Bitrix\Main\Loader;

Loader::includeModule('tasks');
Loader::includeModule('crm');

$rootActivity = $this->GetRootActivity();
$step = (int)$rootActivity->GetVariable('STEP');
$executors = $rootActivity->GetVariable('EXECUTORS');

$userId = (int)$executors[$step];
$this->SetVariable('USER', 'user_' . $userId);
