<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Sale;
use dky\gift\Tools;
use dky\gift\Options;
use Bitrix\Catalog\Product\Basket;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Localization\Loc;
use Bitrix\Highloadblock\HighloadBlockTable as HL;
use Bitrix\Catalog\ProductTable;

Loc::loadMessages(__FILE__);

/**
 * @author dimabresky
 */
class DkyGiftWidgetComponent extends CBitrixComponent implements Controllerable {

    /**
     * 
     * @var array
     */
    protected $basketItemsList = null;

    public function configureActions() {

        return [
            'setProductQuantity' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod(
                            array(ActionFilter\HttpMethod::METHOD_GET, ActionFilter\HttpMethod::METHOD_POST)
                    ),
                    new ActionFilter\Csrf()
                ],
            ],
            'deleteBasketItem' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod(
                            array(ActionFilter\HttpMethod::METHOD_GET, ActionFilter\HttpMethod::METHOD_POST)
                    ),
                    new ActionFilter\Csrf()
                ],
            ],
            'getTotalData' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod(
                            array(ActionFilter\HttpMethod::METHOD_GET, ActionFilter\HttpMethod::METHOD_POST)
                    ),
                    new ActionFilter\Csrf()
                ],
            ]
        ];
    }

    /**
     * 
     * @return array
     */
    function getTotalData() {

        $this->setResult();
        return [
            'conditions' => $this->arResult['DISPLAY_GIFT_CONDITION'],
            'basket' => $this->arResult['BASKET']
        ];
    }

    /**
     * 
     * @return array
     */
    function getTotalDataAction() {
        return $this->componentMethodDecorateAndRun('getTotalData');
    }

    /**
     * 
     * @param array $basketItemid
     * @return array
     */
    function deleteBasketItemAction($basketItemid) {

        $this->componentMethodDecorateAndRun("deleteBasketItem", [$basketItemid]);
    }

    /**
     * 
     * @param int $basketItemid
     */
    function deleteBasketItem($basketItemid) {
        CSaleBasket::Delete($basketItemid);
    }

    /**
     * 
     * @param int $basketItemid
     * @param int $quantity
     */
    function setProductQuantityAction($basketItemid, $quantity) {

        $this->componentMethodDecorateAndRun("setProductQuantity", [$basketItemid, $quantity]);
    }

    /**
     * 
     * @param int $basketItemid
     * @param int $quantity
     */
    function setProductQuantity($basketItemid, $quantity) {

        CSaleBasket::Update($basketItemid, ['QUANTITY' => $quantity]);
    }

    /**
     * @param string $mehtodName
     * @param array $args
     * @return mixed
     */
    function componentMethodDecorateAndRun(string $mehtodName, array $args = []) {
        $this->initComponentParameters();
        $GLOBALS['DKY.GIFT:PROCESS'] = true;
        $result = call_user_func_array([$this, $mehtodName], $args);
        unset($GLOBALS['DKY.GIFT:PROCESS']);
        return $result;
    }

    function initComponentParameters() {
        Loader::includeModule('dky.gift');
        Loader::includeModule('iblock');
        Loader::includeModule('sale');
        Loader::includeModule('catalog');

        require_once "ComponentWidgetHelper.php";
    }

    function executeComponent() {

        if (CSite::InDir("/personal/cart/") && CSite::InDir("/personal/order/make/")) {
            return;
        }

        if (!Loader::includeModule('dky.gift')) {
            return;
        }

        $this->initComponentParameters();

        if (!Tools::canUse()) {
            $this->removeGifts();
            return;
        }

        $this->setResult();

        $this->includeComponentTemplate();
    }

    /**
     * @return $this
     */
    function setResult() {

        $this->arResult['DISPLAY_GIFT_CONDITION'] = null;
        $this->arResult['SHOW_GIFT_INFO'] = $_COOKIE['dky_gift_not_show_gift_info'] !== 'Y';
        $this->arResult['GIFT_INFO'] = null;

        $this->removeGifts();

        $lastBasketGiftProductid = $this->addAndGetAddedGiftId();

        $this->setLastGiftInfo($lastBasketGiftProductid);

        $this->setDisplayGiftsConditionData();

        $this->setDisplayBasketData();

        return $this;
    }

    function setDisplayGiftsConditionData() {
        $price = $this->getBasketTotalPrice();
        $arConditions = Tools::getConditionsByPrice($this->getBasketTotalPrice());
        if ($arConditions['next']) {

            $priceFrom = 0;
            $priceTo = 0;
            if ($current = array_pop($arConditions['current'])) {
                $priceFrom = $current['priceFrom'];
                $priceTo = $current['priceTo'];
            } else {
                $priceFrom = $arConditions['next']['priceFrom'];
                $priceTo = $arConditions['next']['priceTo'];
            }

            $range = $priceTo - $priceFrom;
            $percent = 0;
            $d1 = $priceTo - $price;
            $d2 = $price - $priceFrom;
            if ($d2 > 0) {
                $percent = round(100 * ($d2) / $range);
            }


            $this->arResult['DISPLAY_GIFT_CONDITION'] = [
                'priceFrom' => $priceFrom,
                'priceTo' => $priceTo,
                'delta' => $d1,
                'percent' => $percent,
                'gifts' => [],
                'giftDelivery' => $arConditions['next']['giftDelivery']
            ];

            if ($arConditions['next']['giftProducts'] && !empty($arConditions['next']['productsList'])) {
                $productsListId = array_column($arConditions['next']['productsList'], 'ID');
                $arInvolveGifts = $this->getInvolveGifts($productsListId);
                if (isset($arInvolveGifts[0])) {

                    $arRow = CIBlockElement::GetList(false, ['IBLOCK_ID' => Options::CATALOG_IBLOCK_ID, 'ACTIVE' => 'Y', 'ID' => $arInvolveGifts[0]], false, false, ['ID', 'NAME', 'DETAIL_PICTURE'])->Fetch();
                    $this->arResult['DISPLAY_GIFT_CONDITION']['gifts'][] = [
                        'ID' => $arRow['ID'],
                        'NAME' => $arRow['NAME'],
                        'IMG_SRC' => $this->getResizedImgSrc($arRow['DETAIL_PICTURE'])
                    ];
                }
            }

            if ($arConditions['next']['giftDelivery']) {
                $this->arResult['DISPLAY_GIFT_CONDITION']['gifts'][] = [
                    'ID' => 'DELIVERY',
                    'NAME' => Loc::getMessage('DKY_GIFT_WIDGET_FREE_DELIVERY'),
                    'IMG_SRC' => $this->__path . '/delivery.png'
                ];
            }
        }

        if (!empty($this->arResult['DISPLAY_GIFT_CONDITION']['gifts'])) {
            $this->arResult['DISPLAY_GIFT_CONDITION']['gifts'] = array_chunk($this->arResult['DISPLAY_GIFT_CONDITION']['gifts'], 2);
        }
    }

    /**
     * 
     * @param int|null $lastBasketGiftProductid
     */
    function setLastGiftInfo($lastBasketGiftProductid) {

        if ($lastBasketGiftProductid && @$this->arResult['SHOW_GIFT_INFO']) {
            $arProduct = CIBlockElement::GetList(false, ['IBLOCK_ID' => Options::CATALOG_IBLOCK_ID, 'ACTIVE' => 'Y', 'ID' => $lastBasketGiftProductid], false, false, ['ID', 'NAME', 'DETAIL_PICTURE'])->Fetch();
            if ($arProduct) {
                $this->arResult['GIFT_INFO'] = [
                    'ID' => $arProduct['ID'],
                    'NAME' => $arProduct['NAME'],
                    'IMG_SRC' => $this->getResizedImgSrc($arProduct['DETAIL_PICTURE'])
                ];
            }
        }
    }

    /**
     * 
     * @return float
     */
    function getBasketTotalPrice() {
        $fuserid = Sale\Fuser::getId();
        $arBasketListItems = ComponentWidgetHelper::getFUserBasketList($fuserid);
        return ComponentWidgetHelper::getBasketTotalPrice($arBasketListItems);
    }

    function getBasketPrice() {
        $fuserid = Sale\Fuser::getId();
        $arBasketListItems = ComponentWidgetHelper::getFUserBasketList($fuserid);
        return ComponentWidgetHelper::getBasketPrice($arBasketListItems);
    }

    /**
     * 
     * @param int $imgid
     * @return string|null
     */
    function getResizedImgSrc($imgid) {

        $imgSrc = null;
        if ($imgid) {
            $arImg = CFile::ResizeImageGet($imgid, array('width' => 260, 'height' => 260), BX_RESIZE_IMAGE_EXACT, false);
            if (isset($arImg['src'])) {
                $imgSrc = $arImg['src'];
            }
        }
        return $imgSrc;
    }

    /**
     * 
     * @return int|null
     */
    function addAndGetAddedGiftId() {

        $arConditions = Tools::getConditionsByPrice($this->getBasketTotalPrice());
        $giftid = [];
        if (!empty($arConditions)) {
            $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), Context::getCurrent()->getSite());
            foreach ($arConditions['current'] as $arCondition) {

                if ($arCondition['giftProducts'] && !empty($arCondition['productsList'])) {

                    // get gifts with quantity >0 in system
                    $arInvolveGifts = $this->getInvolveGifts(array_column($arCondition['productsList'], "ID"));
                    foreach ($arCondition['productsList'] as $k => $product) {
                        if (in_array($product['ID'], $arInvolveGifts)) {
                            // add gift with custom price
                            $arPriceData = CCatalogProduct::GetOptimalPrice($product['ID']);
                            $fields = [
                                'PRODUCT_ID' => $product['ID'], // ID товара, обязательно
                                'QUANTITY' => 1, // количество, обязательно
                                'PRICE' => Options::PRICE,
                                'DISCOUNT_PRICE' => $arPriceData['RESULT_PRICE']['BASE_PRICE'] - Options::PRICE,
                                'BASE_PRICE' => $arPriceData['RESULT_PRICE']['BASE_PRICE'],
                                'CURRENCY' => Options::CURRENCY,
                                'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
                                'CUSTOM_PRICE' => 'Y',
                                'PROPS' => [
                                    ['NAME' => 'gift', 'CODE' => 'DKY:GIFT', 'VALUE' => 'Y'],
                                ],
                            ];

                            $res = Basket::addProduct($fields, [], ['USE_MERGE' => 'N']);
                            if ($res->isSuccess()) {
                                $giftid = $product['ID'];
                            }
                            break;
                        }
                    }
                }
            }
        }
        return $giftid;
    }

    /**
     * 
     * @param array $giftsid
     * @return array
     */
    function getInvolveGifts(array $giftsid) {

        return array_column(ProductTable::getList([
                            'filter' => [
                                'ID' => $giftsid,
                                '>QUANTITY' => 0
                            ],
                            'select' => ['ID']
                        ])->fetchAll(), "ID") ?: [];
    }

    /**
     * @param array $arBasketGiftsItemsBasketid
     */
    function removeGifts() {

        $arBasketItems = ComponentWidgetHelper::getFUserBasketList(Sale\Fuser::getId());

        if (!empty($arBasketItems)) {
            $arProperties = $this->getBasketItemsProperties([
                'BASKET_ID' => array_column($arBasketItems, 'ID'),
                'CODE' => "DKY:GIFT"
            ]);
            if (!empty($arProperties)) {
                foreach (array_keys($arProperties) as $basketItemid) {
                    CSaleBasket::Delete($basketItemid);
                }
            }
        }
    }

    /**
     * 
     * @param array $filter
     * @return array
     */
    function getBasketItemsProperties(array $filter) {
        $dbRows = CSaleBasket::GetPropsList(
                        array(
                            "SORT" => "ASC",
                            "NAME" => "ASC"
                        ),
                        $filter
        );
        $arProperties = [];
        while ($arRow = $dbRows->Fetch()) {

            $arProperties[$arRow['BASKET_ID']] = $arRow;
        }

        return $arProperties;
    }

    /**
     * 
     * @return array
     */
    function setDisplayBasketData() {
        $totalPriceWithDiscount = $this->getBasketTotalPrice();
        $result = [
            'total' => ComponentWidgetHelper::formatNumber($this->getBasketPrice()),
            'totalWithDiscountSource' => $totalPriceWithDiscount,
            'totalWithDiscount' => ComponentWidgetHelper::formatNumber($totalPriceWithDiscount),
            'items' => []
        ];

        $arBasketItems = ComponentWidgetHelper::getFUserBasketList(Sale\Fuser::getId());
        if (!empty($arBasketItems)) {
            $arGiftsProperties = $this->getBasketItemsProperties([
                'BASKET_ID' => array_column($arBasketItems, "ID"),
                'CODE' => 'DKY:GIFT'
            ]);

            foreach ($arBasketItems as $arBasketItem) {

                $result['items'][] = [
                    'DISABLE_CHANGE_QUANTITY' => false,
                    'GIFT' => isset($arGiftsProperties[$arBasketItem['ID']]),
                    'ID' => $arBasketItem['ID'],
                    'PRODUCT_ID' => $arBasketItem['PRODUCT_ID'],
                    'QUANTITY' => intval($arBasketItem['QUANTITY']),
                    'PRICE' => ComponentWidgetHelper::formatNumber($arBasketItem['PRICE'], 0),
                    'OLD_PRICE' => ComponentWidgetHelper::formatNumber($arBasketItem['BASE_PRICE'], 0),
                    'CURRENCY' => $arBasketItem['CURRENCY'],
                    'NAME' => $arBasketItem['NAME'],
                    'BRAND' => '',
                    'IMG_SRC' => null,
                    'DETAIL_PAGE_URL' => $arBasketItem['DETAIL_PAGE_URL']
                ];
            }

            if (!empty($result)) {

                $HLTableEntityClass = null;

                $productsid = array_column($result['items'], "PRODUCT_ID");
                $dbRows = CIBlockElement::GetList(false, ['ACTIVE' => 'Y', 'IBLOCK_ID' => Options::CATALOG_IBLOCK_ID, 'ID' => $productsid], false, false, ['ID', 'PROPERTY_BRAND', 'IBLOCK_ID', 'DETAIL_PICTURE', 'PREVIEW_PICTURE']);
                while ($row = $dbRows->GetNextElement()) {
                    $arFields = $row->GetFields();
                    $arProps = $row->GetProperties();
                    foreach ($productsid as $index => $productid) {
                        if ($productid == $arFields['ID']) {

                            $result['items'][$index]['BRAND'] = null;

                            if ($arProps['BRAND']['VALUE']) {

                                if (!$HLTableEntityClass) {
                                    $arHLData = HL::getList(['filter' => ['TABLE_NAME' => $arProps['BRAND']['USER_TYPE_SETTINGS']['TABLE_NAME']], 'select' => [
                                                    'ID'
                                        ]])->fetch();
                                    $HLTableEntityClass = HL::compileEntity($arHLData['ID'])->getDataClass();
                                }

                                $arBrand = $HLTableEntityClass::getList(['filter' => ['UF_XML_ID' => $arProps['BRAND']['VALUE']], 'select' => ['UF_NAME']])->fetch();

                                $result['items'][$index]['BRAND'] = @$arBrand['UF_NAME'];
                            }

                            if ($arFields['PREVIEW_PICTURE'] > 0) {
                                $result['items'][$index]['IMG_SRC'] = $this->getResizedImgSrc($arFields['PREVIEW_PICTURE']);
                            } elseif ($arFields['DETAIL_PICTURE'] > 0) {
                                $result['items'][$index]['IMG_SRC'] = $this->getResizedImgSrc($arFields['DETAIL_PICTURE']);
                            }
                        }
                    }
                }
            }
        }


        $this->arResult['BASKET'] = $result;
    }

    /**
     * 
     * @return boolean
     */
    function onlyGiftInBasket() {
        $arBasketItems = ComponentWidgetHelper::getFUserBasketList(Sale\Fuser::getId());

        if (!empty($arBasketItems)) {
            $arProperties = $this->getBasketItemsProperties([
                'BASKET_ID' => array_column($arBasketItems, 'ID'),
                'CODE' => "DKY:GIFT"
            ]);

            return count($arBasketItems) == 1 && !empty($arProperties);
        }

        return false;
    }

}
