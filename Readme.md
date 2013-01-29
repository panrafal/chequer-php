Chequer (like in check-and-query)
=================================

__Chequer is magicaly fast and amazingly simple. As an added bonus, it validates scalars, arrays, objects and grumpy cats!__

It's only one lightweight class with one-but-powerfull function.
In short - use MongoDB-like queries to match input values. 

What Chequer does differently, is that it does'nt use any additional classes to do it's work. It's
self contained in one file and uses only one simple class.

It's intentional - Chequer is **fast** and **simple**, and loading additional classes through factories is... well, *not*.
As an added bonus (and by design), you can use plain text config files to setup your validation, and don't have to worry
about factories and all the bloat.

But what is most important - chequer is actually not designed for validation! It simply allows to check
if something matches the query - so you *can* validate. But, it's main purpose is to check stuff,
whether it's user input, environment variables, function results, objects etc.

It's also extensible - you can extend the class with your own operands, and you can use
closures as checks.

Why another validation library?
-----------------------------

Simply because - it's not a validation library :) There are many others better suited for this purpose, 
but there are none (to my knowledge), which allow you to really quickly (in terms of code and execution) 
check a value - be it simple string, or a complex array.

Usage
-----

This example will match all users from *localhost*, plus those with IP starting from *'192'* and having a *'debug'* cookie.

Btw., the `checkEnvironment` function is a neat shortcut for checking _SERVER, _ENV, _COOKIES etc. in one go.
```php
if (Chequer::checkEnvironment([
    ['REMOTE_ADDR' => ['127.0.0.1', 'localhost']],
    ['REMOTE_ADDR' => ['$regex' => '/^192\./'], '_COOKIE' => ['debug' => true]]
], false)) {
    echo 'Debug!';
}
```

Query language
--------------
Query language is modelled a bit after MongoDB. 
At least the operands start with '$' (use single quotes or escape!) and share the same names.

A `query` can be:
* `scalar` (`string`, `int` etc.) - the value should match the query (with type conversion - 1 == '1')
* `null` - the value should be exactly `null`
* `false` - the value should be exactly `false`
* `true` - the value should be anything `true` in php
* `array` - a complex query with any combination of following **key** => **rule**:
    * `$operand` => operand's parameter 
      one of special operands (see below)
    * '$' => `bool` 
      `true` will set this query to `AND` mode, `false` will set it to `OR`
    * `string` => `query` 
      check the value's subkey with the `query` (see below)
    * `int` => `query` 
      check the value with the `query`

### Match All (AND) / Match Any (OR) in complex queries

By default, every rule in a query should match. This is the `AND` mode. Queries that match a simple
scalar will default to `OR`.

The mode is **not** carried over to subqueries.

When calling `check` you can specify the first level's mode in $matchAll parameter.

You can also use '$' key to change the mode, or use `$or`/`$and` operands.

Consider these examples
* *AND* `['$regex' => 'foo', '$not' => 'foobar']`
* *OR* `['foo', 'bar']`
* *OR* `['foo', '$regex' => 'bar']`
* *OR* `['$' => false, '$regex' => 'foo', '$not' => 'foobar']`
* *OR* `['$or' => ['$regex' => 'foo', '$not' => 'foobar']]`

### Operands

* `$and` => [`query`, `query`, ...] - matches all queries
* `$or` => [`query`, `query`, ...] - matches any query
* `$not` => `query` - matches if `query` results to false
* `$regex` => '/regexp/' - matches strings using regular expressions
* `$eq` => value - matches value using strict operand (===)
* `$call` => callable - matches if callable($value) returns TRUE

### Subkeys

Subkey can be an array's key or object's property. 

If it does not exist in the value, and the value is an 0-indexed array, Chequer will traverse this
array in search for the first array/object having this key.

Like here:
```php
Chequer::checkValue([
    'foo' => 'bar', 
    ['some' => 'thing'],
    ['hello' => 'world'],
    ['hello' => 'bye']
], ['foo' => true, 'hello' => true]);
```

We are looking for both 'foo' and 'hello' keys, but the 'hello' is defined inside the array. However
it will be discovered, because the array has continuous keys starting from 0.

Note however, that `['hello' => 'bye']` will not match, because the first element takes the precedence.

### Extending

Simply define protected function with the name checkOperand*

To define the `$true` operand:
```php
protected function checkOperandTrue($value, $rule) {
    return true;
}
```


&copy;2013 Rafal Lindemann
