
BX.namespace('DkyGifts');

BX.DkyGifts.WidgetComponent = function (options) {
    BX.Vue.component('dky-widget', {
        template: document.getElementById('widget-template').innerHTML,
        props: {
            isMobile: Boolean,
            conditions: {
                type: Object,
                default: options.conditions
            }
        },
        computed: {
            delta() {
                return BX.message('DKY_GIFT_WIDGET_DELTA').replace('#delta#', this.conditions.delta);
            },
            title() {
                return BX.message('DKY_GIFT_WIDGET_TITLE').replace('#priceTo#', this.conditions.priceTo)
            }
        }
    });

    if (BX.browser.IsMobile()) {
        if (options.conditions && options.triggerWidgetShowSelector && options.widgetContainerSelector) {
            BX.Vue.create({
                el: options.widgetContainerSelector,
                data: {
                    showWidget: options.showWidget,
                    isMobile: true
                },
                mounted() {

                    const node = document.querySelector(options.triggerWidgetShowSelector);
                    if (node) {
                        node.onclick = e => {
                            e.preventDefault();
                            this.showWidget = true;
                        };
                    }
                    BX.addCustomEvent("dky:widget-close", () => {
                        this.showWidget = false;
                    });
                },
                beforeDestroy() {
                    BX.removeCustomEvent("dky:widget-close");
                },
                template: '<dky-widget :is-mobile="isMobile" v-if="showWidget"/>',
            });
        }

        if (options.showGiftInfo && options.giftInfo && options.giftInfoContainerSelector) {
            BX.Vue.create({
                el: options.giftInfoContainerSelector,
                template: document.getElementById('gift-info-template').innerHTML,
                data: {
                    giftInfo: options.giftInfo,
                    showGiftInfo: true,
                    isMobile: true
                }
            });
        }
    } else {
        const popupWidget = BX.PopupWindowManager.create("popup-basket", document.querySelector(options.triggerWidgetShowSelector), {
            content: document.getElementById('popup-basket-template').innerHTML,
            width: 595, // ширина окна
            zIndex: 100, // z-index
            closeByEsc: true, // закрытие окна по esc
            darkMode: false, // окно будет светлым или темным
            autoHide: true, // закрытие при клике вне окна
            draggable: false, // можно двигать или нет
            resizable: false, // можно ресайзить
            lightShadow: true, // использовать светлую тень у окна
            angle: true, // появится уголок

        });

        BX.Vue.create({
            el: options.popupBasketSelector,
            data: {
                isMobile: false,
                basket: options.basket,
                conditions: options.conditions
            },
            created() {
                BX.addCustomEvent('OnBasketChange', this.onBasketChange);

            },
            beforeDestroy() {
                BX.removeCustomEvent('OnBasketChange', this.onBasketChange);
            },
            mounted() {

                this.setOpenWidjetEvent();

                if (options.showPopupBasket) {
                    popupWidget.show();
                }
            },
            methods: {
                closePopup() {
                    popupWidget.close();
                },
                minusProductQuantity(i) {

                    this.basket.items[i].QUANTITY -= 1;

                    if (this.basket.items[i].QUANTITY < options.minProductQuantity) {
                        this.basket.items[i].QUANTITY = options.minProductQuantity;
                    }

                    this.setProductQuantity(i);

                },
                plusProductQuantity(i) {

                    this.basket.items[i].QUANTITY += 1;
                    if (this.basket.items[i].QUANTITY > options.maxProductQuantity) {
                        this.basket.items[i].QUANTITY = options.maxProductQuantity;
                    }

                    this.setProductQuantity(i);

                },
                keyDown: function (e, i) {
                    if (e.keyCode === 13) {
                        this.inputProductQuantity(e, i);
                    }
                },
                inputProductQuantity(e, i) {

                    if (this.firedByEnter) {
                        return;
                    }

                    this.basket.items[i].QUANTITY = parseInt(e.target.value.replace(/[^\d]/g, ''));
                    if (this.basket.items[i].QUANTITY > options.maxProductQuantity) {
                        this.basket.items[i].QUANTITY = options.maxProductQuantity;
                    } else if (this.basket.items[i].QUANTITY < options.minProductQuantity) {
                        this.basket.items[i].QUANTITY = options.minProductQuantity;
                    }
                    this.setProductQuantity(i);
                },
                setProductQuantity(i) {

                    this.basket.items[i].DISABLE_CHANGE_QUANTITY = true;
                    BX.ajax.runComponentAction('dky:gift.widget', 'setProductQuantity', {
                        mode: 'class',
                        data: {
                            basketItemid: this.basket.items[i].ID,
                            quantity: this.basket.items[i].QUANTITY
                        }
                    }).then((response) => {
                        if (response.status === 'success') {
                            BX.onCustomEvent('OnBasketChange');
                        }
                        this.basket.items[i].DISABLE_CHANGE_QUANTITY = false;
                    });

                },
                deleteBasketItem(i) {
                    BX.ajax.runComponentAction('dky:gift.widget', 'deleteBasketItem', {
                        mode: 'class',
                        data: {
                            basketItemid: this.basket.items[i].ID
                        }
                    }).then((response) => {
                        if (response.status === 'success') {
                            BX.onCustomEvent('OnBasketChange');
                        }
                    });
                },
                setOpenWidjetEvent() {

                    const node = document.querySelector(options.triggerWidgetShowSelector);
                    
                    if (node) {

                        node.onclick = (e) => {
                            
                            e.preventDefault();

                            popupWidget.setBindElement(node);
                            popupWidget.show();


                        };
                    }
                },
                onBasketChange() {
                    setTimeout(() => {
                        this.setOpenWidjetEvent();
                    }, 3000);
                    BX.ajax.runComponentAction('dky:gift.widget', 'getTotalData', {
                        mode: 'class'
                    }).then((response) => {
                        if (response.status === 'success') {
                            this.conditions = response.data.conditions;
                            this.basket = response.data.basket;
                        }
                    });
                }
            }
        });


        if (options.showGiftInfo && options.giftInfo && options.popupGiftInfoSelector) {

            const popupGiftInfo = BX.PopupWindowManager.create("popup-gift-info", document.querySelector(options.triggerWidgetShowSelector), {
                content: document.getElementById('gift-info-template').innerHTML,
                width: 500, // ширина окна
                zIndex: 100, // z-index
                closeByEsc: false, // закрытие окна по esc
                darkMode: false, // окно будет светлым или темным
                autoHide: true, // закрытие при клике вне окна
                draggable: false, // можно двигать или нет
                resizable: false, // можно ресайзить
                lightShadow: true, // использовать светлую тень у окна
                angle: true, // появится уголок

            });

            BX.Vue.create({
                el: options.popupGiftInfoSelector,
                data: {
                    giftInfo: options.giftInfo,
                    showGiftInfo: true,
                    isMobile: false
                },
                watch: {
                    showGiftInfo() {
                        popupGiftInfo.close();
                    }
                },
                mounted() {
                    popupGiftInfo.show();
                }
            });
        }
    }
}
