<?php

namespace BestIt\CTCustomTypesBundle\Model;

use ArrayAccess;

/**
 * Model which contains the custom types keys for one ResourceType
 * @author chowanski <chowanski@bestit-online.de>
 * @package BestIt\CTCustomTypesBundle
 * @subpackage Model
 * @version $id$
 */
class CustomTypeResource implements ArrayAccess
{
    /**
     * The collection
     * @var array
     */
    private $collection = [];

    /**
     * The unique resource name
     * @var string
     */
    private $name;

    /**
     * CustomTypeResource constructor
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->setName($name);
    }

    /**
     * Get all keys
     * @return array
     */
    public function all(): array
    {
        return $this->collection;
    }

    /**
     * Find one (first) key of resource
     * @return string|null
     */
    public function findOne()
    {
        return $this->offsetGet(0);
    }

    /**
     * Get name
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return isset($this->collection[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->collection[$offset] : null;
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->collection[] = $value;
        } else {
            $this->collection[$offset][] = $value;
        }
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        unset($this->collection[$offset]);
    }

    /**
     * Set name
     * @param string $name
     * @return CustomTypeResource
     */
    public function setName(string $name): CustomTypeResource
    {
        $this->name = $name;

        return $this;
    }
}
