<?php

$root = $this->GetRootActivity();
$message = $root->GetVariable("ERROR_MESSAGE");

preg_match('/№(\d+)/', $message, $matches);

if (isset($matches[1])) {
    $dealNumber = $matches[1];
    $this->SetVariable('DEAL_ID', $dealNumber);
} else {
    $this->SetVariable('DEAL_ID', "Номер не найден в тексте ошибки.");
}