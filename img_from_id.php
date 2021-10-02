<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$items = [];
$arPhotos = [];
$ids = [];

foreach ($arResult['ITEMS'] as $key => $item) {
    $items[$item['ID']] = $item;

    // Получаем ID изображений объектов
    if (!empty($item["PROPERTIES"]["PHOTOS"]["VALUE"])) {
        foreach ($item["PROPERTIES"]["PHOTOS"]["VALUE"] as $arPhoto) {
            $arPhotos[] = $arPhoto;
            $ids[$arPhoto] = $item["ID"];
        }
    }
}
if (!empty($arPhotos)) {
    //Получаем изображения
    $resPhotos = CFile::GetList(
        [],
        ["@ID" => implode(',', $arPhotos)]
    );

    while ($res_arr = $resPhotos->Fetch()) {
        $items[$ids[$res_arr["ID"]]]["PHOTO"] = COption::GetOptionString("main", "upload_dir", "upload") . "/" . $res_arr["SUBDIR"] . "/" . $res_arr["FILE_NAME"];
    }
}
$arResult["ITEMS"] = $items;
