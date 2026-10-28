<?php

namespace Acme\Equipment\Controller;

use Acme\Equipment\EquipmentRepository;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;

final class Equipment extends Controller
{
    public function configureActions(): array
    {
        return [
            'count' => [
                'prefilters' => [new ActionFilter\Authentication(), new ActionFilter\HttpMethod(['GET'])],
            ],
        ];
    }

    public function countAction(int $dealId): array
    {
        if ($dealId <= 0 || !\CCrmDeal::CheckReadPermission($dealId)) {
            $this->addError(new \Bitrix\Main\Error('Доступ запрещён', 'ACCESS_DENIED'));
            return [];
        }

        return ['count' => EquipmentRepository::countByDeal($dealId)];
    }
}

