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
$logString = "Начало обработки документа\n";
$logString .= "Тип документа: {$entityTypeName}, ID: {$entityId}\n";
$logString .= "Исходный текст:\n{$inputText}\n\n";

function findFioInText($text) {
    $fioPatterns = [
        // Полное ФИО (Фамилия Имя Отчество)
        '/\b([А-ЯЁ][а-яё]+)\s+([А-ЯЁ][а-яё]+)\s+([А-ЯЁ][а-яё]+)\b/u',
        
        // Фамилия и Имя
        '/\b([А-ЯЁ][а-яё]+)\s+([А-ЯЁ][а-яё]+)\b/u',
        
        // Фамилия и инициалы с точками (Аверкин М.Е. или Аверкин М. Е.)
        '/\b([А-ЯЁ][а-яё]+)\s+([А-ЯЁ])\.?\s*([А-ЯЁ])?\.?/u',
        
        // Фамилия и инициалы без точек (Аверкин МЕ)
        '/\b([А-ЯЁ][а-яё]+)\s+([А-ЯЁ]{1,3})\b/u',
        
        // Только фамилия (Гусаров) - длиннее 3 букв
        '/\b([А-ЯЁ][а-яё]{3,})(?=\s|$|\.|,|-)/u',
        
        // Только инициалы с точками (А.Б.В или А. Б. В.)
        '/\b([А-ЯЁ])\.\s*([А-ЯЁ])\.\s*([А-ЯЁ])\.?\b/u',
        
        // Только инициалы без точек (АБВ)
        '/\b([А-ЯЁ]{3})\b/u',
        
        // Только инициалы с пробелами (А Б В)
        '/\b([А-ЯЁ])\s+([А-ЯЁ])\s+([А-ЯЁ])\b/u',
        
        // Только инициалы с точками и тире (А.Б.В -)
        '/\b([А-ЯЁ])\.([А-ЯЁ])\.([А-ЯЁ])\.?\s*[–\-]/u'
    ];

    $result = [];
    
    foreach ($fioPatterns as $pattern) {
        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fio = '';
                
                // Обработка разных форматов
                if (count($match) > 4) {
                    // Полное ФИО или ФИ
                    $fio = trim("{$match[1]} {$match[2]} " . (isset($match[3]) ? $match[3] : ''));
                } elseif (isset($match[3])) {
                    // Инициалы (А.Б.В или А Б В)
                    $fio = "{$match[1]} {$match[2]} {$match[3]}";
                } elseif (isset($match[2])) {
                    // Фамилия + инициалы
                    if (mb_strlen($match[2]) > 1) {
                        // Для формата "Фамилия АБ" -> "Фамилия А Б"
                        $initials = implode(' ', mb_str_split($match[2]));
                        $fio = "{$match[1]} {$initials}";
                    } else {
                        $fio = "{$match[1]} {$match[2]}";
                    }
                } else {
                    // Только фамилия или инициалы (3 буквы)
                    if (mb_strlen($match[1]) === 3 && preg_match('/^[А-ЯЁ]{3}$/u', $match[1])) {
                        $fio = implode(' ', mb_str_split($match[1]));
                    } else {
                        $fio = $match[1];
                    }
                }
                
                // Нормализация
                $fio = preg_replace('/[–\-\(\)]/u', ' ', $fio);
                $fio = preg_replace('/\s*\.\s*/u', ' ', $fio);
                $fio = preg_replace('/\s+/u', ' ', $fio);
                $fio = trim($fio);
                
                // Проверка минимальной длины (если не инициалы)
                if (!preg_match('/^([А-ЯЁ]\s?){2,3}$/u', $fio) && mb_strlen(str_replace(' ', '', $fio)) < 3) {
                    continue;
                }
                
                $result[] = $fio;
            }
        }
    }
    
    return array_unique($result);
}

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

function isValidDate($dateStr) {
    // Проверка формата DD.MM.YYYY
    if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $dateStr)) {
        $parts = explode('.', $dateStr);
        return checkdate($parts[1], $parts[0], $parts[2]);
    }
    
    // Проверка формата DD.MM.YY
    if (preg_match('/^\d{2}\.\d{2}\.\d{2}$/', $dateStr)) {
        $parts = explode('.', $dateStr);
        return checkdate($parts[1], $parts[0], '20'.$parts[2]);
    }
    
    // Проверка формата DD.MM
    if (preg_match('/^\d{2}\.\d{2}$/', $dateStr)) {
        $parts = explode('.', $dateStr);
        return checkdate($parts[1], $parts[0], date('Y'));
    }
    
    // Проверка текстовой даты (23 апреля)
    $months = [
        'января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
        'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'
    ];
    
    if (preg_match('/^(\d{1,2})\s+('.implode('|', $months).')/ui', $dateStr)) {
        return true;
    }
    
    return false;
}

// Функция поиска сроков (включая недели)
function findDeadlines($text) {
    $patterns = [
        // Полная дата (23.04.2025 или 23.04.25)
        '/(\d{2}\.\d{2}\.(\d{4}|\d{2}))/',
        
        // Дата без года (23.04)
        '/(\d{2}\.\d{2})(?!\.\d{1,4})/',
        
        // Текстовый формат (23 апреля)
        '/(\d{1,2}\s+[а-яё]+)/ui',
        
        // Срок в неделях (2 недели)
        '/(\d+)\s+недел[ьи]/ui',
        
        // Срок в днях (5 дней)
        '/(\d+)\s+дн[еяёй]/ui',
        
        // Формат "до 01.05.25"
        '/до\s+(\d{2}\.\d{2}\.\d{2})/ui',
        
        // Формат "срок до 01.05"
        '/срок\s+до\s+(\d{2}\.\d{2})/ui'
    ];
    
    $deadlines = [];
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $dateStr = $match[1];
                
                // Для недель преобразуем в дату
                if (preg_match('/^\d+\s+недел[ьи]/ui', $match[0])) {
                    $weeks = (int)$dateStr;
                    $date = (new DateTime())->add("{$weeks} weeks");
                    $dateStr = $date->format('d.m.Y');
                }
                // Для дней преобразуем в дату
                elseif (preg_match('/^\d+\s+дн[еяёй]/ui', $match[0])) {
                    $days = (int)$dateStr;
                    $date = (new DateTime())->add("{$days} days");
                    $dateStr = $date->format('d.m.Y');
                }
                // Для дат с двухзначным годом (01.05.25)
                elseif (preg_match('/^\d{2}\.\d{2}\.\d{2}$/', $dateStr)) {
                    $dateStr = substr($dateStr, 0, 6) . '20' . substr($dateStr, 6);
                }
                
                if (isValidDate($dateStr)) {
                    $deadlines[] = $dateStr;
                }
            }
        }
    }
    
    usort($deadlines, function($a, $b) {
        return strtotime(str_replace('.', '-', $a)) - strtotime(str_replace('.', '-', $b));
    });
    
    return array_unique($deadlines);
}


function findBitrixUser($fioInput, &$logString = null) {
    $log = "Поиск пользователя по запросу: {$fioInput}\n";


    // Разбиваем вход на части
    $fioInput = trim($fioInput);
    $fioParts = preg_split('/\s+/', $fioInput);


    // Поиск по инициалам, если 3 заглавные буквы (например, АВС)
    if (count($fioParts) == 1 && preg_match('/^[А-ЯЁ]{3}$/u', $fioParts[0])) {
        $initials = $fioParts[0];
        $log .= "Поиск по инициалам: {$initials}\n";

        $users = UserTable::getList([
            'filter' => [
                '=ACTIVE' => 'Y',
                'LAST_NAME' => mb_substr($initials, 0, 1).'%',
                'NAME' => mb_substr($initials, 1, 1).'%',
                'SECOND_NAME' => mb_substr($initials, 2, 1).'%',
            ],
            'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME'],
        ])->fetchAll();

        foreach ($users as $user) {
            if (
                mb_substr($user['LAST_NAME'], 0, 1) === mb_substr($initials, 0, 1) &&
                mb_substr($user['NAME'], 0, 1) === mb_substr($initials, 1, 1) &&
                mb_substr($user['SECOND_NAME'], 0, 1) === mb_substr($initials, 2, 1)
            ) {
                $fullName = "{$user['LAST_NAME']} {$user['NAME']} {$user['SECOND_NAME']}";
                $log .= "Найден по инициалам: {$fullName} (ID: {$user['ID']})\n";
                if ($logString !== null) $logString .= $log;
                return $fullName;
            }
        }

        $log .= "Не найден по инициалам.\n";
        if ($logString !== null) $logString .= $log;
        return false;
    }

    // Уровни поиска от самого точного к менее точному
    $searchLevels = [];

    if (count($fioParts) === 3) {
        $searchLevels[] = [
            'desc' => 'Точное совпадение ФИО',
            'filter' => [
                '=ACTIVE' => 'Y',
                '=LAST_NAME' => $fioParts[0],
                '=NAME' => $fioParts[1],
                '=SECOND_NAME' => $fioParts[2],
            ],
        ];
    }

    if (count($fioParts) === 2) {
        $searchLevels[] = [
            'desc' => 'Точное совпадение ФИ',
            'filter' => [
                '=ACTIVE' => 'Y',
                '=LAST_NAME' => $fioParts[0],
                '=NAME' => $fioParts[1],
            ],
        ];
    }

    if (count($fioParts) === 1) {
        $searchLevels[] = [
            'desc' => 'Точное совпадение фамилии',
            'filter' => [
                '=ACTIVE' => 'Y',
                '=LAST_NAME' => $fioParts[0],
            ],
        ];
    }

    // Мягкий поиск (по началу)
    if (count($fioParts) >= 3) {
        $searchLevels[] = [
            'desc' => 'Начало ФИО',
            'filter' => [
                '=ACTIVE' => 'Y',
                'LAST_NAME' => $fioParts[0] . '%',
                'NAME' => $fioParts[1] . '%',
                'SECOND_NAME' => $fioParts[2] . '%',
            ],
        ];
    }

    if (count($fioParts) >= 2) {
        $searchLevels[] = [
            'desc' => 'Начало ФИ',
            'filter' => [
                '=ACTIVE' => 'Y',
                'LAST_NAME' => $fioParts[0] . '%',
                'NAME' => $fioParts[1] . '%',
            ],
        ];
    }

    if (count($fioParts) >= 1) {
        $searchLevels[] = [
            'desc' => 'Начало фамилии',
            'filter' => [
                '=ACTIVE' => 'Y',
                'LAST_NAME' => $fioParts[0] . '%',
            ],
        ];
    }

    // Поиск по уровням
    foreach ($searchLevels as $level) {
        $log .= "Пробуем: {$level['desc']}\n";
        $user = UserTable::getList([
            'filter' => $level['filter'],
            'select' => ['ID', 'NAME', 'LAST_NAME', 'SECOND_NAME'],
            'limit' => 1,
        ])->fetch();

        if ($user) {
            $fullName = "{$user['LAST_NAME']} {$user['NAME']} {$user['SECOND_NAME']}";
            $log .= "Найден: {$fullName} (ID: {$user['ID']})\n";
            if ($logString !== null) $logString .= $log;
            return $fullName;
        }
    }
    $log .= "Пользователь не найден.\n";
    if ($logString !== null) $logString .= $log;
    return false;
}


// Функция обработки абзаца (без удаления исполнителей и сроков)
function processParagraph($paragraph, &$logString) {
    $original = $paragraph;
    $logString .= "Обработка абзаца: {$paragraph}\n";
    
    // 1. Находим ФИО (без удаления из текста)
    $fios = findFioInText($paragraph);
    $logString .= "Найдены ФИО: ".($fios ? implode(", ", $fios) : "нет")."\n";
    
    // 2. Находим сроки (без удаления из текста)
    $deadlines = findDeadlines($paragraph);
    $logString .= "Найдены сроки: ".($deadlines ? implode(", ", $deadlines) : "нет")."\n";
    
    $executors = [];
    $finalDeadline = null;
    
    // 3. Обработка ФИО (только поиск, без удаления)
    foreach ($fios as $fio) {
        $fullName = findBitrixUser($fio, $logString);
        if ($fullName) {
            $executors[] = $fullName;
        }
    }
    
    // 4. Определяем крайний срок (без удаления дат из текста)
    if (!empty($deadlines)) {
        $finalDeadline = end($deadlines);
    }
    
    // 5. Очистка текста (только форматирование)
    $paragraph = preg_replace('/\s+/', ' ', $paragraph);
    $paragraph = preg_replace('/\s*([.,;:])\s*/', '$1 ', $paragraph);
    $paragraph = preg_replace('/\s*г\.?/u', '', $paragraph);
    $paragraph = preg_replace('/\s*-\s*/u', ' ', $paragraph);
    $paragraph = trim($paragraph);
    
    // 6. Добавляем исполнителей и срок в конец (если найдены)
    if (!empty($executors)) {
        $paragraph .= "\nИсполнители: ".implode(", ", $executors).".";
    }
    
    if ($finalDeadline) {
        $paragraph .= "\nКрайний срок: ".$finalDeadline.".";
    }
    
    $logString .= "Результат:\n{$paragraph}\n\n";
    return $paragraph;
}

$inputText = str_replace(["\r\n", "\r"], "\n", $inputText);
$inputText = preg_replace("/\n{3,}/", "\n\n", $inputText);
$allParagraphs = preg_split('/(?<=\n\n)/', $inputText, -1, PREG_SPLIT_NO_EMPTY);
$logString .= "Найдено абзацев: " . count($allParagraphs)-1 . "\n";

$processedParagraphs = [];
// Пропускаем первый абзац, начиная с индекса 1
for ($i = 1; $i < count($allParagraphs); $i++) {
    $paragraph = trim($allParagraphs[$i]);
    if (!empty($paragraph)) {
        $processedParagraphs[] = processParagraph($paragraph, $logString);
    }
}

// Если нужно, можно добавить первый абзац без изменений:
$processedParagraphs = array_merge([trim($allParagraphs[0])], $processedParagraphs);

$this->SetVariable('PARAGRAPHS', $processedParagraphs);
$this->SetVariable('LOG_STRING', $logString);