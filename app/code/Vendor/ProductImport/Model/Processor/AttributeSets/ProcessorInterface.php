<?php

declare(strict_types=1);

namespace CreationLabs\ProductImport\Model\Processor\AttributeSets;

use SimpleXMLElement;

interface ProcessorInterface
{
    // Attributes
    public const ATTR_PRODUCER = 'WebProducer';
    public const ATTR_COLOR = 'WebColor';
    public const ATTR_SYNC = 'WebSync';
    public const ATTR_TYPE = 'WebType';
    public const ATTR_SCREEN = 'WebScreen';
    public const ATTR_PROCESSOR = 'WebProcesor';
    public const ATTR_MEMORY = 'WebMemory';
    public const ATTR_CAPACITY = 'WebCapacity';
    public const ATTR_GRAPHIC = 'WebGraphic';
    public const ATTR_CAMERA = 'WebCamera';
    public const ATTR_CONNECTION = 'WebConnection';
    public const ATTR_CELLULAR = 'WebCellular';
    public const ATTR_WIRELESS = 'WebWireles';
    public const ATTR_BATTERY = 'WebBatery';
    public const ATTR_WEIGHT = 'WebWeigth';
    public const ATTR_MATERIAL = 'WebMaterial';
    public const ATTR_RESISTANCE = 'WebResistance';
    public const ATTR_PACKED = 'WebPacked';
    public const ATTR_INFO = 'WebOtherInf';
    public const ATTR_REDUCED_PRICE = 'ReducedPrice';
    public const ATTR_KEY_LANG = 'WebKeyLang';

    /**
     * @param SimpleXMLElement $productData
     *
     * @return array
     */
    public function mapAttributes(SimpleXMLElement $productData): array;

}
