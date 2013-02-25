<?php

/*
 * CHEQUER for PHP
 * 
 * Copyright (c)2013 Rafal Lindemann <rl@stamina.pl>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code. * 
 */

namespace Chequer;

use DynamicObject;

/** Expands scalars and objects and treats them like strings.
 * 
 * It's mostly compatible with JavaScript objects and supports method chaining!
 * 
 * ```php
 * $string = (new String("foobar"))
 *      ->substr(0, 3)
 *      ->upper // the same as toUpperCase()
 * ;
 * // if you need a standard PHP string - cast it:
 * $string = (string)$string;
 * ```
 * 
 */
class String extends DynamicObject {

    protected $string;
    
    /** Getters are predeclared for speed. To override them use setGetter().
     * 
     * @property-read int $length
     * @property-read int $lower
     * @property-read int $upper
     * @property-read int $trim
     */
    protected $__getters = array(
        'length' => 'get_length',
        'lower' => 'toLowerCase',
        'upper' => 'toUpperCase',
        'trim' => 'trim',
    );

    protected $__methods = array(
        'length' => 'get_length',
    );


    /** @return String */
    public static function create($value) {
        if ($value instanceof String) return $value;
        return new String($value);
    }
    
    function __construct($value) {
        $this->string = (string)$value;
        parent::__construct(is_object($value) ? $value : null);
    }

    /** @return String */
    public function get_length() {
        return mb_strlen($this->string);
    }
    
    public function __toString() {
        return $this->string;
    }

    /** @return String */
    public function toLowerCase() {
        return new String(mb_strtolower($this->string));
    }
    
    /** @return String */
    public function toUpperCase() {
        return new String(mb_strtoupper($this->string));
    }
    
    /** @return String */
    public function trim() {
        return new String(trim($this->string));
    }
    
    /** @return String */
    public function substr($start, $length = null) {
        return new String(mb_substr($this->string, $start, $length));
    }
    
    /** @return String */
    public function substring($offsetA, $offsetB = null) {
        return new String(mb_substr($this->string, $offsetA, $offsetB !== null ? $offsetB - $offsetA : null));
    }
    
    public function charAt($pos) {
        return mb_substr($this->string, $pos, 1);
    }
    
    public function match($regex) {
        $matches = array();
        if (preg_match($regex, $this->string, $matches)) {
            return $matches;
        }
        return null;
    }
    
    /** @return String */
    public function replace($regex, $replacement) {
        if (!$regex || ($regex[0] !== '/' && $regex[0] !== '~')) {
            return new String(str_replace($regex, $replacement, $this->string));
        }
        return new String(preg_replace($regex, $replacement, $this->string));
    }
    
}