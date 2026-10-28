<?php

use Bitrix\Main\Loader;

defined('B_PROLOG_INCLUDED') || die;

Loader::registerAutoLoadClasses('acme.equipment', [
    Acme\Equipment\EquipmentRepository::class => 'lib/EquipmentRepository.php',
    Acme\Equipment\CrmIntegration::class => 'lib/CrmIntegration.php',
    Acme\Equipment\Controller\Equipment::class => 'lib/Controller/Equipment.php',
]);

