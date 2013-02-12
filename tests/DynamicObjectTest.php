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
    public $_objectGet = 'objectGet';
    public $_objectGetter = 'objectGetter';
    public $_objectAutoGetter = 'objectAutoGetter';
    public $_objectAll = array('objectAllGetter' => 'objectAllGetter');

    protected $__getters = array(
        'objectGetter' => 'objectGetterSetter', 
        'objectEmptyGetter' => 'objectEmptyGetterSetter', 
        DynamicObject::OVERLOAD_PREFIX => 'get_',
        DynamicObject::OVERLOAD_ALL => 'allGetterSetter'
    );
    protected $__setters = array(
        'objectGetter' => 'objectGetterSetter', 
        'objectEmptyGetter' => 'objectEmptyGetterSetter', 
        DynamicObject::OVERLOAD_PREFIX => 'set_',
        DynamicObject::OVERLOAD_ALL => 'allGetterSetter'
    );
    protected $__methods = array(
        DynamicObject::OVERLOAD_PREFIX => 'set_',
        DynamicObject::OVERLOAD_ALL => 'allMethod'
    );
    
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
    
    protected function allGetterSetter(&$handled, $name, $value = false) {
        if ($name !== 'objectAllGetter') {
            $handled = false;
            return false;
        }
        if (!$handled) {
            $handled = true;
            return $this->_objectAll[$name] !== null;
        }
        
        if ($value !== false) {
            $this->_objectAll[$name] = $value;
        } else {
            return $this->_objectAll[$name];
        }
    }
    
    protected function allMethod(&$handled, $name, $arguments) {
        if ($name !== 'objectAllMethod') {
            $handled = false;
            return false;
        }
        if (!$handled) {
            $handled = true;
            return true;
        }
        
        return "$name(".json_encode($arguments).")";
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

        $this->assertFalse($this->obj->_isCallable('declaredPublicProperty'));
        
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
        
        $this->assertEquals(array('id', 'declaredPublicProperty', 
            '_objectGet', '_objectGetter', '_objectAutoGetter', '_objectAll', 'objectDynamicProperty'), 
                    array_keys(get_object_vars($this->obj)));
        
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
        $this->assertEquals('foo', $this->obj->_objectGet);
        
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
        $this->assertEquals('foo', $this->obj->_objectGetter);
        
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
        if (PHP_VERSION_ID < 50400) $this->markTestSkipped('PHP 5.4 required');
        
        $this->obj->_addProperty('test', $this->closureProperty);
        $this->assertTrue($this->obj->_isCallable('test'), 'Should be callable as ->test()!');

        $new = new TestObject();
        $new->_addProperty('test', $this->closureProperty);
        
        $this->clonedPropertyTest($new);
    }    

    // done
    public function testGetterSetterCallable() {
        $this->obj->_addGetter('test', array('DynamicObjectTest', 'staticGetterSetter'));
        $this->obj->_addSetter('test', array('DynamicObjectTest', 'staticGetterSetter'));
        $this->assertFalse($this->obj->_isCallable('test'), 'Should NOT be callable as ->test()!');
        
        $new = new TestObject();
        $new->_addProperty('test', array('DynamicObjectTest', 'staticGetterSetter'));
        
        $this->clonedPropertyTest($new);
    }    
    
    // done
    public function testAutoGetterSetter() {
        $this->assertFalse($this->obj->_isCallable('objectAutoGetter'), 'Should NOT be callable!');
        
        // isset
        $this->assertTrue(isset($this->obj->objectAutoGetter));
        $this->assertFalse(isset($this->obj->objectAutoMissing));
        
        // get
        $this->assertEquals('objectAutoGetter', $this->obj->objectAutoGetter);
        
        // set
        $this->obj->objectAutoGetter = 'foo';
        $this->assertEquals('foo', $this->obj->objectAutoGetter);
        $this->assertEquals('foo', $this->obj->_objectAutoGetter);
        
        // unset
        unset($this->obj->objectAutoGetter);
        $this->assertTrue(isset($this->obj->objectAutoGetter));
        $this->assertNull($this->obj->objectAutoGetter);
    }

    // done
    public function testAutoGetterSetterClosure() {
        if (PHP_VERSION_ID < 50400) $this->markTestSkipped('PHP 5.4 required');

        $this->obj->get_test = $this->obj->set_test = $this->closureProperty;
        
        $this->assertTrue($this->obj->_isCallable('get_test'));
        $this->assertTrue($this->obj->_isCallable('set_test'));
        
        $new = new TestObject();
        $new->get_test = $new->set_test = $this->closureProperty;
        
        $this->clonedPropertyTest($new);
    }

    // done
    public function testAutoAllGetterSetter() {
        if (PHP_VERSION_ID < 50400) $this->markTestSkipped('PHP 5.4 required');
        
        // isset
        $this->assertTrue(isset($this->obj->objectAllGetter));
        
        // get
        $this->assertEquals('objectAllGetter', $this->obj->objectAllGetter);
        
        // set
        $this->obj->objectAllGetter = 'foo';
        $this->assertEquals('foo', $this->obj->objectAllGetter);
        $this->assertEquals(array('objectAllGetter' => 'foo'), $this->obj->_objectAll);
        
        // unset
        unset($this->obj->objectAllGetter);
        $this->assertFalse(isset($this->obj->objectAllGetter));
        $this->assertNull($this->obj->objectAllGetter);

        // shadowing...
        $this->obj->_addProperty(DynamicObject::OVERLOAD_ALL, function(&$handled, $name, $value = false) {
            if (!$handled) {
                // handle all
                $handled = true;
                return true;
            }
            if ($value === false) return $this->overloadAllTest;
            else $this->overloadAllTest = $value;
        }, false);
        
        $this->obj->parentDeclaredProperty = 'test';
        $this->assertEquals('test', $this->obj->parentDeclaredProperty);
        $this->assertEquals('parentDeclaredProperty', $this->parent->parentDeclaredProperty);
        
        $new = new TestObject();
        $new->_addProperty(DynamicObject::OVERLOAD_ALL, 'allGetterSetter');
        
        $this->clonedPropertyTest($new);
    }
    
    // done
    public function testStrings() {
        if (PHP_VERSION_ID < 50400) $this->markTestSkipped('PHP 5.4 required');
        
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
        $this->assertTrue($this->obj->_isCallable('declaredMethod'));
        $this->assertEquals('declaredMethod', $this->obj->declaredMethod());
        
        $this->assertTrue($this->obj->_isCallable('parentMethod'));
        $this->assertEquals('parentMethod', $this->obj->parentMethod());
    }

    // done
    public function testCallCall() {
        $this->assertFalse($this->obj->_isCallable('objectCall'), 'We dont know it');
        $this->assertEquals('objectCall', $this->obj->objectCall());
        
        $this->assertFalse($this->obj->_isCallable('parentCall'), 'We dont know it');
        $this->assertEquals('parentCall', $this->obj->parentCall());
    }
    
    // done
    public function testCallAddedMethod() {
        $this->obj->_addMethod('testMethod', 'declaredMethod');
        $this->obj->_addMethod('testClosure', $this->closure);

        $this->assertTrue($this->obj->_isCallable('testMethod'));
        $this->assertTrue($this->obj->_isCallable('testClosure'));
        
        $this->assertEquals('declaredMethod', $this->obj->testMethod());
        if (PHP_VERSION_ID >= 50400) {
            $this->assertTrue($this->obj === $this->obj->testClosure());
        }
        
        $this->assertEquals('test', $this->obj->testMethod('test'));
        $this->assertEquals('test', $this->obj->testClosure('test'));
    }
    
    // done
    public function testCallClosure() {
        if (PHP_VERSION_ID < 50400) $this->markTestSkipped('PHP 5.4 required');
        
        $this->obj->test = $this->closure;

        $this->assertTrue($this->obj->_isCallable('test'));
        
        $this->assertInstanceOf('Closure', $this->obj->test);
        $this->assertFalse($this->closure === $this->obj->test, 'Bound closure should be different!');
        
        $this->assertEquals($this->obj, $this->obj->test());
        
        $this->assertEquals('test', $this->obj->test('test'));
    }
    
    // done
    public function testCallOverloadingAll() {
        $this->obj->_addMethod(DynamicObject::OVERLOAD_ALL, function(&$handled, $name, $arguments = null) {
            if (!$handled) {
                // handle all
                $handled = true;
                return true;
            }
            return "$name(".json_encode($arguments).")";
        }, false);

        $this->assertTrue($this->obj->_isCallable('anything really!'), 'Anything should be callable');
        $this->assertEquals('parentMethod([])', $this->obj->parentMethod(), 'Should be overshadowed');

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
        
        if (PHP_VERSION_ID >= 50400) {
            $this->obj->_addProperty('closureProperty', $this->closureProperty);
            $this->referencesTest('closureProperty');
        }
        
        $this->obj->_addGetter('callableGetterSetter', array('DynamicObjectTest', 'staticGetterSetter'));
        $this->obj->_addSetter('callableGetterSetter', array('DynamicObjectTest', 'staticGetterSetter'));
        $this->referencesTest('callableGetterSetter');

        if (PHP_VERSION_ID >= 50400) {
            $this->obj->get_closureAutoProperty = $this->obj->set_closureAutoProperty = $this->closureProperty;
            $this->referencesTest('closureAutoProperty');
        }
        
    }
 
    
    public function testUsecase_createClass() {
        if (PHP_VERSION_ID < 50400) $this->markTestSkipped('PHP 5.4 required');
        
        // create a class with a getter, one method and one property
        $myClass = new DynamicObject();
        $myClass->timeOffset = 'now';
        $myClass->_addGetter('whatTime', function() {
                return $this->format(strtotime($this->timeOffset));
            })
            ->_addMethod('format', function($value) {
                return strftime('%Y-%m-%d', $value);
            })
            ;
        
        // instantiate two class objects
        $today = clone $myClass;
        $yesterday = clone $myClass;
        $yesterday->timeOffset = "-1 day";
            
        $this->assertEquals(strftime('%Y-%m-%d'), $today->whatTime);
        $this->assertEquals(strftime('%Y-%m-%d', strtotime('-1 day')), $yesterday->whatTime);
        
    }    
    
    public function testUsecase_extendClass() {
        
        $file = new SplFileInfo(__FILE__);
        $file->getSize();
        
        $superFile = new DynamicObject($file);
        $superFile->getSize = function() use ($file) {
            return $file->getSize() * 1000;
        };
        
//        echo 'Whoa! ' . $superFile->getFilename() . ' is somewhat bigger! It has ' . $superFile->getSize();
        
        $this->assertEquals(__FILE__, $superFile->getRealPath());
        $this->assertEquals($file->getSize() * 1000, $superFile->getSize());
        
    }    
}

