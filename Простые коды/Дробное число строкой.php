<?php
// Получаем значение цены квартиры
$root = $this->GetRootActivity();
$value = $root->GetVariable("APARTMENT_PRICE_MONEY");

function numberToWords($number, $flag = FALSE) { 
    $words = [ 
        0 => 'ноль', 
        1 => 'один', 
        2 => 'два', 
        3 => 'три', 
        4 => 'четыре', 
        5 => 'пять', 
        6 => 'шесть', 
        7 => 'семь', 
        8 => 'восемь', 
        9 => 'девять', 
        10 => 'десять', 
        11 => 'одиннадцать', 
        12 => 'двенадцать', 
        13 => 'тринадцать', 
        14 => 'четырнадцать', 
        15 => 'пятнадцать', 
        16 => 'шестнадцать', 
        17 => 'семнадцать', 
        18 => 'восемнадцать', 
        19 => 'девятнадцать', 
        20 => 'двадцать', 
        30 => 'тридцать', 
        40 => 'сорок', 
        50 => 'пятьдесят', 
        60 => 'шестьдесят', 
        70 => 'семьдесят', 
        80 => 'восемьдесят', 
        90 => 'девяносто', 
        100 => 'сто', 
        200 => 'двести', 
        300 => 'триста', 
        400 => 'четыреста', 
        500 => 'пятьсот', 
        600 => 'шестьсот', 
        700 => 'семьсот', 
        800 => 'восемьсот', 
        900 => 'девятьсот' 
    ]; 

    if ($number < 1000) { 
        if ($number < 20) { 
            if ($flag && $number == 2) {
            return 'две'; 
            } else {
            return $words[$number];
        }
        } elseif ($number < 100) { 
            return $words[floor($number / 10) * 10] . ($number % 10 ? ' ' . ($flag && $number % 10 == 2 ? 'две' : $words[$number % 10]) : ''); 
        } else { 
            return $words[floor($number / 100) * 100] . ($number % 100 ? ' ' . ($flag ? numberToWords($number % 100, TRUE) : numberToWords($number % 100)) : ''); 
        } 
    } elseif ($number < 1000000) {
        return numberToWords(floor($number / 1000), TRUE) . ' ' . getThousandForm(floor($number / 1000)) . ($number % 1000 ? ' ' . numberToWords($number % 1000) : ''); 
    } else { 
        return numberToWords(floor($number / 1000000)) . ' ' . getMillionForm(floor($number / 1000000)) . ($number % 1000000 ? ' ' . numberToWords($number % 1000000) : ''); 
    } 
} 

function getThousandForm($number) { 
    $lastDigit = $number % 10; 
    $lastTwoDigits = $number % 100; 

    if ($lastTwoDigits >= 11 && $lastTwoDigits <= 14) { 
        return 'тысяч'; 
    } 

    switch ($lastDigit) { 
        case 1: return 'тысяча'; // "одна тысяча"
        case 2: return 'тысячи'; // "две тысячи"
        case 3: return 'тысячи'; // "три тысячи"
        case 4: return 'тысячи'; // "четыре тысячи"
        default: return 'тысяч'; // "пять тысяч" и далее
    } 
} 

function getMillionForm($number) { 
    $lastDigit = $number % 10; 
    $lastTwoDigits = $number % 100; 

    if ($lastTwoDigits >= 11 && $lastTwoDigits <= 14) { 
        return 'миллионов'; 
    } 

    switch ($lastDigit) { 
        case 1: return 'миллион'; // "один миллион"
        case 2: return 'миллиона'; // "два миллиона"
        case 3: return 'миллиона'; // "три миллиона"
        case 4: return 'миллиона'; // "четыре миллиона"
        default: return 'миллионов'; // "пять миллионов" и далее
    } 
} 

function formatPrice($amount) { 
    list($rubles, $kopecks) = explode('.', number_format($amount, 2, '.', '')); 
    $rubles = (int)$rubles; 
    $kopecks = (int)$kopecks; 

    $rublesInWords = numberToWords($rubles); 
    $kopecksInWords = numberToWords($kopecks); 

    return "{$rubles} ({$rublesInWords}) рублей {$kopecks} копеек ({$kopecksInWords})"; 
}

// Предполагаем, что $APARTMENT_PRICE_MONEY — это значение в формате "Деньги" 
$price = $value; 
 
// Проверяем, если цена больше нуля 
if ($price > 0) { 
    // Форматируем цену с учетом рублей и копеек 
    $rubles = floor($price); // Целая часть (рубли) 
    $kopecks = round(($price - $rubles) * 100); // Дробная часть (копейки) 
 
    // Форматируем вывод 
    $formattedNumber = formatPrice($price);
} else { 
    // Если цена равна нулю 
    $formattedNumber = "0 рублей"; 
    $textValue = "ноль"; 
    $kopecks = "00"; 
} 

// Формируем окончательный вывод
$finalOutput = $formattedNumber

// Присваиваем значение переменной
$root->SetVariable('PRICE_OF_THE_APARTMENT', $finalOutput);