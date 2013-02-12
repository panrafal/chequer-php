DynamicObject 0.1
-----------------

DynamicObject tries to give the PHP some flexibility, that users of more dynamic languages enjoy everyday.

When abused, it may bring many sorts of headaches, but on the other side, open a whole
new world of possibilities. 

For example, you can __dynamically create classes__!
```php
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

        echo "Today is {$today->whatTime}, yesterday was {$yesterday->whatTime}";

```

Or __extend existing objects__!
```php
        $file = new SplFileInfo(__FILE__);
        $file->getSize();
        
        $superFile = new DynamicObject($file);
        // overriding methods is THAT easy!
        $superFile->getSize = function() {
            return $this->_getParentObject()->getSize() * 1000;
        };
        
        echo 'Whoa! ' . $superFile->getFilename() . ' is somewhat bigger! It has ' . $superFile->getSize();
```

This is something very fresh, but well unit tested (97% coverage!). 

It's a part of **Chequer** project for now, but it probably should have it's own repository.

------------------------------------------------------

Logic
-----

You use `DynamicObjects` in the same way as every other object. You just have a lot more possibilities available.
Below are typical object operations, with ordered lists of what will happen inside the object.
You can check `\Chequer\Time` and `\Chequer\File` as real-life examples.

### Getting properties
```php
echo $object->property;
```
Will echo:
* Declared property <small>(PHP handled)</small>
* Dynamic property set with `$object->property = 'a';` <small>(PHP handled)</small>
* Anything handled by `__get()` <small>(subclass handled)</small>
* Result of getter function set with `_addGetter()` (if exists)
* Result of OVERLOAD_ALL getter function called with `callback(&$handled = true, $name)` (if exists, and &$handled is TRUE)
* Result of OVERLOAD_PREFIX getter function called with `$prefix$name($name)` (if exists)
* Parent object's property if `isset($parent->property)` is true
* null

Properties are not returned by reference, so `$object->property['foo'] = 'bar';` will not work for anything
other, than dynamic properties!

### Setting properties
```php
$object->property = 'a';
```
Will set:
* Declared or existing dynamic property <small>(PHP handled)</small>
* Anything handled by `__set()` <small>(subclass handled)</small>
* Any `Closure` will be bound to `$this`
* Call setter function set with `_addSetter()` (if exists)
* Call OVERLOAD_ALL setter function with `callback(&$handled = true, $name, $value)` (if exists, and &$handled is TRUE)
* Call OVERLOAD_PREFIX setter function with `$prefix$name($name, $value)` (if exists)
* Parent object's property (if `isset($parent->property)` is true.)
* New dynamic property

Note, that `Closures` are always bound to `$this` (the DynamicObject) - even when set on the parent object.
This way they can behave as first-class methods of the DynamicObject and still have access to the parent object.

Note also, that if the closure is set as a dynamic property it will NOT work as a getter/setter. 
`$object->someClosure = 'a';` will replace the closure by `'a'`. Any subsequent closure set this way
will also NOT be bound to `$this`. So the best way to set closures is to use `unset($object->closure); $object->closure = ...`
or `$object->addMethod()`.

If you clone the object, all closures will be bounded to the new `$this`. As this is not a trivial matter,
the current implementation may be buggy.

### Calling methods
```php
$object->method();
```
Will call:
* Existing method <small>(PHP handled)</small>
* Anything handled by `__call()` <small>(subclass handled)</small>
* Function set with `_addMethod()`
* Any `Closure` or `callable` that is the result of `$object->property`
* Call OVERLOAD_ALL method function with `callback(&$handled = true, $name, $arguments)` (if exists, and &$handled is TRUE)
* Parent object's method
* throw an exception

Anything from above list will return `true` with `isCallable()`

### Casting strings
```php
echo (string)$object;
```
Will call:
* Existing __toString() <small>(subclass handled)</small>
* Closure with the name `__toString` (which will result in "(Method '__toString' not found)" if missing)


### Checking property existence
```php
isset($object->property);
```
Will check:
* Declared and dynamic properties <small>(PHP handled)</small>
* Anything handled by `__isset()` <small>(subclass handled)</small>
* Existence of getter function set with `_addGetter()`
* Result from OVERLOAD_ALL getter called with `callback(&$handled = false, $name)`. Callback should return TRUE, and &$handled should be set to TRUE.
* Existence of OVERLOAD_PREFIX getter function with the name `$prefix$name`
* Parent object's property with `isset($parent->property)`

There is also a `isCallable()` function, that will look for anything that can be called.


### Unsetting properties
```php
unset($object->property);
```
Will unset:
* Declared and dynamic properties <small>(PHP handled)</small>
* Anything handled by `__unset()` <small>(subclass handled)</small>
* Set null with getter function set with `_addGetter()`
* Call OVERLOAD_ALL setter function with `callback(&$handled = true, $name, null)` (if exists, and &$handled is TRUE)
* Call OVERLOAD_PREFIX setter function with `$prefix$name($name, null)` (if exists)
* Parent object's property with `unset($parent->property)`

------------------------------------------------------

Callbacks
---------

You can set callbacks with `_addGetter`, `_addSetter`, `_addMethod` and `_addParameter` which combines all two/three in one.

You can provide four types of callbacks. Callback arguments are denoted as `...`:
* `string` - Will call `$this->{$name}(...)`
* `Closure` - Will call `$closure->methodname(...)`, plus Closures are always bound to `$this`.
* `[class, methodname]` - Will call `class::methodname($this, ...)`
* `[object, methodname]` - Will call `$object->methodname($this, ...)`

Arguments for callbacks are:
* For getters: ()
* For setters: (`value`)
* For methods: (method arguments)

### OVERLOAD_ALL
You can simulate __get, __set, __call, __unset and __isset by adding OVERLOAD_ALL callback.

Every call has the `$handled` reference as the first argument. 
* For get, set, unset and call `$handled` will be set to TRUE. It should be set to FALSE if handler is NOT handling this property/method.
* For isset and isCallback `$handled` will be set to FALSE. It should be set to TRUE is handler IS handling this property/method.

By using `_addGetter` you override:
* __get: callback(&$handled = TRUE, $name) = $value
* __isset: callback(&$handled = FALSE, $name) = $isSetOrNot

By using `_addSetter` you override:
* __set: callback(&$handled = TRUE, $name, $value)
* __unset: callback(&$handled = FALSE, $name, null)

By using `_addMethod` you override:
* __call: callback(&$handled = TRUE, $name, $value) = $result
* isCallback: callback(&$handled = FALSE, $name, null) = $isCallbackOrNot

A good starting point for a closure would be:

```php
    function (&$handled, $name, $value = null) {
        // check handled names
        if ($name !== ...) { 
            // we dont handle these...
            $handled = false;
            return false;
        }
        if (!$handled) {
            $handled = true;
            // check if it is set, or is callable
            return ...;
        }
        // do something usefull
        ...
    }
```

Note, that if the callback is anything other than object's method or Closure, the list of arguments will always be prepended with `$this`.

### OVERLOAD_PREFIX
You can add overloading prefix for getter and setter. This will translate all unknown parameters *and* methods
to call `prefix`+`name` functions:

```php
$o->addGetter(DynamicObject::OVERLOAD_PREFIX, 'get_');
echo $o->foo;
// in fact calls:
echo $o->get_foo();
```


------------------------------------------------------
Note on PHP 5.3
---------------

As this class uses Closure::bindTo, it's not entirely compatible with PHP5.3. However you can successfully
use it in >=5.3 environment if you stick to these simple rules:

* Don't use `$this` in closures at all. It's easy on 5.3, as it's not supported. If you need a reference 
  to the object, pass it with `use`:

  ```php
    $obj = new DynamicObject();
    $obj->foo = function() use ($obj) {
        return $obj->bar;
    };
  ```
* If you need the object at call time, because you want to create generic closures, pass the closure 
  as an array to `_setMethod`, `_setGetter`, ... This way you will always get the `$object` as a first
  argument - just like with callables.

  ```php
    $obj = new DynamicObject();
    $obj->_setMethod('foo', array(function($self) {
        return $self->bar;
    }));
  ```

------------------------------------------------------

**DynamicObject** was brought to you by [Rafał Lindemann](http://www.stamina.pl/).

<small>Copyright &copy;2013 Rafał Lindemann</small>