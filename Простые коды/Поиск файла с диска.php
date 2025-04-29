<?php
use Bitrix\Main\Loader;
use Bitrix\Disk;

try {
    $fileId = {=A65256_61494_91520_25437:ObjectId};
    $targetFileName = '{=A65256_61494_91520_25437:Name}';
    $targetFileId = null;

    if ($file = \Bitrix\Disk\File::getById($fileId)) {
        // Проверяем основную версию
        if ($file->getName() === $targetFileName) {
            $targetFileId = $file->getFileId();
        } else {
            // Ищем среди версий файла
            $versions = \Bitrix\Disk\Version::getList([
                'filter' => ['OBJECT_ID' => $file->getId(), 'NAME' => $targetFileName],
                'select' => ['FILE_ID'],
                'limit' => 1
            ]);
            
            if ($version = $versions->fetch()) {
                $targetFileId = $version['FILE_ID'];
            }
        }
    }

    $this->SetVariable('FILE_VARIABLE_ID', $targetFileId ?: null);
    
} catch (Exception $e) {
    $this->WriteToTrackingService("Ошибка: " . $e->getMessage());
    $this->SetVariable('FILE_VARIABLE_ID', null);
}