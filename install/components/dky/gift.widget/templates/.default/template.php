<?php

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);


if (CSite::InDir("/personal/cart/") || CSite::InDir("/personal/order/make/")) {
    return;
}

CJSCore::Init(['popup']);
CJSCore::Init(['ajax']);
\Bitrix\Main\UI\Extension::load("ui.vue");
?>
<template id="gift-info-template">
    <div v-if="showGiftInfo" class="dky-widget dky-widget-gift-info dky-widget_shadow">

        <div class="dky-widget__gifts dky-widget-gifts">
            <div class="dky-widget-gifts__gift gift">
                <div class="gift__img">
                    <img :src="giftInfo.IMG_SRC">
                </div>
                <div class="gift__title"><?= Loc::getMessage('DKY_GIFT_WIDGET_GIFT_INFO_TITLE') ?></div>
            </div>
            <div class="dky-widget-gifts__gift gift">
                <button onclick="BX.setCookie('dky_gift_not_show_gift_info', 'Y', {expires: 999999});location.href = '/personal/cart/'" class="btn_gift"><?= Loc::getMessage('DKY_GIFT_WIDGET_SHOW_BTN') ?></button>
            </div>
        </div>
        <div v-if="isMobile" @click="BX.setCookie('dky_gift_not_show_gift_info', 'Y', {expires: 999999});showGiftInfo=false" class="dky-widget__closer">&times;</div>
        <div v-else @click="BX.setCookie('dky_gift_not_show_gift_info', 'Y', {expires: 999999});showGiftInfo=false" class="dky-widget__closer dky-widget__closer_popup-position">&times;</div>
    </div>
</template>
<template id="widget-template">
    <div :class="!isMobile ? 'dky-widget' : 'dky-widget dky-widget_shadow'">
        <div class="dky-widget__title" v-html="title"></div>
        <div v-for="giftsRow, index in conditions.gifts" :key="index" class="dky-widget__gifts dky-widget-gifts">
            <div v-for="gift in giftsRow" class="dky-widget-gifts__gift gift gift_mw">
                <div class="gift__img">
                    <img :src="gift.IMG_SRC">
                </div>
                <div class="gift__title">{{gift.NAME}}</div>
            </div>

        </div>
        <div :class="isMobile ? 'dky-widget__scale dky-widget-scale' : 'dky-widget__scale dky-widget-scale dky-widget__scale_small'">
            <div class="dky-widget-scale__color"></div>
            <div class="dky-widget-scale__range range">
                <div :style="{width: conditions.percent + '%'}" class="range__color"></div>
                <div class="range__values range-values">
                    <div class="range-values__value">{{conditions.priceFrom}} <i class="rub"></i></div>
                    <div :class="isMobile ? 'range-values__value' : 'range-values__value range-values__value_abs'"><span class="range__value_text" v-html="delta"></span></div>
                    <div class="range-values__value">{{conditions.priceTo}} <i class="rub"></i></div>
                </div>
            </div>
        </div>
        <div v-if="isMobile" class="dky-widget__btn-area"><button onclick="location.href = '/personal/cart/'" class="btn_gift"><?= Loc::getMessage('DKY_GIFT_WIDGET_MAKE_ORDER_BTN') ?></button></div>
        <div v-if="isMobile" onclick="BX.onCustomEvent('dky:widget-close')" class="dky-widget__closer">&times;</div>
    </div>
</template>
<template id="popup-basket-template">
    <div class="dky-popup-basket">
        <div @click="closePopup" class="dky-widget__closer dky-widget__closer_desktop-position">&times;</div>
        <div v-if="basket.items && basket.items.length" class="dky-popup-basket__table-wrapper">
            <table class="dky-popup-basket__basket">

                <tbody>
                    <tr v-for="item, index in basket.items">
                        <td class="basket__item basket__item-image-td">
                            <a :href="item.DETAIL_PAGE_URL">                                        
                                <img class="basket__item-image dky-popup-basket_imgw" :src="item.IMG_SRC">
                            </a>
                        </td>
                        <td class="basket__item basket__item_name">
                            <div class="product__brand">{{item.BRAND}}</div>
                            <a :title="item.NAME" class="dky-popup-basket__product-name" :href="item.DETAIL_PAGE_URL">  
                                {{item.NAME}}
                            </a>                                    
                        </td>

                        <td class="basket__item basket__item_quantity">
                            <div v-if="!item.GIFT" class="dky-popup-basket__basket-num basket-num d-flex mx-auto">
                                <div v-if="item.DISABLE_CHANGE_QUANTITY" class="basket-num__disabler"></div>
                                <span>
                                    <a @click="minusProductQuantity(index)" href="javascript:void(0);" class="basket__minus" >-</a>
                                </span>
                                <input @keydown="keyDown(event, index)" @focusout="inputProductQuantity(event, index)" type="text" class="basket__quantity" size="3" maxlength="18" :value="item.QUANTITY">
                                <span><a @click="plusProductQuantity(index)" href="javascript:void(0);" class="basket__plus" >+</a></span>
                            </div>
                            <div v-else><img title="<?= Loc::getMessage('DKY_GIFT_WIDGET_GIFT_IMG_TITLE') ?>" src="<?= $templateFolder ?>/gift.png"></div>
                        </td>
                        <td class="basket__item basket__item-price-td">
                            <div class="current_price">
                                {{item.PRICE}} <i class="rub"></i>                                    
                            </div>
                            <div v-if="item.PRICE!=item.OLD_PRICE" class="old_price">
                                {{item.OLD_PRICE}} <i class="rub"></i>
                            </div>
                        </td>

                        <td class="control basket__item basket__item-delete-td">
                            <a v-if="!item.GIFT" @click="deleteBasketItem(index)" href="javascript:;"><span class="fa fa-remove"></span></a><br>
                        </td>


                    </tr>
                </tbody>

            </table>
        </div>
        <dky-widget v-if="conditions" :conditions="conditions" :is-mobile="isMobile"></dky-widget>
        <div class="dky-popup-basket__footer dky-popup-basket-footer">
            <div class="dky-popup-basket-footer__left">
                Итого: <span class="dky-popup-basket_line-through">{{basket.total}} <i class="rub"></i></span><span class="dky-popup-basket__total-with-discount reduced_price">&nbsp;&nbsp;&nbsp;&nbsp;{{basket.totalWithDiscount}} <i class="rub"></i></span>
            </div>
            <div class="dky-popup-basket-footer__right">
                <button onclick="location.href = '/personal/cart/'" class="btn_gift"><?= Loc::getMessage('DKY_GIFT_WIDGET_MAKE_ORDER_BTN') ?></button>
            </div>
        </div>

    </div>
</template>

<div id="mobile-widget"></div>
<div id="mobile-gift-info"></div>

<script>
    BX.ready(function () {
        BX.message({
            DKY_GIFT_WIDGET_DELTA: '<?= Loc::getMessage('DKY_GIFT_WIDGET_DELTA') ?>',
            DKY_GIFT_WIDGET_TITLE: '<?= Loc::getMessage('DKY_GIFT_WIDGET_TITLE') ?>:'
        });
        BX.DkyGifts.WidgetComponent({
            showGiftInfo: <?
if ($arResult['SHOW_GIFT_INFO']) {
    echo "true";
} else {
    echo "false";
}
?>,
            giftInfo: <?
if ($arResult['GIFT_INFO']) {
    echo json_encode($arResult['GIFT_INFO']);
} else {
    echo "null";
}
?>,
            showWidget: false,
            showPopupBasket: false,
            triggerWidgetShowSelector: '.tools__basket',
            conditions: <?= json_encode($arResult['DISPLAY_GIFT_CONDITION']) ?>,
            basket: <?= json_encode($arResult['BASKET']) ?>,
            minProductQuantity: 1,
            maxProductQuantity: 22,
            widgetContainerSelector: '#mobile-widget',
            giftInfoContainerSelector: '#mobile-gift-info',
            popupBasketSelector: '.dky-popup-basket',
            popupGiftInfoSelector: '.dky-widget-gift-info'
        });
    });
</script>