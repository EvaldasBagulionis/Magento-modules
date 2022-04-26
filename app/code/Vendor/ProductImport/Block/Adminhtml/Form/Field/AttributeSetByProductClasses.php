<?php

declare(strict_types=1);

namespace Vendor\ProductImport\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Vendor\Admin\Block\Adminhtml\Form\Field\Renderer\AttributeSet;
use Vendor\Admin\Block\Adminhtml\Form\Field\FieldArray;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\BlockInterface;

class AttributeSetByProductClasses extends FieldArray
{
    private $attributeSetRenderer = null;

    /**
     * @return BlockInterface|null
     * @throws LocalizedException
     */
    private function getAttributeSetRenderer() {
        if ($this->attributeSetRenderer) {
            return $this->attributeSetRenderer;
        }

        $this->attributeSetRenderer = $this->createRenderer(AttributeSet::class);

        return $this->attributeSetRenderer;
    }

    /**
     * @throws LocalizedException
     */
    protected function _prepareToRender(): void
    {
        $this->addColumn(
            'attribute_set',
            [
                'label' => __('Attribute set'),
                'renderer' => $this->getAttributeSetRenderer(),
            ]
        );
        $this->addColumn('classes', ['label' => __('Classes'), 'size' => 220]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add New Combo');
    }
}
