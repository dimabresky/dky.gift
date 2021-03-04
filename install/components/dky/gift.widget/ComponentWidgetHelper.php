<?php

use Bitrix\Sale;

class ComponentWidgetHelper extends Sale\BasketComponentHelper {

    /**
     * 
     * @param int $fuserId
     * @param string $siteId
     * @return array
     */
    static function getFUserBasketList($fuserId, $siteId = null) {

        return parent::getFUserBasketList($fuserId, $siteId);
    }

    /**
     * @param array $basketList
     * @return float
     */
    static function getBasketTotalPrice() {


        $basket = Sale\Basket::loadItemsForFUser(Sale\Fuser::getId(), Bitrix\Main\Context::getCurrent()->getSite());
        $totalPrice = self::calculateBasketCost($basket);
        unset($basket);

        return $totalPrice;
    }

    /**
     * @param array $basketList
     * @return float
     */
    static function getBasketPrice(array $basketList) {
        $totalPrice = 0;
        foreach ($basketList as $basketData) {

            $totalPrice += $basketData["BASE_PRICE"] * $basketData["QUANTITY"];
        }

        return $totalPrice;
    }

    /**
     * 
     * @param float|int $number
     * @param int $decPoint
     * @return float
     */
    static function formatNumber($number, int $decPoint = 2) {
        return number_format($number, $decPoint, '.', ' ');
    }

}
