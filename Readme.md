Chequer 2.0
===========

__THE SPARKLING NEW LANGUAGE FOR CHECKING THINGS IN A SANE WAY__<br />
Match scalars, arrays, objects and grumpy cats against the query of Your choice!

__Checkout the [chequer.stamina.pl](http://chequer.stamina.pl/) for more information__!


Oh wait! There is more!
--------------------

Part of the package is `DynamicObject` class, which lets you __dynamically create classes__ in PHP,
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

Usage and examples
------------------

# Usage

There are a couple of usage patterns to choose from.

## Simple checks

For simple checks use static function `checkValue()`
```php
if (Cheque::checkValue($value, $query)) {}
```

## Reusing the query

When you want to reuse your query, or pass it somewhere as a callback, create the object and call `check` method,
or invoke the object.
```php
// build the query object
$chequer = new Chequer($query);

// use it witch check()
if ($chequer->check($value)) {}

// or invoke it as a function
if ($chequer($value)) {}

// or pass it as a callback
array_filter($array, $chequer);
```

## Global configuration

You can store all your queries in configuration files and use them when they are needed. This way you can separate
your validation/filtering logic from your code - just like you do with the templates!

```php
// load the rules from the JSON file
Chequer::addGlobalRules(json_decode(file_get_contents('queries.json'), JSON_OBJECT_AS_ARRAY));

// reuse them
if (Chequer::checkValue($value, ['$rule' => 'some_defined_rule'])) {}
```

As every `query` is a `scalar` or an `array` - they can be easely stored in JSON, YAML, MongoDB - you name it.

## Dependency injection

If you rather prefer DI - fret not. You can add rules to `Chequer` objects directly, which means you can
make a factory, or pass the `Chequer` object around and still populate it with predefined rules.

The above example rewritten as Silex factory:

```php
// load the queries once
$app['chequer.rules'] = $app->share(function() {
        return json_decode(file_get_contents('queries.json'), JSON_OBJECT_AS_ARRAY);
    });
// always have a fresh chequer on hand
$app['chequer'] = function() use ($app) { 
    return (new Chequer())->addRules($app['chequer.rules']);
};

// reuse
if ($app['chequer']->query($value, ['$rule' => 'some_defined_rule'])) {}
```

---------------------------------------------------------

Note, that the whole idea is very fresh. I've come up with the concept on January 29th, and made the lib the same day. <br/>
And that means - it *will* change!

&copy;2013 Rafal Lindemann

[![githalytics.com alpha](https://cruel-carlota.pagodabox.com/b0780748041204c1d29e52c80d852fa1 "githalytics.com")](http://githalytics.com/panrafal/chequer-php)
