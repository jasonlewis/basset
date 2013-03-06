<?php namespace Basset\Factory;

class FactoryManager {

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
     * @param  mixed  $factory
     * @return Basset\Factory\FactoryManager
     */
    public function register($name, $factory)
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

}