<?php
use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;

Loader::includeModule('tasks');
Loader::includeModule('crm');

$rootActivity = $this->GetRootActivity();
$inputText = $rootActivity->GetVariable('INPUTTEXT');

// Функция проверки валидности ФИО
function isValidFio($fio) {
    $parts = explode(' ', $fio);
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
    foreach ($fioPatterns as $pattern) {
        if (preg_match_all($pattern, $text, $found, PREG_SET_ORDER)) {
            foreach ($found as $match) {
                // Для паттерна с инициалами с точками
                if (count($match) >= 4 && !empty($match[3])) {
                    $fio = $match[1].' '.$match[2].' '.$match[3];
                    $matches[] = $fio;
                    continue;
                }
                
                // Для паттерна с инициалами без точек
                if (count($match) >= 3 && !empty($match[2]) && mb_strlen($match[2]) > 1) {
                    $initials = preg_split('//u', $match[2], -1, PREG_SPLIT_NO_EMPTY);
                    $fio = $match[1].' '.implode(' ', $initials);
                    $matches[] = $fio;
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
                    $matches[] = $fio;
                }
            }
        }
    }
    return array_unique($matches);
}

// Парсинг ФИО на составляющие
function parseFio($fioString) {
    $parts = explode(' ', $fioString);
    $result = [
        'LAST_NAME' => '',
        'NAME' => '',
        'SECOND_NAME' => ''
    ];
    
    if (count($parts) >= 1) $result['LAST_NAME'] = $parts[0];
    if (count($parts) >= 2) $result['NAME'] = $parts[1];
    if (count($parts) >= 3) $result['SECOND_NAME'] = $parts[2];
    
    return $result;
}

// Основная логика
$foundFios = findFioInText($inputText);

if (!empty($foundFios)) {
    // Берем первое найденное ФИО
    $fio = $foundFios[0];
    $parsed = parseFio($fio);
    
    $this->SetVariable('SECOND_NAME', $parsed['SECOND_NAME']);
    $this->SetVariable('NAME', $parsed['NAME']);
    $this->SetVariable('LAST_NAME', $parsed['LAST_NAME']);
} else {
    // Если ФИО не найдено, устанавливаем пустые значения
    $this->SetVariable('SECOND_NAME', '');
    $this->SetVariable('NAME', '');
    $this->SetVariable('LAST_NAME', '');
}