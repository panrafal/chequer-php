Chequer (as in Check-with-Query) 1.1
====================================

__Chequer is amazingly fast and magicaly simple.__<br/>
__As an added bonus, it matches scalars, arrays, objects and grumpy cats against the query of Your choice!__

It's only one lightweight class with one-but-powerfull function. Ok, there are more functions and classes,
but there is _the one_ that makes all the hustle.

In short - use __queries__ to __match values__. 

What Chequer does differently, is that it doesn't use any additional classes to do it's core work. It's
self contained in one file and uses only one simple class. You don't construct validation rules, 
you just pass a query in of three simple syntaxes - depending on what you need.

It's intentional - Chequer is **fast** and **simple**, and loading additional classes through factories is... well, *not*.
As an added bonus (and by design), you can use plain text (think config files, command line) to setup your validation! 
And you don't have to worry about factories and all the bloat!

But what is most important - Chequer is actually _not designed_ for validation! It simply allows to check
if something matches the query - so you *can* validate. But, it's a lot more than that! You can validate, 
check and filter almost anything - be it user input, environment variables, function results, objects, iterators, 
deep arrays, files and so on. And as the syntax is quite powerful, you can also query objects, 
call methods, convert results... Whoa!

Did I mention it's extensible - you can extend the class with your own operators, you can use
closures as checks, you can create your own value conversions and you can do it all at runtime. 
Plus it's **MIT** licensed, so share the love and contribute!

Why another validation library?
-----------------------------

Simply because - it's not a validation library! There are many others better suited for this purpose, 
but there are none (to my knowledge), which allow you to really quickly (in terms of code and execution) 
check a value - be it simple string, or a complex array.

Wait! There is more!
--------------------

Part of the package is `DynamicObject` class, which lets you __dynamically create classes__,
**modify** object's **methods** on the fly, __extend objects__ and moar! [Go check it out](/panrafal/chequer-php/blob/master/DynaminObject.md)!
It's here to make typecasting easy, but it's pretty awesome on it's own!

---------------------------------------------------------

Install
-------

Use [Composer](http://getcomposer.org/) package `stamina/chequer-php` to install.

The minimum required PHP version is 5.3. Because 5.4 introduces the shorthand array syntax - this version is recommended
and used in this documentation.

```
php composer.phar require stamina/chequer-php
```

[![Build Status](https://travis-ci.org/panrafal/chequer-php.png?branch=master)](https://travis-ci.org/panrafal/chequer-php)

---------------------------------------------------------

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
        '.getExtension()' => ['php', 'html']
        '.getMTime()' => ['$lt' => strtotime('-1 day')]
    ]));
foreach($files as $file) {}
```

---------------------------------------------------------

Chequer Query Language
----------------------
Query language is modelled a bit after MongoDB. 
At least the operators start with '$' (use single quotes or escape!) and share the same names where possible.

Chequer uses three syntaxes at the same time:
* basic comparison - very basic, see below
* [key:rule][keyrule] - very fast, based on hashmaps (mongo-like), great for key lookups
* [shorthand][shorthand] - parsed language, based on strings (sql-like), great for complex queries

Every query operation assumes, that you ask if a `value` matches the `rule`.

Whenever there is a reference to `query` it may be:
* `Chequer` - the `Chequer` object with a query
* `scalar` (`string`, `int` etc.) - the value should match the query (with type conversion - 1 == '1')
  * With an exception, that strings starting with `$` are assumed to be in [shorthand syntax][shorthand].<br/>
    To use the string, you have to prefix it with '\' (`\$tring!` will match '$tring!'). You should not
    escape any other character! If you don't use shorthand, you can turn it off entirely.
* `null` - the value should be exactly `null`
* `false` - the value should be exactly `false`
* `true` - the value should be anything `true` in PHP
* `array` - a complex query in [key:rule syntax][keyrule]
* `$string` - strings starting with `$` are complex queries in [shorthand syntax][shorthand]

### Key:rule syntax
[keyrule]: #key-rule-syntax
* `array` - a complex query with any combination of following **key** => **rule**:
    * `'$operator'` => operator's parameter <br/>
        one of special operators - ([see operators](#operators))
    * `'$'` => `bool` | `'AND'` | `'OR'`  <br/>
      `true` and `'AND'` will set this query to `AND` mode, `false` and `'OR'` will set it to `OR`
    * `string` => `query`  <br/>
      check the value's `subkey` with the `query` - ([see subkeys](#subkeys))
    * `'@typecast'` => `query`<br/>
      get the `typecast` value and check it against the `query` - ([see typecasts](#typecasts))
    * `'@typecast()'` => `query`<br/>
      convert current value using the `typecast` and check it against the `query` - ([see typecasts](#typecasts))
    * `'.subkey1.subkey2'` => `query`<br/>
      check the value's two (and more) `subkey`s deep with the `query` - ([see subkeys](#subkeys))
    * `int` => `query`  <br/>
      check the `value` with the `query`
    * `'$ shorthand'` => `query` <br/>
      [shorthand query][shorthand] which will be checked with the `query`

__Match All (AND) / Match Any (OR) in complex queries__

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

### Shorthand syntax
[shorthand]: #shorthand-syntax

Shorthand syntax is all about doing wild stuff on values, and returning the result of the operation.

This shorthand
```php
Chequer::checkValue('foo.txt', '$ @file().mtime > -1 day');
```
is equivalent to this key:rule:
```php
Chequer::checkValue('foo.txt', ['@file().mtime' => ['$gt' => '-1 day']]);
```

But this:
```php
// check if 'foo.txt' was modified around a week ago
Chequer::checkValue('foo.txt', '$ $abs(@time.now - 1 week - @file().mtime) < 1 day');
```
is doable in key:rule, but rather very hard.

The rules of shorthand are:
* Every shorthand should start with a dollar sign `$`. If first element is an operator, you can
  use it immediately. Otherwise you have to put a space:

  ```php
  '$gt 10' // ok!
  '$> 10' // ok!
  '$ .subkey > 20' // ok!
  '$ $gt 10' // ok!
  '$ 1 + 1 = 2' // ok!
  '> 10' // NOT ok!
  '$.key' // NOT ok! 
  ```
* To not use the shorthand, **escape** the first dollar with backslash `\`. `'\\$tring!'`
* There is __no operator precedence__. Query is evaluated from _left_ to _right_.
  
  This is __extremely important__ as every operation is done through operators. Including AND/OR constructs.<br/>
  `$ 1 = 1 && 2 = 2` will evaluate like `$ ((1 = 1) && 2) = 2`<br/>
  What you would want is rather this: `$ (1 = 1) && (2 = 2)`

* You can **group** operators and values with parentheses `()`.
* You can **quote** the strings with either single or double quotes. You can escape the quotes
  by using backslash `\`. Both are valid: `'this "is" ok' "this 'is' ok two!"`
* Floating point **numbers** less than 1 should be prefixed with `0`. This is ok: `$< 0.1`, 
  this is **not**: `$< .1`. Moreover, the second example will work, because you will fetch a second
  digit from the number (equivalent to `$value[1]`).
* To use **current** `value` use single dot `.`. To access the subkeys use the [dot notation][dotnotation]:

  ```php
  '$ .' = value
  '$ .key.subkey' = value['key']['subkey']
  '$ .method().key' = calls value.method()['key']
  ```
* The **strings** can be unquoted if they don't contain any special characters. 
  These words will be converted into their respectable types:

  ```php
  '$ 123' = 123;
  '$ 0.123' = 0.123;
  '$ TRUE' = true;
  '$ FALSE' = false;
  '$ NULL' = null;
  ```
* **Whitespace** between values is preserved. It's ignored before first value, after last one
  and *before* quoted strings.
  
  ```php
  '$ some text' = 'some text'
  '$ some text  +  more text' = 'some textmore text'
  '$ some .subkey text' = 'some SUBKEY text'
  '$ some.subkey text' = 'someSUBKEY text'
  '$ some(.subkey) text' = 'someSUBKEY text'
  '$ "some" .subkey text' = 'some SUBKEY text'
  '$ some( .subkey) "te""xt"' = 'someSUBKEYtext'
  '$ 1 "+" 1 + "=" 2' = '1+ 1= 2'
  ```
* Concatenation of types different then strings is undefined. Currently
numbers will be treated as strings, FALSE is not represented, TRUE is 1 and
arrays are changed to '(Array)'. This may change, so don't rely on it

  ```php
  '$    array is (1,2,3) numbers are 1 2 3 false is FALSE true is TRUE null is NULL' 
     = 'array is (Array) numbers are 1 2 3 false is  true is  null is '
  ```

* If two values follow each other with a comma `,`, they will be put into an **array**:

  ```php
  '$ 1, 2' = [1, 2]
  '$ (,)' = []
  '$ one, two, three four' = ['one', 'two', 'three four']
  '$ one, two, (three, four)' = ['one', 'two', ['three', 'four']]
  ```
* If value is immediately followed by a colon `:`, the next value will be put under that key in a **hashmap**.<br/>
  Note, that key name should not have any unquoted or unparenthesized white spaces!
  ```php
  '$ 1, two:2' = ['1', 'two' => 2]
  '$ (@time.year):"Now!"' = [2013 => 'Now!']
  '$ year @time.year:"Now!"' = ['year', 2013 => 'Now!'] // unsecured whitespace!
  '$ (year @time.year):"Now!"' = ['year 2013' => 'Now!'] // now it's ok!

  ```
* When calling mathods and typecasts you can follow exactly the same syntax. Remember to put parentheses
  directly after an identifier - without any whitespace! 

  ```php
  '$ .myMethod()' - calls myMethod()
  '$ .myMethod(1, 2, 3)' - calls myMethod(1, 2, 3)
  '$ .myMethod((1, 2, 3), 4)' - calls myMethod([1, 2, 3], 4)
  '$ @typecast()' - calls typecast([value])
  '$ @typecast(1, 2, 3)' - calls typecast([1, 2, 3])
  '$ @typecast(., 1, 2, 3)' - calls typecast([value, 1, 2, 3])
  '$ .subkey@typecast()' - calls typecast([value['subkey']])
  '$ .subkey@typecast(.)' - calls typecast([value])
  '$ @typecast(.subkey)' - calls typecast([value['subkey']])
  '$ @typecast' - calls typecast()
  ```
* The logic behind it, is to collect a `value`, an `operator` and the `parameter`.
  Afterwards call the `operator`**(** `value`, `parameter` **)** and use it's result as the `value` of the next `operator`.

  * Every query is run under a `context` - which is a `value` you are querying. The `context` stays the same
    for the whole query, so no matter how deep you are, `.` will give you the `context`.
  * You can skip the `value` at the beginning of the query, group or array index. `context` will be used as `value`.

    `$< 10` is thus equivalent to `$ . < 10`.<br/>
    `$ .method( < 10, > 10)` is the same as `$ .method( . < 10, . > 10)`
  * You can skip the `operator` - the collected `value` will be the result.
  * If there is no `parameter` but another `operator` follows, it's result will be used as the `parameter`:

    `'$ $not $regex foo'` will first evaluate `'$regex foo'` and using it's result - `'$not'`.

  Combining all this you can write `$= 10 || (= 20) || (! ~ "/\d/")` which is equivalent 
    to `$ (. = 10) || (. = 10) || (!(~ "/\d/"))`.

* If the value you are trying to access is missing, it will return null. It holds true even if
  you are trying to access a deep subkey! You can set strict mode to TRUE to throw exceptions instead.
* Operators may throw `\Chequer\BreakException` - this will exit current level with a return value
  set in the exception. This way `$or` and `$and` are made not greedy.
    
Note, that you can disable this syntax by using setShorthandSyntax(). This way, you will not have to
worry about strings starting with '$'.

------------------------------------------------------------------


### Operators

Operators start with a `$`, accept a `value`, a `parameter` and return the `result`. Some operators
have short versions (`!`, `+`, `>` ...), but they still have to be used with `$` if outside of the
shorthand syntax.

The currently available operators are:
* `$` => `true` | `1` | `'AND'`

  Enables AND mode, requiring every rule to match
* `$` => `false` | `0` | `'OR'`

  Enables OR mode, only single rule has to match
* `$and` => [`query`, `query`, ...] | `scalar`

  When array is passed, all queries will have to match. Useful in [key:rule syntax][keyrule].<br/>
  When a scalar is matched, then both `value` and `scalar` have to be true. Otherwise matching is stopped
  at this level.
  ```php
  Chequer::checkValue(FALSE, [
    '$and' => true, // value is FALSE, next rule won't be evaluated
    '$gt' => 10
  ]);
  ```

  Watch out for passing arrays in `shorthand`! This will essentialy call `operator_and([1,2,3], [1,2,3])`, which means: <br/>
  `value` = [1,2,3] must match `1`, `2` and `3`.
  ```php
  Chequer::checkValue([1,2,3], '$ . && .');
  ```

  But it may be very helpfull too. This will check if `value` = 'foobar' is `"foo"` or `"bar"` or matches
  regular expression `/foo/`!
  ```php
  Chequer::checkValue('foobar', '$ . || "foo", "bar", "$~ foo"');
  ```
* `$or` => [`query`, `query`, ...] | `scalar`

  When array is passed, only one query will have to match. Useful in [key:rule syntax][keyrule].<br/>
  When a scalar is matched, then `value` or `scalar` have to be true. If true, matching is stopped
  at this level. See examples for `$end`.
* `$not` | `$!` => `query`

  negates the `query`
* `$regex` | `$~` => '/regexp/flags' | '#regexp#flags' | '~regexp~flags' | 'regexp'

  Matches strings using regular expressions.<br/>
  With third syntax, regular expression is automatically enclosed in '~' character, so it's safe to use
  `/` without escaping.
* `$eq` => `compare`

  matches value using loose operator (==)
* `$same` => `compare`

  matches value using strict operator (===)
* `$nc` => `compare`

  not case-sensitive comparison (multibyte)
* `$gt`|`$gte`|`$lt`|`$lte` | `$>`|`$>=`|`$<`|`$<=` => `compare`

  greater-than|lower-than comparisons
* `$between` => [`lower`, `upper`]

  checks if `value` is between lower and upper bounds (inclusive)
* `$in` => [`compare`, `compare`, ...]

  checks if `value` is on the list
* `$add` | `+` => `second_value`

  Addition
  * if both values are numeric, they will be added,
  * if `second_value` is an array, it will be merged with `value`,
  * if `value` is an array, but `second_value` is not, it will be pushed,
  * otherwise it will concatenate strings
* `$sub` | `-` => `second_value`

  Substraction
  * if both values are numeric, they will be substracted,
  * if `second_value` is an array, it will be substracted from `value`,
  * if `value` is an array, but `second_value` is not, it will be removed,
  * otherwise it will remove the `second_value` from the string
* `$mult` | `*` => `second_value`

  Multiplication
  * if both values are numeric, they will be multiplicated
* `$div` | `/` => `second_value`

  Division
  * if both values are numeric, they will be divided
* `$check` => `callable`

  matches if callable($value) returns TRUE
* `$size` => `query`

  checks the size of array or string using the `query`
* `$rule` => [`rulename1`,`rulename2`] | '`rulename1` `rulename2`'

  allows to reuse predefined rules, which you can set with addRules().
  You can specify many rules as an array, or space delimited string. 

  If you want to match any of the rules, place `OR` as one the rule names.

  If you wan't a rule to NOT match, prepend it with '!'

  ```php
  $checker->query(..., '$rule email lowercase');
  $checker->query(..., '$rule email AND lowercase'); // this is equivalent to the former
  $checker->query(..., '$rule email OR lowercase'); // email or lowercase
  $checker->query(..., '$rule "email OR !lowercase"'); // email or not lowercase. We have to quote it because of '!'
  ```
  

* `$eval` => [`$query`, `query`, ...]

  Evaluates every query by passing the `result` as the next query's `value`.

  ```php
  // the result is 3:
  Chequer::checkValue(1, [
    ['$eval' => ['$add' => 1, '$add' => 1]]
  ]);
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

---------------------------------------------------------

Note, that the whole idea is very fresh. I've come up with the concept on January 29th, and made the lib the same day. <br/>
And that means - it *will* change!

&copy;2013 Rafal Lindemann

[![githalytics.com alpha](https://cruel-carlota.pagodabox.com/b0780748041204c1d29e52c80d852fa1 "githalytics.com")](http://githalytics.com/panrafal/chequer-php)
