<?php
$root = $this->GetRootActivity();
$LIST_OF_PAYMENTS = $root->GetVariable("LIST_OF_PAYMENTS"); // Список выплат
$PAY_NUM = $root->GetVariable("PAY_NUM"); // Номер выплаты

// Логируем начальные значения
$this->WriteToTrackingService("Полученные данные: LIST_OF_PAYMENTS = " . json_encode($LIST_OF_PAYMENTS) . ", PAY_NUM = " . intval($PAY_NUM));

// Приводим PAY_NUM к целому числу
$PAY_NUM = intval($PAY_NUM);

// Инициализируем сумму выплат
$SUM_OF_PAYMENTS = 0;

// Проверяем, является ли $LIST_OF_PAYMENTS массивом
if (is_array($LIST_OF_PAYMENTS) && !empty($LIST_OF_PAYMENTS)) {
    // Получаем индекс выплаты, учитывая, что массивы в PHP начинаются с 0
    $index = max(0, min($PAY_NUM - 1, count($LIST_OF_PAYMENTS) - 1)); // Ограничиваем индекс

    // Логируем индекс
    $this->WriteToTrackingService("Индекс для расчета суммы: " . $index);

    // Суммируем выплаты до указанного индекса (включительно)
    for ($i = $index; $i <= count($LIST_OF_PAYMENTS); $i++) {
        // Убедимся, что значение является денежным типом
        $paymentValue = floatval($LIST_OF_PAYMENTS[$i]); // Приводим значение к денежному типу
        $SUM_OF_PAYMENTS += $paymentValue;
        
        // Логируем каждую добавленную выплату
        $this->WriteToTrackingService("Добавлено к сумме: " . $paymentValue . ", текущая сумма: " . $SUM_OF_PAYMENTS);
    }
} else {
    // Логируем случай, когда список выплат пуст или не является массивом
    $this->WriteToTrackingService("Список выплат пуст или не является массивом.");
}

// Устанавливаем итоговую сумму выплат
$root->SetVariable('SUM_OF_PAYMENTS', $SUM_OF_PAYMENTS);

// Логируем итоговую сумму
$this->WriteToTrackingService("Итоговая сумма выплат: " . $SUM_OF_PAYMENTS);


