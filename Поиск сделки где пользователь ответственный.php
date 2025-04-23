<?php
use Bitrix\Main\Loader;
use Bitrix\Crm\DealTable;
use Bitrix\Main\Type\DateTime;

/**
 * Загружаем модуль CRM.
 */
Loader::IncludeModule("crm");

/**
 * Получаем корневую активность текущего процесса.
 *
 * @var $rootActivity \SomeNamespace\RootActivity
 */
$rootActivity = $this->GetRootActivity();

/**
 * Извлекаем идентификатор документа из корневой активности.
 *
 * @var string $documentId Идентификатор документа
 */
$documentId = $rootActivity->GetDocumentId();

/**
 * Разбиваем идентификатор документа на тип сущности и ID сущности.
 *
 * @var array $entityTypeName Название типа сущности
 * @var int $entityId ID сущности
 */
[
    $entityTypeName,
    $entityId
] = mb_split('_(?=[^_]*$)', $documentId[2]);

/**
 * Получаем ID пользователя из переменной корневой активности.
 *
 * @var int $userId ID пользователя
 */
$userId = $rootActivity->GetVariable('userID');
$substituteId = $rootActivity->GetVariable('substituteID');

/**
 * Проверяем существование пользователя по его ID.
 *
 * Если пользователь не найден, завершаем выполнение.
 */
$user = \CUser::GetByID($userId);
if (!$user->Fetch()) {
    $this->WriteToTrackingService("не польз");
    return; // Завершаем выполнение, если пользователя нет
} else {
    $this->WriteToTrackingService("польз");
}

try {
    /**
     * Определяем массив категорий сделок.
     *
     * @var array $arCategoryId Массив ID категорий
     */
    $arCategoryId = [5];

    /**
     * Подготавливаем фильтр для получения категорий сделок.
     *
     * @var array $arFilter Фильтр для категорий
     */
    $arFilter = [];
    if (!is_null($arCategoryId)) {
        $arFilter[] = [
            "@ID" => $arCategoryId
        ];
    }

    /**
     * Получаем список категорий сделок с использованием фильтра.
     *
     * @var \Bitrix\Crm\Category\DealCategory $DealCategoryControl Контроллер категорий сделок
     */
    $DealCategoryControl = \Bitrix\Crm\Category\DealCategory::getList([
        "select" => ["*"],
        "filter" => $arFilter
    ]);

    /**
     * Массив для хранения статусов сделок.
     *
     * @var array $statuses Статусы сделок
     */
    $statuses = [];
    while ($arDealCategory = $DealCategoryControl->Fetch()) {
        // Получаем стадии сделок для текущей категории
        $arStageList = \Bitrix\Crm\Category\DealCategory::getStageInfos($arDealCategory["ID"]);
        $statuses = array_merge($statuses, $arStageList);
    }

    /**
     * Фильтруем статусы сделок, исключая успешные и неудачные.
     *
     * @var array $arStageListFilter Статусы, которые нужно исключить
     */
    $arStageListFilter = [
        \Bitrix\Crm\PhaseSemantics::FAILURE,
        \Bitrix\Crm\PhaseSemantics::SUCCESS
    ];

    /**
     * Фильтруем статусы сделок по семантике.
     *
     * @var array $arDocumentStatuses Отфильтрованные статусы сделок
     */
    $arDocumentStatuses = array_filter($statuses, function ($stageItem) use ($arStageListFilter) {
        return !in_array($stageItem["SEMANTICS"], $arStageListFilter);
    });

    /**
     * Подготавливаем фильтр для получения сделок.
     *
     * @var array $arFilterDeals Фильтр для сделок
     */
    $arFilterDeals = [
        'ASSIGNED_BY_ID' => $userId,
        'CATEGORY_ID' => 5,
        '@STAGE_ID' => array_keys($arDocumentStatuses) // Используем статусы сделок
    ];

    /**
     * Определяем, какие поля нужно выбрать из таблицы сделок.
     *
     * @var array $arSelect Поля для выборки
     */
    $arSelect = ['ID', 'ASSIGNED_BY_ID'];

    /**
     * Получаем список сделок по заданному фильтру.
     *
     * @var array $arDeals Список сделок
     */
    $arDeals = DealTable::getList([
        'order' => ['ID' => 'DESC'],
        'filter' => $arFilterDeals,
        'select' => $arSelect
    ])->fetchAll();

    /**
     * Формируем массив сделок по их ID.
     *
     * @var array $deals Массив сделок
     */
    $deals = [];

    foreach ($arDeals as $deal) {
        $deals[$deal['ID']] = $deal;

        // Смена ответственного для каждой сделки
        DealTable::update($deal['ID'], [
            'ASSIGNED_BY_ID' => $substituteId
        ]);
    }

    /*** Сохраняем только ID сделок в переменной корневой активности.
     */
    $rootActivity->SetVariable("ID_DEAL_LIST", array_keys($deals)); // Сохраняем только ID сделок

} catch (\Exception $e) {
    // Обработка ошибок
    echo "Произошла ошибка: " . $e->getMessage();
}