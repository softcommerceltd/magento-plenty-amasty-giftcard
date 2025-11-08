<?php
/**
 * Copyright © Soft Commerce Ltd. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace SoftCommerce\PlentyAmastyGiftCard\Model\OrderExportService\ItemGenerator;

use Amasty\GiftCardAccount\Model\GiftCardExtension\Order\Order as GiftCardOrder;
use Magento\Framework\Exception\LocalizedException;
use SoftCommerce\PlentyOrderProfile\Model\OrderExportService\Generator\Order\Items\ItemAbstract;
use SoftCommerce\PlentyOrderProfile\Model\OrderExportService\Processor\Order as OrderProcessor;
use SoftCommerce\PlentyOrder\RestApi\OrderInterface as HttpClient;
use SoftCommerce\PlentyOrder\RestApi\OrderInterface as HttpOrderClient;
use SoftCommerce\Profile\Model\ServiceAbstract\ProcessorInterface;

/**
 * @inheritdoc
 * Class GiftCard used to export
 * Amasty Gift Cards
 */
class GiftCard extends ItemAbstract implements ProcessorInterface
{
    private const string XML_PATH_DISCOUNT_INCLUDES_TAX = 'tax/calculation/discount_tax';

    /**
     * @inheritDoc
     */
    public function execute(): void
    {
        $this->initialize();
        $this->generate();
        $this->finalize();
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    private function generate(): void
    {
        $context = $this->getContext();

        if ($context->getClientOrder()->getItemByTypeId(HttpClient::ITEM_TYPE_GIFT_CARD)
            || !$giftCardOrder = $this->getGiftCardOrder()
        ) {
            return;
        }

        $vatRate = 0;
        if ($this->scopeConfig->getValue(self::XML_PATH_DISCOUNT_INCLUDES_TAX)) {
            $vatRate = $this->getSalesOrderTaxRate->getTaxRate(
                (int) $context->getSalesOrder()->getEntityId()
            );
        }

        $referrerId = (float) $context->storeConfig()->getReferrerIdByStoreId(
            (int) $context->getSalesOrder()->getStoreId()
        );

        /** @var array $giftCard */
        foreach ($giftCardOrder->getGiftCards() as $giftCard) {
            if (!isset($giftCard['amount'])) {
                continue;
            }

            $request = [
                HttpClient::TYPE_ID => HttpClient::ITEM_TYPE_GIFT_CARD,
                HttpClient::REFERRER_ID => $referrerId,
                HttpClient::QUANTITY => 1,
                HttpClient::COUNTRY_VAT_ID => $this->getCountryId(
                    $context->getSalesOrder()->getBillingAddress()->getCountryId()
                ),
                HttpClient::VAT_FIELD => 0,
                HttpClient::VAT_RATE => $vatRate,
                HttpClient::ORDER_ITEM_NAME => __('Gift Card: (%1)', $giftCard['code'] ?? 'N/A'),
                HttpClient::AMOUNTS => [
                    [
                        HttpClient::IS_SYSTEM_CURRENCY => true,
                        HttpClient::CURRENCY => $this->getContext()->getSalesOrder()->getBaseCurrencyCode(),
                        HttpClient::EXCHANGE_RATE => 1,
                        HttpClient::PRICE_ORIGINAL_GROSS => -$giftCard['amount'],
                        HttpClient::SURCHARGE => 0,
                        HttpClient::DISCOUNT => 0,
                        HttpClient::IS_PERCENTAGE => false
                    ]
                ],
            ];

            $context->getRequestStorage()->addData(
                $request,
                [OrderProcessor::TYPE_ID, HttpOrderClient::ORDER_ITEMS]
            );

            $context->getClientOrder()->setIsDiscountApplied(true);
        }
    }

    /**
     * @return GiftCardOrder|null
     * @throws LocalizedException
     */
    private function getGiftCardOrder(): ?GiftCardOrder
    {
        $extensionAttributes = $this->getContext()->getSalesOrder()->getExtensionAttributes();
        return $extensionAttributes?->getAmGiftcardOrder();
    }
}
