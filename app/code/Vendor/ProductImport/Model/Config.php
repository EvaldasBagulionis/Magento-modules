<?php

declare(strict_types=1);

namespace Vendor\ProductImport\Model;

class Config extends \CreationLabs\Admin\Model\Config
{
    public const XML_PATH_HANSA_PRODUCT_IMPORT = 'bbb_import/product/';

    /**
     * @param int $storeId
     *
     * @return bool
     */
    public function getIsEnabled(int $storeId = 0): bool
    {
        $field = self::XML_PATH_HANSA_PRODUCT_IMPORT . 'bbb_product_import_enable';

        return (bool) $this->getConfig($field, $storeId);
    }

    /**
     * @param int $storeId
     *
     * @return bool
     */
    public function getDebugModeEnabled(int $storeId = 0): bool
    {
        $path = self::XML_PATH_HANSA_PRODUCT_IMPORT . 'debug_mode';
        return (bool) $this->getConfig($path, $storeId);
    }

    /**
     * @param int $storeId
     *
     * @return array
     */
    public function getAttributeSetClasses(int $storeId = 0): array
    {
        $field = self::XML_PATH_HANSA_PRODUCT_IMPORT . 'attribute_set_classes';

        if ($attributeSetClasses = $this->getConfig($field, $storeId)) {
            $attributeSetClasses = $this->serializer->unserialize($attributeSetClasses);

            return is_array($attributeSetClasses) ? $attributeSetClasses : [];
        }

        return [];
    }

    /**
     * @param int $storeId
     *
     * @return string
     */
    public function getMetaDescriptionSuffix(int $storeId = 0): string
    {
        $path = self::XML_PATH_HANSA_PRODUCT_IMPORT . 'meta_description_suffix';
        return $this->getConfig($path, $storeId);
    }

    /**
     * @param int $storeId
     *
     * @return array
     */
    public function getSkipConfigurableSkus(int $storeId = 0): array
    {
        $path = self::XML_PATH_HANSA_PRODUCT_IMPORT . 'skip_configurable_skus';
        if ('' !== $skipConfigurableSkus = (string) $this->getConfig($path, $storeId)) {
            return explode(',', $skipConfigurableSkus);
        }
        return [];
    }

    /**
     * @param int $storeId
     *
     * @return string
     */
    public function getChildProductUrl(int $storeId = 0): string
    {
        $path = self::XML_PATH_HANSA_PRODUCT_IMPORT . 'child_products_url';
        return $this->getConfig($path, $storeId);
    }

    /**
     * @param int $storeId
     *
     * @return string
     */
    public function getParentProductUrl(int $storeId = 0): string
    {
        $path = self::XML_PATH_HANSA_PRODUCT_IMPORT . 'parent_products_url';
        return $this->getConfig($path, $storeId);
    }
}
