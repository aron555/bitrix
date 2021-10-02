</style>
<?php
$methodSC = 'asc';
$chevronSC = 'up';
if ($_GET["sort"] == "SHOW_COUNTER" AND $_GET["method"] == 'asc') {
	$methodSC = 'desc';
	$chevronSC = 'down';
}

$methodSN = 'asc';
$chevronSN = 'up';
if ($_GET["sort"] == "name" AND $_GET["method"] == 'asc') {
	$methodSN = 'desc';
	$chevronSN = 'down';
}
?>

<p class="brends-sort">
	<a
		<? if ($_GET["sort"] == "SHOW_COUNTER"): ?> class="active" <? endif; ?>
			href="<?= $arResult["SECTION_PAGE_URL"] ?>?sort=SHOW_COUNTER&method=<?= $methodSC; ?>"
	>
		По популярности <span class="fa fa-chevron-<?= $chevronSC; ?>"></span>
	</a>
	<a
		<? if ($_GET["sort"] == "name"): ?> class="active" <? endif; ?>
			href="<?= $arResult["SECTION_PAGE_URL"] ?>?sort=name&method=<?= $methodSN; ?>"
	>
		По алфавиту <span class="fa fa-chevron-<?= $chevronSN; ?>"></span>
	</a>
</p>

<? if (
	isset($_GET["sort"]) && isset($_GET["method"]) && ($_GET["sort"] == "name" || $_GET["sort"] == "SHOW_COUNTER")
) {
	$arParams["SORT_BY1"] = $_GET["sort"];
	$arParams["SORT_ORDER1"] = $_GET["method"];
} ?>
