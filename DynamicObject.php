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

    /** Overloading prefix for getters and setters
     * 
     * ```
     * $o -> addGetter(DynamicObject::OVERLOAD_PREFIX, 'get_');
     * echo $o->foo;
     * // is the same as
     * echo $o->get_foo();
     * ```
     *  */
    const OVERLOAD_PREFIX = 'PREFIX*';
    /** Overloading prefix for getters and setters. Acts the same as __get, __set and __call magic functions.
     * 
     * See documentation for more specific information.
     */
    const OVERLOAD_ALL = '*ALL*';
    
    function __construct($parent = null) {
        if ($parent && !is_object($parent)) throw new \InvalidArgumentException("Parent should be an object!");
        $this->__parent = $parent;
    }


    public function _getParentObject() {
        return $this->__parent;
    }


    public function _getGetters() {
        return $this->__getters;
    }


    public function _getSetters() {
        return $this->__setters;
    }


    public function _getMethods() {
        return $this->__methods;
    }

    protected function _prepareClosure($group, $name, $bind, &$closure = null) {
        if (PHP_VERSION_ID < 50400) return;
        if ($bind) {
            $this->__closures[$group][$name] = true;
            if ($closure) $closure = $closure->bindTo($this, $this);
        } else {
            unset($this->__closures[$group][$name]);
        }
    }

    /** 
     * @param $property Property name or self::OVERLOAD_PREFIX to set getter prefix (eg. 'get_')
     * @return self */
    public function _addGetter($property, $callback, $bind = true) {
        if ($callback instanceof Closure) $this->_prepareClosure('getter', $property, $bind, $callback);
        $this->__getters[$property] = $callback;
        return $this;
    }


    /** 
     * @param $property Property name or self::OVERLOAD_PREFIX to set setter prefix (eg. 'get_')
     * @return self */
    public function _addSetter($property, $callback, $bind = true) {
        if ($callback instanceof Closure) $this->_prepareClosure('setter', $property, $bind, $callback);
        $this->__setters[$property] = $callback;
        return $this;
    }


    /** @return self */
    public function _addMethod($property, $callback, $bind = true) {
        if ($callback instanceof Closure) $this->_prepareClosure('method', $property, $bind, $callback);
        $this->__methods[$property] = $callback;
        return $this;
    }


    /** @return self */
    public function _addProperty($property, $callback, $setMethod = true, $bind = true) {
        if ($callback instanceof Closure) {
            $this->_prepareClosure('getter', $property, $bind, $callback);
            $this->_prepareClosure('setter', $property, $bind);
            if ($setMethod) $this->_prepareClosure('method', $property, $bind);
        }
        $this->__getters[$property] = $this->__setters[$property] = $callback;
        if ($setMethod) $this->__methods[$property] = $callback;
        return $this;
    }

    /** The same as setting the Closure as property, but with controling the binding to $this */
    public function _setClosure($property, Closure $closure, $bind = true) {
        return $this->_setProperty($property, $closure, $bind);
    }

    /** Returns TRUE if $this->property() is callable */
    public function _isCallable($name) {
        if (method_exists($this, $name))
            return true;
        if (isset($this->__methods[$name])) {
            return true;
        }
        if (isset($this->__methods[self::OVERLOAD_ALL])) {
            $handled = false;
            $result = $this->_callMethodDeclaration($this->__methods[self::OVERLOAD_ALL], array(&$handled, $name, null));
            if ($handled) return $result;
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
        if (is_array($method) && $method[0] instanceof Closure) return call_user_func_array($method[0], $arguments);
        return call_user_func_array($method, $arguments);
    }

    
    public function __call($name, $arguments) {
        if (isset($this->__methods[$name])) {
            return $this->_callMethodDeclaration($this->__methods[$name], $arguments);
        }
        if (isset($this->__methods[self::OVERLOAD_ALL])) {
            $handled = true;
            $result = $this->_callMethodDeclaration($this->__methods[self::OVERLOAD_ALL], array(&$handled, $name, $arguments));
            if ($handled) return $result;
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
        if (isset($this->__getters[self::OVERLOAD_ALL])) {
            $handled = true;
            $result = $this->_callMethodDeclaration($this->__getters[self::OVERLOAD_ALL], array(&$handled, $name));
            if ($handled) return $result;
        }
        if (isset($this->__getters[self::OVERLOAD_PREFIX])
                // protect from getting the getter
                && strncmp($name, $this->__getters[self::OVERLOAD_PREFIX], strlen($this->__getters[self::OVERLOAD_PREFIX])) !== 0
                && ($autoName = $this->__getters[self::OVERLOAD_PREFIX] . $name)
                && $this->_isCallable($autoName)
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
        if (isset($this->__getters[self::OVERLOAD_ALL])) {
            $handled = false;
            $result = $this->_callMethodDeclaration($this->__getters[self::OVERLOAD_ALL], array(&$handled, $name));
            if ($handled) return $result;
        }
        if (isset($this->__getters[self::OVERLOAD_PREFIX])
                && ($autoName = $this->__getters[self::OVERLOAD_PREFIX] . $name)
                && $this->_isCallable($autoName)
                ) {
            return true;
        }
        
        
        return ($this->__parent && isset($this->__parent->{$name}));
    }

    public function __set($name, $value) {
        return $this->_setProperty($name, $value);
    }
    
    protected function _setProperty($name, $value, $bindClosures = true) {
        if ($value instanceof Closure) $this->_prepareClosure('property', $name, $bindClosures, $value);
        
        if (isset($this->__setters[$name])) {
            return $this->_callMethodDeclaration($this->__setters[$name], array($value));
        }
        if (isset($this->__setters[self::OVERLOAD_ALL])) {
            $handled = true;
            $result = $this->_callMethodDeclaration($this->__setters[self::OVERLOAD_ALL], array(&$handled, $name, $value));
            if ($handled) return $result;
        }
        if (isset($this->__setters[self::OVERLOAD_PREFIX])
                && ($autoName = $this->__setters[self::OVERLOAD_PREFIX] . $name)
                && $this->_isCallable($autoName)
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
        if (isset($this->__setters[self::OVERLOAD_ALL])) {
            $handled = true;
            $result = $this->_callMethodDeclaration($this->__setters[self::OVERLOAD_ALL], array(&$handled, $name, null));
            if ($handled) return $result;
        }
        if (isset($this->__setters[self::OVERLOAD_PREFIX])
                && ($autoName = $this->__setters[self::OVERLOAD_PREFIX] . $name)
                && $this->_isCallable($autoName)
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
        foreach($this->__getters as $name => &$func) {
            if ($func instanceof Closure && isset($this->__closures["getter"][$name])) $func = $func->bindTo($this, $this);
        }
        foreach($this->__setters as $name => &$func) {
            if ($func instanceof Closure && isset($this->__closures["setter"][$name])) $func = $func->bindTo($this, $this);
        }
        foreach($this->__methods as $name => &$func) {
            if ($func instanceof Closure && isset($this->__closures["method"][$name])) $func = $func->bindTo($this, $this);
        }
        
        // reset the list of closures...
        $closures = $this->__closures;
        $this->__closures = array();
        
        if (isset($closures['property'])) {
            // and try to rebind them
            foreach($closures['property'] as $name => $enabled) {
                if (!$enabled) continue;
                if (($closure = $this->{$name}) instanceof Closure) {
                    // rebind and assign again
                    $this->{$name} = $closure->bindTo($this, $this);
                }
            }
        }
    }


}
