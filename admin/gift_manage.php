<?

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use dky\gift\Options;
use dky\gift\Conditions;
use dky\gift\Tools;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

Loc::loadMessages(__FILE__);

Loader::includeModule('iblock');
Loader::includeModule('dky.gift');

$request = \Bitrix\Main\Context::getCurrent()->getRequest();

$conditions = new Conditions(true);

if ($request->isAjaxRequest()) {
    if ($request->get('action') === 'get_products') {
        $arFilter = [
            'IBLOCK_ID' => Options::CATALOG_IBLOCK_ID,
            'ACTIVE' => 'Y'
        ];
        $arNavPageParams = false;
        if ($request->get('term')) {
            $arFilter['NAME'] = "%" . $request->get('term') . "%";
        } else {
            $arNavPageParams['nTopCount'] = 1000;
        }
        $dbProducts = CIBlockElement::GetList(['NAME' => 'ASC'], $arFilter, false, $arNavPageParams, ['ID', 'NAME']);
        $arProducts = [];
        while ($arProduct = $dbProducts->Fetch()) {
            $arProduct['checked'] = false;
            $arProduct['SORT'] = 100;
            $arProduct['PAGE_LINK'] = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . Options::CATALOG_IBLOCK_ID . '&type=catalog&ID=' . $arProduct['ID'] . '&lang=ru&find_section_section=0&WF=Y';
            $arProducts[] = $arProduct;
        }
        Tools::sendJsonResponse(['error' => false, 'result' => !empty($arProducts) ? $arProducts : null]);
    } elseif ($request->isPost() && $request->getPost('action') === 'save_conditions') {
        try {
            $conditions->save($request->getPost('conditions') && is_array($request->getPost('conditions')) ? $request->getPost('conditions') : []);
            Tools::sendJsonResponse(['error' => false, 'result' => true]);
        } catch (Exception $ex) {
            Tools::sendJsonResponse(['error' => true, 'message' => $ex->getMessage()]);
        }
    } elseif ($request->isPost() && $request->getPost('action') === 'change_module_state') {
        Options::set('USE', $request->get('include') === 'Y' ? 'Y' : 'N');
        Tools::sendJsonResponse(['error' => false, 'result' => true]);
    } else {
        Tools::sendJsonResponse(['error' => true, 'message' => Loc::getMessage('DKY_GIFT_ADMIN_PAGE_AJAX_TOTAL_ERROR')]);
    }
}


\Bitrix\Main\UI\Extension::load("ui.forms");
\Bitrix\Main\UI\Extension::load("ui.alerts");
\Bitrix\Main\UI\Extension::load("ui.buttons.icons");
\Bitrix\Main\UI\Extension::load("ui.vue");
$APPLICATION->SetTitle(Loc::getMessage('DKY_GIFT_ADMIN_PAGE_TITLE'));

require_once ($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

echo BeginNote();
echo Loc::getMessage('DKY_GIFT_NOTIFY');
echo EndNote()
?>
<style>
    .dky-gift {
        width: 1120px;
    }
    .dky-gift__condition {
        box-sizing: border-box;
        padding: 20px;
        background: #fff;
        font-size: 16px;
        width: 100%;
        margin-bottom: 20px;
        position: relative;
    }
    .mr-20 {
        margin-top: 20px;
    }
    .add-product-popup-wrapper {
        display: none;
    }
    .product-list {
        max-height: 300px;
        overflow-y: auto;
        margin: 20px 0;
    }
    .text-center {
        text-align: center;
    }
    .popup-buttons {
        margin-top: 10px;
    }
    .product-tag {
        font-size: 22px;
        margin-right: 20px;
        margin-top: 20px;
        padding: 10px;
        border: 1px solid #000;
        background-color: lightgoldenrodyellow;
    }
    .product-tag__remove {
        font-size: 24px;
        cursor: pointer;
    }
    .condition__remove {
        position: absolute;
        top: 0px;
        right: 5px;
        font-size: 22px;
        cursor: pointer;
    }
    .product-tag__name {
        color: #000;
    }
    .inc-options {
        margin-bottom: 20px;
    }
    .sort-input {
        width: 50px;
    }
</style>
<template id="add-product-popup-template">

    <div class="add-product-popup">
        <div class="ui-ctl ui-ctl-after-icon">
            <div v-if="showLoader" class="ui-ctl-after ui-ctl-icon-loader"></div>
            <div v-else class="ui-ctl-after ui-ctl-icon-search"></div>
            <input @input="findProducts" type="text" class="ui-ctl-element ui-ctl-textbox">
        </div>
        <div v-if="productsList" class="product-list">
            <div v-for="product in productsList" :key="product.id" class="product-list__item">
                <input type="checkbox" v-model="product.checked"> {{product.NAME}}
            </div>
        </div>
        <div class="text-center popup-buttons">
            <button @click="add" class="ui-btn ui-btn-success"><?= Loc::getMessage('DKY_GIFT_ADMIN_PAGE_ADD_BTN') ?></button>
            <button @click="close" class="ui-btn ui-btn-danger"><?= Loc::getMessage('DKY_GIFT_ADMIN_PAGE_CLOSE_BTN') ?></button>
        </div>
    </div>

</template>

<div class="dky-gift">
    <div class="inc-options">
        <input type="checkbox" v-model="canUse"> <strong><?= Loc::getMessage('DKY_GIFT_ADMIN_PAGE_INCLUDE_MODULE') ?></strong>
    </div>
    <div v-if="canUse">
        <div v-if="errors" class="ui-alert ui-alert-danger">
            <span class="ui-alert-message">
                <strong>{{errors}}</strong>
            </span>
        </div>
        <div v-if="success" class="ui-alert ui-alert-success">
            <span class="ui-alert-message">
                <strong><?= Loc::getMessage('DKY_GIFT_ADMIN_PAGE_SAVE_SUCCESS') ?></strong>
            </span>
        </div>
        <div v-for="condition, index in conditions" :key="index" class="dky-gift__condition condition">
            <?= Loc::getMessage('DKY_GIFT_ADMIN_PAGE_PRICE_FROM') ?> <div class="ui-ctl ui-ctl-textbox ui-ctl-inline">
                <input v-model="condition.priceFrom" type="text" class="ui-ctl-element">
            </div> <?= Options::CURRENCY ?> &nbsp; 
            <?= Loc::getMessage('DKY_GIFT_ADMIN_PAGE_PRICE_TO') ?> <div class="ui-ctl ui-ctl-textbox ui-ctl-inline">
                <input v-model="condition.priceTo" type="text" class="ui-ctl-element">
            </div> <?= Options::CURRENCY ?>
            <div class="ui-ctl ui-ctl-inline">
                <label><input v-model="condition.giftDelivery" type="checkbox"> <?= Loc::getMessage('DKY_GIFT_ADMIN_PAGE_GIFT_DELIVERY') ?></label>
                <label><input v-model="condition.giftProducts" type="checkbox"> <?= Loc::getMessage('DKY_GIFT_ADMIN_PAGE_GIFT_PRODUCTS') ?></label>
            </div>
            <div v-if="condition.giftProducts" class="ui-ctl ui-ctl-inline">
                <button @click="BX.onCustomEvent('dky.gift:OpenProductsPopup', [index])" class="ui-btn ui-btn-success ui-btn-icon-add"><?= Loc::getMessage('DKY_GIFT_ADMIN_PAGE_ADD_PRODUCTS_BTN') ?></button>
            </div>
            <div v-if="condition.giftProducts"  class="product-list-tags">
                <div v-for="product in condition.productsList" :key="product.ID" class="products-list-tags__tag product-tag">
                    <b>s:</b> <input class="sort-input" type="number" v-model="product.SORT">&nbsp;
                    <a target="_blank" :href="product.PAGE_LINK" class="product-tag__name">{{product.NAME}}</a>&nbsp;&nbsp;<span @click="removeProduct(index, product.ID)" class="product-tag__remove">&times;</span>
                </div>
            </div>
            <div class="condition__remove" :data-index="index" @click="removeCondition(index)">&times;</div>
        </div>
        <div class="dky-gift__buttons buttons">
            <button @click="save" :class="this.showLoader ? 'ui-btn-clock ui-btn ui-btn-success' : 'ui-btn ui-btn-success'"><?= Loc::getMessage('DKY_GIFT_ADMIN_PAGE_SAVE_BTN') ?></button>
            <button @click="addCondition" class="ui-btn ui-btn-primaty ui-btn-icon-add"><?= Loc::getMessage('DKY_GIFT_ADMIN_PAGE_ADD_CONDITION_BTN') ?></button>
        </div>
    </div>
</div>
<script>
    BX.ready(function () {

        let popup = new BX.PopupWindow("add-product-popup", null, {
            content: BX('add-product-popup-template').innerHTML,
            closeIcon: {right: "20px", top: "10px"},
            titleBar: {content: BX.create("h2", {html: "<?= Loc::getMessage('DKY_GIFT_ADMIN_PAGE_POPUP_TITLE') ?>", 'props': {'className': 'add-product-title-bar'}})},
            zIndex: 0,
            offsetLeft: 0,
            offsetTop: 0,
            draggable: {restrict: false},
            overlay: {backgroundColor: 'black', opacity: '80'}
        });

        BX.Vue.create({
            el: '.add-product-popup',
            data() {
                return {
                    conditionIndex: null,
                    timer: null,
                    popup: popup,
                    productsList: null,
                    showLoader: false
                };
            },
            created() {
                if (!this.productsList) {
                    this.findProductsRequest();
                }

                BX.addCustomEvent('dky.gift:OpenProductsPopup', (conditionIndex) => {
                    this.conditionIndex = conditionIndex;
                    this.popup.show();
                });
            },
            beforeDestroy() {
                BX.removeCustomEvent('dky.gift:OpenProductsPopup');
            },
            methods: {
                findProducts(e) {
                    if (!this.timer) {
                        this.showLoader = true;
                        this.timer = setTimeout(() => {
                            this.findProductsRequest(e.target.value || '');
                        }, 500);
                    }
                },
                findProductsRequest(term = '') {
                    BX.ajax.get("<?= $APPLICATION->GetCurPageParam("", ["term"]) ?>", {term: term, action: "get_products"}, (res) => {
                        res = JSON.parse(res);
                        if (res.error) {
                            alert(res.message);
                        } else {
                            this.productsList = res.result;
                        }

                        this.showLoader = false;
                        this.timer = null;
                    });
                },
                add() {
                    BX.Vue.event.$emit('dky.gift:addProducts', {
                        index: this.conditionIndex,
                        productsList: this.productsList.filter((product) => {
                            return product.checked;
                        })
                    });
                    this.close();
                },
                close() {
                    this.popup.close();
                }
            },

        });

        BX.Vue.create({
            el: '.dky-gift',
            data: {
                canUse: <?
            if (Tools::canUse()) {
                echo "true";
            } else {
                echo "false";
            }
            ?>,
                errors: null,
                success: null,
                showLoader: false,
                conditions: <?= json_encode(!empty($conditions->toArray()) ? $conditions->toArray() : [$conditions->defaultStructure()]) ?>
            },
            created() {
                BX.Vue.event.$on('dky.gift:addProducts', (data) => {

                    data.productsList.map((p) => {
                        return {
                            ID: p.ID,
                            NAME: p.NAME
                        };
                    }).forEach((p) => {
                        const product = this.conditions[data.index].productsList.find(p2 => p2.ID == p.ID);
                        if (typeof product === 'undefined') {
                            this.conditions[data.index].productsList.push(p);
                        }
                    });


                });
            },
            beforeDestroy() {
                BX.Vue.event.$off('dky.gift:addProducts');
            },
            watch: {
                canUse() {

                    BX.ajax.post('<?= $APPLICATION->GetCurPageParam() ?>', {
                        sessid: BX.bitrix_sessid(),
                        action: 'change_module_state',
                        include: this.canUse ? "Y" : "N"
                    });
                }
            },
            methods: {

                removeProduct(index, productid) {
                    this.conditions[index].productsList = this.conditions[index].productsList.filter(p => p.ID != productid);
                },
                addCondition() {
                    this.conditions.push(<?= json_encode($conditions->defaultStructure()) ?>);
                },
                removeCondition(index) {

                    this.conditions = this.conditions.filter((c, i) => {

                        return index != i;
                    });
                },

                save() {
                    this.errors = null;
                    this.success = false;
                    this.showLoader = true;
                    BX.ajax.post('<?= $APPLICATION->GetCurPageParam() ?>', {
                        sessid: BX.bitrix_sessid(),
                        action: 'save_conditions',
                        conditions: this.conditions.map(c => {

                            let data = {};
                            for (let prop in <?= json_encode($conditions->defaultStructure()) ?>) {
                                data[prop] = c[prop];
                            }
                            return data;
                        })
                    }, res => {
                        res = JSON.parse(res);
                        if (res.error) {
                            this.errors = res.message;
                            this.$nextTick(function () {
                                BX.scrollToNode(document.querySelector('.ui-alert-danger'))
                            });
                        } else {
                            this.success = true;
                            this.$nextTick(function () {
                                BX.scrollToNode(document.querySelector('.ui-alert-success'))
                            });
                        }
                        this.showLoader = false;
                    });
                }
            }
        });
    });

</script>
<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
?>
