Dynamic Object
--------------

Dynamic objects try to give the PHP some flexibility, that users of more dynamic languages enjoy.

Remember, that it can bring many sorts of headaches, but on the other side, may be very usable in
some situations. For example, you can dynamically create classes or extend existing objects!

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
* Result of getter function set with `addGetter()` (if exists)
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
* Any `Closure` will be bound to `$this`
* Call setter function set with `addSetter()` (if exists)
* Call prefixed setter function set with `addSetter('*', 'prefix')` (if exists)
* Parent object's property (if `isset($parent->property)` is true.)
* New dynamic property

Note, that `Closures` are always bound to `$this` (the DynamicObject) - even when set on the parent object.

Note also, that if the closure is set as a dynamic property it will NOT work as a getter/setter. 
`$object->someClosure = 'a';` will replace the closure by `'a'`. Any subsequent closure set this way
will also NOT be bound to `$this`. So the best way to set closures is to use `unset($object->closure); $object->closure = ...`
or `$object->addMethod()`.

### Calling methods
```php
$object->method();
```
Will call:
* Existing method <small>(PHP handled)</small>
* Anything handled by `__call()` <small>(subclass handled)</small>
* Function set with `addMethod()`
* Any `Closure` or `callable` that is the result of `$object->property`
* Parent object's method
* throw an exception

Anything from above list will return `true` with `isCallable()`

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
* Set null with getter function set with `addGetter()`
* Set null with prefixed getter function set with `addGetter('*', 'prefix')` (if exists)
* Parent object's property with `unset($parent->property)`

