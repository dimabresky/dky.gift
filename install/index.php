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

    function __construct() {

        $this->MODULE_NAME = Loc::getMessage('DKY_GIGT_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('DKY_GIFT_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('DKY_GIFT_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('DKY_GIFT_PARTNER_URI');

        if (strpos(__DIR__, 'local/modules') !== false) {
            $this->moduleRoot = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/dky.gift';
        } else {
            $this->moduleRoot = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/dky.gift';
        }
    }

    public function DoInstall() {
        $this->installEventsHandlers();
        $this->copyFiles();
        RegisterModule($this->MODULE_ID);
    }

    public function DoUninstall() {

        $this->uninstallEventsHandlers();
        $this->deleteFiles();
        unRegisterModule($this->MODULE_ID);
    }

    function copyFiles() {

        CopyDirFiles($this->moduleRoot . '/install/admin', $_SERVER["DOCUMENT_ROOT"] . '/bitrix/admin');
    }

    function deleteFiles() {
        DeleteDirFiles(
                $this->moduleRoot . '/install/admin',
                $_SERVER["DOCUMENT_ROOT"] . '/bitrix/admin'
        );
    }

    function installEventsHandlers() {
        RegisterModuleDependences("sale", "OnSaleDeliveryServiceCalculate", $this->MODULE_ID, "\\dky\\gift\\EventsHandlers", "onSaleDeliveryServiceCalculate");
    }

    function uninstallEventsHandlers() {
        unRegisterModuleDependences("sale", "OnSaleDeliveryServiceCalculate", $this->MODULE_ID, "\\dky\\gift\\EventsHandlers", "onSaleDeliveryServiceCalculate");
    }

}
