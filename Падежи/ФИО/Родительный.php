<?php
$root = $this->GetRootActivity();
$fullName = $root->GetVariable("FIO");

// Нормализация и разбиение на компоненты
$words = preg_split('/\s+/u', trim($fullName), -1, PREG_SPLIT_NO_EMPTY);

if (count($words) === 3) {
    list($surname, $name, $patronymic) = $words;
    
    // Определение пола по отчеству
    $gender = (mb_substr($patronymic, -2) === 'ич') ? 'male' : 'female';
    
    // Расширенные правила для фамилий
    $nonDeclinableEndings = [
        'ко', 'енко', 'о', 'ых', 'их', 'аго', 'яго', 'у', 'ю', 'иа',
        'ия', 'ая', 'яя', 'ц', 'ук', 'юк', 'ик', 'ель', 'ер', 'швили',
        'ян', 'зон', 'ман', 'киши', 'ски', 'цка', 'цко', 'ихи', 'ого'
    ];
    
    $isForeign = preg_match('/[a-z]/ui', $surname);
    $surnameLower = mb_strtolower($surname);
    $declinedSurname = $surname;
    
    // Склонение фамилии
    if (!$isForeign && !in_array(mb_substr($surnameLower, -2), $nonDeclinableEndings)) {
        $lastTwo = mb_substr($surnameLower, -2);
        $lastThree = mb_substr($surnameLower, -3);
        
        if ($gender === 'male') {
            switch (true) {
                case preg_match('/(ов|ев)$/u', $surnameLower):
                    $declinedSurname .= 'а';
                    break;
                case preg_match('/(ий|ый)$/u', $surnameLower):
                    $declinedSurname = mb_substr($surname, 0, -2) . 'ого';
                    break;
                case preg_match('/ец$/u', $surnameLower):
                    $declinedSurname = mb_substr($surname, 0, -2) . 'ца';
                    break;
                case preg_match('/ь$/u', $surnameLower):
                    $declinedSurname = mb_substr($surname, 0, -1) . 'я';
                    break;
                case preg_match('/[ая]$/u', $surnameLower):
                    $declinedSurname = preg_replace('/(а|я)$/u', 'ой', $surname);
                    break;
                default:
                    $declinedSurname .= 'а';
            }
        } else {
            switch (true) {
                case preg_match('/ая$/u', $surnameLower):
                    $declinedSurname = mb_substr($surname, 0, -2) . 'ой';
                    break;
                case preg_match('/яя$/u', $surnameLower):
                    $declinedSurname = mb_substr($surname, 0, -2) . 'ей';
                    break;
                case preg_match('/а$/u', $surnameLower):
                    $declinedSurname = mb_substr($surname, 0, -1) . 'ой';
                    break;
                case preg_match('/я$/u', $surnameLower):
                    $declinedSurname = mb_substr($surname, 0, -1) . 'и';
                    break;
                default:
                    $declinedSurname .= 'ой';
            }
        }
    }

    // Склонение имени
    $declinedName = $name;
    $nameLower = mb_strtolower($name);
    
    switch (true) {
        case preg_match('/(ий|ей)$/u', $nameLower):
            $declinedName = mb_substr($name, 0, -2) . 'ого';
            break;
        case preg_match('/ь$/u', $nameLower):
            $declinedName = mb_substr($name, 0, -1) . 'я';
            break;
        case preg_match('/а$/u', $nameLower):
            $declinedName = mb_substr($name, 0, -1) . 'ы';
            break;
        case preg_match('/я$/u', $nameLower):
            $declinedName = mb_substr($name, 0, -1) . 'и';
            break;
        case preg_match('/[бвгджзклмнпрстфхцчшщ]$/u', $nameLower):
            $declinedName .= 'а';
            break;
        default:
            $declinedName .= 'а';
    }

    // Склонение отчества
    $declinedPatronymic = $patronymic;
    $patronymicLower = mb_strtolower($patronymic);
    
    if ($gender === 'male') {
        if (preg_match('/ич$/u', $patronymicLower)) {
            $declinedPatronymic .= 'а';
        }
    } else {
        if (preg_match('/вна$/u', $patronymicLower)) {
            $declinedPatronymic = mb_substr($patronymic, 0, -3) . 'вны';
        } elseif (preg_match('/на$/u', $patronymicLower)) {
            $declinedPatronymic = mb_substr($patronymic, 0, -2) . 'ны';
        } elseif (preg_match('/инична$/u', $patronymicLower)) {
            $declinedPatronymic = mb_substr($patronymic, 0, -5) . 'иничны';
        }
    }

    $finalString = "$declinedSurname $declinedName $declinedPatronymic";
} else {
    $finalString = $fullName;
}

$root->SetVariable('FIO', $finalString);