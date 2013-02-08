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


    /** @return self */
    public function addGetter($property, $getter) {
        $this->__getters[$property] = $getter;
        return $this;
    }


    /** @return self */
    public function addSetter($property, $setter) {
        $this->__setters[$property] = $setter;
        return $this;
    }


    /** @return self */
    public function addMethod($property, $method) {
        $this->__methods[$property] = $method;
        return $this;
    }


    /** @return self */
    public function addProperty($property, $method) {
        $this->__getters[$property] =
                $this->__setters[$property] =
                $this->__methods[$property] = $method;
        return $this;
    }


    /** Returns TRUE if $this->property() is callable */
    public function isCallable($name) {
        if (method_exists($this, $name))
            return true;
        if ($this->__parent && method_exists($this->__parent, $name))
            return true;
    }

    protected function _callMethodDeclaration($method, $arguments) {
        if ($method instanceof Closure) return call_user_func_array($method, $arguments);
        if (is_string($method)) return call_user_func_array(array($this, $method), $arguments);
        array_unshift($arguments, $this);
        return call_user_func_array($method, $arguments);
    }

    public function __call($name, $arguments) {
        
    }


    public function &__get($name) {
        
    }


    public function __isset($name) {
        
    }


    public function __set($name, $value) {
        
    }


    public function __toString() {
        
    }


    public function __unset($name) {
        
    }


}
