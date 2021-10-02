<?php
// устанавливаем отзывы для категории
namespace Aron;
use Bitrix\Main\EventManager;

$handler = EventManager::getInstance();

$handler->addEventHandler(
    "iblock",
    "OnAfterIBlockElementUpdate",
    [
        "Aron\\ReviewsCategory",
        "setReviewsCategory"
    ]
);

$handler->addEventHandler(
    "iblock",
    "OnAfterIBlockElementAdd",
    [
        "Aron\\ReviewsCategory",
        "setReviewsCategory"
    ]
);

class ReviewsCategory {
    function setReviewsCategory(&$arFields)
    {
        if ($arFields['IBLOCK_ID'] != iCatalog) {
            return $arFields;
        }

        $element = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => iCatalog,
                'ID' => $arFields['ID'],
            ],
            FALSE,
            FALSE,
            ['IBLOCK_ID', 'ID', 'ACTIVE', 'PROPERTY_RATING', 'PROPERTY_VOTE_COUNT']
        )->Fetch();

        if ($element['ACTIVE'] != 'Y')
            return false;


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
            $arFields['ID'],
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
                // получаем в разделе рейтинги и количество проголосовавших
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
                    $sectWithChildsIds = array_merge((array)$section['ID'], $childIds);
                }

                $arSelectVote = [
                    'ID',
                    'IBLOCK_ID',
                    'PROPERTY_RATING',
                    'PROPERTY_VOTE_COUNT'
                ];

                $arFilter = [
                    'IBLOCK_ID' => iCatalog, // Каталог
                    'ACTIVE' => 'Y',
                    'SECTION_ID' => $sectWithChildsIds, // id раздела(-ов)
                ];

                $arVoteResults = \CIBlockElement::GetList([], $arFilter, false, [], $arSelectVote);

                $arRatings = $arCounts = [];

                while ($arVoteResult = $arVoteResults->Fetch()) {
                    if (!empty($arVoteResult['PROPERTY_RATING_VALUE'])) {
                        $arRatings[] = $arVoteResult['PROPERTY_RATING_VALUE'];
                    }

                    if (!empty($arVoteResult['PROPERTY_VOTE_COUNT_VALUE'])) {
                        $arCounts[] = $arVoteResult['PROPERTY_VOTE_COUNT_VALUE'];
                    }

                }

                $sectRating = (count($arRatings) > 0)
                    ? round(array_sum($arRatings) / count($arRatings))
                    : 0;

                $sectCount = (count($arCounts) > 0)
                    ? round(array_sum($arCounts))
                    : 0;

                $sectRating = ($sectRating < 0) ? 0 : $sectRating;

                $sectRating = ($sectRating > 5) ? 5 : $sectRating;

                $arFieldsSectionVote = [
                    'UF_RATING' => $sectRating,
                    'UF_VOTE_COUNT' => $sectCount
                ];

                $bs->Update($section['ID'], $arFieldsSectionVote);
            }
        }
    }
}
