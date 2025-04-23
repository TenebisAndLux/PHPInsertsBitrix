<?php
$root = $this->GetRootActivity();
$fullName = $root->GetVariable("FIO");

// Нормализация пробелов и разбиение на части
$words = preg_split('/\s+/u', trim($fullName), -1, PREG_SPLIT_NO_EMPTY);

// Проверяем корректность входных данных (ФИО должно содержать три слова)
if (count($words) === 3) {
    $surname = $words[0];
    $name = $words[1];
    $patronymic = $words[2];

    // Склонение фамилии в дательном падеже
    $lastCharSurname = mb_substr($surname, -1);
    $lastTwoCharsSurname = mb_substr($surname, -2);
    
    if ($lastTwoCharsSurname === 'ев' || $lastTwoCharsSurname === 'ов') {
        $surname = mb_substr($surname, 0, -2) . 'еву'; // Для фамилий на -ев, -ов
    } elseif ($lastCharSurname === 'а') {
        $surname = mb_substr($surname, 0, -1) . 'ой'; // Для женских фамилий на -а
    } elseif ($lastCharSurname === 'я') {
        $surname = mb_substr($surname, 0, -1) . 'е'; // Для женских фамилий на -я
    } elseif ($lastCharSurname === 'ь') {
        $surname = mb_substr($surname, 0, -1) . 'ю'; // Для фамилий на -ь
    } elseif (in_array($lastTwoCharsSurname, ['ий', 'ый'])) {
        $surname = mb_substr($surname, 0, -2) . 'ому'; // Для фамилий на -ий, -ый
    } else {
        $surname .= 'у'; // Общий случай
    }

    // Склонение имени в дательном падеже
    $lastCharName = mb_substr($name, -1);
    
    if ($lastCharName === 'а') {
        $name = mb_substr($name, 0, -1) . 'е'; // Для женских имен на -а
    } elseif ($lastCharName === 'я') {
        $name = mb_substr($name, 0, -1) . 'е'; // Для женских имен на -я
    } elseif ($lastCharName === 'й' || mb_substr($name, -2) === 'ий') {
        $name = mb_substr($name, 0, -1) . 'ю'; // Для имен на -й, -ий
    } else {
        $name .= 'у'; // Общий случай
    }

    // Склонение отчества в дательном падеже
    $lastTwoCharsPatronymic = mb_substr($patronymic, -2);
    
    if ($lastTwoCharsPatronymic === 'ич') {
        $patronymic = mb_substr($patronymic, 0, -2) . 'ичу'; // Для отчеств на -ич
    } elseif ($lastTwoCharsPatronymic === 'на') {
        $patronymic = mb_substr($patronymic, 0, -2) . 'не'; // Для отчеств на -на
    }

    // Собираем результат
    $finalString = trim("$surname $name $patronymic");
} else {
    // Если формат ФИО неправильный, возвращаем исходное значение
    $finalString = $fullName;
}

$root->SetVariable('FIO', $finalString);