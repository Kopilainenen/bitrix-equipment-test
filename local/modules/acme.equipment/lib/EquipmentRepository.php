<?php

namespace Acme\Equipment;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;

final class EquipmentRepository
{
    public const HL_NAME = 'Equipment';

    /** @return class-string<\Bitrix\Main\ORM\Data\DataManager> */
    public static function dataClass(): string
    {
        Loader::includeModule('highloadblock');
        $hlBlock = HighloadBlockTable::getList([
            'filter' => ['=NAME' => self::HL_NAME],
            'limit' => 1,
        ])->fetch();

        if (!$hlBlock) {
            throw new SystemException('HL-блок Equipment не найден. Установите модуль acme.equipment.');
        }

        return HighloadBlockTable::compileEntity($hlBlock)->getDataClass();
    }

    public static function countByDeal(int $dealId): int
    {
        if ($dealId <= 0) {
            throw new ArgumentException('DEAL_ID должен быть положительным числом.');
        }

        $class = self::dataClass();
        return $class::getCount(['=UF_DEAL_ID' => $dealId]);
    }
}

