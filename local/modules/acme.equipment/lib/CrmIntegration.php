<?php

namespace Acme\Equipment;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;

final class CrmIntegration
{
    private const TAB_ID = 'acme_equipment';

    public static function onEntityDetailsTabsInitialized(Event $event): EventResult
    {
        $tabs = (array)$event->getParameter('tabs');
        $entityTypeId = (int)$event->getParameter('entityTypeID');
        $entityId = (int)$event->getParameter('entityID');
        $smartTypeId = (int)\COption::GetOptionInt('acme.equipment', 'SMART_ENTITY_TYPE_ID', 0);

        if ($smartTypeId <= 0 || $entityTypeId !== $smartTypeId || $entityId <= 0) {
            return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs]);
        }

        $dealId = self::getDealId($smartTypeId, $entityId);
        if ($dealId <= 0) {
            return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs]);
        }

        $tabs[] = [
            'id' => self::TAB_ID,
            'name' => 'Оборудование',
            'html' => '<div class="ui-entity-editor-content-block">Загрузка…</div>',
            'loader' => [
                'serviceUrl' => '/local/modules/acme.equipment/tools/tab.php',
                'componentData' => ['dealId' => $dealId],
            ],
        ];

        return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs]);
    }

    public static function onEpilog(): void
    {
        global $APPLICATION;

        if (!Loader::includeModule('crm') || (int)\CCrmOwnerType::Deal <= 0) {
            return;
        }

        if (preg_match('~^/crm/deal/details/(\d+)/~', $APPLICATION->GetCurPageParam('', []), $match) !== 1) {
            return;
        }

        Extension::load('acme.equipment.deal-counter');
        $dealId = (int)$match[1];
        $currentUser = $GLOBALS['USER'] ?? null;
        $highlight = (is_object($currentUser) && (int)$currentUser->GetID() === 5) ? 'true' : 'false';
        Asset::getInstance()->addString(sprintf(
            '<script>BX.ready(function(){Acme.Equipment.DealCounter.mount(%d, %s);});</script>',
            $dealId,
            $highlight
        ));
    }

    private static function getDealId(int $entityTypeId, int $entityId): int
    {
        Loader::includeModule('crm');
        $factory = Container::getInstance()->getFactory($entityTypeId);
        $item = $factory?->getItem($entityId, ['ID', 'PARENT_ID_' . \CCrmOwnerType::Deal]);

        return (int)($item?->get('PARENT_ID_' . \CCrmOwnerType::Deal) ?? 0);
    }
}

