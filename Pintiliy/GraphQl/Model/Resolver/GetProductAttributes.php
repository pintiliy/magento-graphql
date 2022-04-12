<?php
declare(strict_types=1);

namespace Pintiliy\GraphQl\Model\Resolver;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\Phrase;

class GetProductAttributes implements ResolverInterface
{
    /**
     * @var ValueFactory
     */
    private $valueFactory;

    public function __construct(
        ValueFactory $valueFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository
    ) {
        $this->valueFactory = $valueFactory;
        $this->_productRepository = $productRepository;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)  {

        if (!isset($args['productId'])) {
            throw new GraphQlAuthorizationException(
                __(
                    'productId is required',
                    [\Magento\Customer\Model\Customer::ENTITY]
                )
            );
        }
        try {
            $data = $this->getProductAttributesData($args['productId']);
            $result = function () use ($data) {
                return !empty($data) ? $data : [];
            };
            return $this->valueFactory->create($result);
        } catch (NoSuchEntityException $exception) {
            throw new GraphQlNoSuchEntityException(__($exception->getMessage()));
        } catch (LocalizedException $exception) {
            throw new GraphQlNoSuchEntityException(__($exception->getMessage()));
        }
    }

    private function getProductAttributesData($productId) : array
    {
        try {

            $_product = $this->_productRepository->getById($productId);
            $attributes = $_product->getAttributes();

            $attributes_data = [];
            foreach ($attributes as $attribute) {
                if ($attribute->getIsVisibleOnFront() && $attribute->getIsUserDefined()) {
                    $attributeLabel = $attribute->getFrontend()->getLabel();
                    $attributeValue = $attribute->getFrontend()->getValue($_product);
                    if (is_string($attributeValue) && strlen(trim($attributeValue))) {
                        $attributes_data[] = [
                            "id" => $attribute->getId(),
                            "name" => $attributeLabel,
                            "value" => $attributeValue,
                            "attribute_code" => $attribute->getAttributeCode(),
                            "is_searchable" => $attribute->getIsSearchable()
                        ];
                    }
                }
            }

            return [
                "attributes" => $attributes_data
            ];

        } catch (NoSuchEntityException $e) {
            return [];
        } catch (LocalizedException $e) {
            throw new NoSuchEntityException(__($e->getMessage()));
        }
    }
}