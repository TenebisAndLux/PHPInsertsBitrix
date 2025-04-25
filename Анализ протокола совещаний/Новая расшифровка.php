<?php
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Main\Type\DateTime;

Loader::includeModule('tasks');
Loader::includeModule('crm');

$rootActivity = $this->GetRootActivity();
$documentId = $rootActivity->GetDocumentId();
[$entityTypeName, $entityId] = mb_split('_(?=[^_]*$)', $documentId[2]);
$entityTypeId = \CCrmOwnerType::ResolveID($entityTypeName);

$inputText = $rootActivity->GetVariable('INPUTTEXT');
$paragraphs = [];
$executors = [];
$deadLines = [];

// Обработка текста
$inputText = str_replace(["\r\n", "\r"], "\n", $inputText);
$inputText = preg_replace("/\n{3,}/", "\n\n", $inputText);
$allParagraphs = preg_split('/(?<=\n\n)/', $inputText, -1, PREG_SPLIT_NO_EMPTY);

// Функция поиска пользователя по полному ФИО
function findUserByFullFio($fio) {
    $fioParts = preg_split('/\s+/', trim($fio));
    
    if (count($fioParts) >= 3) {
        $user = UserTable::getList([
            'filter' => [
                '=ACTIVE' => 'Y',
                'LAST_NAME' => $fioParts[0],
                'NAME' => $fioParts[1],
                'SECOND_NAME' => $fioParts[2],
            ],
            'select' => ['ID'],
            'limit' => 1
        ])->fetch();
        
        if ($user) {
            return $user['ID'];
        }
    }
    
    return 0;
}

foreach ($allParagraphs as $paragraph) {
    $paragraph = trim($paragraph);
    if (strpos($paragraph, 'Исполнители: ') === false) {
        continue;
    }
    
    // Извлечение даты
    $deadline = '';
    if (preg_match('/Крайний срок:\s*([^\n]+)/ui', $paragraph, $matches)) {
        $dateStr = trim($matches[1]);
        // Обработка разных форматов даты
        if (preg_match('/\d{2}\.\d{2}\.\d{4}/', $dateStr, $dateMatch)) {
            $deadline = $dateMatch[0];
        } elseif (preg_match('/\d{1,2}\s+[а-яё]+/ui', $dateStr, $dateMatch)) {
            $months = [
                'января'=>1,'февраля'=>2,'марта'=>3,'апреля'=>4,
                'мая'=>5,'июня'=>6,'июля'=>7,'августа'=>8,
                'сентября'=>9,'октября'=>10,'ноября'=>11,'декабря'=>12
            ];
            $parts = explode(' ', $dateMatch[0]);
            $day = $parts[0];
            $month = mb_strtolower($parts[1]);
            if (isset($months[$month])) {
                $deadline = sprintf('%02d.%02d.%04d', $day, $months[$month], date('Y'));
            }
        } elseif (preg_match('/\d{2}\.\d{2}/', $dateStr, $dateMatch)) {
            $deadline = $dateMatch[0] . '.' . date('Y');
        }
    }
    
    // Если дата не найдена, устанавливаем дефолтную (через 31 день)
    if (empty($deadline)) {
        $deadline = (new DateTime())->add('31 days')->format('d.m.Y');
    }
    
    // Извлечение исполнителей
    if (preg_match('/Исполнители:\s*([^\n]+)/ui', $paragraph, $matches)) {
        $executorsList = trim($matches[1]);
        $executorsList = rtrim($executorsList, '.');
        $executorsArray = array_map('trim', explode(',', $executorsList));
        
        foreach ($executorsArray as $executor) {
            $userId = findUserByFullFio($executor);
            if ($userId > 0) {
                $paragraphs[] = $paragraph;
                $executors[] = $userId;
                $deadLines[] = $deadline;
            }
        }
    }
}

// Установка переменных
$this->SetVariable('PARAGRAPHS', $paragraphs);
$this->SetVariable('EXECUTORS', $executors);
$this->SetVariable('DEAD_LINES', $deadLines);

// Находим максимальную дату
$maxDate = '';
if (!empty($deadLines)) {
    $dates = [];
    foreach ($deadLines as $dateStr) {
        try {
            $dates[] = new DateTime($dateStr);
        } catch (Exception $e) {
            continue;
        }
    }
    if (!empty($dates)) {
        $maxDate = max($dates)->format('Y-m-d');
    }
}
$this->SetVariable('maxDate', $maxDate);