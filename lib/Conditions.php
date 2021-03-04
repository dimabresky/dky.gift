<?php

namespace dky\gift;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Class of gifts conditions
 *
 * @author dimabresky
 */
class Conditions implements \ArrayAccess, \Iterator, \Countable {

    protected $position = 0;
    protected $withIblockLink = false;
    protected $conditions = [];

    function __construct(bool $withIblockLink = false) {
        $this->withIblockLink = $withIblockLink;
        $this->rewind();
        Loader::includeModule('iblock');
        $this->conditions = Options::getConditions();

        if (!empty($this->conditions)) {

            $arProductsListid = $arProducts = $arConditionsKeys = [];
            foreach ($this->conditions as $k => $arCondition) {

                if (!empty($arCondition['productsList'])) {
                    $arConditionsKeys[] = $k;
                    foreach ($arCondition['productsList'] as $product) {
                        if (isset($product['ID']) && !\in_array($product['ID'], $arProductsListid)) {
                            $arProductsListid[] = $product['ID'];
                        }
                    }
                }
            }

            if (!empty($arProductsListid)) {
                $dbRows = \CIBlockElement::GetList(false, ['IBLOCK_ID' => Options::CATALOG_IBLOCK_ID, 'ACTIVE' => 'Y'], false, false, ['ID', 'NAME']);
                while ($arRow = $dbRows->Fetch()) {

                    $arProducts[$arRow['ID']] = $arRow;
                }

                foreach ($arConditionsKeys as $k) {
                    $arCondition = &$this->conditions[$k];
                    if ($arCondition['giftProducts']) {
                        foreach ($arCondition['productsList'] as &$arProduct) {
                            if (isset($arProducts[$arProduct['ID']])) {

                                $arProduct['PAGE_LINK'] = '#';
                                if ($this->withIblockLink) {
                                    $arProduct['PAGE_LINK'] = '/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . Options::CATALOG_IBLOCK_ID . '&type=catalog&ID=' . $arProduct['ID'] . '&lang=ru&find_section_section=0&WF=Y';
                                }

                                $arProduct['NAME'] = $arProducts[$arProduct['ID']]['NAME'];
                            } else {
                                $arProduct = null;
                            }
                        }
                        $arCondition['productsList'] = array_values(array_filter($arCondition['productsList'], function ($arr) {
                                    return $arr !== null;
                                }));
                    } else {
                        $arCondition['productsList'] = [];
                    }
                }
            }
        }
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->condtions[] = $value;
        } else {
            $this->condtions[$offset] = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->condtions[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->condtions[$offset]);
    }

    public function offsetGet($offset) {
        return isset($this->condtions[$offset]) ? $this->condtions[$offset] : null;
    }

    public function rewind() {
        $this->position = 0;
    }

    public function current() {
        return $this->conditions[$this->position];
    }

    public function key() {
        return $this->position;
    }

    public function next() {
        ++$this->position;
        return $this->current();
    }

    public function valid() {
        return isset($this->conditions[$this->position]);
    }

    public function count() {
        return count($this->conditions);
    }

    /**
     * @param array $conditions
     */
    function save(array $conditions) {

        $result = [];

        if (!empty($conditions)) {
            foreach ($conditions as $condition) {

                // prepare values
                $condition['giftDelivery'] = @$condition['giftDelivery'] === true || @$condition['giftDelivery'] === 'true' || @$condition['giftDelivery'] > 0 ? 1 : 0;
                $condition['giftProducts'] = @$condition['giftProducts'] === true || @$condition['giftProducts'] === 'true' || @$condition['giftProducts'] > 0 ? 1 : 0;
                $condition['priceFrom'] = @$condition['priceFrom'] ?: 0;
                $condition['priceTo'] = @$condition['priceTo'] ?: 0;
                $condition['currency'] = @$condition['currency'] ?: Options::CURRENCY;
                $condition['productsList'] = !empty(@$condition['productsList']) || is_array($condition['productsList']) ? $condition['productsList'] : [];

                // check
                if (
                        $condition['priceFrom'] < $condition['priceTo'] &&
                        ($condition['giftDelivery'] || $condition['giftProducts'])
                ) {

                    if ($condition['giftProducts'] && empty($condition['productsList'])) {
                        throw new \Exception(Loc::getMessage('DKY_GIGT_PRODUCTS_CONDITIONS_ERROR'));
                    }
                    $result[] = $condition;
                } else {
                    throw new \Exception(Loc::getMessage('DKY_GIGT_TOTAL_CONDITIONS_ERROR'));
                }
            }
        }


        if (!empty($result) && count($result) > 1) {

            usort($result, function ($c1, $c2) {

                if ($c1['priceFrom'] == $c2['priceFrom']) {
                    return 0;
                }

                return $c1['priceFrom'] > $c2['priceFrom'] ? 1 : -1;
            });

            usort($result, function ($c1, $c2) {
                if ($c2['priceFrom'] < $c1['priceTo']) {
                    throw new \Exception(Loc::getMessage('DKY_GIGT_HAVE_CROSS_CONDITIONS'));
                }

                return 0;
            });
        }

        Options::setConditions($result);
    }

    function defaultStructure() {
        return [
            'priceFrom' => null,
            'priceTo' => null,
            'currency' => Options::CURRENCY,
            'giftDelivery' => false,
            'giftProducts' => false,
            'productsList' => []
        ];
    }

    public function toArray() {
        return $this->conditions;
    }

}
