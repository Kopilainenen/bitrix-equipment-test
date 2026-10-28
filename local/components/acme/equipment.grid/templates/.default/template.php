<?php

defined('B_PROLOG_INCLUDED') || die;

$APPLICATION->IncludeComponent('bitrix:main.ui.filter', '', [
    'FILTER_ID' => $arResult['filterId'],
    'GRID_ID' => $arResult['gridId'],
    'FILTER' => $arResult['filterFields'],
    'ENABLE_LIVE_SEARCH' => true,
    'ENABLE_LABEL' => true,
]);

$APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [
    'GRID_ID' => $arResult['gridId'],
    'COLUMNS' => [
        ['id' => 'ID', 'name' => 'ID', 'sort' => 'ID', 'default' => true],
        ['id' => 'UF_NAME', 'name' => 'Название', 'sort' => 'UF_NAME', 'default' => true],
        ['id' => 'UF_TYPE', 'name' => 'Тип', 'sort' => 'UF_TYPE', 'default' => true],
        ['id' => 'UF_CREATED_TIME', 'name' => 'Дата создания', 'sort' => 'UF_CREATED_TIME', 'default' => true],
    ],
    'ROWS' => $arResult['rows'],
    'NAV_OBJECT' => $arResult['navigation'],
    'TOTAL_ROWS_COUNT' => $arResult['navigation']->getRecordCount(),
    'AJAX_MODE' => 'Y',
    'AJAX_OPTION_JUMP' => 'N',
    'AJAX_OPTION_HISTORY' => 'N',
    'SHOW_ROW_CHECKBOXES' => false,
    'SHOW_TOTAL_COUNTER' => true,
    'SHOW_PAGESIZE' => true,
    'PAGE_SIZES' => [['NAME' => '20', 'VALUE' => '20'], ['NAME' => '50', 'VALUE' => '50']],
    'ALLOW_SORT' => true,
]);

