<?php

require_once __DIR__ . '/../Chequer.php';

class TokenizerTest extends PHPUnit_Framework_TestCase {

    protected $tokenizer;

	protected function getTokenizer($data, $regex = null) {
        if ($regex === null) $regex = '/\$[a-z!~&^*\-+=\/|%<>]+|[!~&\^*\-+=\/|%<>]{1,3}|(?<!\.)\d+\.\d+|\d+|[a-z]+|\s+|./i';
        return new \Chequer\Tokenizer($data, $regex);
    }


    public function testBasics() {
        $tokenizer = $this->getTokenizer('foo bar');
        
        $this->assertEquals('foo', $tokenizer->current);
        $this->assertEquals(0, $tokenizer->position);
        $this->assertEquals(3, $tokenizer->count);
        
        $this->assertEquals('foo', $tokenizer->getToken());
        $this->assertEquals(' ', $tokenizer->getToken());
        
        $this->assertEquals('bar', $tokenizer->current);
        $this->assertEquals(2, $tokenizer->position);
        $this->assertEquals(3, $tokenizer->count);
        $this->assertFalse($tokenizer->eot());
        $this->assertEquals('bar', $tokenizer->getToken());
        $this->assertNull($tokenizer->current);
        $this->assertNull($tokenizer->getToken());
        $this->assertNull($tokenizer->peek());
        $this->assertTrue($tokenizer->eot());
    }

    public function testStopChar() {
        $tokenizer = $this->getTokenizer('foo bar(hello!)');
        $stopChars = array('(' => 1, ')' => 1);
        $this->assertEquals('foo bar', $tokenizer->getToken($stopChars));
        $this->assertEquals('(', $tokenizer->current);
        $this->assertEquals('(', $tokenizer->getToken());
        $this->assertEquals('hello!', $tokenizer->getToken($stopChars));
        $this->assertEquals(')', $tokenizer->current);
        $this->assertFalse($tokenizer->eot());
        $this->assertEquals(')', $tokenizer->getToken());
        $this->assertTrue($tokenizer->eot());
    }    

    public function testEscapeChar() {
        // "foo bar\(hello\!\\\)(\(world!)\)123)\"
        $text = 'foo bar\(hello\\!\\\\\\)(\\(world!)\\)123)\\';
        $tokenizer = $this->getTokenizer($text);
        $stopChars = array('(' => 1, ')' => 1);
        $this->assertEquals('foo bar(hello!\\)', $tokenizer->getToken($stopChars));
        $this->assertEquals('(', $tokenizer->getToken());
        $this->assertEquals('\\', $tokenizer->current);
        $this->assertEquals('(', $tokenizer->getToken());
        $this->assertEquals('world!', $tokenizer->getToken($stopChars));
        $this->assertEquals(')', $tokenizer->getToken());
        $this->assertEquals('\\', $tokenizer->current);
        $this->assertEquals(')123', $tokenizer->getToken($stopChars));
        $this->assertFalse($tokenizer->eot());
        $this->assertEquals(')', $tokenizer->getToken());
        $this->assertFalse($tokenizer->eot());
        $this->assertEquals(null, $tokenizer->getToken());
        $this->assertTrue($tokenizer->eot());
    }    
    
}

