<?php

namespace dky\gift;

/**
 * Module tools
 *
 * @author dimabresky
 */
class Tools {

    /**
     * @global \CMain $APPLICATION
     * @param mixed $data
     */
    static function sendJsonResponse($data) {
        global $APPLICATION;

        header('Content-Type: application/json');
        $APPLICATION->RestartBuffer();
        echo \Bitrix\Main\Web\Json::encode($data);
        die;
    }

    /**
     * @param float $price
     * @return array
     */
    static function getConditionsByPrice($price) {

        $conditions = new Conditions;

        $result = ['current' => [], 'next' => null];

        foreach ($conditions as $c) {

            if (($c['priceFrom'] <= $price && $c['priceTo'] > $price) || $price > $c['priceTo']) {

                $result['current'][] = $c;
            } else {

                $result['next'] = $c;
                break;
            }
        }

        return $result;
    }

    /**
     * @return boolean
     */
    function canUse() {
        return Options::get('USE') === 'Y';
    }

}
