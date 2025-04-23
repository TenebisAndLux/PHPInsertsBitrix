<?php
$root = $this->GetRootActivity();
$fullName = $root->GetVariable("FIO");

// Нормализация пробелов и разбиение на части
$words = preg_split('/\s+/u', trim($fullName), -1, PREG_SPLIT_NO_EMPTY);

if (count($words) === 3) {
    list($surname, $name, $patronymic) = $words;

    // Определение пола по отчеству
    $gender = (mb_substr($patronymic, -2) === 'ич') ? 'male' : 'female';

    // Расширенный список исключений для фамилий
    $nonDeclinableEndings = [
        'ко', 'енко', 'о', 'ых', 'их', 'аго', 'яго', 'у', 'ю', 'иа', 
        'ия', 'ая', 'яя', 'ц', 'ук', 'юк', 'ик', 'ель', 'ер', 'швили', 
        'ян', 'зон', 'ман', 'киши', 'ски', 'цка', 'цко', 'ихи', 'ых', 'ого'
    ];

    // Проверка на иностранные фамилии (примерная проверка)
    $isForeign = preg_match('/[a-z]/ui', $surname);

    // Склонение фамилии
    $surnameLower = mb_strtolower($surname);
    $declinedSurname = $surname;
    $lastChar = mb_substr($surnameLower, -1);
    $lastTwoChars = mb_substr($surnameLower, -2);
    $lastThreeChars = mb_substr($surnameLower, -3);

    if (!$isForeign && !in_array($lastTwoChars, $nonDeclinableEndings) && !in_array($lastThreeChars, $nonDeclinableEndings)) {
        if ($gender === 'male') {
            if (preg_match('/ий$/u', $surnameLower)) {
                $declinedSurname = mb_substr($surname, 0, -2) . 'ого';
            } elseif (preg_match('/ый$/u', $surnameLower)) {
                $declinedSurname = mb_substr($surname, 0, -2) . 'ого';
            } elseif (preg_match('/(ец|ёц)$/u', $surnameLower)) {
                $declinedSurname = mb_substr($surname, 0, -2) . 'ца';
            } elseif ($lastChar === 'ь') {
                $declinedSurname = mb_substr($surname, 0, -1) . 'я';
            } elseif (preg_match('/[ая]$/u', $surnameLower)) {
                $declinedSurname = preg_replace('/(а|я)$/u', 'у', $surname);
            } else {
                $declinedSurname .= 'а';
            }
        } else {
            if (preg_match('/а$/u', $surnameLower)) {
                $declinedSurname = mb_substr($surname, 0, -1) . 'у';
            } elseif (preg_match('/я$/u', $surnameLower)) {
                $declinedSurname = mb_substr($surname, 0, -1) . 'ю';
            }
        }
    }

    // Склонение имени
    $declinedName = $name;
    $nameLower = mb_strtolower($name);
    $nameEnding = mb_substr($nameLower, -2);

    switch (true) {
            case preg_match('/[ая]$/u', $nameLower):
                $declinedName = preg_replace('/(а|я)$/u', 'у', $name);
                break;
            case preg_match('/ий$/u', $nameLower):
                $declinedName = mb_substr($name, 0, -2) . 'ия';
                break;
            case preg_match('/ай$/u', $nameLower):
                $declinedName = mb_substr($name, 0, -2) . 'ая';
                break;
            case preg_match('/ей$/u', $nameLower):
                $declinedName = mb_substr($name, 0, -2) . 'ея';
                break;
            case preg_match('/ь$/u', $nameLower):
                $declinedName = mb_substr($name, 0, -1) . 'я';
                break;
            case preg_match('/[бвгджзклмнпрстфхцчшщ]$/u', $nameLower):
                $declinedName .= 'а';
                break;
    }

    // Склонение отчества
    $declinedPatronymic = $patronymic;
    $patronymicLower = mb_strtolower($patronymic);

    if ($gender === 'male') {
        if (mb_substr($patronymicLower, -2) === 'ич') {
            $declinedPatronymic .= 'а';
        }
    } else {
        if (preg_match('/на$/u', $patronymicLower)) {
            $declinedPatronymic = mb_substr($patronymic, 0, -1) . 'у';
        } elseif (preg_match('/вна$/u', $patronymicLower)) {
            $declinedPatronymic = mb_substr($patronymic, 0, -2) . 'вну';
        } elseif (preg_match('/инична$/u', $patronymicLower)) {
            $declinedPatronymic = mb_substr($patronymic, 0, -3) . 'иничну';
        }
    }

    $finalString = "$declinedSurname $declinedName $declinedPatronymic,";
} else {
    $finalString = $fullName;
}

$root->SetVariable('FIO', $finalString);