<?php

declare(strict_types=1);

namespace Vendor\ProductImport\Cron;

use Vendor\ProductImport\Model\ProductImporter;
use Magento\Framework\Exception\ValidatorException;

class ImportProducts
{
    private ProductImporter $productImport;

    public function __construct(
        ProductImporter $productImport
    ) {
        $this->productImport = $productImport;
    }

    /**
     * @throws ValidatorException
     */
    public function execute(): void
    {
        $this->productImport->execute();
    }
}
