<?php

namespace Vendor\ProductImport\Model;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\StateException;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import as ProductImport;
use SimpleXMLElement;
use Generator;
use Vendor\ProductImport\Model\Mapper\ProductMapper;
use Magento\ImportExport\Model\ResourceModel\Import\Data;
use Magento\CatalogImportExport\Model\Import\Product\Proxy as ProductImportProxy;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Vendor\BbbImport\Model\BbbApi;
use Psr\Log\LoggerInterface;
use Vendor\ProductImport\Model\Config;
use Magento\Framework\Exception\ValidatorException;
use Vendor\ProductImport\Import\ModdedProduct;

class ProductImporter
{
    public const BATCH_SIZE = 1;

    private BbbApi $bbbApi;

    private ProductMapper $productMapper;

    private Data $importData;

    private ProductImportProxy $productImportProxy;

    private SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory;

    private ProductRepositoryInterface $productRepository;

    private LoggerInterface $logger;

    private Config $config;

    private int $processedItemCount = 0;

    protected string $productXmlUrl = '';

    protected string $productGroupXmlUrl = '';

    private ?SimpleXMLElement $productXml = null;

    private array $importedSkus = [];

    public function __construct(
        BbbApi $bbbApi,
        ProductMapper $productMapper,
        Data $importData,
        ProductImportProxy $productImportProxy,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger,
        Config $config
    ) {
        $this->bbbApi = $bbbApi;
        $this->productMapper = $productMapper;
        $this->importData = $importData;
        $this->productImportProxy = $productImportProxy;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->config = $config;

        $this->productXmlUrl = $this->config->getChildProductUrl();
        $this->productGroupXmlUrl = $this->config->getParentProductUrl();
    }

    /**
     * @param string $type
     *
     * @return Generator
     */
    public function createBunch(string $type): Generator
    {
        $bunch = [];
        $iteration = 0;

        foreach ($this->productXml as $productData) {

            $iteration++;
            $this->processedItemCount++;

            $bunch[$iteration] = $this->prepareProductData($productData, $type);

            if (empty($bunch[$iteration])) {
                unset($bunch[$iteration]);
                continue;
            }

            $this->importedSkus[] = $bunch[$iteration][ImportProduct::COL_SKU];

            $this->processedItemCount++;

            if ($iteration % self::BATCH_SIZE === 0) {
                yield $bunch;
                $bunch = [];
            }
        }

        if (count($bunch) > 0) {
            yield $bunch;
        }
    }

    /**
     * @param $productDataIn
     * @param string $type
     *
     * @return array
     */
    private function prepareProductData($productDataIn, string $type): array
    {
        switch ($type) {
            case 'simple':
                $productDataOut = $this->productMapper->generateSimple($productDataIn);
                break;
            case 'configurable':
                $productDataOut = $this->productMapper->generateConfigurable($productDataIn);
                break;
            default :
                $this->debug('Product type not specified.');
                die();
        }

        $this->debug('Product data IN:');
        $this->debug($productDataIn);
        $this->debug('Product data OUT:');
        $this->debug($productDataOut);

        return $productDataOut;
    }

    /**
     * @throws ValidatorException
     */
    public function execute()
    {
        $this->debug('Starting product import...');

        if (!$this->productXml = $this->bbbApi->bbbApiRequest($this->productXmlUrl)) {
            return;
        }

        $this->importData->cleanBunches();

        $this->productImportProxy->setParameters(
            [
                'behavior' => Import::BEHAVIOR_APPEND,
                ProductImport::FIELDS_ENCLOSURE => false,
                ProductImport::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR => '&&',
                ProductImport::FIELD_EMPTY_ATTRIBUTE_VALUE_CONSTANT => '__EMPTY__VALUE__',
            ]
        );

        $this->processedItemCount = 0;

        $this->debug('Importing simple products...');

        foreach ($this->createBunch('simple') as $bunch) {
            if (!empty($bunch)) {
                $this->importData->saveBunch(
                    $this->productImportProxy->getEntityTypeCode(),
                    '',
                    $bunch
                );
            }
        }

        $this->debug('Processed simple products: ' . $this->processedItemCount);

        $this->productImportProxy->importData();

        $this->importData->cleanBunches();

        if (!$this->productXml = $this->bbbApi->bbbApiRequest($this->productGroupXmlUrl)) {
            return;
        }

        $this->processedItemCount = 0;

        $this->debug('Importing configurable products...');

        foreach ($this->createBunch('configurable') as $bunch) {
            if (!empty($bunch)) {
                $this->importData->saveBunch(
                    $this->productImportProxy->getEntityTypeCode(),
                    '',
                    $bunch
                );
            }
        }

        $this->debug('Processed configurable products: ' . $this->processedItemCount);

        $this->productImportProxy->importData();

        try {
            $this->disableInactiveProducts();
        } catch (\Exception $e) {
            $this->debug('Failed in disabling products: ' . $e->getMessage());
        }

        $this->debug('Finished product import');
    }

    /**
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws StateException
     */
    private function disableInactiveProducts()
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();

        $searchCriteria = $searchCriteriaBuilder->addFilter('attribute_set_id', 21, 'NEQ')
            ->addFilter('sku', $this->importedSkus, 'NIN')
            ->addFilter('sku', 'TEMPX%', 'NLIKE')
            ->addFilter('sku', 'PREORDER%', 'NLIKE')
            ->addFilter('sku', 'mok-asm%', 'NLIKE')
            ->addFilter('sku', 'mok-grup%', 'NLIKE')
            ->addFilter('sku', 'dovanu-kuponas', 'NLIKE')
            ->create();

        $products = $this->productRepository->getList($searchCriteria)->getItems();

        foreach ($products as $product) {
            $this->debug('Disabling productId: ' . $product->getId());
            $product->setStatus(Status::STATUS_DISABLED);
            $this->productRepository->save($product);
        }
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
