<?php

require_once __DIR__ . '/../DynamicObject.php';
require_once __DIR__ . '/../Chequer.php';
require_once __DIR__ . '/../Chequer/DynamicChequerObject.php';
require_once __DIR__ . '/../Chequer/Time.php';

class TimeTest extends PHPUnit_Framework_TestCase {

    /** @dataProvider timeProvider */
    public function testTime($timeobject, $now = null, $reference = null) {
        if ($reference === null) {
            if ($now !== null) throw new Exception("Provide the reference for $now");
            $reference = strtotime((string)$timeobject);
        }
        $time = new Chequer\Time($timeobject, $now);
        
        $this->assertEquals(strftime('%Y-%m-%d %H:%M:%S', $reference), (string)$time);
        
    }
    
    public function timeProvider() {
        $array = array(
            array(null, null, time()),
            array('now'),
            array('-1 month'),
            array('+1 month'),
            array('1 minute', null, 60),
            array('1 minute -1 minute', null, 0),
            array('1 year', null, strtotime('1 year', 0)),
        );
        $result = array();
        foreach($array as $i => $item) {
            if (is_int($i)) $result["#$i: " . implode(', ', $item)] = $item;
            else $result[$i] = $item;
        }
        return $result;
    }
    
}