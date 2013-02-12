<?php

require_once __DIR__ . '/ChequerTest.php';
require_once __DIR__ . '/../DynamicObject.php';
require_once __DIR__ . '/../Chequer/DynamicChequerObject.php';
require_once __DIR__ . '/../Chequer/File.php';
require_once __DIR__ . '/../Chequer/Time.php';


class ChequerTypecastsTest extends PHPUnit_Framework_TestCase {

    /** @return Chequer */
    public function buildChequer( $rules = null, $matchAll = null ) {
        $ch = new Chequer($rules, $matchAll);
        $ch->addTypecast('typecast', new ChequerTest_Object);
        return $ch;
    }

    /**
     * @dataProvider generalTypecastsProvider
     */
    public function testGeneralTypecasts( $expected, $rules, $data = null, $typecasts = null ) {
        if ($expected instanceof Exception) {
            $this->setExpectedException(get_class($expected));
            $expected = 'Should throw exception!';
        }
        $chequer = $this->buildChequer($rules);
        if ($typecasts) $chequer->addTypecasts($typecasts);
        $this->assertEquals($expected, $chequer->check($data));
    }

    public function generalTypecastsProvider() {
        $closure = function($a = null) {return $a . 'bar';};
        $closureArray = function($a = null) {return array('foo' => 'bar', 'bar' => $a);};
        return self::providerResult(array(
            'value' => array('foo', '$ @test', false, array('test' => 'foo')),
            'notcallable' => array(new Exception, '$ @test()', false, array('test' => 'foo')),
            'closure-use' => array('bar', '$ @test', 'foo', array('test' => $closure)),
            'closure-typecast' => array(true, '$ .@test() = foobar', 'foo', array('test' => $closure)),
            'closurearray-use' => array(true, 
                    array('$' => 'AND', '$ @test.foo = bar', array('@test.bar' => null)),
                    'foo', 
                    array('test' => $closureArray), 
                ),
            'closurearray-typecast' => array(true, 
                    array('$' => 'AND', '$ @test().foo = bar', '$ @test().bar = foo'),
                    'foo', 
                    array('test' => $closureArray), 
                ),
            
            ), 1);
    }    

    /**
     * @dataProvider timeTypecastProvider
     */
    public function testTimeTypecast( $expected, $rules, $data = null, $typecasts = null ) {
        return $this->testGeneralTypecasts($expected, $rules, $data, $typecasts);
    }    
    
    public function timeTypecastProvider() {
        return self::providerResult(array(
            'time-now' => array(strftime('%Y'), '$ @time.year'),
            'time-specific-unixtime' => array(60, '$ @time(1 minute).unixtime'),
            'time-specific-interval' => array(true, '$ @time(1 minute) = 60'),
            'time-specific-interval-conversion' => array(true, '$ @time(1 minute) = 1 minute'),
            'time-value' => array(true, '$ @time() = "2005-10-15"', '2005-10-15'),
            'time-math' => array(new Chequer\Time('-1 day'), '$ @time - 1 day'),
            'time-cmp' => array(true, '$ @time - "1 day" > @time(-1 week)'),
            ), 1);
    }     
    
    
    protected static function providerResult($array, $infoKey) {
        foreach($array as $i => $item) {
            if (is_int($i)) {
                $result["#$i: " . $item[$infoKey]] = $item;
            } else {
                $result[$i] = $item;
            }
        }        
        return $result;
    }
    
    
}

