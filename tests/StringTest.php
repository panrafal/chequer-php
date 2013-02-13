<?php

require_once __DIR__ . '/../DynamicObject.php';
require_once __DIR__ . '/../Chequer.php';
require_once __DIR__ . '/../Chequer/String.php';

class StringTest extends PHPUnit_Framework_TestCase {

    /** @dataProvider fileProvider */
    public function testFile($object) {
        $string = new Chequer\String($object);
        $reference = (string)$object;
        
        $this->assertEquals($reference, (string)$string);
        $this->assertEquals(strlen($reference), $string->length, 'length');
        $this->assertEquals(strlen($reference), $string->length(), 'length');
        $this->assertEquals(mb_strtolower($reference), $string->lower);
        $this->assertEquals(mb_strtolower($reference), $string->toLowerCase());
        $this->assertEquals(mb_strtoupper($reference), $string->upper);
        $this->assertEquals(mb_strtoupper($reference), $string->toUpperCase());
        $this->assertEquals(mb_substr($reference, 0, 2), $string->substr(0, 2));
        $this->assertEquals(mb_substr($reference, -2, 2), $string->substr(-2, 2));
        $this->assertEquals(mb_substr($reference, -2), $string->substring(-2));
        
    }
    
    public function fileProvider() {
        $array = array(
            array('foobar'),
            array(''),
            array(null),
        );
        $result = array();
        foreach($array as $i => $item) {
            if (is_int($i)) $result["#$i: " . $item[0]] = $item;
            else $result[$i] = $item;
        }
        return $result;
    }
    
    
}