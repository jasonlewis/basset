<?php namespace Basset\Factory;

use Countable;
use ArrayAccess;

class Manager implements ArrayAccess {

    /**
     * Array of registered factories.
     *
     * @var array
     */
    protected $factories = array();

    /**
     * Register a factory with the manager.
     *
     * @param  string  $name
     * @param  Basset\Factory\FactoryInterface  $factory
     * @return Basset\Factory\Manager
     */
    public function register($name, FactoryInterface $factory)
    {
        $this->factories[$name] = $factory;

        return $this;
    }

    /**
     * Get a factory from the manager.
     *
     * @param  string  $name
     * @return mixed
     */
    public function get($name)
    {
        return $this->factories[$name];
    }

    /**
     * Determine if the manager has a given factory.
     *
     * @param  string  $name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->factories[$name]);
    }

    /**
     * Dynamically load a factory from the registered factories.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        if ($this->has($key))
        {
            return $this->get($key);
        }
    }

    /**
     * Set a factory offset.
     *
     * @param  string  $offset
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->register($offset ?: $this->count(), $value);
    }

    /**
     * Determine if a factory offset exists.
     *
     * @param  string  $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Unset a factory offset.
     *
     * @param  string  $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->factories[$offset]);
    }

    /**
     * Get a factory offset.
     *
     * @param  string  $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Get the total number of factories.
     *
     * @return int
     */
    public function count()
    {
        return count($this->factories);
    }

    /**
     * Get the registered factories.
     * 
     * @return array
     */
    public function getFactories()
    {
        return $this->factories;
    }

}