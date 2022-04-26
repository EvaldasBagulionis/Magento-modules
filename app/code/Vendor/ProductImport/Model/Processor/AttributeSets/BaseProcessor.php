<?php

declare(strict_types=1);

namespace Vendor\ProductImport\Model\Processor\AttributeSets;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\OptionLabel;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Psr\Log\LoggerInterface;
use Vendor\ProductImport\Model\Config;
use Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Eav\Api\AttributeOptionManagementInterface;

class BaseProcessor
{
    protected EavConfig $eavConfig;

    protected LoggerInterface $logger;

    private Config $config;

    private AttributeOptionLabelInterfaceFactory $optionLabelFactory;

    private AttributeOptionInterfaceFactory $optionFactory;

    private AttributeOptionManagementInterface $attributeOptionManagement;

    protected array $loadedAttributeOptions = [];

    protected array $attributeFrontendTypes = [];

    public function __construct(
        EavConfig $eavConfig,
        LoggerInterface $logger,
        Config $config,
        AttributeOptionLabelInterfaceFactory $optionLabelFactory,
        AttributeOptionInterfaceFactory $optionFactory,
        AttributeOptionManagementInterface $attributeOptionManagement
    ) {
        $this->eavConfig = $eavConfig;
        $this->logger = $logger;
        $this->config = $config;
        $this->optionLabelFactory = $optionLabelFactory;
        $this->optionFactory = $optionFactory;
        $this->attributeOptionManagement = $attributeOptionManagement;
    }

    /**
     * @param string $attributeCode
     * @param string $optionValue
     *
     * @return string
     */
    protected function getFilterOptionValue(string $attributeCode, string $optionValue): string
    {
        if ($optionValue === '') {
            return '';
        }

        if (in_array(($trimmedValue = trim($optionValue)), $options = $this->getAttributeOptions($attributeCode))) {
            return $trimmedValue;
        }

        if ($this->getAttributeFrontendType($attributeCode) === 'multiselect') {
            $optionValues = explode(', ', $optionValue);
            $existingValues = [];
            $notExistingValues = [];

            foreach ($optionValues as $value) {
                $value = trim($value);

                if ($value === '') {
                    continue;
                }
                if (!in_array($value, $options) && !$this->createAttributeOption($attributeCode, $value)) {
                    $notExistingValues[] = $value;
                    continue;
                }

                $existingValues[] = $value;
            }

            $existingValues = array_unique($existingValues);

            if (!empty($notExistingValues)) {
                $this->debug('Not existing vales and failed to create in ' . $attributeCode . 'multi select:');
                $this->debug($notExistingValues);
            }

            return implode('|', $existingValues);
        }

        if ($this->createAttributeOption($attributeCode, ($trimmedValue = trim($optionValue)))) {
            return $trimmedValue;
        }

        $this->debug('Not existing and failed to create values in ' . $attributeCode . ':');
        $this->debug($optionValue);

        return '';
    }

    /**
     * @param string $attributeCode
     *
     * @return array
     * @throws LocalizedException
     */
    private function getAttributeOptions(string $attributeCode): array
    {
        if (
            isset($this->loadedAttributeOptions[$attributeCode])
            && !empty($this->loadedAttributeOptions[$attributeCode])
        ) {
            return $this->loadedAttributeOptions[$attributeCode];
        }
        $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeCode);

        return $this->loadedAttributeOptions[$attributeCode] = $this->getOptionsLabels(
            $attribute->getSource()->getAllOptions()
        );
    }

    /**
     * @param string $attributeCode
     *
     * @return string
     * @throws LocalizedException
     */
    private function getAttributeFrontendType(string $attributeCode): string
    {
        if (isset($this->attributeFrontendTypes[$attributeCode])) {
            return $this->attributeFrontendTypes[$attributeCode];
        }
        $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);

        return $this->attributeFrontendTypes[$attributeCode] = $attribute->getFrontendInput();
    }

    /**
     * @param array $options
     *
     * @return array
     */
    private function getOptionsLabels(array $options): array
    {
        $labels = [];

        foreach ($options as $option) {
            if (!isset($option['label'])) {
                continue;
            }

            $labels[] = $option['label'];
        }

        return $labels;
    }

    /**
     * @param array $attributesCheck
     *
     * @return array
     */
    protected function validateAttributes(array $attributesCheck): array
    {
        $data = [];
        foreach ($attributesCheck as $key => $val) {
            if (!$val || $val === '') {
                continue;
            }
            $data[$key] = trim($val);
        }

        return $data;
    }

    /**
     * @param string $attributeCode
     * @param string $optionValue
     *
     * @return bool
     * @throws InputException
     * @throws LocalizedException
     * @throws StateException
     */
    private function createAttributeOption(string $attributeCode, string $optionValue): bool
    {
        $this->debug('Trying to create value: ' . $optionValue . ' for attribute: ' . $attributeCode);

        /** @var OptionLabel $optionLabel */
        $optionLabelAdmin = $this->optionLabelFactory->create();
        $optionLabelAdmin->setStoreId(0)->setLabel($optionValue);

        $optionLabelDefault = $this->optionLabelFactory->create();
        $optionLabelDefault->setStoreId(1)->setLabel($optionValue);

        $option = $this->optionFactory->create()
            ->setLabel($optionValue)
            ->setStoreLabels([$optionLabelAdmin, $optionLabelDefault])
            ->setSortOrder(0)
            ->setIsDefault(false);

        $this->attributeOptionManagement->add(
            Product::ENTITY,
            $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode)->getAttributeId(),
            $option
        );

        unset($this->loadedAttributeOptions[$attributeCode]);

        if (in_array($optionValue, $this->getAttributeOptions($attributeCode))) {
            $this->debug('Successfully created value: ' . $optionValue . ' for attribute: ' . $attributeCode);
            return true;
        }

        $this->debug('Failed to create value: ' . $optionValue . ' for attribute: ' . $attributeCode);

        return false;
    }

    /**
     * @param $message
     */
    private function debug($message): void
    {
        if (!$this->config->getDebugModeEnabled()) {
            return;
        }

        $this->logger->debug(print_r($message, true));
    }
}
