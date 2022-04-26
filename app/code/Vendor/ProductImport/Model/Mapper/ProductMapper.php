<?php

declare(strict_types=1);

namespace Vendor\ProductImport\Model\Mapper;

use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\ConfigurableImportExport\Model\Export\RowCustomizer;
use Magento\Framework\Exception\NoSuchEntityException;
use SimpleXMLElement;
use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\CategoryListInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Vendor\ProductImport\Model\Config;
use Vendor\ProductImport\Model\Provider\CategoryProvider;
use Vendor\ProductImport\Model\Provider\AttributeSetProvider;
use Psr\Log\LoggerInterface;

class ProductMapper
{

    // Core info
    public const SKU = 'Code';
    public const NAME = 'Descr';
    public const CLASSS = 'Class';
    public const GROUP = 'Group';
    public const PARENT_SKU = 'SameItem';
    public const PRICE = 'Price';
    public const OLD_PRICE = 'OldPrice';
    public const DESCRIPTION = 'WebDesc';

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

    private SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory;

    private CategoryRepositoryInterface $categoryRepository;

    private CollectionFactory $categoryCollectionFactory;

    private CategoryListInterface $categoryList;

    private Config $config;

    private AttributeSetRepositoryInterface $attributeSetRepository;

    private CategoryProvider $categoryProvider;

    private AttributeSetProvider $attributeSetProvider;

    private LoggerInterface $logger;

    private string $defaultWebsiteCode;

    private array  $attributeSetProcessorCollection;

    private array $usedUrlKeys = [];

    private array $preGeneratedConfigurableDataCollection = [];

    private string $visible;

    private string $notVisible;

    public function __construct(
        WebsiteRepositoryInterface $websiteRepository,
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        CategoryRepositoryInterface $categoryRepository,
        CollectionFactory $categoryCollectionFactory,
        CategoryListInterface $categoryList,
        Config $config,
        AttributeSetRepositoryInterface $attributeSetRepository,
        CategoryProvider $categoryProvider,
        AttributeSetProvider $attributeSetProvider,
        LoggerInterface $logger,
        array $attributeSetProcessorCollection
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->categoryRepository = $categoryRepository;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryList = $categoryList;
        $this->config = $config;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->categoryProvider = $categoryProvider;
        $this->attributeSetProvider = $attributeSetProvider;
        $this->logger = $logger;
        $this->attributeSetProcessorCollection = $attributeSetProcessorCollection;
        $this->defaultWebsiteCode = $websiteRepository->getDefault()->getCode();

        $this->visible = Visibility::getOptionText(Visibility::VISIBILITY_BOTH)->__toString();
        $this->notVisible = Visibility::getOptionText(Visibility::VISIBILITY_NOT_VISIBLE)->__toString();
    }

    /**
     * @param SimpleXMLElement $productData
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function generateSimple(SimpleXMLElement $productData): array
    {
        $classArray = explode(',', (string) $productData->{self::CLASSS} ?? '');

        if (empty($productData->{$this::OLD_PRICE})) {
            $price = (float) str_replace(' ', '', (string) $productData->{$this::PRICE});
            $specialPrice = 0;
        } else {
            $price = ((float) str_replace(' ', '', (string) $productData->{$this::OLD_PRICE}));
            $specialPrice = (float) str_replace(' ', '', (string) $productData->{$this::PRICE});
        }

        $generated = [
            ImportProduct::COL_SKU => (string) $productData->{self::SKU} ?? '',
            ImportProduct::COL_NAME => (string) $productData->{self::NAME} ?? '',
            ImportProduct::COL_TYPE => 'simple',
            ImportProduct::COL_VISIBILITY => (string) $productData->{self::PARENT_SKU} ? $this->notVisible : $this->visible,
            ImportProduct::COL_CATEGORY => (string) $productData->{self::PARENT_SKU} ?
                $this->categoryProvider->getCategoryTitleByClasses($classArray) ??
                'Default' : 'Default',
            PageInterface::META_KEYWORDS => '',
            PageInterface::META_DESCRIPTION => ((string) $productData->{self::NAME} ?? '')
                                               . ' '
                                               . $this->config->getMetaDescriptionSuffix(),
            ImportProduct::URL_KEY => $this->getUrlKey(
                (string) $productData->{self::NAME} ?? '',
                (string) $productData->{self::SKU} ?? ''
            ),
            '_attribute_set' => $this->attributeSetProvider->getAttributeSetName($classArray),
            'status' => Status::STATUS_ENABLED,
            'description' => (string) $productData->{self::DESCRIPTION} ?? '',
            '_product_websites' => $this->defaultWebsiteCode,
            'product_websites' => $this->defaultWebsiteCode,
            'store' => 'admin',
            'last_updated_date' => date('Y-m-d H:i:s'),
            'website_id' => 0,
            'tax_class_id' => 2,
            'price' => $price,
            'special_price' => $specialPrice,

            // TODO temporary stock placeholder, remove later
            'qty' => 10,
        ];

        $attributeSetId = $this->attributeSetProvider->getAttributeSetId($classArray);

        if (isset($this->attributeSetProcessorCollection[$attributeSetId])) {
            $generated += $this->attributeSetProcessorCollection[$attributeSetId]->mapAttributes($productData);
        } else {
            $this->debug('Attribute set processor not set, more info V');
            $this->debug(
                [
                    'Attribute set id' => $attributeSetId,
                    'sku' => $generated[ImportProduct::COL_SKU],
                    'name' => $generated[ImportProduct::COL_NAME]
                ]
            );
        }

        if ('' !== $parentSku = $productData->{self::PARENT_SKU}->__toString()) {
            $this->addToPreGeneratedConfigurableCollection($parentSku, $generated);
        }

        return $generated;
    }

    /**
     * @param string $configurableSku
     * @param array $simpleData
     */
    private function addToPreGeneratedConfigurableCollection(string $configurableSku, array $simpleData): void
    {
        $this->preGeneratedConfigurableDataCollection[$configurableSku]['data'] = $this->getPreGeneratedConfigurableData(
            $simpleData
        );
        $this->preGeneratedConfigurableDataCollection[$configurableSku]['children'][] = $simpleData[ImportProduct::COL_SKU];

        $variation = (isset($simpleData['color']) || isset($simpleData['aaa_keyboard_language']) ? 'sku=' . $simpleData[ImportProduct::COL_SKU] : '') .
                     (isset($simpleData['color']) ? '&&color=' . $simpleData['color'] : '') .
                     (isset($simpleData['aaa_keyboard_language']) ? '&&aaa_keyboard_language=' . $simpleData['aaa_keyboard_language'] : '');

        if ($variation === '') {
            return;
        }

        $this->preGeneratedConfigurableDataCollection[$configurableSku]['variations'][] = $variation;
    }

    /**
     * @param array $generatedSimple
     *
     * @return array
     */
    private function getPreGeneratedConfigurableData(array $generatedSimple): array
    {
        $preGeneratedConfigurable = $generatedSimple;

        unset($preGeneratedConfigurable[ImportProduct::COL_SKU]);
        unset($preGeneratedConfigurable[ImportProduct::COL_NAME]);
        unset($preGeneratedConfigurable[ImportProduct::URL_KEY]);
        unset($preGeneratedConfigurable[ImportProduct::COL_TYPE]);
        unset($preGeneratedConfigurable[ImportProduct::COL_VISIBILITY]);

        unset($preGeneratedConfigurable['qty']);
        unset($preGeneratedConfigurable['color']);
        unset($preGeneratedConfigurable['aaa_color']);
        unset($preGeneratedConfigurable['aaa_color_filter']);

        return $preGeneratedConfigurable;
    }

    /**
     * @param string $name
     * @param string $sku
     *
     * @return string
     */
    private function getUrlKey(string $name, string $sku): string
    {
        $replace = [
            'ą' => 'a',
            'Ą' => 'A',
            'č' => 'c',
            'Č' => 'C',
            'ę' => 'e',
            'Ę' => 'E',
            'ė' => 'e',
            'Ė' => 'E',
            'į' => 'i',
            'Į' => 'I',
            'š' => 's',
            'Š' => 'S',
            'ū' => 'u',
            'Ū' => 'U',
            'ų' => 'u',
            'Ų' => 'U',
            'ž' => 'z',
            'Ž' => 'z',
            '+ ' => '-plus-',
            ' +' => '-plus-',
            '+' => '-plus-'
        ];

        $nameSlug = $this->slugify($name, $replace);

        if (in_array($nameSlug, $this->usedUrlKeys, true)) {
            $nameSlug .= '-' . $this->slugify($sku, $replace);
        }

        $this->usedUrlKeys[] = $nameSlug;

        return $nameSlug;
    }

    /**
     * @param string $string
     * @param array $replace
     * @param string $delimiter
     *
     * @return string
     */
    public function slugify(string $string, array $replace = [], string $delimiter = '-'): string
    {
        if (!extension_loaded('iconv')) {
            throw new RuntimeException('iconv module not loaded');
        }

        /**
         * Save the old locale and set the new locale to UTF-8
         */
        $oldLocale = setlocale(LC_ALL, '0');
        setlocale(LC_ALL, 'en_US.UTF-8');

        if (!empty($replace)) {
            $string = strtr($string, $replace);
        }

        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
        $clean = strtolower($clean);
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);
        $clean = trim($clean, $delimiter);
        $clean = str_replace(['/', ' '], '', $clean);

        /**
         * Revert to the old locale
         */
        setlocale(LC_ALL, $oldLocale);

        return $clean;
    }

    /**
     * @param SimpleXMLElement $productData
     *
     * @return array
     */
    public function generateConfigurable(SimpleXMLElement $productData): array
    {
        $sku = (string) $productData->{self::SKU};
        $name = (string) $productData->{self::NAME};

        if ($sku === ''
            || !isset($this->preGeneratedConfigurableDataCollection[$sku])
            || in_array($sku, $this->config->getSkipConfigurableSkus())
        ) {
            $this->debug('Skipped configurable product: ' . $sku);
            return [];
        }

        if (!isset($this->preGeneratedConfigurableDataCollection[$sku]['variations'])) {
            $this->debug('Variation not set for configurable product: ' . $sku);
            return [];
        }

        $generated = [
            ImportProduct::COL_SKU => $sku,
            ImportProduct::COL_NAME => $name,
            ImportProduct::COL_TYPE => 'configurable',
            ImportProduct::URL_KEY => $this->getUrlKey($name, $sku),
            RowCustomizer::CONFIGURABLE_VARIATIONS_COLUMN => implode(
                '|',
                $this->preGeneratedConfigurableDataCollection[$sku]['variations']
            ),
            ImportProduct::COL_VISIBILITY => $this->visible,
        ];

        $generated += $this->preGeneratedConfigurableDataCollection[$sku]['data'];

        return $generated;
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

