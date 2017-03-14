<?php

namespace BestIt\CTCustomTypesBundle\Collection;

use ArrayAccess;
use BestIt\CTCustomTypesBundle\Model\CustomTypeResource;

/**
 * Provide a collection for custom type keys
 * @author chowanski <chowanski@bestit-online.de>
 * @package BestIt\CTCustomTypesBundle
 * @subpackage Collection
 * @version $id$
 */
class CustomTypeCollection implements ArrayAccess
{
    /**
     * The collection
     * @var CustomTypeResource[]
     */
    private $collection = [];

    /**
     * Get all keys
     * @return CustomTypeResource[]
     */
    public function all(): array
    {
        return $this->collection;
    }

    /**
     * Clears all keys
     * @return CustomTypeCollection
     */
    public function clear(): CustomTypeCollection
    {
        $this->collection = [];

        return $this;
    }

    /**
     * Get specific array of keys of given type name
     * @param string $name
     * @return CustomTypeResource|null
     */
    public function get(string $name): CustomTypeResource
    {
        $result = null;

        if ($this->has($name)) {
            $result = $this->collection[$name];
        }

        return $result;
    }

    /**
     * Check if given key name exists
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists(strtolower($name), $this->collection);
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->collection[] = $value;
        } else {
            $this->set($offset, $value);
        }
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * Removes a key value pair
     * @param string $name
     * @return CustomTypeCollection
     */
    public function remove(string $name): CustomTypeCollection
    {
        unset($this->collection[strtolower($name)]);

        return $this;
    }

    /**
     * Add a key value pair
     * @param string $name
     * @param CustomTypeResource $value
     * @return CustomTypeCollection
     */
    public function set(string $name, CustomTypeResource $value): CustomTypeCollection
    {
        $this->collection[strtolower($name)] = $value;

        return $this;
    }

    /**
     * This will add one key into a custom type resource.
     * If the custom type resource not exists, we'll generate one
     * @param string $name
     * @param string $key
     * @return CustomTypeCollection
     */
    public function add(string $name, string $key): CustomTypeCollection
    {
        if (!$this->has($name)) {
            $this->collection[$name] = new CustomTypeResource($name);
        }

        $this->collection[$name][] = $key;

        return $this;
    }
}
