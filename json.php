<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$brandsIblockId = 9;
$arBrandsNames = [];

$res = CIBlockElement::GetList(
	[],
	['IBLOCK_ID' => $brandsIblockId],
	false,
	false,
	['NAME']
);

while ($ob = $res->Fetch()) {
	$arBrandsNames[] = $ob['NAME'];
}

$brand = in_array($arResult['NAME'], $arBrandsNames)
	? $arResult['NAME']
	: '';

$arDateLastUpdate = ParseDateTime($arResult['TIMESTAMP_X']);
$imgPath = '';

if (!empty($arResult['PICTURE']['SRC'])) {
	$imgPath = $arResult['PICTURE']['SRC'];
	$imgFullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $imgPath;
}

$arProtocol = CMain::IsHTTPS() ? "https://" : "http://";

if (strpos($imgPath, ':') === false) {
	$imgPath = $arProtocol . $_SERVER['HTTP_HOST'] . $imgPath;
}

$query = CIBlockSection::GetList(
	[],
	[
		'ID' => $arResult['ID'],
		'IBLOCK_ID' => $arResult['IBLOCK_ID']
	],
	false,
	['ID', 'UF_MIN_PRICE', 'UF_MAX_PRICE', 'UF_RATING', 'UF_VOTE_COUNT']
);

if ($resQuery = $query->fetch()) {
	$minPrice = $resQuery['UF_MIN_PRICE'];
	$maxPrice = $resQuery['UF_MAX_PRICE'];
	$voteRating = $resQuery['UF_RATING'];
	$voteCount = $resQuery['UF_VOTE_COUNT'];
}

if (!empty($minPrice)) {
	$arResult['MIN_PRICE'] = $minPrice;
}

if (!empty($voteRating)) {
	$arResult['VOTE_RATING'] = $voteRating;
}

if (!empty($voteCount)) {
	$arResult['VOTE_COUNT'] = $voteCount;
}

$microJs = [
	"@context" => "http://schema.org/",
	"@type" => "Product",
	"name" => !empty($arResult['IPROPERTY_VALUES']['SECTION_PAGE_TITLE'])
		? $arResult['IPROPERTY_VALUES']['SECTION_PAGE_TITLE']
		: $arResult['NAME'],
	"image" => file_exists($imgFullPath) ? $imgPath : '',
	"description" => !empty($arResult['~DESCRIPTION']) ? $arResult['~DESCRIPTION'] : '',
	"brand" => !empty($brand) ? $brand : '',
	"offers" => [
		"@type" => "AggregateOffer",
		"url" => $arProtocol . $_SERVER['HTTP_HOST'] . $APPLICATION->GetCurPage(),
		"priceCurrency" => "RUB",
		"lowPrice" => (!empty($minPrice) && $minPrice > 0) ? $minPrice : '',
		"highPrice" => (!empty($maxPrice) && $maxPrice > 0) ? $maxPrice : '',
	],
	"aggregateRating" => [
		"@type" => "AggregateRating",
		"ratingValue" => !empty($voteRating)
			? $voteRating
			: 5,
		"reviewCount" => !empty($voteCount)
			? $voteCount
			: 1,
	]
];

if (strlen($microJs['image']) == 0) unset($microJs['image']);
if (strlen($microJs['description']) == 0) unset($microJs['description']);
if (strlen($microJs['brand']) == 0) unset($microJs['brand']);
if (strlen($microJs['offers']['lowPrice']) == 0 || strlen($microJs['offers']['highPrice']) == 0) unset($microJs['offers']);


$APPLICATION->AddHeadString("<script type=\"application/ld+json\">" . json_encode($microJs) . "</script>");
