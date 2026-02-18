<?php
/**
 * Copyright © Byte8 Ltd (formerly Soft Commerce). All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\PlentyAmastyGiftCard\Model\ItemExportService\Collector;

use Amasty\GiftCard\Model\GiftCard\Product\Type\GiftCard;
use Magento\Catalog\Api\Data\ProductInterface;
use SoftCommerce\PlentyItemProfile\Model\ItemExportService\Collector\SimpleProductCommandCollector;

/**
 * Amasty Gift Card Command Collector
 *
 * Handles export of Amasty gift card products.
 * Gift cards are exported identically to simple products.
 */
class AmGiftCardCommandCollector extends SimpleProductCommandCollector
{
    /**
     * @inheritDoc
     */
    public function canCollect(ProductInterface $product): bool
    {
        return $product->getTypeId() === GiftCard::TYPE_AMGIFTCARD;
    }
}
