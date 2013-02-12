<?php

require_once __DIR__ . '/ChequerTest.php';
require_once __DIR__ . '/../DynamicObject.php';
require_once __DIR__ . '/../Chequer/DynamicChequerObject.php';
require_once __DIR__ . '/../Chequer/File.php';
require_once __DIR__ . '/../Chequer/Time.php';


class ChequerExamplesTest extends PHPUnit_Framework_TestCase {

    public function testQuerying() {
        // data for 10 most populated countries on earth
        $populatedCountries = '[{"gdp":7973000,"name":"China","pop":1330044000},{"gdp":3297000,"name":"India","pop":1173108018},{"gdp":14260000,"name":"United States","pop":310232863},{"gdp":914600,"name":"Indonesia","pop":242968342},{"gdp":1993000,"name":"Brazil","pop":201103330},{"gdp":427300,"name":"Pakistan","pop":184404791},{"gdp":224000,"name":"Bangladesh","pop":156118464},{"gdp":335400,"name":"Nigeria","pop":154000000},{"gdp":2266000,"name":"Russia","pop":140702000},{"gdp":4329000,"name":"Japan","pop":127288000}]';
        $populatedCountries = json_decode($populatedCountries);
        
        $result = implode("\n", 
            // If gdp is more then 5mln return "#### with #### of GDP". Otherwise return NULL which is filtered out.    
            Chequer::shorthand('(.gdp > 5000000) ? (.name with .gdp of GDP) : NULL')
                ->walk($populatedCountries)
        );
        $this->assertEquals("China with 7973000 of GDP\nUnited States with 14260000 of GDP", $result);

        $result = json_encode( 
            // If gdp is more then 5mln add {### : {gdp : ###, pop : ###}} to the results
            Chequer::shorthand('(.gdp > 5000000) ? (.name : (gdp:.gdp, pop:.pop)) : NULL')
                ->merge($populatedCountries)
        );
        $this->assertEquals('{"China":{"gdp":7973000,"pop":1330044000},"United States":{"gdp":14260000,"pop":310232863}}', $result);
        
        
    }

    public function testFileIterator() {
        if (PHP_VERSION_ID < 50400) $this->markTestSkipped('PHP 5.4 required');
        
        $files = new FilesystemIterator(dirname(__DIR__));
        $files = new CallbackFilterIterator($files, new Chequer('$ @file() $(.extension $in(php, html) && (.size > 1024))'));
        $this->assertContains('Chequer.php', array_map('basename', array_keys(iterator_to_array($files))));
        
    }
    
}

