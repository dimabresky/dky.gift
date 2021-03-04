<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class dky_gift extends CModule {

    public $MODULE_ID = 'dky.gift';
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $moduleRoot = '';
    public $componentsRoot = '';

    function __construct() {

        $this->MODULE_NAME = Loc::getMessage('DKY_GIGT_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('DKY_GIFT_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('DKY_GIFT_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('DKY_GIFT_PARTNER_URI');

        if (strpos(__DIR__, 'local/modules') !== false) {
            $this->moduleRoot = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/dky.gift';
            $this->componentsRoot = $_SERVER['DOCUMENT_ROOT'] . '/local/components';
        } else {
            $this->moduleRoot = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/dky.gift';
            $this->componentsRoot = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components';
        }
    }

    public function DoInstall() {
        RegisterModule($this->MODULE_ID);
        $this->installEventsHandlers();
        $this->copyFiles();
    }

    public function DoUninstall() {

        $this->uninstallEventsHandlers();
        $this->deleteFiles();
        unRegisterModule($this->MODULE_ID);
    }

    function copyFiles() {

        CopyDirFiles($this->moduleRoot . '/install/admin', $_SERVER["DOCUMENT_ROOT"] . '/bitrix/admin');
        CopyDirFiles($this->moduleRoot . '/install/components', $this->componentsRoot);
    }

    function deleteFiles() {
        DeleteDirFiles(
                $this->moduleRoot . '/install/admin',
                $_SERVER["DOCUMENT_ROOT"] . '/bitrix/admin'
        );
        DeleteDirFiles(
                $this->moduleRoot . '/install/components',
                $this->componentsRoot
        );
    }

    function installEventsHandlers() {
        RegisterModuleDependences("sale", "OnSaleDeliveryServiceCalculate", $this->MODULE_ID, "\\dky\\gift\\EventsHandlers", "onSaleDeliveryServiceCalculate");
    }

    function uninstallEventsHandlers() {
        unRegisterModuleDependences("sale", "OnSaleDeliveryServiceCalculate", $this->MODULE_ID, "\\dky\\gift\\EventsHandlers", "onSaleDeliveryServiceCalculate");
    }

}
