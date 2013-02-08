<?php

require_once __DIR__ . '/../DynamicObject.php';

class TestParentObject {
    
    public $parentDeclaredProperty = 'parentDeclaredProperty';
    private $props = array(
        'parentGet' => 'parentGet'
    );

    public function parentMethod() {
        return 'parentMethod';
    }
    
    public function __call($name, $arguments) {
        if ($name == 'parentCall') return 'parentCall';
        throw new Exception("Unknown function '$name'");
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

    public function __toString() {
        return 'parentToString';
    }

}

class TestObject extends DynamicObject {
    public $id = 1;
    public $declaredPublicProperty = 'declaredPublicProperty';
    private $declaredPrivateProperty = 'declaredPrivateProperty';
    private $_objectGet = 'objectGet';
    private $_objectGetter = 'objectGetter';
    private $_objectAutoGetter = 'objectAutoGetter';

    protected $__getters = array('objectGetter' => 'objectGetterSetter', 'objectEmptyGetter' => 'objectEmptyGetterSetter', '*' => 'get_');
    protected $__setters = array('objectGetter' => 'objectGetterSetter', 'objectEmptyGetter' => 'objectEmptyGetterSetter', '*' => 'set_');
    protected $__methods = array();
    
    public function __construct($parent = null) {
        parent::__construct($parent);
    }

    public function declaredMethod($value = false) {
        return $value === false ? 'declaredMethod' : $value;
    }
    
    public function callMethodDeclaration($method, $arguments) {
        return $this->_callMethodDeclaration($method, $arguments);
    }    
    
    public function getDeclaredPrivateProperty() {
        return $this->declaredPrivateProperty;
    }
    
    protected function objectGetterSetter($value = false) {
        if ($value !== false) {
            $this->_objectGetter = $value;
        } else {
            return $this->_objectGetter;
        }
    }
    
    protected function objectEmptyGetterSetter($value = null) {
        return null;
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

    public function __get($name) {
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
        return parent::__set($name, $value);
    }

    public function __unset($name) {
        if ($name == 'objectGet') {
            unset($this->_objectGet);
            return;
        }
        return parent::__unset($name);
    }    
}






class DynamicObjectTest extends PHPUnit_Framework_TestCase {

    protected $obj;
    protected $parent;
    protected $closure;
    protected $closureProperty;
    
    public function setUp() {
        $this->parent = new TestParentObject();
        $this->obj = new TestObject($this->parent);
        
        $this->closureProperty = function($value = false) {
            if ($value !== false) {
                $this->closureProperty = $value;
            } else {
                return $this->closureProperty;
            }
        };
        $this->closure = function($value = false) {
            return $value === false ? $this : $value;
        };
    }
    
    public static function staticGetterSetter($self, $value = false) {
        if ($value !== false) {
            $self->staticProperty = $value;
        } else {
            return $self->staticProperty;
        }    
    }
    
    public static function staticMethod($self) {
        return $self;
    }
    
    // done
    public function testDeclaredProperty() {
        // isset
        $this->assertTrue(isset($this->obj->declaredPublicProperty));
        $this->assertFalse(isset($this->obj->declaredPrivateProperty));
        $this->assertTrue(isset($this->obj->parentDeclaredProperty));
        $this->assertFalse(isset($this->obj->undefined));

        $this->assertFalse($this->obj->isCallable('declaredPublicProperty'));
        
        // get
        $this->assertEquals('declaredPublicProperty', $this->obj->declaredPublicProperty);
        $this->assertNull($this->obj->declaredPrivateProperty);
        $this->assertNull($this->obj->undefined);
        $this->assertEquals('parentDeclaredProperty', $this->parent->parentDeclaredProperty);
        $this->assertEquals('parentDeclaredProperty', $this->obj->parentDeclaredProperty);
        
        // set
        $this->obj->declaredPublicProperty = 'foo';
        $this->assertEquals('foo', $this->obj->declaredPublicProperty);
        
        $this->obj->parentDeclaredProperty = 'bar';
        $this->assertEquals('bar', $this->obj->parentDeclaredProperty);
        $this->assertEquals('bar', $this->parent->parentDeclaredProperty);
        
        // unset
        unset($this->obj->declaredPublicProperty);
        unset($this->obj->declaredPrivateProperty);
        unset($this->obj->parentDeclaredProperty);
        $this->assertFalse(isset($this->obj->declaredPublicProperty));
        $this->assertFalse(isset($this->obj->declaredPrivateProperty));
        $this->assertFalse(isset($this->obj->parentDeclaredProperty));
        $this->assertNull($this->obj->declaredPublicProperty);
        $this->assertEquals('declaredPrivateProperty', $this->obj->getDeclaredPrivateProperty());
    }
    
    // done
    public function testDynamicProperty() {
        // isset
        $this->assertFalse(isset($this->obj->objectDynamicProperty));
        
        // get
        $this->assertNull($this->obj->objectDynamicProperty);
        
        // set
        $this->obj->objectDynamicProperty = 'objectDynamicProperty';
        $this->assertEquals('objectDynamicProperty', $this->obj->objectDynamicProperty);
        $this->assertTrue(isset($this->obj->objectDynamicProperty));
        
        $this->assertEquals(array('id' => 1, 'objectDynamicProperty' => 'objectDynamicProperty', 'declaredPublicProperty' => 'declaredPublicProperty'), get_object_vars($this->obj));
        
        // unset
        unset($this->obj->objectDynamicProperty);
        $this->assertFalse(isset($this->obj->objectDynamicProperty));
    }
    
    // done
    public function testGetSetProperty() {
        // isset
        $this->assertTrue(isset($this->obj->objectGet));
        $this->assertTrue(isset($this->obj->parentGet));
        
        // get
        $this->assertEquals('objectGet', $this->obj->objectGet);
        $this->assertEquals('parentGet', $this->obj->parentGet);

        // set
        $this->obj->objectGet = 'foo';
        $this->assertEquals('foo', $this->obj->objectGet);
        
        $this->obj->parentGet = 'bar';
        $this->assertEquals('bar', $this->obj->parentGet);
        
        // unset
        unset($this->obj->objectGet);
        $this->assertTrue(isset($this->obj->objectGet));
        $this->assertNull($this->obj->objectGet);
        unset($this->obj->parentGet);
        $this->assertFalse(isset($this->obj->parentGet));
    }
    
    // done
    public function testGetterSetter() {
        // isset
        $this->assertTrue(isset($this->obj->objectGetter));
        $this->assertTrue(isset($this->obj->objectEmptyGetter));
        
        // get
        $this->assertEquals('objectGetter', $this->obj->objectGetter);
        $this->assertNull($this->obj->objectEmptyGetter);
        
        // set
        $this->obj->objectGetter = 'foo';
        $this->assertEquals('foo', $this->obj->objectGetter);
        
        // unset
        unset($this->obj->objectGetter);
        $this->assertTrue(isset($this->obj->objectGetter));
        $this->assertNull($this->obj->objectGetter);
    }

    /** @todo wider test for cloning */
    protected function clonedPropertyTest($new) {
        $obj = $this->obj;
        $cloned = clone $this->obj;
        $cloned->id += 1;
        $new->id += 2;
        // isset
        $this->assertTrue(isset($obj->test));
        // set
        $obj->test = 'property';
        $cloned->test = 'clonedProperty';
        $new->test = 'newProperty';
        // get
        $this->assertEquals('property', $obj->test);
        // unset
        unset($obj->test);
        $this->assertTrue(isset($obj->test));
        $this->assertNull($this->obj->test);
        
        // cloned?
        $this->assertEquals('clonedProperty', $cloned->test);
        // new?
        $this->assertEquals('newProperty', $new->test);
    }
    
    // done
    public function testPropertyClosure() {
        $this->obj->addProperty('test', $this->closureProperty);
        $this->assertTrue($this->obj->isCallable('test'), 'Should be callable as ->test()!');

        $new = new TestObject();
        $new->addProperty('test', $this->closureProperty);
        
        $this->clonedPropertyTest($new);
    }    

    // done
    public function testGetterSetterCallable() {
        $this->obj->addGetter('test', ['DynamicObjectTest', 'staticGetterSetter']);
        $this->obj->addSetter('test', ['DynamicObjectTest', 'staticGetterSetter']);
        $this->assertFalse($this->obj->isCallable('test'), 'Should NOT be callable as ->test()!');
        
        $new = new TestObject();
        $new->addProperty('test', ['DynamicObjectTest', 'staticGetterSetter']);
        
        $this->clonedPropertyTest($new);
    }    
    
    // done
    public function testAutoGetterSetter() {
        $this->assertFalse($this->obj->isCallable('objectAutoGetter'), 'Should NOT be callable!');
        
        // isset
        $this->assertTrue(isset($this->obj->objectAutoGetter));
        $this->assertFalse(isset($this->obj->objectAutoMissing));
        
        // get
        $this->assertEquals('objectAutoGetter', $this->obj->objectAutoGetter);
        
        // set
        $this->obj->objectAutoGetter = 'foo';
        $this->assertEquals('foo', $this->obj->objectAutoGetter);
        
        // unset
        unset($this->obj->objectAutoGetter);
        $this->assertTrue(isset($this->obj->objectAutoGetter));
        $this->assertNull($this->obj->objectAutoGetter);
    }

    // done
    public function testAutoGetterSetterClosure() {
        $this->obj->get_test = $this->obj->set_test = $this->closureProperty;
        
        $this->assertTrue($this->obj->isCallable('get_test'));
        $this->assertTrue($this->obj->isCallable('set_test'));
        
        $new = new TestObject();
        $this->obj->get_test = $this->obj->set_test = $this->closureProperty;
        
        $this->clonedPropertyTest($new);
    }
    
    // done
    public function testStrings() {
        $this->assertEquals('parentToString', (string)$this->obj);
        
        $this->obj->__toString = function() { 
            return 'Hello world! ' . $this->declaredPublicProperty; 
        };
        $this->assertEquals('Hello world! declaredPublicProperty', (string)$this->obj);
        
        $new = new TestObject();
        ///TODO: got to decide what to return :)
        $this->assertEquals("(Method '__toString' is undefined!)", (string)$new);
    }

    // done
    public function testCallDeclaredMethod() {
        $this->assertTrue($this->obj->isCallable('declaredMethod'));
        $this->assertEquals('declaredMethod', $this->obj->declaredMethod());
        
        $this->assertTrue($this->obj->isCallable('parentMethod'));
        $this->assertEquals('parentMethod', $this->obj->parentMethod());
    }

    // done
    public function testCallCall() {
        $this->assertFalse($this->obj->isCallable('objectCall'), 'We dont know it');
        $this->assertEquals('objectCall', $this->obj->objectCall());
        
        $this->assertFalse($this->obj->isCallable('parentCall'), 'We dont know it');
        $this->assertEquals('parentCall', $this->obj->parentCall());
    }
    
    // done
    public function testCallAddedMethod() {
        $this->obj->addMethod('testMethod', 'declaredMethod');
        $this->obj->addMethod('testClosure', $this->closure);

        $this->assertTrue($this->obj->isCallable('testMethod'));
        $this->assertTrue($this->obj->isCallable('testClosure'));
        
        $this->assertEquals('declaredMethod', $this->obj->testMethod());
        $this->assertTrue($this->obj === $this->obj->testClosure());
        
        $this->assertEquals('test', $this->obj->testMethod('test'));
        $this->assertEquals('test', $this->obj->testClosure('test'));
    }
    
    // done
    public function testCallClosure() {
        $this->obj->test = $this->closure;

        $this->assertTrue($this->obj->isCallable('test'));
        
        $this->assertInstanceOf('Closure', $this->obj->test);
        $this->assertFalse($this->closure === $this->obj->test, 'Bound closure should be different!');
        
        $this->assertEquals($this->obj, $this->obj->test());
        
        $this->assertEquals('test', $this->obj->test('test'));
    }
    
    public function testCallMissing() {
        $this->setExpectedException('BadMethodCallException');

        $obj = new TestObject();
        $obj->missingMethod();
    }
    
    // done
    protected function referencesTest($property) {
        $this->obj->{$property} = 'some value';
        $val = $this->obj->{$property};
        $original = $val;
        $val .= 'should not change';
        $this->assertEquals($original, $this->obj->{$property}, "$property should not change");
        
//        $this->obj->{$property} = array('foo' => 'foo');
//        $this->obj->{$property}['foo'] = 'bar';
//        $this->assertEquals(array('foo' => 'bar'), $this->obj->{$property}, "$property should change");
        
    }
    
    // this test had more sense with references, but it's probably not possible to do them sensibly
    public function testGetReference() {
        $this->referencesTest('objectGet');        
        $this->referencesTest('objectGetter');        
        $this->referencesTest('objectAutoGet');        
        $this->referencesTest('objectAutoGet');   
        
        $this->referencesTest('parentDeclaredProperty');        
        $this->referencesTest('parentGet');        
        
        $this->obj->addProperty('closureProperty', $this->closureProperty);
        $this->referencesTest('closureProperty');
        
        $this->obj->addGetter('callableGetterSetter', ['DynamicObjectTest', 'staticGetterSetter']);
        $this->obj->addSetter('callableGetterSetter', ['DynamicObjectTest', 'staticGetterSetter']);
        $this->referencesTest('callableGetterSetter');

        $this->obj->get_closureAutoProperty = $this->obj->set_closureAutoProperty = $this->closureProperty;
        $this->referencesTest('closureAutoProperty');
        
    }
 
    
    
}

