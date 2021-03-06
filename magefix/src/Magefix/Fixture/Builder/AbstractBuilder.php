<?php

namespace Magefix\Fixture\Builder;

use Mage_Core_Model_Abstract as MagentoModel;
use Magefix\Exceptions\UndefinedDataProvider;
use Magefix\Fixture\DataIterator;
use Magefix\Fixture\Builder;
use Magefix\Fixture\Factory\Builder as FixtureBuilder;
use Magefix\Fixtures\Data\Provider;
use Magefix\Magento\Store\Scope as MagentoStoreScope;
use Magefix\Exceptions\UndefinedAttributes;

/**
 * Class AbstractBuilder
 *
 * @package Magefix\Fixture\Builder
 * @author  Carlo Tasca <ctasca.d3@gmail.com>
 */
abstract class AbstractBuilder implements Builder
{
    /**
     * @var MagentoModel
     */
    private $_mageModel;

    /**
     * @var Provider
     */
    private $_dataProvider;

    /**
     * @var string
     */
    private $_hook;

    /**
     * @var array
     */
    protected $_data;

    public function __construct(array $parserData, MagentoModel $model, $hook)
    {
        $this->_mageModel = $model;
        $this->_data = $parserData;
        $this->_hook = $hook;
    }

    /**
     *
     * Concrete classes to provide implementation for build method
     *
     * @return mixed
     */
    abstract public function build();

    /**
     * @return MagentoModel
     * @throws \Exception
     */
    public function getMageModel()
    {
        return $this->_getMageModel();
    }

    /**
     * @return string
     */
    public function getHook()
    {
        return $this->_getHook();
    }

    /**
     * Iterate fixture data
     */
    public function invokeProvidersMethods()
    {
        $iterator = new DataIterator($this->_data);
        $this->_data = $iterator->apply($this->_getDataProvider());
    }

    /**
     * @param $mageModel
     * @param $data
     *
     */
    public function saveFixtureWithModelAndData($mageModel, $data)
    {
        $this->_saveFixtureWithModelAndData($mageModel, $data);
    }

    /**
     * @param $entity
     *
     * @throws UndefinedDataProvider
     *
     */
    public function throwUndefinedDataProvider($entity)
    {
        $this->_throwUndefinedDataProvider($entity);
    }

    protected function _build()
    {
        $this->invokeProvidersMethods();
        $defaultData = $this->_getMageModelData() ? $this->_getMageModelData() : [];
        $mergedData  = array_merge($defaultData, $this->_getFixtureAttributes());

        $this->_getMageModel()->setData($mergedData);

        return $this->_saveFixture();
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function _getMageModelData()
    {
        return $this->_getMageModel()->getData();
    }

    /**
     * @return MagentoModel
     */
    protected function _getMageModel()
    {
        return $this->_mageModel;
    }

    /**
     * @return mixed
     */
    protected function _getDataProvider()
    {
        $this->_throwUndefinedDataProvider($this->_data['fixture']);

        if (is_null($this->_dataProvider)) {
            $dataProvider = new \ReflectionClass($this->_data['fixture']['data_provider']);
            $this->_dataProvider = $dataProvider->newInstance();
        }
        return $this->_dataProvider;
    }

    /**
     * @return string
     */
    protected function _getHook()
    {
        return $this->_hook;
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function _getFixtureAttributes()
    {
        $this->_throwUndefinedAttributesException(isset($this->_data['fixture']['attributes']));

        return $this->_data['fixture']['attributes'];
    }

    /**
     * @return int
     * @throws \Exception
     */
    protected function _saveFixture()
    {
        MagentoStoreScope::setAdminStoreScope();
        $this->_getMageModel()->save();
        MagentoStoreScope::setCurrentStoreScope();

        return $this->_getMageModel()->getId();
    }

    /**
     * @param MagentoModel $mageModel
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    protected function _saveFixtureWithModelAndData(MagentoModel $mageModel, $data)
    {
        MagentoStoreScope::setAdminStoreScope();
        $mageModel->setData($data);
        $mageModel->save();
        MagentoStoreScope::setCurrentStoreScope();

        return $mageModel->getId();
    }

    /**
     * Add data to model, but don't invoke save
     *
     * @param array $data
     */
    protected function _initFixtureWithData(array $data)
    {
        $this->_getMageModel()->setData($data);
    }

    /**
     * @param $node
     *
     * @return array Keeps indexes as defined in the fixture node.
     */
    protected function _buildManySimple($node)
    {
        $loadedProducts = $this->_loadProductsById($node);
        $newProducts = $this->_createProductsFromDefinition($node);

        return $loadedProducts + $newProducts;
    }

    /**
     * @param boolean $isSet
     * @param string $message
     *
     * @throws UndefinedAttributes
     *
     */
    protected function _throwUndefinedAttributesException(
        $isSet, $message = 'Fixture attributes have not been defined. Check fixture yml file.'
    )
    {
        if (!$isSet) {
            throw new UndefinedAttributes($message);
        }
    }

    /**
     * @param array $a
     *
     * @throws UndefinedDataProvider
     *
     */
    protected function _throwUndefinedDataProvider(array $a)
    {
        if (!isset($a['data_provider'])) {
            throw new UndefinedDataProvider(
                'Fixture has no data provider specified. Check fixture yml file.'
            );
        }
    }

    /**
     * @param $node
     *
     * @return array
     */
    private function _loadProductsById($node)
    {
        $loadedProducts = [];
        foreach ($this->_data['fixture'][$node]['products'] as $index => $productDefinition) {
            if (is_scalar($productDefinition)) {
                $loadedProducts[$index] = FixtureBuilder::load('catalog/product', $productDefinition);
            }
        }
        return $loadedProducts;
    }

    /**
     * @param $node
     *
     * @return array
     * @throws \Magefix\Exceptions\UndefinedFixtureModel
     */
    private function _createProductsFromDefinition($node)
    {
        $newProducts = [];
        foreach ($this->_data['fixture'][$node]['products'] as $index => $productDefinition) {
            if (is_array($productDefinition)) {
                $newProducts[$index] = FixtureBuilder::buildMany(
                    FixtureBuilder::SIMPLE_PRODUCT_FIXTURE_TYPE,
                    $this,
                    $productsDefinedByAttributes,
                    $this->getHook()
                );
            }
        }

        return $newProducts;
    }
}
