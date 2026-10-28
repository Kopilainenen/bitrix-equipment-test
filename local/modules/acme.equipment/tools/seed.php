<?php

use Acme\Equipment\EquipmentRepository;
use Bitrix\Crm\Item;
use Bitrix\Crm\Model\Dynamic\TypeTable;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;

// Запуск из административной PHP-консоли после установки модуля.
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

foreach (['acme.equipment', 'crm', 'highloadblock'] as $module) {
    if (!Loader::includeModule($module)) {
        throw new RuntimeException("Модуль {$module} недоступен");
    }
}

global $USER;
if (!$USER->IsAdmin() || !check_bitrix_sessid()) {
    throw new RuntimeException('Требуются права администратора и корректный sessid.');
}
$userId = max(1, (int)$USER->GetID());

// Тип смарт-процесса безопаснее создать один раз в UI: название «Заявка на оборудование»,
// связь «Сделка». Его entityTypeId передаётся через ?smartTypeId=NNN.
$smartTypeId = (int)($_REQUEST['smartTypeId'] ?? 0);
$factory = Container::getInstance()->getFactory($smartTypeId);
if (!$factory) {
    throw new RuntimeException('Передайте entityTypeId существующего смарт-процесса: ?smartTypeId=NNN');
}

$deal = new CCrmDeal(false);
$dealId = (int)$deal->Add([
    'TITLE' => 'Тестовая сделка — оборудование',
    'ASSIGNED_BY_ID' => $userId,
    'STAGE_ID' => 'NEW',
    'OPENED' => 'Y',
]);
if ($dealId <= 0) {
    throw new RuntimeException($deal->LAST_ERROR ?: 'Не удалось создать сделку');
}

$item = $factory->createItem([
    Item::FIELD_NAME_TITLE => 'Тестовый элемент — оборудование',
    Item::FIELD_NAME_ASSIGNED => $userId,
    'PARENT_ID_' . CCrmOwnerType::Deal => $dealId,
]);
$operation = $factory->getAddOperation($item)->disableCheckAccess();
$saveResult = $operation->launch();
if (!$saveResult->isSuccess()) {
    throw new RuntimeException(implode('; ', $saveResult->getErrorMessages()));
}

COption::SetOptionInt('acme.equipment', 'SMART_ENTITY_TYPE_ID', $smartTypeId);

$dataClass = EquipmentRepository::dataClass();
$hlBlock = \Bitrix\Highloadblock\HighloadBlockTable::getList([
    'filter' => ['=NAME' => EquipmentRepository::HL_NAME], 'limit' => 1,
])->fetch();
$typeField = CUserTypeEntity::GetList([], [
    'ENTITY_ID' => 'HLBLOCK_' . $hlBlock['ID'], 'FIELD_NAME' => 'UF_TYPE',
])->Fetch();
$enum = CUserFieldEnum::GetList(['SORT' => 'ASC'], ['USER_FIELD_ID' => $typeField['ID']]);
$typeIds = [];
while ($value = $enum->Fetch()) {
    $typeIds[$value['VALUE']] = (int)$value['ID'];
}
foreach ([['MikroTik CCR', 'Роутер'], ['ThinkPad T14', 'Ноутбук'], ['Dell U2723QE', 'Монитор']] as [$name, $type]) {
    $dataClass::add([
        'UF_NAME' => $name,
        'UF_TYPE' => $typeIds[$type],
        'UF_CREATED_TIME' => new DateTime(),
        'UF_DEAL_ID' => $dealId,
    ]);
}

echo "dealId={$dealId}; smartItemId={$item->getId()}; smartTypeId={$smartTypeId}";

