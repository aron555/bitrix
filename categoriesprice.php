<?php
// устанавливаем минимальную и максимальную цены для категории
// при обновлении, добавлении товара и цены
namespace Aron;
use Bitrix\Main\EventManager;

$handler = EventManager::getInstance();

$handler->addEventHandler(
    "iblock",
    "OnAfterIBlockElementUpdate",
    [
        "Aron\\PriceToCategory",
        "setPriceToCategory"
    ]
);

$handler->addEventHandler(
    "iblock",
    "OnAfterIBlockElementAdd",
    [
        "Aron\\PriceToCategory",
        "setPriceToCategory"
    ]
);

$handler->addEventHandler(
    "catalog",
    "OnPriceAdd",
    [
        "Aron\\PriceToCategory",
        "setPriceToCategory"
    ]
);

$handler->addEventHandler(
    "catalog",
    "OnPriceUpdate",
    [
        "Aron\\PriceToCategory",
        "setPriceToCategory"
    ]
);

class PriceToCategory {
    function setPriceToCategory($id, $arFields = false)
    {
        // Получим id товара при обновлении
        if (is_array($arFields) && $arFields['PRODUCT_ID'] > 0) {
            // цены
            $elementIdX = $arFields['PRODUCT_ID'];

        } elseif (is_array($id) && $id['ID'] > 0 && ($id['IBLOCK_ID'] == iCatalog || $id['IBLOCK_ID'] == iSku)) {
            // товара или ТП
            $elementIdX = $id['ID'];
        }

        if (empty($elementIdX)) {
            return false;
        }

        // Поля товара
        $arElementFields = \CIBlockElement::GetByID($elementIdX)->Fetch();

        if ($arElementFields['IBLOCK_ID'] == iCatalog) {
            // Каталог
            $elementId = $elementIdX;

        } elseif ($arElementFields['IBLOCK_ID'] == iSku) {
            // ТП
            $mxResult = \CCatalogSku::GetProductInfo(
                $elementIdX
            );

            if (is_array($mxResult)) {
                $elementId = $mxResult['ID'];
            }
        }

        if (empty($elementId)) {
            return false;
        }

        // Используем метод ASPRO для установки Минимальной и Максимальной цены
        $prodId['PRODUCT_ID'] = $arElementFields['ID'];
        \COptimus::DoIBlockAfterSave(false, $prodId);

        // Разделы товара

        $sectSelect = [
            'ID',
            'IBLOCK_ID',
            'IBLOCK_SECTION_ID', // ID раздела-родителя
            'ACTIVE',
            'GLOBAL_ACTIVE',
            'LEFT_MARGIN',
            'RIGHT_MARGIN',
            'DEPTH_LEVEL'
        ];

        $arSections = \CIBlockElement::GetElementGroups(
            $elementId,
            true,
            $sectSelect
        );

        $sections = [];

        while ($arSection = $arSections->fetch()) {

            $sections[] = $arSection;

            // добавим родительскую категорию при наличии
            if (!empty($arSection['IBLOCK_SECTION_ID'])) {

                $parentSection = \CIBlockSection::GetList(
                    [],
                    [
                        'ACTIVE' => 'Y',
                        'ID' => $arSection['IBLOCK_SECTION_ID']
                    ],
                    false,
                    $sectSelect
                )->fetch();

                $sections[] = $parentSection;
            }

        }


        if (!empty($sections)) {
            $bs = new \CIBlockSection;

            foreach ($sections as $section) {
                $sectId = $section['ID'];
                $sectWithChildsIds = $section['ID'];

                // выберем потомков для получения в них цен
                $arFilterChild = [
                    'IBLOCK_ID' => $section['IBLOCK_ID'],
                    'ACTIVE' => 'Y',
                    '>LEFT_MARGIN' => $section['LEFT_MARGIN'],
                    '<RIGHT_MARGIN' => $section['RIGHT_MARGIN'],
                    '>DEPTH_LEVEL' => $section['DEPTH_LEVEL']
                ];

                $rsSect = \CIBlockSection::GetList(
                    [],
                    $arFilterChild,
                    false,
                    ['ID']
                );

                $childIds = [];

                while ($arSect = $rsSect->Fetch()) {
                    $childIds[] = $arSect['ID'];
                }

                if (!empty($childIds)) {
                    $sectWithChildsIds = array_merge((array)$sectId, $childIds);
                }

                // если есть раздел
                if (!empty($sectId)) {
                    // получаем в разделе минимальную и максимальную цены товаров
                    $arSelectPrice = [
                        'ID',
                        'IBLOCK_ID',
                        'PROPERTY_MINIMUM_PRICE',
                        'PROPERTY_MAXIMUM_PRICE'
                    ];

                    $arFilter = [
                        'IBLOCK_ID' => iCatalog, // Каталог
                        'ACTIVE' => 'Y',
                        'SECTION_ID' => $sectWithChildsIds, // id раздела(-ов)
                        //'!ID' => $elementId // кроме текущего товара
                    ];

                    $allPrices = \CIBlockElement::GetList([], $arFilter, false, [], $arSelectPrice);

                    $arMinPrices = $arMaxPrices = [];

                    while ($arPrice = $allPrices->fetch()) {
                        $arMinPrices[] = $arPrice['PROPERTY_MINIMUM_PRICE_VALUE'];
                        $arMaxPrices[] = $arPrice['PROPERTY_MAXIMUM_PRICE_VALUE'];
                    }

                    $sectionMinPrice = 0;

                    if (!empty($arMinPrices)) {
                        $sectionMinPrice = ceil(min($arMinPrices));
                    }

                    $sectionMaxPrice = 0;

                    if (!empty($arMaxPrices)) {
                        $sectionMaxPrice = ceil(max($arMaxPrices));
                    }

                    // установим свойство Минимальная цена
                    if (!empty($sectionMinPrice) && $sectionMinPrice > 0) {
                        if (!empty($arFields['PRICE']) && $arFields['PRICE'] < $sectionMinPrice) {
                            $sectionMinPrice = $arFields['PRICE'];
                        }

                        $arFieldsSectionMin = [
                            'UF_MIN_PRICE' => $sectionMinPrice
                        ];

                        $bs->Update($sectId, $arFieldsSectionMin);
                    }

                    // установим свойство Максимальная цена
                    if (!empty($sectionMaxPrice) && $sectionMaxPrice > 0) {
                        if (!empty($arFields['PRICE']) && $arFields['PRICE'] > $sectionMaxPrice) {
                            $sectionMaxPrice = $arFields['PRICE'];
                        }

                        $arFieldsSectionMax = [
                            'UF_MAX_PRICE' => $sectionMaxPrice
                        ];

                        $bs->Update($sectId, $arFieldsSectionMax);
                    }

                }

            }

        }

    }
}
