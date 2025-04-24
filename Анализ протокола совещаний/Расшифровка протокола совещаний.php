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

// Функция проверки валидности ФИО
function isValidFio($fio) {
    // Если инициалы с пробелами (3 буквы), считаем валидным
    if (preg_match('/^([А-ЯЁ]\s){2}[А-ЯЁ]$/u', $fio)) {
        return true;
    }

    $parts = preg_split('/\s+/', $fio);
    if (count($parts) < 2) return false;
    
    foreach ($parts as $part) {
        if (mb_strlen($part) < 1) {
            return false;
        }
    }
    
    return true;
}

// Функция поиска ФИО в тексте
function findFioInText($text) {
    $fioPatterns = [
        // Полное ФИО (Фамилия Имя Отчество)
        '/\b[А-ЯЁ][а-яё]+\s+[А-ЯЁ][а-яё]+\s+[А-ЯЁ][а-яё]+\b/u',
        
        // Фамилия и Имя
        '/\b[А-ЯЁ][а-яё]+\s+[А-ЯЁ][а-яё]+\b/u',
        
        // Фамилия и инициалы с точками (Аверкин М.Е. или Аверкин М. Е.)
        '/\b([А-ЯЁ][а-яё]+)\s+([А-ЯЁ])\.?\s*([А-ЯЁ])?\.?/u',
        
        // Фамилия и инициалы без точек (Аверкин МЕ)
        '/\b([А-ЯЁ][а-яё]+)\s+([А-ЯЁ]{1,3})\b/u',
        
        // Только инициалы с точками и тире (только 3 буквы)
        '/\b[А-ЯЁ]{3}\.?\s*[–\-]/u',
        
        // Только инициалы без точек (только 3 буквы)
        '/\b[А-ЯЁ]{3}\b/u',
    ];

    $matches = [];
    $processedNames = []; // Для отслеживания уже обработанных фамилий
    
    foreach ($fioPatterns as $pattern) {
        if (preg_match_all($pattern, $text, $found, PREG_SET_ORDER)) {
            foreach ($found as $match) {
                // Для паттерна с инициалами с точками
                if (count($match) >= 4 && !empty($match[3])) {
                    $fio = $match[1].' '.$match[2].' '.$match[3];
                    $lastName = $match[1];
                    if (!isset($processedNames[$lastName])) {
                        $matches[] = $fio;
                        $processedNames[$lastName] = true;
                    }
                    continue;
                }
                
                // Для паттерна с инициалами без точек
                if (count($match) >= 3 && !empty($match[2]) && mb_strlen($match[2]) > 1) {
                    $initials = preg_split('//u', $match[2], -1, PREG_SPLIT_NO_EMPTY);
                    $fio = $match[1].' '.implode(' ', $initials);
                    $lastName = $match[1];
                    if (!isset($processedNames[$lastName])) {
                        $matches[] = $fio;
                        $processedNames[$lastName] = true;
                    }
                    continue;
                }
                
                $fio = $match[0];
                $fio = preg_replace('/[–\-\(\)]/u', ' ', $fio);
                $fio = preg_replace('/\s*\.\s*/u', ' ', $fio);
                $fio = preg_replace('/\s+/u', ' ', $fio);
                $fio = trim($fio);

                // Обработка инициалов без фамилии
                if (preg_match('/^[А-ЯЁ]{3}$/u', $fio)) {
                    $fio = implode(' ', mb_str_split($fio));
                }

                if (isValidFio($fio)) {
                    // Извлекаем фамилию для проверки дубликатов
                    $parts = explode(' ', $fio);
                    $lastName = $parts[0];
                    
                    if (!isset($processedNames[$lastName])) {
                        $matches[] = $fio;
                        $processedNames[$lastName] = true;
                    }
                }
            }
        }
    }
    return array_unique($matches);
}

function findBitrixUserByFio($fioParts, &$logString = null) {
    $logMessage = "Поиск пользователя по ФИО: " . implode(' ', $fioParts) . "\n";
    
    // Если это 3 буквенные инициалы (АМЕ)
    if (count($fioParts) == 1 && preg_match('/^[А-ЯЁ]{3}$/u', $fioParts[0])) {
        $initials = $fioParts[0];
        $logMessage .= "Поиск по инициалам: {$initials}\n";
        
        // 1. Точное совпадение инициалов (первые буквы ФИО)
        $users = UserTable::getList([
            'filter' => [
                '=ACTIVE' => 'Y',
                'LAST_NAME' => mb_substr($initials, 0, 1).'%',
                'NAME' => mb_substr($initials, 1, 1).'%',
                'SECOND_NAME' => mb_substr($initials, 2, 1).'%'
            ],
            'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME'],
        ])->fetchAll();

        foreach ($users as $user) {
            $lastNameInitial = mb_substr($user['LAST_NAME'], 0, 1);
            $nameInitial = mb_substr($user['NAME'], 0, 1);
            $secondNameInitial = mb_substr($user['SECOND_NAME'], 0, 1);
            
            if ($lastNameInitial == mb_substr($initials, 0, 1) &&
               $nameInitial == mb_substr($initials, 1, 1) &&
               $secondNameInitial == mb_substr($initials, 2, 1)) {
                
                $logMessage .= "Точное совпадение инициалов: ID: {$user['ID']} (Фамилия: {$user['LAST_NAME']} Имя: {$user['NAME']} Отчество: {$user['SECOND_NAME']})\n";
                if ($logString !== null) $logString .= $logMessage;
                return $user['ID'];
            }
        }
        
        $logMessage .= "Пользователь с точными инициалами не найден\n";
        if ($logString !== null) $logString .= $logMessage;
        return 0;
    }

    // Определяем уровни поиска
    $searchLevels = [];
    
    // Полное совпадение ФИО
    if (count($fioParts) >= 3) {
        $searchLevels[] = [
            'name' => 'Полное совпадение ФИО',
            'filter' => [
                'LAST_NAME' => $fioParts[0],
                'NAME' => $fioParts[1],
                'SECOND_NAME' => $fioParts[2],
            ]
        ];
    }
    
    // Частичное совпадение ФИО (по началу)
    if (count($fioParts) >= 3) {
        $searchLevels[] = [
            'name' => 'Частичное совпадение ФИО (по началу)',
            'filter' => [
                'LAST_NAME' => $fioParts[0].'%',
                'NAME' => $fioParts[1].'%',
                'SECOND_NAME' => $fioParts[2].'%',
            ]
        ];
    }
    
    // Полное совпадение ФИ (без отчества)
    if (count($fioParts) >= 2) {
        $searchLevels[] = [
            'name' => 'Полное совпадение ФИ',
            'filter' => [
                'LAST_NAME' => $fioParts[0],
                'NAME' => $fioParts[1],
            ]
        ];
    }
    
    // Частичное совпадение ФИ (по началу)
    if (count($fioParts) >= 2) {
        $searchLevels[] = [
            'name' => 'Частичное совпадение ФИ (по началу)',
            'filter' => [
                'LAST_NAME' => $fioParts[0].'%',
                'NAME' => $fioParts[1].'%',
            ]
        ];
    }
    
    // Поиск по фамилии и первой букве имени
    if (count($fioParts) >= 2 && (mb_strlen($fioParts[1]) == 1 || (mb_strlen($fioParts[1]) == 2 && substr($fioParts[1], -1) == '.'))) {
        $searchLevels[] = [
            'name' => 'По фамилии и первой букве имени',
            'filter' => [
                'LAST_NAME' => $fioParts[0],
                'NAME' => mb_substr($fioParts[1], 0, 1).'%',
            ]
        ];
    }
    
    // Полное совпадение фамилии
    if (count($fioParts) >= 1) {
        $searchLevels[] = [
            'name' => 'Полное совпадение фамилии',
            'filter' => [
                'LAST_NAME' => $fioParts[0],
            ]
        ];
    }
    
    // Частичное совпадение фамилии
    if (count($fioParts) >= 1) {
        $searchLevels[] = [
            'name' => 'Частичное совпадение фамилии',
            'filter' => [
                'LAST_NAME' => $fioParts[0].'%',
            ]
        ];
    }
    
    // Если фамилия содержит пробел (например, "Иванов Петр")
    if (count($fioParts) == 1 && strpos($fioParts[0], ' ') !== false) {
        $parts = explode(' ', $fioParts[0]);
        if (count($parts) >= 2) {
            $searchLevels[] = [
                'name' => 'По фамилии и первой букве имени из одной строки',
                'filter' => [
                    'LAST_NAME' => $parts[0],
                    'NAME' => mb_substr($parts[1], 0, 1).'%',
                ]
            ];
        }
    }

    // Выполняем поиск по уровням
    foreach ($searchLevels as $level) {
        $logMessage .= "Уровень поиска: {$level['name']}\n";
        $logMessage .= "Фильтр: " . print_r($level['filter'], true) . "\n";
        
        $user = UserTable::getList([
            'filter' => $level['filter'],
            'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME'],
            'limit' => 1
        ])->fetch();
        
        if ($user) {
            $logMessage .= "Найден пользователь ID: {$user['ID']} (Фамилия: {$user['LAST_NAME']} Имя: {$user['NAME']} Отчество: {$user['SECOND_NAME']})\n";
            if ($logString !== null) $logString .= $logMessage;
            return $user['ID'];
        }
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

// Получаем массив дат
$deadLines = $processedDeadlines;

// Преобразуем строки дат в объекты DateTime для корректного сравнения
$dateObjects = [];
foreach ($deadLines as $dateStr) {
    try {
        $dateObjects[] = new DateTime($dateStr);
    } catch (Exception $e) {
        // Обработка ошибки неверного формата даты
        error_log("Некорректный формат даты: " . $dateStr);
    }
}

// Если есть валидные даты, находим максимальную
if (!empty($dateObjects)) {
    $maxDate = max($dateObjects);
    $maxDateStr = $maxDate->format('Y-m-d'); // или другой нужный формат
    $this->SetVariable('maxDate', $maxDateStr);
} else {
    $this->SetVariable('maxDate', null); // или пустая строка, или значение по умолчанию
}

// Обновляем лог
$this->SetVariable('LOG_STRING', $logString);