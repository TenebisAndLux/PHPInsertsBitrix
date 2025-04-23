<?php
use Bitrix\Main\Loader;
use Bitrix\Disk;

try {
    // Подключаем необходимые модули
    if (!Loader::includeModule('disk')) {
        throw new Exception('Модуль Disk не подключен');
    }

    $rootActivity = $this->GetRootActivity();
    
    // ID файла на диске
    $fileId = 1551385;
    $targetFileName = 'ПодписьАленина.PNG';
    
    // Получаем основной файл
    $file = \Bitrix\Disk\File::getById($fileId);
    if (!$file) {
        throw new Exception('Файл с ID '.$fileId.' не найден');
    }
    
    // Получаем все версии файла
    $versions = \Bitrix\Disk\Version::getList([
        'filter' => ['OBJECT_ID' => $file->getId()],
        'order' => ['ID' => 'DESC']
    ]);
    
    $targetFileId = null;
    
    // Ищем нужную версию по имени файла
    foreach ($versions as $versionData) {
        if ($versionData['NAME'] === $targetFileName) {
            $targetFileId = $versionData['FILE_ID'];
            break;
        }
    }
    
    if ($targetFileId === null) {
        // Если не нашли среди версий, проверяем основной файл
        if ($file->getName() === $targetFileName) {
            $targetFileId = $file->getFileId();
        } else {
            throw new Exception('Файл "'.$targetFileName.'" не найден среди версий файла с ID '.$fileId);
        }
    }

    // Устанавливаем только fileId в переменную бизнес-процесса
    $this->SetVariable('FILE_VARIABLE_ID', $targetFileId);
    
} catch (Exception $e) {
    $this->WriteToTrackingService("Ошибка при загрузке файла: " . $e->getMessage());
    $this->SetVariable('FILE_VARIABLE_ID', null);
}