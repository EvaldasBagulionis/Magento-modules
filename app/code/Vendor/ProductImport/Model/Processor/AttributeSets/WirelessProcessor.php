<?php

declare(strict_types=1);

namespace Vendor\ProductImport\Model\Processor\AttributeSets;

use SimpleXMLElement;

class WirelessProcessor extends BaseProcessor implements ProcessorInterface
{
    /**
     * @param SimpleXMLElement $productData
     *
     * @return array
     */
    public function mapAttributes(SimpleXMLElement $productData): array
    {
        // Set attributes value
        $attributesCheck = [
            'aaa_manufacturer' => $productData->{self::ATTR_PRODUCER}->__toString() ?? '',
            'aaa_capacity' => $productData->{self::ATTR_CAPACITY}->__toString() ?? '',
            'aaa_connector' => $productData->{self::ATTR_CONNECTION}->__toString() ?? '',
            'aaa_pack' => $productData->{self::ATTR_PACKED}->__toString() ?? '',
            'aaa_wireless' => $productData->{self::ATTR_WIRELESS}->__toString() ?? '',
            'aaa_delivery_time' => $productData->DeliveryTerm->__toString(),
            'aaa_information' => $productData->{self::ATTR_INFO}->__toString() ?? '',
        ];

        return $this->validateAttributes($attributesCheck);
    }
}
