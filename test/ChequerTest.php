<?php

require_once __DIR__ . '/../Chequer.php';

class ChequerTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var Chequer
	 */
	protected $chequer;
    
    protected $data;

	protected function setUp() {
        $this->data = 
                [
                    'foo' => 'bar',
                    'array' => [1, 2, 3],
                    'hashmap' => ['one' => 'ONE', 'two' => 'TWO', 3, 'sub' => [1, 2, 'foo' => 'BAR']],
                    'number' => 1,
                    [
                        'hello' => 'World!',
                    ],
                    [
                        'hello' => 'obscured'
                    ]
                ];
		$this->chequer = new Chequer();
	}

	protected function tearDown() {
		
	}


    /**
     * @dataProvider checkProvider
     */
    public function testCheck($expected, $rules, $matchAll = null, $data = null) {
        if (func_num_args() < 4) $data = $this->data;
        if (is_string($expected)) $this->setExpectedException($expected);
        $this->assertEquals($expected, $this->chequer->check($data, $rules, $matchAll));
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
        ];
    }
    
}

