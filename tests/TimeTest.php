<?php

require_once __DIR__ . '/../DynamicObject.php';
require_once __DIR__ . '/../Chequer.php';
require_once __DIR__ . '/../Chequer/DynamicChequerObject.php';
require_once __DIR__ . '/../Chequer/Time.php';

class TimeTest extends PHPUnit_Framework_TestCase {

    /** @dataProvider timeProvider */
    public function testTime($timeobject, $now = null, $reference = null) {
        $unixTime = time(); // use single unix-time instance...
        if ($reference === null) {
            if ($now !== null) throw new Exception("Provide the reference for $now");
            $reference = strtotime((string)$timeobject, $unixTime);
            if ($now === null) $now = $unixTime;
        }
        $time = new Chequer\Time($timeobject, $now);
        
        $this->assertEquals(strftime('%Y-%m-%d %H:%M:%S', $reference), (string)$time);
        $this->assertEquals((int)strftime('%Y', $reference), $time->year, 'Year');
        $this->assertEquals((int)strftime('%m', $reference), $time->month, 'Month');
        $this->assertEquals((int)strftime('%d', $reference), $time->day, 'Day');
        $this->assertEquals((int)strftime('%v', $reference), $time->week, 'Week');
        $this->assertEquals((int)strftime('%u', $reference), $time->weekday, 'Weekday');
        $this->assertEquals((int)strftime('%H', $reference), $time->hour, 'Hour');
        $this->assertEquals((int)strftime('%M', $reference), $time->minute, 'Minute');
        $this->assertEquals((int)strftime('%S', $reference), $time->second, 'Second');
        $this->assertEquals($reference, (string)$time->unixtime, 'Unix');
        
    }
    
    public function timeProvider() {
        $array = array(
            array('now'),
            array('-1 month'),
            array('+1 month'),
            array('1 minute', null, 60),
            array('1 minute -1 minute', null, 0),
            array('1 year', null, strtotime('1 year', 0)),
            array(60, null, 60),
            array(123456, null, 123456),
            array('123456', null, 123456),
            array('@123456', null, 123456),
            array('2012-02-01', null, mktime(0,0,0,2,1,2012)),
            array('2012-02-08', null, mktime(0,0,0,2,8,2012)),
            array('02/08/2012 08:12', null, mktime(8,12,0,2,8,2012)),
        );
        $result = array();
        foreach($array as $i => $item) {
            if (is_int($i)) $result["#$i: " . implode(', ', $item)] = $item;
            else $result[$i] = $item;
        }
        return $result;
    }
    
    public function testConstructor() {
        $time = time();
        $this->assertEquals(strftime('%Y'), Chequer\Time::create(null, null)->year);
        $this->assertEquals(strftime('%Y'), Chequer\Time::create(null, false)->year);
        $this->assertEquals(strftime('%Y'), Chequer\Time::create(null, true)->year);
        $this->assertEquals(0, Chequer\Time::create(null, 0)->unixtime);
        $this->assertEquals($time, Chequer\Time::create(null, $time)->unixtime);
        $this->assertEquals($time, Chequer\Time::create('+0 seconds', $time)->unixtime);
    }
    
    public function testAdd() {
        $this->assertEquals(strtotime('+1 day'), Chequer\Time::create('now')->add('1 day')->unixtime);
        $this->assertEquals(strtotime('+1 minute'), Chequer\Time::create('now')->add('60')->unixtime);
    }
    
    public function testSub() {
        $this->assertEquals(strtotime('-1 day'), Chequer\Time::create('now')->sub('1 day')->unixtime);
        $this->assertEquals(0, Chequer\Time::create('now')->sub('now')->unixtime);
        $this->assertEquals(60, Chequer\Time::create('+1 minute')->sub('now')->unixtime);
    }
    
    public function testAbs() {
        $this->assertEquals(60, Chequer\Time::create('now')->sub('+1 minute')->abs->unixtime);
    }

    public function testFormat() {
        $this->assertEquals(strftime('%Y-%m-%d %H:%M:%S'), Chequer\Time::create('now')->format());
        $this->assertEquals(strftime('%Y-%m-%d %H:%M:%S'), Chequer\Time::create('now')->format);
    }
    
    
}