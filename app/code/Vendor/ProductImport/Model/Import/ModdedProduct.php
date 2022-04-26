<?php

declare(strict_types=1);

namespace Vendor\ProductImport\Model\Import;

use Magento\CatalogImportExport\Model\Import\Product;

class ModdedProduct extends Product
{

    /**
     * @param array $categoriesData
     *
     * @return $this|ModdedProduct
     */
    protected function _saveProductCategories(array $categoriesData)
    {
        static $tableName = null;

        if (!$tableName) {
            $tableName = $this->_resourceFactory->create()->getProductCategoryTable();
        }
        if ($categoriesData) {
            $categoriesIn = [];
            $delProductId = [];

            foreach ($categoriesData as $delSku => $categories) {
                $productId = $this->skuProcessor->getNewSku($delSku)['entity_id'];
                $delProductId[] = $productId;

                foreach (array_keys($categories) as $categoryId) {
                    $categoriesIn[] = ['product_id' => $productId, 'category_id' => $categoryId, 'position' => 0];
                }
            }

            // OG import only deletes old categories if import behavior is not "append", but we need to override old categories with new ones from import
            $this->_connection->delete(
                $tableName,
                $this->_connection->quoteInto('product_id IN (?)', $delProductId)
            );

            if ($categoriesIn) {
                $this->_connection->insertOnDuplicate($tableName, $categoriesIn, ['product_id', 'category_id']);
            }
        }
        return $this;
    }
}
