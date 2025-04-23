<?php
$root = $this->GetRootActivity();
$string = $root->GetVariable("dolj");
$string = mb_strtolower($string);

// Разбиваем строку на слова
$words = preg_split('/\s+/', trim($string));
$accusativeWords = [];

foreach ($words as $word) {
    // Особые случаи и исключения
    if ($word === 'медсестра') {
        $accusativeWords[] = 'медсестру';
        continue;
    }
    if ($word === '"зеленый') {
        $accusativeWords[] = '"Зеленый';
        continue;
    }
    if ($word === 'сад') {
        $accusativeWords[] = 'сад';
        continue;
    }
    if ($word === 'комфортный"') {
        $accusativeWords[] = 'Комфортный"';
        continue;
    }

    // Слова, которые не требуют изменения
    $nonDeclinableWords = [
        'общим', 'бренд', 'вопросам', 'битрикс', 'it-интернет', 
        'спецконтингент', 'металлоконструкций', 'гаражом', 'закупок', 
        'изысканий', 'кострукций', '1с', 'тс', 'ст', 'мкд', 'пто', 
        'скс', 'ск', 'сму'
    ];

    if (in_array($word, $nonDeclinableWords)) {
        $accusativeWords[] = $word;
        continue;
    }

    // Склонение слова
    $lastLetter = mb_substr($word, -1);
    $lastTwoLetters = mb_substr($word, -2);
    $lastThreeLetters = mb_substr($word, -3);

    if ($lastLetter === 'р') {
        $accusativeWords[] = mb_substr($word, 0, -1) . 'ра';
    } elseif ($lastLetter === 'к') {
        $accusativeWords[] = mb_substr($word, 0, -1) . 'ка';
    } elseif ($lastLetter === 'б') {
        $accusativeWords[] = mb_substr($word, 0, -1) . 'ба';
    } elseif ($lastLetter === 'ь') {
        $accusativeWords[] = mb_substr($word, 0, -1) . 'я';
    } elseif ($lastLetter === 'г') {
        $accusativeWords[] = mb_substr($word, 0, -1) . 'га';
    } elseif ($lastTwoLetters === 'ст') {
        $accusativeWords[] = mb_substr($word, 0, -1) . 'та';
    } elseif ($lastLetter === 'т') {
        $accusativeWords[] = mb_substr($word, 0, -1) . 'та';
    } elseif ($lastThreeLetters === 'щий') {
        $accusativeWords[] = mb_substr($word, 0, -2) . 'его';
    } elseif ($lastTwoLetters === 'ий') {
        $accusativeWords[] = mb_substr($word, 0, -2) . 'ого';
    } elseif ($lastLetter === 'п') {
        $accusativeWords[] = mb_substr($word, 0, -1) . 'па';
    } elseif ($lastLetter === 'м') {
        $accusativeWords[] = mb_substr($word, 0, -1) . 'ма';
    } elseif ($lastTwoLetters === 'ая') {
        $accusativeWords[] = mb_substr($word, 0, -2) . 'ую';
    } elseif ($lastTwoLetters === 'ый') {
        $accusativeWords[] = mb_substr($word, 0, -2) . 'ого';
    } else {
        $accusativeWords[] = $word;
    }
}

// Собираем слова обратно в строку
$finalString = implode(' ', $accusativeWords);
$root->SetVariable('dolj', $finalString);