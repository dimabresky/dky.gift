<?php

namespace dky\gift;

use Bitrix\Sale;
use Bitrix\Main\Context;

/**
 * Process events
 * 
 * @author dimabresky
 */
class EventsHandlers {

    /**
     * @param \Bitrix\Main\EventResult $result
     * @param \Bitrix\Sale\Shipment $shipment
     * @param int $deliveryid
     * @return int
     */
    public static function onSaleDeliveryServiceCalculate($result, $shipment) {

        if (Tools::canUse()) {
            $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), Context::getCurrent()->getSite());
            $arGiftsConditions = Tools::getConditionsByPrice($basket->getPrice());

            if (isset($arGiftsConditions['current'])) {

                foreach ($arGiftsConditions['current'] as $arCondition) {

                    if ($arCondition['giftDelivery']) {
                        $result->setDeliveryPrice(0);
                        if ($shipment && method_exists($shipment, 'setBasePriceDelivery')) {
                            $shipment->setBasePriceDelivery(0, true);
                        }

                        break;
                    }
                }
            }
        }

        return \Bitrix\Main\EventResult::SUCCESS;
    }

}
