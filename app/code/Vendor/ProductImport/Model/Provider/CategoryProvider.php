<?php

declare(strict_types=1);

namespace Vendor\ProductImport\Model\Provider;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\CategoryListInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class CategoryProvider
{
    private SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory;

    private CategoryRepositoryInterface $categoryRepository;

    private CategoryListInterface $categoryList;

    public function __construct(
        SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        CategoryRepositoryInterface $categoryRepository,
        CategoryListInterface $categoryList
    ) {
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->categoryRepository = $categoryRepository;
        $this->categoryList = $categoryList;
    }

    /**
     * @param array $classArray
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCategoryTitleByClasses(array $classArray): string
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();

        $searchCriteria = $searchCriteriaBuilder
            ->addFilter('bbb_id', $classArray, 'IN')
            ->create();

        $categories = $this->categoryList->getList($searchCriteria)->getItems();

        $paths = [];

        foreach ($categories as $category) {
            $ids = explode('/', $category->getPath());
            $path = [];
            foreach ($ids as $key => $id) {
                // Skip first category path id as its 'Root Category" and is not needed in name path.
                if ($key === 0) {
                    continue;
                }

                $path[] = $this->categoryRepository->get($id)->getName();
            }
            $paths[] = implode('/', $path);
        }

        if (empty($paths)) {
            return 'Default';
        }

        return implode('&&', $paths);
    }
}
