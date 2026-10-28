<?php

use Acme\Equipment\EquipmentRepository;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Type\DateTime;

defined('B_PROLOG_INCLUDED') || die;

final class acme_equipment extends CModule
{
    public $MODULE_ID = 'acme.equipment';
    public $MODULE_NAME = 'Оборудование CRM';
    public $MODULE_DESCRIPTION = 'HL-блок оборудования и интеграция с CRM.';
    public $PARTNER_NAME = 'ACME';
    public $PARTNER_URI = 'https://example.org';

    public function __construct()
    {
        $version = [];
        include __DIR__ . '/version.php';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
    }

    public function DoInstall(): void
    {
        global $APPLICATION;

        if (!Loader::includeModule('highloadblock') || !Loader::includeModule('crm')) {
            $APPLICATION->ThrowException('Необходимы модули highloadblock и crm.');
            return;
        }

        ModuleManager::registerModule($this->MODULE_ID);
        Loader::includeModule($this->MODULE_ID);
        try {
            $this->installHighloadBlock();
            $this->installEvents();
            $this->installFiles();
        } catch (Throwable $exception) {
            ModuleManager::unRegisterModule($this->MODULE_ID);
            $APPLICATION->ThrowException($exception->getMessage());
        }
    }

    public function DoUninstall(): void
    {
        $this->uninstallEvents();
        DeleteDirFilesEx('/local/js/acme/equipment');
        COption::RemoveOption($this->MODULE_ID);
        ModuleManager::unRegisterModule($this->MODULE_ID);
        // HL-блок намеренно сохраняется: удаление бизнес-данных не должно быть побочным эффектом uninstall.
    }

    private function installEvents(): void
    {
        RegisterModuleDependences('crm', 'onEntityDetailsTabsInitialized', $this->MODULE_ID,
            Acme\Equipment\CrmIntegration::class, 'onEntityDetailsTabsInitialized');
        RegisterModuleDependences('main', 'OnProlog', $this->MODULE_ID,
            Acme\Equipment\CrmIntegration::class, 'onEpilog');
    }

    private function uninstallEvents(): void
    {
        UnRegisterModuleDependences('crm', 'onEntityDetailsTabsInitialized', $this->MODULE_ID,
            Acme\Equipment\CrmIntegration::class, 'onEntityDetailsTabsInitialized');
        UnRegisterModuleDependences('main', 'OnProlog', $this->MODULE_ID,
            Acme\Equipment\CrmIntegration::class, 'onEpilog');
    }

    private function installFiles(): void
    {
        CopyDirFiles(__DIR__ . '/js', Application::getDocumentRoot() . '/local/js', true, true);
    }

    private function installHighloadBlock(): void
    {
        $existing = HighloadBlockTable::getList(['filter' => ['=NAME' => EquipmentRepository::HL_NAME]])->fetch();
        if ($existing) {
            return;
        }

        $result = HighloadBlockTable::add([
            'NAME' => EquipmentRepository::HL_NAME,
            'TABLE_NAME' => 'acme_equipment',
        ]);
        if (!$result->isSuccess()) {
            throw new RuntimeException(implode('; ', $result->getErrorMessages()));
        }

        $entityId = 'HLBLOCK_' . $result->getId();
        $field = new CUserTypeEntity();
        $nameId = $field->Add($this->field($entityId, 'UF_NAME', 'string', 'Название', true, 100));
        $typeId = $field->Add($this->field($entityId, 'UF_TYPE', 'enumeration', 'Тип оборудования', true, 200));
        $createdId = $field->Add($this->field($entityId, 'UF_CREATED_TIME', 'datetime', 'Дата создания', true, 300));
        $dealId = $field->Add($this->field($entityId, 'UF_DEAL_ID', 'integer', 'ID сделки', true, 400));
        if (!$nameId || !$typeId || !$createdId || !$dealId) {
            throw new RuntimeException('Не удалось создать пользовательские поля HL-блока.');
        }

        (new CUserFieldEnum())->SetEnumValues($typeId, [
            'n0' => ['VALUE' => 'Роутер', 'SORT' => 100, 'DEF' => 'N'],
            'n1' => ['VALUE' => 'Ноутбук', 'SORT' => 200, 'DEF' => 'N'],
            'n2' => ['VALUE' => 'Монитор', 'SORT' => 300, 'DEF' => 'N'],
        ]);
    }

    private function field(string $entityId, string $name, string $type, string $label, bool $mandatory, int $sort): array
    {
        return [
            'ENTITY_ID' => $entityId,
            'FIELD_NAME' => $name,
            'USER_TYPE_ID' => $type,
            'XML_ID' => 'ACME_' . $name,
            'SORT' => $sort,
            'MULTIPLE' => 'N',
            'MANDATORY' => $mandatory ? 'Y' : 'N',
            'SHOW_FILTER' => 'I',
            'EDIT_FORM_LABEL' => ['ru' => $label],
            'LIST_COLUMN_LABEL' => ['ru' => $label],
            'LIST_FILTER_LABEL' => ['ru' => $label],
        ];
    }
}

