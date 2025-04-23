<?php
$root = $this->GetRootActivity();
$string = $root->GetVariable("dolj");
$string = mb_strtolower($string);

// Разбиваем строку на слова
$words = preg_split('/\s+/', trim($string));
$genitiveWords = [];

foreach ($words as $word) {
    // Особые случаи и исключения
    if ($word === 'медсестра') {
        $genitiveWords[] = 'медсестры';
        continue;
    }
    if ($word === '"зеленый') {
        $genitiveWords[] = '"Зеленый';
        continue;
    }
    if ($word === 'сад') {
        $genitiveWords[] = 'сад';
        continue;
    }
    if ($word === 'комфортный"') {
        $genitiveWords[] = 'Комфортный"';
        continue;
    }

    // Слова, которые не требуют изменения
    $exceptions = [
        'общим', 'бренд', 'вопросам', 'битрикс', 'it-интернет', 'зеленый', 'сад', 'комфортный',
        'спецконтингент', 'металлоконструкций', 'гаражом', 'закупок', 'изысканий', 'конструкций'
    ];
    
    if (in_array($word, $exceptions)) {
        $genitiveWords[] = $word;
        continue;
    }

    // Аббревиатуры и сокращения (оставляем в верхнем регистре)
    $abbreviations = ['1с', 'тс', 'ст', 'мкд', 'пто', 'скс', 'ск', 'сму'];
    if (in_array($word, $abbreviations)) {
        $genitiveWords[] = mb_strtoupper($word);
        continue;
    }

    // Основные правила склонения
    $lastLetter = mb_substr($word, -1);
    $lastTwoLetters = mb_substr($word, -2);
    $lastThreeLetters = mb_substr($word, -3);

    if ($lastThreeLetters === 'щий') {
        $genitiveWords[] = mb_substr($word, 0, -3) . 'щего';
    } elseif ($lastTwoLetters === 'ий') {
        $genitiveWords[] = mb_substr($word, 0, -2) . 'ого';
    } elseif ($lastTwoLetters === 'ая') {
        $genitiveWords[] = mb_substr($word, 0, -2) . 'ой';
    } elseif ($lastTwoLetters === 'ый') {
        $genitiveWords[] = mb_substr($word, 0, -2) . 'ого';
    } elseif ($lastLetter === 'ь') {
        $genitiveWords[] = mb_substr($word, 0, -1) . 'я';
    } elseif (in_array($lastLetter, ['р', 'к', 'б', 'г', 'п', 'м', 'т'])) {
        $genitiveWords[] = $word . 'а';
    } elseif ($lastTwoLetters === 'ст') {
        $genitiveWords[] = $word . 'а';
    } else {
        $genitiveWords[] = $word;
    }
}

// Собираем слова обратно в строку
$finalString = implode(' ', $genitiveWords);
$root->SetVariable('dolj', $finalString);