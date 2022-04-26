<?php

declare(strict_types=1);

namespace Vendor\ProductImport\Model\Processor\AttributeSets;

use SimpleXMLElement;

class OtherProcessor extends BaseProcessor implements ProcessorInterface
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
            'aaa_pack' => $productData->{self::ATTR_PACKED}->__toString() ?? '',
            'aaa_delivery_time' => $productData->DeliveryTerm->__toString(),
            'aaa_information' => $productData->{self::ATTR_INFO}->__toString() ?? '',
        ];

        // Set attributes with options value
        $attributesCheck += [
            'aaa_manufacturer_filter' => $this->getFilterOptionValue(
                'aaa_manufacturer_filter',
                $productData->{self::ATTR_PRODUCER}->__toString()
            ),
            'color' => $this->getFilterOptionValue(
                'color',
                $productData->{self::ATTR_COLOR}->__toString()
            ),
        ];

        return $this->validateAttributes($attributesCheck);
    }
}
