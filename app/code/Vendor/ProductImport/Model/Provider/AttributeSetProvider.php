<?php

declare(strict_types=1);

namespace Vendor\ProductImport\Model\Provider;

use Vendor\ProductImport\Model\Config;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class AttributeSetProvider
{
    private AttributeSetRepositoryInterface $attributeSetRepository;

    private Config $config;

    private array $attributeSetsNameClasses = [];

    private array $attributeSetsIdClasses = [];

    public function __construct(
        AttributeSetRepositoryInterface $attributeSetRepository,
        Config $config
    ) {
        $this->attributeSetRepository = $attributeSetRepository;
        $this->config = $config;
    }

    /**
     * @param array $classArray
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getAttributeSetName(array $classArray): string
    {
        $attributeSetsClasses = $this->getAttributeSetsNameClasses();

        foreach ($attributeSetsClasses as $attributeSetName => $attributeSetClasses) {
            if (empty(array_intersect($attributeSetClasses, $classArray))) {
                continue;
            }
            return $attributeSetName;
        }

        // Default attribute set name
        return 'Default';
    }

    /**
     * @param array $classArray
     *
     * @return int
     */
    public function getAttributeSetId(array $classArray): int
    {
        $attributeSetsClasses = $this->getAttributeSetsIdClasses();

        foreach ($attributeSetsClasses as $attributeSetId => $attributeSetClasses) {
            if (empty(array_intersect($attributeSetClasses, $classArray))) {
                continue;
            }
            return $attributeSetId;
        }

        // Default attribute set id
        return 4;
    }

    /**
     * @return array
     * @throws NoSuchEntityException
     */
    private function getAttributeSetsNameClasses(): array
    {
        if ($this->attributeSetsNameClasses && !empty($this->attributeSetsNameClasses)) {
            return $this->attributeSetsNameClasses;
        }

        $attributeSetsClasses = [];

        $attributeSetsClassesTable = $this->config->getAttributeSetClasses();

        foreach ($attributeSetsClassesTable as $attributeSetClasses) {
            if (!isset($attributeSetClasses['attribute_set']) || !isset($attributeSetClasses['classes'])) {
                continue;
            }

            $attributeSetCode = $this->attributeSetRepository->get($attributeSetClasses['attribute_set']);

            $attributeSetsClasses[$attributeSetCode->getAttributeSetName()] = explode(
                ',',
                $attributeSetClasses['classes']
            );
        }

        return $this->attributeSetsNameClasses = $attributeSetsClasses;
    }

    /**
     * @return array
     */
    private function getAttributeSetsIdClasses(): array
    {
        if ($this->attributeSetsIdClasses && !empty($this->attributeSetsIdClasses)) {
            return $this->attributeSetsIdClasses;
        }

        $attributeSetsClasses = [];

        $attributeSetsClassesTable = $this->config->getAttributeSetClasses();

        foreach ($attributeSetsClassesTable as $attributeSetClasses) {
            if (!isset($attributeSetClasses['attribute_set']) || !isset($attributeSetClasses['classes'])) {
                continue;
            }

            $attributeSetsClasses[$attributeSetClasses['attribute_set']] = explode(
                ',',
                $attributeSetClasses['classes']
            );
        }

        return $this->attributeSetsIdClasses = $attributeSetsClasses;
    }

}
