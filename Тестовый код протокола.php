<?php
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;

Loader::includeModule('tasks');
Loader::includeModule('crm');

$rootActivity = $this->GetRootActivity();
$firstName = $rootActivity->GetVariable('NAME');       // Имя
$lastName = $rootActivity->GetVariable('LAST_NAME');   // Фамилия
$secondName = $rootActivity->GetVariable('SECOND_NAME'); // Отчество

$logString = '';

function findBitrixUserByFio($fioParts, &$logString = null) {
    $logMessage = "Поиск пользователя по ФИО: " . implode(' ', $fioParts) . "\n";
    
    $filter = ['LOGIC' => 'OR'];
    
    // Полное ФИО (Фамилия Имя Отчество)
    if (count($fioParts) >= 3) {
        $filter[] = [
            '=LAST_NAME' => $fioParts[0],   // Фамилия
            '=NAME' => $fioParts[1],        // Имя
            '=SECOND_NAME' => $fioParts[2], // Отчество
        ];
        $filter[] = [
            '%LAST_NAME' => $fioParts[0] . '%',
            '%NAME' => $fioParts[1] . '%',
            '%SECOND_NAME' => $fioParts[2] . '%',
        ];
    }
    
    // Фамилия и Имя
    if (count($fioParts) >= 2) {
        $filter[] = [
            '=LAST_NAME' => $fioParts[0],   // Фамилия
            '=NAME' => $fioParts[1],        // Имя
        ];
        $filter[] = [
            '%LAST_NAME' => $fioParts[0] . '%',
            '%NAME' => $fioParts[1] . '%',
        ];
    }
    
    // Только Фамилия
    if (count($fioParts) >= 1) {
        $filter[] = ['=LAST_NAME' => $fioParts[0]];
        $filter[] = ['%LAST_NAME' => $fioParts[0] . '%'];
    }

    $logMessage .= "Фильтр поиска: " . print_r($filter, true) . "\n";
    
    $user = UserTable::getList([
        'filter' => $filter,
        'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME'],
        'limit' => 1
    ])->fetch();

    if ($user) {
        $logMessage .= "Найден пользователь ID: {$user['ID']} (Фамилия: {$user['LAST_NAME']} Имя: {$user['NAME']} Отчество: {$user['SECOND_NAME']})\n";
        return $user['ID'];
    }
    
    $logMessage .= "Пользователь не найден\n";
    if ($logString !== null) $logString .= $logMessage;
    return 0;
}

// Основной поиск с разными вариантами
$searchVariants = [
    [$lastName, $firstName, $secondName],  // Фамилия, Имя, Отчество
    [$lastName, $firstName],               // Фамилия, Имя
    [$lastName . ' ' . mb_substr($firstName, 0, 1) . mb_substr($secondName, 0, 1)], // Фамилия + инициалы
    [$lastName]                            // Только Фамилия
];

$userId = 0;
foreach ($searchVariants as $variant) {
    if (empty(array_filter($variant))) continue;
    
    $userId = findBitrixUserByFio($variant, $logString);
    if ($userId) break;
}

$this->SetVariable('LOG_STRING', $logString);
$this->SetVariable('PERSON_ID', $userId);