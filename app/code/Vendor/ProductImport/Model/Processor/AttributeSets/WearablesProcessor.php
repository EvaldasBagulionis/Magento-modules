<?php

declare(strict_types=1);

namespace Vendor\ProductImport\Model\Processor\AttributeSets;

use SimpleXMLElement;

class WearablesProcessor extends BaseProcessor implements ProcessorInterface
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
            'aaa_battery' => $productData->{self::ATTR_BATTERY}->__toString() ?? '',
            'aaa_compatibility' => $productData->{self::ATTR_SYNC}->__toString() ?? '',
            'aaa_screen' => $productData->{self::ATTR_SCREEN}->__toString() ?? '',
        ];

        // Set attributes with options value
        $attributesCheck += [
            'aaa_manufacturer_filter' => $this->getFilterOptionValue(
                'aaa_manufacturer_filter',
                $productData->{self::ATTR_PRODUCER}->__toString()
            ),
            'aaa_type_filter' => $this->getFilterOptionValue(
                'aaa_type_filter',
                $productData->{self::ATTR_TYPE}->__toString()
            ),
            'aaa_resistance_filter' => $this->getFilterOptionValue(
                'aaa_resistance_filter',
                $productData->{self::ATTR_RESISTANCE}->__toString()
            ),
            'color' => $this->getFilterOptionValue(
                'color',
                $productData->{self::ATTR_COLOR}->__toString()
            ),
        ];

        return $this->validateAttributes($attributesCheck);
    }
}
