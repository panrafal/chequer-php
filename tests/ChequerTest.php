<?php

require_once __DIR__ . '/../Chequer.php';

class ChequerTest_Object {
    function test($val = 'test') {
        return $val;
    }
}

class ChequerTest extends PHPUnit_Framework_TestCase {

    protected $data;

	protected function setUp() {
        $this->data =
                array(
                    'foo' => 'bar',
                    'array' => array(1, 2, 3),
                    'hashmap' => array('one' => 'ONE', 'two' => 'TWO', 3, 'sub' => array(1, 2, 'foo' => 'BAR')),
                    'object' => new ChequerTest_Object(),
                    'number' => 1,
                    'subkey' => 'SUBKEY',
                    array(
                        'hello' => 'World!',
                    ),
                    array(
                        'hello' => 'obscured'
                    )
        );
    }


    protected function tearDown() {
        
    }


    /** @return Chequer */
    public function buildChequer( $rules = null, $matchAll = null ) {
        return new Chequer($rules, $matchAll);
    }

    
    /**
     * @dataProvider checkOperatorsProvider
     */
    public function testCheckOperators( $expected, $data, $rules ) {
        if (is_string($expected)) $this->setExpectedException($expected);
        $chequer = $this->buildChequer($rules);
        $this->assertEquals($expected, $chequer->check($data));
    }

    
    
    public function checkOperatorsProvider() {
        return array(
            'true' => array(true, 1, '1'),
            'false' => array(false, 1, 'blah'),
            'not-false' => array(false, 1, '$not 1'),
            'not-true' => array(true, 1, '$not blah'),
            
            'eq-true' => array(true, 1, array('$eq' => 1)),
            'eq-true2' => array(true, 1, array('$eq' => '1')),
            'eq-true3' => array(true, 1, array('$eq' => '01')),
            'same-true' => array(true, 1, array('$same' => 1)),
            'same-false' => array(false, 1, array('$same' => '1')),
            'same-short-false' => array(false, 1, '$== 1'),
            
            'cmp3' => array(true, array('one' => 1, 'two' => 2), array('$cmp' => '.two > .one')),
            // checks if size of the array is 2
            'cmp2' => array(true, array('one' => 1, 'two' => 2), array('$cmp' => 'size .two')),
            'cmp2-dollar' => array(true, array('one' => 1, 'two' => 2), array('$cmp' => '$size .two')),
            // checks if array(1,2) contains value 2
            'cmp-dollar' => array(true, array(1, 2), array('$cmp' => '.1')),
            // checks if array(1,2) equals itself
            'cmp-dollar' => array(true, array(1, 2), array('$cmp' => '.')),
            );
    }    
    
    
    
    /**
     * @group dev
     * @dataProvider checkShorthandProvider
     */
    public function testCheckShorthand( $query, $expected = true, $value = null ) {
        if ($expected instanceof Exception) $this->setExpectedException(get_class($expected));
        if ($value === null) $value = $this->data;
        $chequer = $this->buildChequer();
        $chequer->setStrictMode(true);
        $this->assertEquals($expected, $chequer->shorthandQuery($value, $query));
    }

    
    public function checkShorthandProvider() {
        $array = array(
            array('$ 1', 1),
            array('0.1', 0.1),
            array('TRUE', true),
            array('FALSE', false),
            array('NULL', null),
            array('true', 'true'),
            array('false', 'false'),
            array('null', 'null'),
            array('foo bar', 'foo bar'),
            array('$ foo bar', 'foo bar'),
            array('$   foo bar', 'foo bar'),
            array('foo1 bar 2', 'foo1 bar 2'),
            array('\'this "is" ok\' " this \'is\' ok two!"', 'this "is" ok this \'is\' ok two!'),
            array('.', 123, 123),
            
            array('some text', 'some text'),
            array('some text  +  more text', 'some textmore text'),
            array('some .subkey text', 'some SUBKEY text'),
            array('some.subkey text', 'someSUBKEY text'),
            array('some(.subkey) text', 'someSUBKEY text'),
            array('"some" .subkey text"', 'some SUBKEY text'),
            array('some( .subkey) "te" "x""t"', 'someSUBKEYtext'),
            array('1 "+" 1 + "=" 2', '1+ 1= 2'),
            
            array('1, 2', array(1, 2)),
            array('one, two, three four', array('one', 'two', 'three four')),
            array('one, two, (three, four)', array('one', 'two', array('three', 'four'))),
            array('array is (1, 2, 3) numbers are 1 2 3 false is FALSE true is TRUE null is NULL', 
                  'array is (Array) numbers are 1 2 3 false is  true is  null is '),

            array('()', null, null),
            array('(,)', array()),
            array('(,NULL)', array(null)),
            array('1,(1+1),(,1+2)', array(1,2,array(3))),
            array('1,1+1,(,1+2),1 ! > 2, 1 > 2', array(1,2,array(3), true, false)),
            
            
//            array('one two (three, four) five six (seven) (eight, nine)', array('one two', array('three', 'four'), 'five six seven', array('eight', 'nine'))),
//            array('zero (one, two) three four (five, six) seven eight', array(
//                'zero', array('one', 'two'), 'three four', array('five', 'six'), 'seven eight')
//            ),
//            array('(one, two), three four (five, six) seven eight', array(
//                array('one', 'two'), 'three four', array('five', 'six'), 'seven eight')
//            ),
            
            array('1, two:2', array(1, 'two' => 2)),
            array('1 2, 3, four:4, five:5 (six:6) four:FOUR', array(
                '1 2', 3, 'four' => 'FOUR', 'five' => 5, array('six' => 6)
                )),
            array('(@time.year):"Now!"', array(intval(strftime('%Y')) => 'Now!')),
            array('year @time.year:"Now!"', array('year', intval(strftime('%Y')) => 'Now!')), // unsecured whitespace!
            array('(year @time.year):"Now!"', array(strftime('year %Y') => 'Now!')), // now it's ok!            
            
//            array('.myMethod()' - calls myMethod()
//            array('.myMethod(1, 2, 3)' - calls myMethod(1, 2, 3)
//            array('.myMethod((1, 2, 3), 4)' - calls myMethod(array(1, 2, 3), 4)
//            array('@typecast()' - calls typecast(array(value))
//            array('@typecast(1, 2, 3)' - calls typecast(array(1, 2, 3))
//            array('@typecast(., 1, 2, 3)' - calls typecast(array(value, 1, 2, 3))
//            array('.subkey@typecast()' - calls typecast(array(valuearray('subkey')))
//            array('.subkey@typecast(.)' - calls typecast(array(value))
//            array('@typecast(.subkey)' - calls typecast(array(valuearray('subkey')))
//            array('@typecast' - calls typecast()            
            
            array('1  +  1', 2),
            array('1+(2*2)', 5),
            array('1+2*2', 6),
            array('5+(5+(2*2.5))+5*4', 80),
            
            array('1 = 1'),
            array('1 < 2'),
            array('2 >= 2'),
            
            array('1  +  1 = 2'),
            array('1+(2*2) = 5'),
            array('1+2*2 = 6'),
            array('5+(5+(2*2.5))+5*4 = 80'),
          
            // escaping
            
            // bad syntax
            
            // and/or breaking
            
        );
        $result = array();
        foreach($array as $item) $result[$item[0]] = $item;
        return $result;
    }     
    

    /**
     * @dataProvider checkArrayProvider
     */
    public function testCheckArray( $expected, $rules, $matchAll = null, $data = null ) {
        if (func_num_args() < 4) $data = $this->data;
        if (is_string($expected)) $this->setExpectedException($expected);
        $chequer = $this->buildChequer($rules, $matchAll);
        $this->assertEquals($expected, $chequer->check($data));
    }


    public function checkArrayProvider() {
        return array(
            'scalar_scalar-true' => array(true, 'foo', null, 'foo'),
            'scalar_scalar-false' => array(false, 'bar', null, 'foo'),
            'array_scalar-true' => array(true, 'foo', null, array('foo', 'bar')),
            'array_scalar-false' => array(false, 'baz', null, array('foo', 'bar')),
            'hashmap-true' => array(true, array('foo' => 'bar')),
            'hashmap-false' => array(false, array('foo' => 'baz')),
            'hashmap_union-true' => array(true, array('hello' => 'World!')),
            'hashmap_union-false' => array(false, array('hello' => 'obscured')),
            'hashmap_missing' => array(false, array('missing' => true)),
            'hashmap_missing-true' => array(true, array('missing' => null)),
            'hashmap_missing-false' => array(false, array('missing' => false)),
            'hashmap_missing-array' => array('Exception', array('missing' => array('some' => 'test'))),
            'hashmap_exists' => array(true, array('foo' => true)),
            'hashmap_sub-true' => array(true, array('hashmap' => array('one' => 'ONE'))),
            'hashmap_sub-false' => array(false, array('hashmap' => array('missing' => 1))),
            'hashmap_sub-short-true' => array(true, array('.hashmap.one' => 'ONE')),
            'hashmap_subsub-short-true' => array(true, array('.hashmap.sub.foo' => 'BAR')),
            'hashmap_subsub-short-false' => array(false, array('hashmap.sub.foo' => 'BAR')),
            
            'array_array-true' => array(true, array('array' => array(1,2,4))),
            'array_array-false' => array(false, array('array' => array(4,5))),
            
            'regex-true' => array(true, array('foo' => array('$regex' => '/bar/'))),
            'regex-false' => array(false, array('foo' => array('$regex' => '/baz/'))),
            'regex-short-true' => array(true, array('$regex' => 'bar'), false, 'foobar'),
            'regex_array' => array('Exception', array('hashmap' => array('$regex' => '/[A-Z]+/'))),
            
            
            'and' => array(true, array('foo' => 'bar', 'number' => 1)),
            'and-false' => array(false, array('foo' => 'bar', 'number' => 2)),
            'and_param' => array(true, array('foo' => 'bar', 'number' => 1), true),
            
            'or_param' => array(true, array('foo' => 'bar', 'number' => 1), false),
            'or2_param' => array(true, array('foo' => 'bar', 'number' => 2), false),
            
            'or_switch' => array(true, array('$' => false, 'foo' => 'bar', 'missing' => true)),
            'or_switch2' => array(true, array('$' => 'OR', 'foo' => 'bar', 'missing' => true)),
            'or_group' => array(true, array('$or' => array('foo' => 'bar', 'missing' => true))),
            'or_auto' => array(true, array('something', 'foo' => 'bar', 'missing' => true)),
            
            'and_force' => array(false, array('something', 'foo' => 'bar', 'missing' => true), true),
            'and_switch' => array(false, array('$' => 'AND', 'foo' => 'bar', 'missing' => true), true),
            'and_switch-true' => array(true, array('$' => 'AND', 'foo' => 'bar', 'missing' => null), false),
            'and_switch2' => array(false, array('$' => true, 'foo' => 'bar', 'missing' => true), true),
            
            'gt' => array(false, array('number' => array('$gt' => 1))),
            'gte' => array(true, array('number' => array('$gte' => 1))),
            'nc' => array(true, array('$nc' => 'FooBar'), false, 'fooBAR'),
            'nc-false' => array(false, array('$nc' => 'FooBaZ'), false, 'fooBAR'),
            
            'between' => array(true, array('number' => array('$between' => array(1, 2)))),
            'between-false' => array(false, array('number' => array('$between' => array(2, 3)))),
            'between-fail' => array('Exception', array('number' => array('$between' => 1))),
            
            'size-string' => array(true, array('foo' => array('$size' => 3))),
            'size-array' => array(true, array('array' => array('$size' => 3))),
            
            'check' => array(true, array('foo' => array('$check' => function($v) {return $v == 'bar';}))),
                    
        );
    }


    
    /**
     * @dataProvider checkTypecastsProvider
     */
    public function testCheckTypecasts( $expected, $data, $typecasts, $rules ) {
        if (is_string($expected)) $this->setExpectedException($expected);
        $chequer = $this->buildChequer($rules);
        $chequer->addTypecasts($typecasts);
        $this->assertEquals($expected, $chequer->check($data));
    }

    
    public function checkTypecastsProvider() {
        $closure = function($a = null) {return $a . 'bar';};
        $closureArray = function($a = null) {return array('foo' => 'bar', 'bar' => $a);};
        return array(
            'value' => array(true, false, array('test' => 'foo'), '$.@test foo'),
            'notcallable' => array('Exception', false, array('test' => 'foo'), '$.@test() foo'),
            'closure-use' => array(true, 'foo', array('test' => $closure), '$.@test bar'),
            'closure-typecast' => array(true, 'foo', array('test' => $closure), '$.@test() foobar'),
            'closurearray-use' => array(true, 
                    'foo', 
                    array('test' => $closureArray), 
                    array('$' => 'AND', '$.@test.foo bar', array('.@test.bar' => null))
                ),
            'closurearray-typecast' => array(true, 
                    'foo', 
                    array('test' => $closureArray), 
                    array('$' => 'AND', '$.@test().foo bar', '$.@test().bar foo')
                ),
            );
    }    
    
    
    public function testCheckEnvironment() {
        $_ENV['TEST'] = 123;
        $this->assertTrue(Chequer::checkEnvironment(array(
                    'PHP_SELF' => $_SERVER['PHP_SELF'],
                    '_SERVER' => array(
                        'PHP_SELF' => $_SERVER['PHP_SELF']
                    ),
                    '_ENV' => array(
                        'TEST' => $_ENV['TEST']
                    )
                )));
    }
    
    public function testInvoke() {
        $this->assertEquals(array(1 => 2, 3 => 4, 5 => 6), array_filter(array(1, 2, 3, 4, 5, 6), new Chequer(array(2, 4, 6))));

        if (class_exists('CallbackFilterIterator')) {
            $files = new FilesystemIterator(dirname(__DIR__));
            $files = new CallbackFilterIterator($files, new Chequer(array('.getExtension()' => 'php')));
            $this->assertContains('Chequer.php', array_map('basename', array_keys(iterator_to_array($files))));
        }
    }
    
    public function testCheckRules(  ) {
        $chequer = $this->buildChequer();
        $chequer->addRules(array(
            'foo' => '$regex foo',
            'bar' => '$regex bar',
            ));
        $chequer->setQuery('$rule foo');
        $this->assertTrue($chequer->check('foobar'));
        $this->assertFalse($chequer->check('hello!'));

        $chequer->setQuery('$rule foo bar');
        $this->assertTrue($chequer->check('foobar'));
        $this->assertFalse($chequer->check('foo'));

        $chequer->setQuery('$rule foo AND bar');
        $this->assertTrue($chequer->check('foobar'));
        $this->assertFalse($chequer->check('foo'));

        $chequer->setQuery('$rule foo OR bar');
        $this->assertTrue($chequer->check('foobar'));
        $this->assertTrue($chequer->check('foo'));
        $this->assertTrue($chequer->check('bar'));
        $this->assertFalse($chequer->check('hello!'));

        $chequer->setQuery('$rule "foo !bar"');
        $this->assertTrue($chequer->check('foo'));
        $this->assertFalse($chequer->check('foobar'));
        
        $this->setExpectedException('Exception');
        $chequer->setQuery('$rule missing');
        $this->assertTrue($chequer->check('foobar'));
    }    

   
    
    
}

