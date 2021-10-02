<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arPhotos = [];
// Получаем ID изображений объектов
foreach ($arResult["ITEMS"] as $key => $item) {
	foreach ($item["PROPERTIES"]["PHOTOS"]["VALUE"] as $arPhoto) {
		$arPhotos[] = $arPhoto;
	}
}
if (!empty($arPhotos)) {
	//Получаем изображения
	$resPhotos = CFile::GetList(
		[],
		[
			"@ID" => implode(',', $arPhotos)
		]
	);

	while ($file = $resPhotos->Fetch()) {
		$file['SRC'] = '/upload' . '/' . $file['SUBDIR'] . '/' . $file['FILE_NAME'];
		$arResult['FILES'][$file['ID']] = $file;
	}
}
