Dynamic Object
--------------

Dynamic objects try to give the PHP some flexibility, that users of more dynamic languages enjoy.

It's a part of **Chequer** project for now, but it probably should have it's own repository.

## Logic

### Getting properties
```php
echo $object->property;
```
Will echo:
* Declared property <small>(PHP handled)</small>
* Dynamic property set with `$object->property = 'a';` <small>(PHP handled)</small>
* Anything handled by `__get()` <small>(subclass handled)</small>
* Result of getter function set with `addGetter()`
* Result of prefixed getter function set with `addGetter('*', 'prefix')` (if exists)
* Parent object's property if `isset($parent->property)` is true
* null

Properties are returned by reference, so `$object->property['foo'] = 'bar';` will work!

### Setting properties
```php
$object->property = 'a';
```
Will set:
* Declared or existing dynamic property <small>(PHP handled)</small>
* Anything handled by `__set()` <small>(subclass handled)</small>
* Result of setter function set with `addSetter()`
* Result of prefixed setter function set with `addSetter('*', 'prefix')` (if exists)
* Parent object's property if `isset($parent->property)` is true
* Closures will be bound to `$this` and set as dynamic property
* New dynamic property

### Calling methods
```php
$object->method();
```
Will call:
* Existing method <small>(PHP handled)</small>
* Anything handled by `__call()` <small>(subclass handled)</small>
* Function set with `addMethod()`
* Any `Closure` or `callable` that is the result of `$object->property`
* Parent object's method if `method_exists($parent, $method)` is true
* throw an exception


### Casting strings
```php
echo (string)$object;
```
Will call:
* Existing __toString() <small>(PHP handled)</small>
* Closure with the name `__toString` (which will throw an exception if missing)


### Checking property existence
```php
isset($object->property);
```
Will check:
* Declared and dynamic properties <small>(PHP handled)</small>
* Anything handled by `__isset()` <small>(subclass handled)</small>
* Existence of getter function set with `addGetter()`
* Existence of prefixed getter function set with `addGetter('*', 'prefix')` (if exists)
* Parent object's property with `isset($parent->property)`

There is also a `isCallable()` function, that will look for anything that can be called.


### Unsetting properties
```php
unset($object->property);
```
Will unset:
* Declared and dynamic properties <small>(PHP handled)</small>
* Anything handled by `__unset()` <small>(subclass handled)</small>
* Getter function set with `addGetter()`
* Parent object's property with `unset($parent->property)`

