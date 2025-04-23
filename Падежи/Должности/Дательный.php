<?php
$root = $this->GetRootActivity();
$string = $root->GetVariable("dolj");
$string = mb_strtolower($string);

// Разбиваем строку на слова
$words = preg_split('/\s+/', trim($string));
$dativeWords = [];

foreach ($words as $word) {
    // Особые случаи и исключения
    if ($word === 'медсестра') {
        $dativeWords[] = 'медсестре';
        continue;
    }
    if ($word === '"зеленый') {
        $dativeWords[] = '"Зеленый';
        continue;
    }
    if ($word === 'сад') {
        $dativeWords[] = 'сад';
        continue;
    }
    if ($word === 'комфортный"') {
        $dativeWords[] = 'Комфортный"';
        continue;
    }

    // Слова, которые не требуют изменения
    $exceptions = [
        'общим', 'бренд', 'вопросам', 'битрикс', 'it-интернет', 'зеленый', 'сад', 'комфортный',
        'спецконтингент', 'металлоконструкций', 'гаражом', 'закупок', 'изысканий', 'конструкций'
    ];
    
    if (in_array($word, $exceptions)) {
        $dativeWords[] = $word;
        continue;
    }

    // Аббревиатуры и сокращения (оставляем в верхнем регистре)
    $abbreviations = ['1с', 'тс', 'ст', 'мкд', 'пто', 'скс', 'ск', 'сму'];
    if (in_array($word, $abbreviations)) {
        $dativeWords[] = mb_strtoupper($word);
        continue;
    }

    // Основные правила склонения
    $lastLetter = mb_substr($word, -1);
    $lastTwoLetters = mb_substr($word, -2);
    $lastThreeLetters = mb_substr($word, -3);

    if ($lastThreeLetters === 'щий') {
        $dativeWords[] = mb_substr($word, 0, -3) . 'щему';
    } elseif ($lastTwoLetters === 'ий') {
        $dativeWords[] = mb_substr($word, 0, -2) . 'ому';
    } elseif ($lastTwoLetters === 'ая') {
        $dativeWords[] = mb_substr($word, 0, -2) . 'ой';
    } elseif ($lastTwoLetters === 'ый') {
        $dativeWords[] = mb_substr($word, 0, -2) . 'ому';
    } elseif ($lastLetter === 'ь') {
        $dativeWords[] = mb_substr($word, 0, -1) . 'ю';
    } elseif (in_array($lastLetter, ['р', 'к', 'б', 'г', 'п', 'м', 'т'])) {
        $dativeWords[] = $word . 'у';
    } elseif ($lastTwoLetters === 'ст') {
        $dativeWords[] = $word . 'у';
    } else {
        $dativeWords[] = $word;
    }
}

// Собираем слова обратно в строку
$finalString = implode(' ', $dativeWords);
$root->SetVariable('dolj', $finalString);