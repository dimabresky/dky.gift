<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arMenu[] = [
    "parent_menu" => "global_menu_store",
    "section" => "gift_manage",
    "sort" => -1,
    "text" => Loc::getMessage('DKY_GIFT_MENU_ITEM_TITLE'),
    "title" => Loc::getMessage('DKY_GIFT_MENU_ITEM_TITLE'),
    "icon" => '',
    "page_icon" => '',
    "items_id" => "menu_bx_gift_manage",
    "url" => "dky_gift_manage.php?lang=" . LANGUAGE_ID,
];

return $arMenu;