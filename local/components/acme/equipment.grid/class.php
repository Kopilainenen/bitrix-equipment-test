<?php

use Acme\Equipment\EquipmentRepository;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Type\DateTime;

defined('B_PROLOG_INCLUDED') || die;

final class AcmeEquipmentGridComponent extends CBitrixComponent implements Controllerable
{
    private const FILTER_FIELDS = [
        ['id' => 'UF_NAME', 'name' => 'Название', 'type' => 'string', 'default' => true],
        ['id' => 'UF_TYPE', 'name' => 'Тип', 'type' => 'list', 'default' => true],
        ['id' => 'UF_CREATED_TIME', 'name' => 'Дата создания', 'type' => 'date', 'time' => true, 'default' => true],
    ];

    public function configureActions(): array
    {
        return [];
    }

    public function onPrepareComponentParams($params): array
    {
        $params['DEAL_ID'] = max(0, (int)($params['DEAL_ID'] ?? 0));
        return $params;
    }

    public function executeComponent(): void
    {
        if (!Loader::includeModule('acme.equipment') || !Loader::includeModule('crm')) {
            ShowError('Необходимы модули acme.equipment и crm.');
            return;
        }
        if ($this->arParams['DEAL_ID'] <= 0 || !CCrmDeal::CheckReadPermission($this->arParams['DEAL_ID'])) {
            ShowError('Сделка не найдена или доступ запрещён.');
            return;
        }

        $gridId = 'ACME_EQUIPMENT_' . $this->arParams['DEAL_ID'];
        $filterId = $gridId . '_FILTER';
        $types = $this->getTypeItems();
        $filterFields = self::FILTER_FIELDS;
        $filterFields[1]['items'] = $types;

        $filter = ['=UF_DEAL_ID' => $this->arParams['DEAL_ID']];
        $rawFilter = (new FilterOptions($filterId))->getFilter($filterFields);
        if (($rawFilter['UF_NAME'] ?? '') !== '') {
            $filter['%UF_NAME'] = trim((string)$rawFilter['UF_NAME']);
        }
        if (!empty($rawFilter['UF_TYPE'])) {
            $filter['=UF_TYPE'] = array_map('intval', (array)$rawFilter['UF_TYPE']);
        }
        if (!empty($rawFilter['UF_CREATED_TIME_from'])) {
            $filter['>=UF_CREATED_TIME'] = DateTime::createFromUserTime($rawFilter['UF_CREATED_TIME_from']);
        }
        if (!empty($rawFilter['UF_CREATED_TIME_to'])) {
            $filter['<=UF_CREATED_TIME'] = DateTime::createFromUserTime($rawFilter['UF_CREATED_TIME_to']);
        }

        $gridOptions = new GridOptions($gridId);
        $sorting = $gridOptions->getSorting(['sort' => ['ID' => 'DESC'], 'vars' => ['by' => 'by', 'order' => 'order']]);
        $sortField = (string)array_key_first($sorting['sort']);
        if (!in_array($sortField, ['ID', 'UF_NAME', 'UF_TYPE', 'UF_CREATED_TIME'], true)) {
            $sorting['sort'] = ['ID' => 'DESC'];
        }
        $navigation = new PageNavigation($gridId);
        $navigation->allowAllRecords(false)->setPageSize($gridOptions->getNavParams()['nPageSize'])->initFromUri();
        $dataClass = EquipmentRepository::dataClass();
        $result = $dataClass::getList([
            'select' => ['ID', 'UF_NAME', 'UF_TYPE', 'UF_CREATED_TIME', 'UF_DEAL_ID'],
            'filter' => $filter,
            'order' => $sorting['sort'],
            'limit' => $navigation->getLimit(),
            'offset' => $navigation->getOffset(),
            'count_total' => true,
        ]);
        $navigation->setRecordCount($result->getCount());

        $rows = [];
        while ($item = $result->fetch()) {
            $rows[] = ['id' => $item['ID'], 'data' => [
                'ID' => $item['ID'],
                'UF_NAME' => htmlspecialcharsbx((string)$item['UF_NAME']),
                'UF_TYPE' => htmlspecialcharsbx($types[(int)$item['UF_TYPE']] ?? '—'),
                'UF_CREATED_TIME' => $item['UF_CREATED_TIME']?->toString() ?? '—',
            ]];
        }

        $this->arResult = compact('gridId', 'filterId', 'filterFields', 'rows', 'navigation');
        $this->includeComponentTemplate();
    }

    private function getTypeItems(): array
    {
        $field = CUserTypeEntity::GetList([], [
            'ENTITY_ID' => 'HLBLOCK_' . $this->getHighloadBlockId(),
            'FIELD_NAME' => 'UF_TYPE',
        ])->Fetch();
        $items = [];
        if ($field) {
            $enum = CUserFieldEnum::GetList(['SORT' => 'ASC'], ['USER_FIELD_ID' => $field['ID']]);
            while ($value = $enum->Fetch()) {
                $items[(int)$value['ID']] = (string)$value['VALUE'];
            }
        }
        return $items;
    }

    private function getHighloadBlockId(): int
    {
        return (int)\Bitrix\Highloadblock\HighloadBlockTable::getList([
            'select' => ['ID'], 'filter' => ['=NAME' => EquipmentRepository::HL_NAME], 'limit' => 1,
        ])->fetch()['ID'];
    }
}

