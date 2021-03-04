<?php

if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/modules/dky.gift/admin/gift_manage.php')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/local/modules/dky.gift/admin/gift_manage.php');
} else {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/dky.gift/admin/gift_manage.php');
}