<?php

use Bitrix\Sale;
use Bitrix\Main;
use Bitrix\Catalog;

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

    /**
     * @param Basket $basket
     *
     * @return int|float
     */
    protected static function calculateBasketCost(Bitrix\Sale\Basket $basket) {
        if ($basket->count() == 0)
            return 0;

        $oldApiStatus = Sale\Compatible\DiscountCompatibility::isUsed(); // TODO: remove this code after refactoring DiscountCompatibility
        if ($oldApiStatus)
            Sale\Compatible\DiscountCompatibility::stopUsageCompatible();
        //DiscountCouponsManager::freezeCouponStorage();
        $basket->refreshData(array('PRICE', 'COUPONS'));
        $discounts = Sale\Discount::buildFromBasket($basket, new Sale\Discount\Context\Fuser($basket->getFUserId(true)));
        $discounts->calculate();
        $discountResult = $discounts->getApplyResult();
        //DiscountCouponsManager::unFreezeCouponStorage();
        if ($oldApiStatus)
            Sale\Compatible\DiscountCompatibility::revertUsageCompatible();

        if (empty($discountResult['PRICES']['BASKET']))
            return 0;

        $result = 0;
        $discountResult = $discountResult['PRICES']['BASKET'];
        /** @var BasketItem $basketItem */
        foreach ($basket as $basketItem) {
            if (!$basketItem->canBuy())
                continue;
            $code = $basketItem->getBasketCode();
            if (!empty($discountResult[$code]))
                $result += $discountResult[$code]['PRICE'] * $basketItem->getQuantity();
            unset($code);
        }
        unset($basketItem);
        unset($discountResult);

        return $result;
    }

}
