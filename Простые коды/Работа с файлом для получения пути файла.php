<?php
use Bitrix\Main\Loader;
use Bitrix\Main\IO;

$rootActivity = $this->GetRootActivity();
$fileInput = $rootActivity->GetVariable("fileInput");

$fileId = $fileInput['ID'];
$filePath = \CFile::GetPath($fileId);

$this->SetVariable('filePath', $filePath);