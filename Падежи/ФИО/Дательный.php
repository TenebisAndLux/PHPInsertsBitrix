<?php
$root = $this->GetRootActivity();
$fullName = $root->GetVariable("FIO");

// Нормализация пробелов и разбиение на части
$words = preg_split('/\s+/u', trim($fullName), -1, PREG_SPLIT_NO_EMPTY);

// Списки исключений для имен и фамилий
$specialNames = [
    'Павел' => 'Павлу',
    'Лев'   => 'Льву',
    'Пётр'  => 'Петру',
    'Игорь' => 'Игорю',
    'Любовь' => 'Любови',
    // Добавьте при необходимости
];

if (count($words) === 3) {
    $surname = $words[0];
    $name = $words[1];
    $patronymic = $words[2];

    // Склонение фамилии в дательном падеже
    $lastCharSurname = mb_substr($surname, -1);
    $lastTwoCharsSurname = mb_substr($surname, -2);

    if ($lastTwoCharsSurname === 'ев' || $lastTwoCharsSurname === 'ов') {
        $surname .= 'у'; // Ганаев -> Ганаеву
    } elseif ($lastCharSurname === 'а') {
        $surname = mb_substr($surname, 0, -1) . 'ой'; // Иванова -> Ивановой
    } elseif ($lastCharSurname === 'я') {
        $surname = mb_substr($surname, 0, -1) . 'е'; // Кузнецкая -> Кузнецкой
    } elseif ($lastCharSurname === 'ь') {
        $surname = mb_substr($surname, 0, -1) . 'ю'; // Лебедь -> Лебедю
    } elseif (in_array($lastTwoCharsSurname, ['ий', 'ый'])) {
        $surname = mb_substr($surname, 0, -2) . 'ому'; // Строилий -> Строилиому
    } else {
        $surname .= 'у'; // Общий случай
    }

    // Склонение имени в дательном падеже
    if (isset($specialNames[$name])) {
        $name = $specialNames[$name];
    } else {
        $lastCharName = mb_substr($name, -1);
        $lastTwoCharsName = mb_substr($name, -2);

        if ($lastCharName === 'а') {
            $name = mb_substr($name, 0, -1) . 'е'; // Ольга -> Ольге
        } elseif ($lastCharName === 'я') {
            $name = mb_substr($name, 0, -1) . 'е'; // Мария -> Марие
        } elseif ($lastCharName === 'й') {
            $name = mb_substr($name, 0, -1) . 'ю'; // Сергей -> Сергею
        } elseif ($lastTwoCharsName === 'ий') {
            $name = mb_substr($name, 0, -2) . 'ию'; // Василий -> Василию
        } elseif ($lastCharName === 'ь') {
            $name = mb_substr($name, 0, -1) . 'ю'; // Игорь -> Игорю (но обработан выше)
        } else {
            $name .= 'у'; // Общий случай
        }
    }

    // Склонение отчества в дательном падеже
    $lastTwoCharsPatronymic = mb_substr($patronymic, -2);

    if ($lastTwoCharsPatronymic === 'ич') {
        $patronymic .= 'у'; // Юрьевич -> Юрьевичу
    } elseif ($lastTwoCharsPatronymic === 'на') {
        $patronymic = mb_substr($patronymic, 0, -1) . 'е'; // Сергеевна -> Сергеевне
    }

    // Собираем результат
    $finalString = trim("$surname $name $patronymic");
} else {
    // Если формат ФИО неправильный, возвращаем исходное значение
    $finalString = $fullName;
}

$root->SetVariable('FIO', $finalString);