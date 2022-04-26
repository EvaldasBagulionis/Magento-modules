<?php

declare(strict_types=1);

namespace Vendor\ProductImport\Model\Processor\AttributeSets;

use SimpleXMLElement;

class PhoneProcessor extends BaseProcessor implements ProcessorInterface
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
            'aaa_camera' => $productData->{self::ATTR_CAMERA}->__toString() ?? '',
            'aaa_connector' => $productData->{self::ATTR_CONNECTION}->__toString() ?? '',
            'aaa_weight' => $productData->{self::ATTR_WEIGHT}->__toString() ?? '',
            'aaa_pack' => $productData->{self::ATTR_PACKED}->__toString() ?? '',
            'aaa_wireless' => $productData->{self::ATTR_WIRELESS}->__toString() ?? '',
            'aaa_delivery_time' => $productData->DeliveryTerm->__toString(),
            'aaa_information' => $productData->{self::ATTR_INFO}->__toString() ?? '',
            'aaa_processor' => $productData->{self::ATTR_PROCESSOR}->__toString() ?? '',
            'aaa_battery' => $productData->{self::ATTR_BATTERY}->__toString() ?? '',
        ];

        // Set attributes with options value
        $attributesCheck += [
            'aaa_screen_filter' => $this->getFilterOptionValue(
                'aaa_screen_filter',
                $productData->{self::ATTR_SCREEN}->__toString()
            ),
            'aaa_capacity_filter' => $this->getFilterOptionValue(
                'aaa_capacity_filter',
                $productData->{self::ATTR_CAPACITY}->__toString()
            ),
            'aaa_mobile_internet_filter' => $this->getFilterOptionValue(
                'aaa_mobile_internet_filter',
                $productData->{self::ATTR_CELLULAR}->__toString()
            ),
            'color' => $this->getFilterOptionValue(
                'color',
                $productData->{self::ATTR_COLOR}->__toString()
            ),
        ];

        return $this->validateAttributes($attributesCheck);
    }

}
