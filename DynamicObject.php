<?php

/*
 * CHEQUER for PHP
 *
 * Copyright (c)2013 Rafal Lindemann <rl@stamina.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code. *
 */

/** Dynamic objects in PHP
 *
 * echo
 *
 */
class DynamicObject {

    /** Parent object we are expanding on */
    protected $__parent = null;
    protected $__getters = array();
    protected $__setters = array();
    protected $__methods = array();
    /** All bounded closures are tracked here as [name => true]. */
    protected $__closures = array();

    const AUTO_PREFIX = '_';
    const AUTO_UCFIRST = '^';
    const AUTO_CATCHALL = '*';
    
    function __construct($parent = null) {
        if ($parent && !is_object($parent)) throw new \InvalidArgumentException("Parrent should be an object!");
        $this->__parent = $parent;
    }


    public function getParentObject() {
        return $this->__parent;
    }


    public function getGetters() {
        return $this->__getters;
    }


    public function getSetters() {
        return $this->__setters;
    }


    public function getMethods() {
        return $this->__methods;
    }


    /** 
     * @param $property Property name or self::AUTO_PREFIX to set getter prefix (eg. 'get_')
     * @return self */
    public function addGetter($property, $getter) {
        if ($getter instanceof Closure) $getter = $getter->bindTo($this, $this);
        $this->__getters[$property] = $getter;
        return $this;
    }


    /** 
     * @param $property Property name or self::AUTO_PREFIX to set setter prefix (eg. 'get_')
     * @return self */
    public function addSetter($property, $setter) {
        if ($setter instanceof Closure) $setter = $setter->bindTo($this, $this);
        $this->__setters[$property] = $setter;
        return $this;
    }


    /** @return self */
    public function addMethod($property, $method) {
        if ($method instanceof Closure) $method = $method->bindTo($this, $this);
        $this->__methods[$property] = $method;
        return $this;
    }


    /** @return self */
    public function addProperty($property, $method) {
        if ($method instanceof Closure) $method = $method->bindTo($this, $this);
        $this->__getters[$property] =
                $this->__setters[$property] =
                $this->__methods[$property] = $method;
        return $this;
    }


    /** Returns TRUE if $this->property() is callable */
    public function isCallable($name) {
        if (method_exists($this, $name))
            return true;
        if (isset($this->__methods[$name])) {
            return true;
        }
        if (($property = $this->{$name}) instanceof Closure || is_callable($property)) {
            return true;
        }
        return ($this->__parent && method_exists($this->__parent, $name));
    }

    
    protected function _callMethodDeclaration($method, $arguments = array()) {
        if ($method instanceof Closure) return call_user_func_array($method, $arguments);
        if (is_string($method)) return call_user_func_array(array($this, $method), $arguments);
        array_unshift($arguments, $this);
        return call_user_func_array($method, $arguments);
    }

    
    public function __call($name, $arguments) {
        if (isset($this->__methods[$name])) {
            return $this->_callMethodDeclaration($this->__methods[$name], $arguments);
        }
        if (($property = $this->{$name}) instanceof Closure || is_callable($property)) {
            return call_user_func_array($property, $arguments);
        }
        if ($this->__parent) {
            return call_user_func_array(array($this->__parent, $name), $arguments);
        }
        throw new BadMethodCallException("Method '$name' is undefined!");
    }


    public function __get($name) {
        if (isset($this->__getters[$name])) {
            return $this->_callMethodDeclaration($this->__getters[$name]);
        }
        if (isset($this->__getters[self::AUTO_PREFIX])
                // protect from getting the getter
                && strncmp($name, $this->__getters[self::AUTO_PREFIX], strlen($this->__getters[self::AUTO_PREFIX])) !== 0
                && ($autoName = $this->__getters[self::AUTO_PREFIX] . $name)
                && $this->isCallable($autoName)
                ) {
            return $this->{$autoName}();
        }
        if ($this->__parent && isset($this->__parent->{$name})) {
            return $this->__parent->{$name};
        }
        return null;
    }


    public function __isset($name) {
        if (isset($this->__getters[$name])) {
            return true;
        }
        if (isset($this->__getters[self::AUTO_PREFIX])
                && ($autoName = $this->__getters[self::AUTO_PREFIX] . $name)
                && $this->isCallable($autoName)
                ) {
            return true;
        }
        return ($this->__parent && isset($this->__parent->{$name}));
    }


    public function __set($name, $value) {
        if ($value instanceof Closure) {
            $this->__closures[$name] = true;
            $value = $value->bindTo($this, $this);
        }

        if (isset($this->__setters[$name])) {
            return $this->_callMethodDeclaration($this->__setters[$name], array($value));
        }
        if (isset($this->__setters[self::AUTO_PREFIX])
                && ($autoName = $this->__setters[self::AUTO_PREFIX] . $name)
                && $this->isCallable($autoName)
                ) {
            return $this->{$autoName}($value);
        }
        if ($this->__parent && isset($this->__parent->{$name})) {
            $this->__parent->{$name} = $value;
            return;
        }
        $this->{$name} = $value;
    }


    public function __toString() {
        try {
            return $this->__call('__toString', array());
        } catch (Exception $e) {
            return '(' . $e->getMessage() . ')';
        }
    }


    public function __unset($name) {
        if (isset($this->__setters[$name])) {
            return $this->_callMethodDeclaration($this->__setters[$name], array(null));
        }
        if (isset($this->__setters[self::AUTO_PREFIX])
                && ($autoName = $this->__setters[self::AUTO_PREFIX] . $name)
                && $this->isCallable($autoName)
                ) {
            return $this->{$autoName}(null);
        }
        if ($this->__parent && isset($this->__parent->{$name})) {
            unset($this->__parent->{$name});
            return;
        }

        unset($this->{$name});

    }

    
    public function __clone() {
        // we need to rebind closures...
        foreach($this->__getters as &$func) {
            if ($func instanceof Closure) $func = $func->bindTo($this, $this);
        }
        foreach($this->__setters as &$func) {
            if ($func instanceof Closure) $func = $func->bindTo($this, $this);
        }
        foreach($this->__methods as &$func) {
            if ($func instanceof Closure) $func = $func->bindTo($this, $this);
        }
        
        // reset the list of closures...
        $closures = $this->__closures;
        $this->__closures = array();
        
        // and try to rebind them
        foreach($closures as $name => $enabled) {
            if (!$enabled) continue;
            if (($closure = $this->{$name}) instanceof Closure) {
                // rebind and assign again
                $this->{$name} = $closure->bindTo($this, $this);
            }
        }
    }


}
