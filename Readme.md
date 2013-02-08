Chequer (like in check-and-query)
=================================

__Chequer is amazingly fast and magicaly simple.__<br/>
__As an added bonus, it matches scalars, arrays, objects and grumpy cats against the query of Your choice!__

It's only one lightweight class with one-but-powerfull function. Ok, there are more functions, but there
is _the one_, that makes all the fuss.

In short - use __queries__ to __match values__. 

What Chequer does differently, is that it doesn't use any additional classes to do it's core work. It's
self contained in one file and uses only one simple class.
It's intentional - Chequer is **fast** and **simple**, and loading additional classes through factories is... well, *not*.
As an added bonus (and by design), you can use plain text config files to setup your validation, and don't have to worry
about factories and all the bloat.

But what is most important - Chequer is actually _not designed_ for validation! It simply allows to check
if something matches the query - so you *can* validate. But, it's a lot more than that! You can validate, 
check and filter almost anything - be it user input, environment variables, function results, objects, iterators, 
deep arrays, files and so on.

It's also extensible - you can extend the class with your own operators, and you can use
closures as checks. Plus it's **MIT** licensed, so share the love and contribute!

Why another validation library?
-----------------------------

Simply because - it's not a validation library! There are many others better suited for this purpose, 
but there are none (to my knowledge), which allow you to really quickly (in terms of code and execution) 
check a value - be it simple string, or a complex array.

Install
-------

Use [Composer](http://getcomposer.org/) package `stamina/chequer-php` to install.

The minimum required PHP version is 5.3. Because 5.4 introduces the shorthand array syntax - this version is recommended
and used in this documentation.

```
php composer.phar require stamina/chequer-php
```

[![Build Status](https://travis-ci.org/panrafal/chequer-php.png?branch=master)](https://travis-ci.org/panrafal/chequer-php)

Usage
-----

For simple checks use 
```php
if (Cheque::checkValue($value, $query)) {}
```

When you want to reuse your query, or pass it somewhere as a callback, create the object and call `check` method,
or invoke the object like this:
```php
$cheque = new Cheque($query);
if ($cheque->check($value)) {}
// or
if ($cheque($value)) {}
```

### Environment variables checking

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

### Array filtering

This will leave only values from 2 to 5 in the array.
```php
$array = [1, 2, 3, 4, 5, 6];
$array = array_filter($array, new Chequer(['$between' => [2, 5]]))
```

### Iterator filtering

This will iterate through files with 'php' or 'html' extensions, that are older than one day.
```php
$files = new FilesystemIterator(dirname(__DIR__));
$files = new CallbackFilterIterator($files, new Chequer([
        'getExtension()' => ['php', 'html']
        'getMTime()' => ['$lt' => strtotime('-1 day')]
    ]));
foreach($files as $file) {}
```

Chequer Query Language
--------------
Query language is modelled a bit after MongoDB. 
At least the operators start with '$' (use single quotes or escape!) and share the same names.

A `query` can be:
* `Chequer` - the `Chequer` object with a query
* `scalar` (`string`, `int` etc.) - the value should match the query (with type conversion - 1 == '1')
* `null` - the value should be exactly `null`
* `false` - the value should be exactly `false`
* `true` - the value should be anything `true` in php
* `array` - a complex query with any combination of following **key** => **rule**:
    * `$operator` => operator's parameter <br/>
        one of special operators - ([see operators](#operators))
    * '$' => `bool` | `'AND'` | `'OR'`  <br/>
      `true` and `'AND'` will set this query to `AND` mode, `false` and `'OR'` will set it to `OR`
    * `string` => `query`  <br/>
      check the value's `subkey` with the `query` - ([see subkeys](#subkeys))
    * `@typecast` => `query`<br/>
      get the `typecast` value and check it against the `query` - ([see typecasts](#typecasts))
    * `@typecast()` => `query`<br/>
      convert current value using the `typecast` and check it against the `query` - ([see typecasts](#typecasts))
    * `.subkey1.subkey2` => `query`<br/>
      check the value's two (and more) `subkey`s deep with the `query` - ([see subkeys](#subkeys))
    * `int` => `query`  <br/>
      check the value with the `query`

With shorthand syntax enabled, which is ON by default, you can also use:
* `$operator rule` - it's the same as using `['$operator' => 'rule']` <br/>
    Note that you can stack the rules, if preceding operator accepts them. `'$not $regex /foo/'` will not match "foo"!
* `$.subkey string` - it's the same as using `['.subkey' => 'rule']`
* `$ string` - shorthand syntax escaping, the value should equal just the `string` -without the `$ ` prefix


### Match All (AND) / Match Any (OR) in complex queries

By default, every rule in a query should match. This is the `AND` mode. Queries that match a simple
scalar will default to `OR`.

The mode is **not** carried over to subqueries.

When calling `check` you can specify the first level's mode in $matchAll parameter.

You can also use '$' key to change the mode, or use `$or`/`$and` operators.

Consider these examples
* *AND* `['$regex' => 'foo', '$not' => 'foobar']`
* *OR* `['foo', 'bar']` because it's an array of scalars
* *OR* `['foo', '$regex' => 'bar']` because element with index 0 is a scalar
* *OR* `['$' => 'OR', '$regex' => 'foo', '$not' => 'foobar']` because of `'$'=>'OR'`
* *OR* `['$or' => ['$regex' => 'foo', '$not' => 'foobar']]` because of `$or`

### Operators

The currently available operators are:
* `$` => `true` | `1` | `'AND'` <br/>
  Enables AND mode, requiring every rule to match
* `$` => `false` | `0` | `'OR'` <br/>
  Enables OR mode, only single rule has to match
* `$and` => [`query`, `query`, ...]  <br/>
  matches all queries
* `$or` => [`query`, `query`, ...]  <br/>
  matches any query
* `$not` | `$!` => `query`  <br/>
  negates the `query`
* `$regex` | `$~` => '/regexp/' | '#regexp#' | 'regexp' <br/>
  Matches strings using regular expressions.<br/>
  With third syntax, regular expression is automatically enclosed in '#' character, so it's safe to use
  `/` in the expression.
* `$eq` => `compare`  <br/>
  matches value using loose operator (==)
* `$same` => `compare`  <br/>
  matches value using strict operator (===)
* `$nc` => `compare`  <br/>
  not case-sensitive comparison (multibyte)
* `$gt`|`$gte`|`$lt`|`$lte` | `$>`|`$>=`|`$<`|`$<=` => `compare` <br/>
  greater-than|lower-than comparisons
* `$between` => [`lower`, `upper`] <br/>
  checks if value is between lower and upper bounds (inclusive)
* `$check` => `callable` <br/>
  matches if callable($value) returns TRUE
* `$size` => `query` <br/>
  checks the size of array or string using the `query`
* `$rule` => [`rulename1`,`rulename2`] | '`rulename1` `rulename2`' <br/>
  allows to reuse predefined rules, which you can set with addRules().
  You can specify many rules as an array, or space delimited string. 

  If you want to match any of the rules, place `OR` as one the rule names:
  ```php
  $checker->query(..., '$rule email lowercase');
  $checker->query(..., '$rule email AND lowercase'); // this is equivalent to the former
  $checker->query(..., '$rule email OR lowercase');
  ```
* `$cmp` => [`value1`, `operator`, `value2`] | '`value1` `operator` `value2`'<br/>
  compares two `values` using the `operator`. Values should be subkeys provided in dot notation.<br/>
  Value of `value2` will be passed to operators as-is. This means it can be used as a query!
  ```php
  '$cmp .subkey > .anothersubkey'`
  ```

  Please note, that both values are subkeys! You can use single dot (`.`) to reference current value,
  or `typecast`s, but you cannot use plain strings! `'$cmp > 20'` will look for 20th element in an
  array. To compare to arbitrary values use the operator in normal way - `$> 20`. 

  To use the current value, skip the `value1`:
  ```php
  ['.subkey' => '$cmp > .anothersubkey']
  ```

  You can skip the first value and the operator, to use the value of `value2` as a query:
  ```php
  // this is equivalent to `Chequer::checkValue('foo', ['foo', 'bar']);`
  (new Chequer(['$cmp' => '@example']))
      ->addTypecasts('example' => ['foo', 'bar'])
      ->check('foo');

  // this is equivalent to `Chequer::checkValue('foo', '$~ .*foo.*');`
  (new Chequer(['$cmp' => '@example']))
      ->addTypecasts('example' => ['$regex' => '$~ .*foo.*'])
      ->check('foo');
  ```



### Subkeys

Subkey can be:
* array's key 
* object's property
* object's method with '()' suffix <br/>
  `Chequer::checkValue(new SplFileInfo(), ['getSize()' => ['$gt' => 0]])`

You can access multiple subkeys at once by using the `dot notation`. You have to start with a `.` (dot)
and join subkeys with a dot like this: `.subkey.method().another_one`. To reference the value itself,
use the `.` itself. <br/>
If you have a subkey with a dot, use standard notation without the `.` prefix, like this: `subkey.with.a.dot`.

If the subkey does not exist in the value, and the value is an 0-indexed array, Chequer will traverse this
array in search for the first array/object having this key. You can control this behaviour by using
`setDeepArrays()`. Note, that two different queries may match in two different subitems.

Like here:
```php
Chequer::checkValue([
    'foo' => 'bar', 
    ['some' => 'thing'],
    ['hello' => 'world'],
    ['hello' => 'bye']
], ['foo' => true, 'some' => true, 'hello' => true]);
```

We are looking for 'foo', 'some' and 'hello' keys, but the 'hello' and 'some' are defined inside the subitems. 
However, they *will* be discovered, because the array has continuous keys starting from 0. 

Note however, that `['hello' => 'bye']` will not match, because the first element takes the precedence.

### Typecasts

Typecasts are special objects which you can read data from and convert values into. They act like a
regular subkeys, and can be accessed using dot notation (`.@typecast.subkey`) or plain notation (`@typecast`).

There are two ways to use a typecast:
- With `@typecast`, you can use the object itself. For example, `@time` will simply return current time
  as a \Chequer\Time object.

  This works by returning the value for user-provided typecasts. <br/>
  For built-in one's, the typecast_*() function will be called without any arguments.
- With `@typecast()`. you can convert current value into another object, hence the word 'typecast'. 
  For example, `@time()` will try to convert current value into \Chequer\Time object.

  This works by calling the value for user-provided typecasts, so they should be callable, but don't have to.<br/>
  For built-in one's, the typecast_*() function will be called with the current value as a first argument.

Typecasts can be really powerfull:
```php
$chequer = new Chequer();
// store the reference to the myFile
$chequer->addTypecasts([
            'myFile' => 'myfile.txt', 
            'myFunc' => function($file = null) {return rand(0,100);}
        ])
        ->setQuery([
            // convert SplFileinfo into Chequer\File. Note the brackets.
            '@file()' => [ 
                /* File's modification time should be newer then on 'myfile.txt'.
                   Note the lack of brackets on @myFile - we are using myFile's value
                   and then we convert it into a Chequer\File - by using the brackets.
                */
                '$cmp' => ['mtime', '>', '.@myFile.file().mtime'],
                /* File should be modified in the current year. 
                   Note the lack of brackets - we are using the current time's value.
                   We also use a shorthand syntax for $cmp.
                */
                '$cmp' => '.mtime.year = .@time.year',
                /* We call the myFunc typecast. The result should be grater than 50 */
                '@myFunc()' => '$> 50'
                /* As myFunc is a closure, we can skip the brackets. It will be called nevertheless. */
                '@myFunc' => '$> 50'
            ]
        ]);

$files = new CallbackFilterIterator($new FilesystemIterator(dirname(__DIR__)), $chequer);
foreach($files as $file) {
    // only files matching the criteria will be listed here!
    echo $file->getFilepath();
}


```

#### Available typecasts:
* `@file`
* `@time`

### Extending

Simply define protected function with the name operator_*

To define the `$true` operator:
```php
protected function operator_true($value, $rule) {
    return true;
}
```

Or you can add the operator/alias to the $operators parameter.


Note, that the whole idea is very fresh. I've come up with the concept on January 29th, and made the lib the same day. <br/>
And that means - it *will* change!

&copy;2013 Rafal Lindemann

[![githalytics.com alpha](https://cruel-carlota.pagodabox.com/b0780748041204c1d29e52c80d852fa1 "githalytics.com")](http://githalytics.com/panrafal/chequer-php)
