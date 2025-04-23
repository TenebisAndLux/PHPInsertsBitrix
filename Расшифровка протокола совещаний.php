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

$logString = "Начало обработки документа\n";
$logString .= "Тип документа: {$entityTypeName}, ID: {$entityId}\n";
$logString .= "Исходный текст:\n{$inputText}\n\n";

$inputText = str_replace(["\r\n", "\r"], "\n", $inputText);
$inputText = preg_replace("/\n{3,}/", "\n\n", $inputText);
$allParagraphs = preg_split('/(?<=\n\n)/', $inputText, -1, PREG_SPLIT_NO_EMPTY);
$logString .= "Найдено абзацев: " . count($allParagraphs) . "\n";

$paragraphs = [];
$usedHashes = [];
foreach ($allParagraphs as $i => $paragraph) {
    if ($i === 0) continue;
    $paragraph = trim($paragraph);
    $hash = md5($paragraph);
    if (!empty($paragraph) && !in_array($hash, $usedHashes)) {
        $paragraphs[] = $paragraph;
        $usedHashes[] = $hash;
    }
}

if (empty($paragraphs)) {
    $paragraphs = [trim(implode("\n\n", array_slice($allParagraphs, 1)))];
    $logString .= "Используется весь текст после первого абзаца\n";
}

$logString .= "Обрабатываемых абзацев: " . count($paragraphs) . "\n";

function findFioInText($text) {
    $fioPatterns = [
        // Полное ФИО (Фамилия Имя Отчество)
        '/\b[А-ЯЁ][а-яё]+\s+[А-ЯЁ][а-яё]+\s+[А-ЯЁ][а-яё]+\b/u',
        
        // Фамилия и Имя
        '/\b[А-ЯЁ][а-яё]+\s+[А-ЯЁ][а-яё]+\b/u',
        
        // Фамилия и инициалы с точками (Аверкин М.Е. или Аверкин М. Е.)
        '/\b[А-ЯЁ][а-яё]+\s+[А-ЯЁ]\s*\.[А-ЯЁ]\s*\./u',
        '/\b[А-ЯЁ][а-яё]+\s+[А-ЯЁ]\s*\./u',
        
        // Фамилия и инициалы без точек (Аверкин МЕ)
        '/\b[А-ЯЁ][а-яё]+\s+[А-ЯЁ]{1,3}\b/u',
        
        // Только инициалы с точками и тире (только 3 буквы)
        '/\b[А-ЯЁ]{3}\.?\s*[–\-]/u',
        
        // Только инициалы без точек (только 3 буквы)
        '/\b[А-ЯЁ]{3}\b/u',
    ];

    $matches = [];
    foreach ($fioPatterns as $pattern) {
        if (preg_match_all($pattern, $text, $found, PREG_SET_ORDER)) {
            foreach ($found as $match) {
                $fio = $match[0];
                
                // Убираем тире, скобки и точки в конце, если есть
                $fio = preg_replace('/[–\-\(\)]/u', ' ', $fio);
                $fio = preg_replace('/\s*\.\s*/u', '', $fio);
                $fio = preg_replace('/\s+/u', ' ', $fio);
                $fio = trim($fio);

                // Если инициалы без фамилии (ровно 3 заглавные буквы подряд)
                if (preg_match('/^[А-ЯЁ]{3}$/u', $fio)) {
                    $fio = implode(' ', mb_str_split($fio));
                }

                if (isValidFio($fio)) {
                    $matches[] = $fio;
                }
            }
        }
    }
    return array_unique($matches);
}

function isValidFio($fio) {
    // Если инициалы с пробелами (3 буквы), считаем валидным
    if (preg_match('/^([А-ЯЁ]\s){2}[А-ЯЁ]$/u', $fio)) {
        return true;
    }

    $parts = preg_split('/\s+/', $fio);
    if (count($parts) < 2) return false;
    if (!preg_match('/^[А-ЯЁ][а-яё]+$/u', $parts[0])) return false;
    foreach (array_slice($parts, 1) as $part) {
        if (!preg_match('/^([А-ЯЁ][а-яё]+|[А-ЯЁ]\.?)$/u', $part)) {
            return false;
        }
    }
    return true;
}

function findBitrixUserByFio($fioParts, &$logString = null) {
    $logMessage = "Поиск пользователя по ФИО: " . implode(' ', $fioParts) . "\n";
    
    // Если это 3 буквенные инициалы (АМЕ)
    if (count($fioParts) == 1 && preg_match('/^[А-ЯЁ]{3}$/u', $fioParts[0])) {
        $initials = $fioParts[0];
        $filter = [
            'LOGIC' => 'AND', // Изменили OR на AND - должны совпадать все условия
            '=ACTIVE' => 'Y',
            ['%=LAST_NAME%' => mb_substr($initials, 0, 1).'%'],
            ['%=NAME%' => mb_substr($initials, 1, 1).'%'],
            ['%=SECOND_NAME%' => mb_substr($initials, 2, 1).'%']
        ];
        
        $logMessage .= "Поиск по инициалам: {$initials}\n";
        $logMessage .= "Фильтр поиска: " . print_r($filter, true) . "\n";
        
        $user = UserTable::getList([
            'filter' => $filter,
            'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME'],
            'limit' => 1
        ])->fetch();

        if ($user) {
            $logMessage .= "Найден пользователь ID: {$user['ID']} (Фамилия: {$user['LAST_NAME']} Имя: {$user['NAME']} Отчество: {$user['SECOND_NAME']})\n";
            if ($logString !== null) $logString .= $logMessage;
            return $user['ID'];
        }
        
        $logMessage .= "Пользователь не найден\n";
        if ($logString !== null) $logString .= $logMessage;
        return 0;
    }

    // Остальная логика поиска (как было)
    $filter = ['LOGIC' => 'OR', '=ACTIVE' => 'Y'];
    
    if (count($fioParts) >= 3) {
        $filter[] = [
            '=LAST_NAME' => $fioParts[0],
            '=NAME' => $fioParts[1],
            '=SECOND_NAME' => $fioParts[2],
        ];
        $filter[] = [
            '%LAST_NAME' => $fioParts[0] . '%',
            '%NAME' => $fioParts[1] . '%',
            '%SECOND_NAME' => $fioParts[2] . '%',
        ];
    }
    
    if (count($fioParts) >= 2) {
        $filter[] = [
            '=LAST_NAME' => $fioParts[0],
            '=NAME' => $fioParts[1],
        ];
        $filter[] = [
            '%LAST_NAME' => $fioParts[0] . '%',
            '%NAME' => $fioParts[1] . '%',
        ];
        
        if (mb_strlen($fioParts[1]) == 1 || (mb_strlen($fioParts[1]) == 2 && substr($fioParts[1], -1) == '.')) {
            $filter[] = [
                '=LAST_NAME' => $fioParts[0],
                '%=NAME' => mb_substr($fioParts[1], 0, 1) . '%'
            ];
        }
    }
    
    if (count($fioParts) >= 1) {
        $filter[] = ['=LAST_NAME' => $fioParts[0]];
        $filter[] = ['%LAST_NAME' => $fioParts[0] . '%'];
        
        if (strpos($fioParts[0], ' ') !== false) {
            $parts = explode(' ', $fioParts[0]);
            if (count($parts) >= 2) {
                $filter[] = [
                    '=LAST_NAME' => $parts[0],
                    '%=NAME' => mb_substr($parts[1], 0, 1) . '%'
                ];
            }
        }
    }

    $logMessage .= "Фильтр поиска: " . print_r($filter, true) . "\n";
    
    $user = UserTable::getList([
        'filter' => $filter,
        'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME'],
        'limit' => 1
    ])->fetch();

    if ($user) {
        $logMessage .= "Найден пользователь ID: {$user['ID']} (Фамилия: {$user['LAST_NAME']} Имя: {$user['NAME']} Отчество: {$user['SECOND_NAME']})\n";
        if ($logString !== null) $logString .= $logMessage;
        return $user['ID'];
    }
    
    $logMessage .= "Пользователь не найден\n";
    if ($logString !== null) $logString .= $logMessage;
    return 0;
}

function extractDeadline($text, &$logString = null) {
    $today = new DateTime();
    $defaultDeadline = (clone $today)->add('31 days')->format('d.m.Y');
    $logMessage = "Извлечение срока из текста: {$text}\n";
    $patterns = [
        '/\d{2}\.\d{2}\.\d{4}/',
        '/\d{1,2}\s+[а-яё]+/ui',
        '/(\d+)\s+дн[еяёй]/ui',
        '/\d{2}\.\d{2}/'
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $matches)) {
            $dateStr = $matches[0];
            if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $dateStr)) return $dateStr;
            if (preg_match('/^(\d{1,2})\s+([а-яё]+)/ui', $dateStr, $m)) {
                $months = [
                    'января'=>1,'февраля'=>2,'марта'=>3,'апреля'=>4,
                    'мая'=>5,'июня'=>6,'июля'=>7,'августа'=>8,
                    'сентября'=>9,'октября'=>10,'ноября'=>11,'декабря'=>12
                ];
                $month = mb_strtolower($m[2]);
                if (isset($months[$month])) {
                    return sprintf('%02d.%02d.%04d', $m[1], $months[$month], $today->format('Y'));
                }
            }
            if (preg_match('/^\d{2}\.\d{2}$/', $dateStr)) return $dateStr . '.' . $today->format('Y');
            if (preg_match('/^\d+$/', $dateStr)) return (clone $today)->add("{$dateStr} days")->format('d.m.Y');
        }
    }
    return $defaultDeadline;
}

$logString .= "Начало обработки абзацев\n";
$processedParagraphs = [];
$processedExecutors = [];
$processedDeadlines = [];

foreach ($paragraphs as $index => $paragraph) {
    $logString .= "\nОбработка абзаца #" . ($index + 1) . ":\n{$paragraph}\n";
    $fios = findFioInText($paragraph);
    $deadline = extractDeadline($paragraph, $logString);
    $fios = array_unique(array_filter($fios));
    $logString .= "Найдены ФИО: " . implode(", ", $fios) . "\n";
    if (empty($fios)) {
        $logString .= "Пропуск абзаца - не найдено ФИО\n";
        continue;
    }
    $added = [];
    foreach ($fios as $fio) {
        $key = md5($paragraph.$fio.$deadline);
        if (!in_array($key, $added)) {
            // Основной поиск с разными вариантами
            $fioParts = preg_split('/\s+/', $fio);
            $searchVariants = [
                $fioParts,  // Оригинальный вариант
                array_reverse($fioParts), // Обратный порядок
                [implode(' ', $fioParts)], // Как одно слово
                [implode('-', $fioParts)], // Через дефис
            ];
            
            $userId = 0;
            foreach ($searchVariants as $variant) {
                if (empty(array_filter($variant))) continue;
                
                $userId = findBitrixUserByFio($variant, $logString);
                if ($userId) break;
            }
            
            $processedParagraphs[] = $paragraph;
            $processedExecutors[] = $userId;
            $processedDeadlines[] = $deadline;
            $added[] = $key;
            $logString .= "Добавлена задача для ФИО: {$fio}, исполнитель: {$userId}, срок: {$deadline}\n";
        }
    }
}

$logString .= "\nИтоговые данные:\n";
$logString .= "Абзацев: " . count($processedParagraphs) . "\n";
$logString .= "Исполнителей: " . implode(", ", $processedExecutors) . "\n";
$logString .= "Сроки: " . implode(", ", $processedDeadlines) . "\n";

$this->SetVariable('PARAGRAPHS', $processedParagraphs);
$this->SetVariable('EXECUTORS', $processedExecutors);
$this->SetVariable('DEAD_LINES', $processedDeadlines);
$this->SetVariable('LOG_STRING', $logString);