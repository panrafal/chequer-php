<?php

require_once __DIR__ . '/../Chequer.php';

class TestParentObject {
    
    public $parentDeclaredProperty = 'parentDeclaredProperty';
    private $props = array();

    public function parentMethod() {
        return 'parentMethod';
    }
    
    public function __call($name, $arguments) {
        if ($name == 'parentCall') return 'parentCall';
        throw new Exception("Unknown function");
    }

    public function __get($name) {
        return $this->props[$name];
    }

    public function __isset($name) {
        return isset($this->props[$name]);
    }

    public function __set($name, $value) {
        $this->props[$name] = $value;
    }

    public function __unset($name) {
        unset($this->props[$name]);
    }


}

class TestObject extends DynamicObject {
    public $declaredPublicProperty = 'declaredPublicProperty';
    private $declaredPrivateProperty = 'declaredPrivateProperty';
    private $_objectGet = 'objectGet';
    private $_objectGetter = 'objectGetter';
    private $_objectAutoGetter = 'objectAutoGetter';

    protected $__getters = array('objectGroperty' => 'objectGetterSetter', '*' => 'get_');
    protected $__setters = array('objectGroperty' => 'objectGetterSetter', '*' => 'set_');
    protected $__methods = array();
    
    public function __construct($parent = null) {
        parent::__construct($parent);
    }

    public function declaredMethod() {
        return 'declaredMethod';
    }
    
    public function callMethodDeclaration($method, $arguments) {
        return $this->_callMethodDeclaration($method, $arguments);
    }    
    
    protected function objectGetterSetter($value = null) {
        if ($value !== null) {
            $this->_objectGetter = $value;
        } else {
            return $this->_objectGetter;
        }
    }
    
    protected function get_objectAutoGetter() {
        return $this->_objectAutoGetter;
    }
    
    protected function set_objectAutoGetter($value) {
        $this->_objectAutoGetter = $value;
    }
    
    public function __call($name, $arguments) {
        if ($name == 'objectCall') return 'objectCall';
        return parent::__call($name, $arguments);
    }

    public function &__get($name) {
        if ($name == 'objectGet') return $this->_objectGet;
        return parent::__get($name);
    }

    public function __isset($name) {
        if ($name == 'objectGet') return true;
        return parent::__isset($name);
    }

    public function __set($name, $value) {
        if ($name == 'objectGet') {
            $this->_objectGet = $value;
            return;
        }
        return parent::__isset($name);
    }

    public function __unset($name) {
        if ($name == 'objectGet') {
            unset($this->_objectGet);
            return;
        }
        return parent::__isset($name);
    }    
}


class DynamicObjectTest extends PHPUnit_Framework_TestCase {

    protected $data;

	protected function setUp() {
    }


    protected function tearDown() {
        
    }


    public function testSom() {
        
    }
    
}

