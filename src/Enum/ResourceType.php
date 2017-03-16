<?php

namespace BestIt\CTCustomTypesBundle\Enum;

use ReflectionClass;

/**
 * Enum for available resource types
 * @author chowanski <chowanski@bestit-online.de>
 * @package BestIt\CTCustomTypesBundle
 * @subpackage Enum
 * @version $id$
 */
class ResourceType
{
    /**
     * Asset
     * @var string
     */
    const ASSET = 'asset';

    /**
     * Category
     * @var string
     */
    const CATEGORY = 'category';

    /**
     * Channel
     * @var string
     */
    const CHANNEL = 'channel';

    /**
     * Customer
     * @var string
     */
    const CUSTOMER = 'customer';

    /**
     * Cart and Order
     * @var string
     */
    const ORDER = 'order';

    /**
     * Line item
     * @var string
     */
    const LINE_ITEM = 'line-item';

    /**
     * Custom line item
     * @var string
     */
    const CUSTOM_LINE_ITEM = 'custom-line-item';

    /**
     * Product price
     * @var string
     */
    const PRODUCT_PRICE = 'product-price';

    /**
     * Payment
     * @var string
     */
    const PAYMENT = 'payment';

    /**
     * Payment interface interaction
     * @var string
     */
    const PAYMENT_INTERFACE_INTERACTION = 'payment-interface-interaction';

    /**
     * Shopping list
     * @var string
     */
    const SHOPPING_LIST = 'shopping-list';

    /**
     * Shopping list text line item
     * @var string
     */
    const SHOPPING_LIST_TEXT_LINE_ITEM = 'shopping-list-text-line-item';

    /**
     * Review
     * @var string
     */
    const REVIEW = 'review';

    /**
     * Check if given value is a valid enum type
     * @param string $key
     * @return bool
     */
    public static function isValid(string $key): bool
    {
        $types = (new ReflectionClass(__CLASS__))->getConstants();

        return in_array($key, $types, true);
    }
}
