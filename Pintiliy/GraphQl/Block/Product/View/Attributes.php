<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Product description block
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
namespace Pintiliy\GraphQl\Block\Product\View;

use Magento\Catalog\Model\Product;
use Magento\Framework\Phrase;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\HTTP\Client\Curl;
/**
 * Attributes attributes block
 *
 * @api
 * @since 100.0.2
 */
class Attributes extends \Magento\Catalog\Block\Product\View\Attributes
{
    public function getAdditionalData(array $excludeAttr = [])
    {
        $data = [];
        $product = $this->getProduct();
        $attributes = $product->getAttributes();
        foreach ($attributes as $attribute) {
            if ($attribute->getIsVisibleOnFront() && $attribute->getIsUserDefined()) {
                $value = $attribute->getFrontend()->getValue($product);

                if ($value instanceof Phrase) {
                    $value = (string)$value;
                } elseif ($attribute->getFrontendInput() == 'price' && is_string($value)) {
                    $value = $this->priceCurrency->convertAndFormat($value);
                }

                if (is_string($value) && strlen(trim($value))) {
                    $data[$attribute->getAttributeCode()] = [
                        'label' => $attribute->getStoreLabel(),
                        'value' => $value,
                        'code' => $attribute->getAttributeCode(),
                    ];
                }
            }
        }
        
        return $data;
    }

    public function getAtributesGraphQl()
    {
        $productId = $this->getProduct()->getId();
        $endpoint = sprintf('http://%s/%s', $_SERVER['HTTP_HOST'], 'graphql'); 
        $this->curl = new Curl();
         $query = "query {
            GetProductAttributes(productId: $productId) {
                attributes {
                    id
                    name,
                    value,
                    attribute_code,
                    is_searchable
                }
            }
        }";

        $data = array ('query' => $query);
        $data = http_build_query($data);

        $this->curl->get("$endpoint?$data");
        $result = $this->curl->getBody();

        return json_decode($result)->data->GetProductAttributes->attributes;
        
    }
}
