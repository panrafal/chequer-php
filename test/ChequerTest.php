<?php

require_once __DIR__ . '/../Chequer.php';

class ChequerTest_Object {
    
}

class ChequerTest extends PHPUnit_Framework_TestCase {

    protected $data;

	protected function setUp() {
        $this->data =
                [
                    'foo' => 'bar',
                    'array' => [1, 2, 3],
                    'hashmap' => ['one' => 'ONE', 'two' => 'TWO', 3, 'sub' => [1, 2, 'foo' => 'BAR']],
                    'object' => new ChequerTest_Object(),
                    'number' => 1,
                    [
                        'hello' => 'World!',
                    ],
                    [
                        'hello' => 'obscured'
                    ]
        ];
    }


    protected function tearDown() {
        
    }


    /** @return Chequer */
    public function buildChequer( $rules, $matchAll ) {
        return new Chequer($rules, $matchAll);
    }


    /**
     * @dataProvider checkProvider
     */
    public function testCheck( $expected, $rules, $matchAll = null, $data = null ) {
        if (func_num_args() < 4) $data = $this->data;
        if (is_string($expected)) $this->setExpectedException($expected);
        $chequer = $this->buildChequer($rules, $matchAll);
        $this->assertEquals($expected, $chequer->check($data));
    }


    public function checkProvider() {
        return [
            'scalar_scalar-true' => [true, 'foo', null, 'foo'],
            'scalar_scalar-false' => [false, 'bar', null, 'foo'],
            'array_scalar-true' => [true, 'foo', null, ['foo', 'bar']],
            'array_scalar-false' => [false, 'baz', null, ['foo', 'bar']],
            'hashmap-true' => [true, ['foo' => 'bar']],
            'hashmap-false' => [false, ['foo' => 'baz']],
            'hashmap_union-true' => [true, ['hello' => 'World!']],
            'hashmap_union-false' => [false, ['hello' => 'obscured']],
            'hashmap_missing' => [false, ['missing' => true]],
            'hashmap_missing-true' => [true, ['missing' => null]],
            'hashmap_missing-false' => [false, ['missing' => false]],
            'hashmap_missing-array' => ['Exception', ['missing' => ['some' => 'test']]],
            'hashmap_exists' => [true, ['foo' => true]],
            'hashmap_sub-true' => [true, ['hashmap' => ['one' => 'ONE']]],
            'hashmap_sub-false' => [false, ['hashmap' => ['missing' => 1]]],
            
            'array_array-true' => [true, ['array' => [1,2,4]]],
            'array_array-false' => [false, ['array' => [4,5]]],
            
            'regex-true' => [true, ['foo' => ['$regex' => '/bar/']]],
            'regex-false' => [false, ['foo' => ['$regex' => '/baz/']]],
            'regex_array' => ['Exception', ['hashmap' => ['$regex' => '/[A-Z]+/']]],
            
            'eq-true' => [true, ['number' => ['$eq' => 1]]],
            'eq-false' => [false, ['number' => ['$eq' => '1']]],
            
            'and' => [true, ['foo' => 'bar', 'number' => 1]],
            'and-false' => [false, ['foo' => 'bar', 'number' => 2]],
            'and_param' => [true, ['foo' => 'bar', 'number' => 1], true],
            
            'or_param' => [true, ['foo' => 'bar', 'number' => 1], false],
            'or2_param' => [true, ['foo' => 'bar', 'number' => 2], false],
            
            'or_switch' => [true, ['$' => false, 'foo' => 'bar', 'missing' => true]],
            'or_group' => [true, ['$or' => ['foo' => 'bar', 'missing' => true]]],
            'or_auto' => [true, ['something', 'foo' => 'bar', 'missing' => true]],
            
            'and_force' => [false, ['something', 'foo' => 'bar', 'missing' => true], true],
            
            'gt' => [false, ['number' => ['$gt' => 1]]],
            'gte' => [true, ['number' => ['$gte' => 1]]],
            
            'between' => [true, ['number' => ['$between' => [1, 2]]]],
            'between-false' => [false, ['number' => ['$between' => [2, 3]]]],
            'between-fail' => ['Exception', ['number' => ['$between' => 1]]],
            
            'size-string' => [true, ['foo' => ['$size' => 3]]],
            'size-array' => [true, ['array' => ['$size' => 3]]],
            
            'check' => [true, ['foo' => ['$check' => function($v) {return $v == 'bar';}]]],
        ];
    }
    
}

