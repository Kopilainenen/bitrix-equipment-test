<?php

use Bitrix\Main\Loader;

define('PUBLIC_AJAX_MODE', true);
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!Loader::includeModule('acme.equipment')) {
    die('Module is not installed');
}

$dealId = (int)($_REQUEST['PARAMS']['dealId'] ?? 0);
$APPLICATION->IncludeComponent('acme:equipment.grid', '', ['DEAL_ID' => $dealId], false);

