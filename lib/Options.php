<?php

namespace dky\gift;

use Bitrix\Main\Loader;

class Options extends \Bitrix\Main\Config\Option {

    const MODULE_ID = "dky.gift";
    const CATALOG_IBLOCK_ID = 4;
    const CURRENCY = "RUB";
    const PRICE = 1;

    /**
     * @param string $name
     * @param string $default
     * @param string $siteId
     */
    static function get($name, $default = "", $siteId = null) {
        return parent::get(self::MODULE_ID, $name, $default, $siteId);
    }

    /**
     * @param string $name
     * @param string $value
     * @param string $siteId
     * @return string
     */
    static function set($name, $value = "", $siteId = null) {
        return parent::set(self::MODULE_ID, $name, $value, $siteId);
    }

    /**
     * Get prepared gift conditions array
     * @return array
     */
    static function getConditions() {

        $str = self::get('CONDITIONS', null);
        if ($str) {
            return json_decode(base64_decode($str), true);
        }
        return [];
    }

    /**
     * @param array $conditions
     */
    static function setConditions(array $conditions) {
        $str = base64_encode(json_encode($conditions));
        self::set('CONDITIONS', $str);
    }

}
