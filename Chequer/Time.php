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

use DateTime;
use SplFileObject;

/** Expands string and objects with Date/Time information.
 * 
 * Supports formats:
 * - `60` | `-60` | `57462043` | ... - Unix timestamp in seconds as integer, or integer-like string
 * - `2010-10-01` | `2010/01/10 15:00` | ... - Full dates. The same format as strtotime()
 * - `-1 day` | `last thursday` | ... - Relative dates. The same formats as strtotime()
 * - `1 day` | `20 seconds` | ... - Time intervals. Don't use '+' or '-'! These are reserved for relative dates!
 * 
 * @property-read int $size File size in bytes
 * 
 * @method SplFileObject openFile ($open_mode = r, $use_include_path = false , $context = NULL )
 */
class Time extends DynamicChequerObject {

    protected $unix;
    
    /** Getters are predeclared for speed. To override them use setGetter().
     * 
     * @property-read string $date
     * @property-read string $time
     * @property-read int $year
     * @property-read int $month 
     * @property-read int $day
     * @property-read int $week
     * @property-read int $weekday
     * @property-read int $hour
     * @property-read int $minute
     * @property-read int $second
     * @property-read int $unixtime
     */
    protected $__getters = array(
        'date' => 'get_date',
        'day' => 'get_day',
        'hour' => 'get_hour',
        'minute' => 'get_minute',
        'month ' => 'get_month ',
        'second' => 'get_second',
        'time' => 'get_time',
        'unixtime' => 'get_unixtime',
        'week' => 'get_week',
        'weekday' => 'get_weekday',
        'year' => 'get_year',
    );

    protected $__methods = array(
        '__toString' => 'strftime',
        'strftime' => 'get_strftime',
        'format' => 'get_format',
    );
    
    public static function anythingToTime($time = 'now', $now = null) {
        if ($now !== null) {
            $now = self::anythingToTime($now);
        }
        if (is_int($time)) {
            return $time;
        } if (is_string($time)) {
            if ((int)$time == $time) {
                return (int)$time;
            } else {
                if ($now === null && preg_match('/^\d+ (sec|seconds?|min|minutes?|hours?|days?|fortnight|forthnight|months?|years?|weeks?)\b/', $time)) {
                    $now = 0;
                }
                return strtotime($time, $now === null ? time() : $now);
            }
        } elseif ($time instanceof DateTime) {
            /* @var $time DateTime */
            return $time->getTimestamp();
        } else {
            // maybe the __toString() will give us the date?
            return self::anythingToTime((string)$time, $now);
        }
    }

    /** @return Time */
    public static function create($time, $now = null) {
        if ($time instanceof Time) return $time;
        return new Time($time, $now);
    }
    
    function __construct($time, $now = null) {
        $this->unix = self::anythingToTime($time, $now);
        if (!is_object($time)) $time = null;
        parent::__construct($time);
    }

    public function chequerOperator( $operator, $value, $rule, $caller ) {
        if (!in_array($operator, array(
            'not', 'rule', 'eval', 'check', 'size', 'regex'
        ))) {
            if ($rule && ($rule instanceof Time == false) && (
                    (is_scalar($rule) && $rule[0] != '$')
                    || (is_object($rule) && method_exists($rule, '__toString'))
            )) {
                $time = new Time($rule);
                if ($time->unix) $rule = $time;
            }
        }
        
        return parent::chequerOperator($operator, $value, $rule, $caller);
    }

    public function operator_add( $value, $rule, $caller ) {
        $value = self::create($value);
        $rule = self::create($rule);
        return new Time($value->unix + $value->unix);
    }    

    public function operator_sub( $value, $rule, $caller ) {
        $value = self::create($value);
        $rule = self::create($rule);
        return new Time($value->unix - $value->unix);
    }    

    
    public function get_unixtime() {
        return $this->unix;
    }
    
    public function get_date() {
        return strftime('%Y-%m-%d', $this->unix);
    }
    
    public function get_time() {
        return strftime('%H:%M:%S', $this->unix);
    }
    
    public function get_year() {
        return (int)strftime('%Y', $this->unix);
    }
    
    public function get_month() {
        return (int)strftime('%m', $this->unix);
    }
    
    public function get_day() {
        return (int)strftime('%d', $this->unix);
    }
    
    public function get_weekday() {
        return (int)strftime('%u', $this->unix);
    }
    
    public function get_week() {
        return (int)strftime('%v', $this->unix);
    }
    
    public function get_hour() {
        return (int)strftime('%H', $this->unix);
    }
    
    public function get_minute() {
        return (int)strftime('%M', $this->unix);
    }
    
    public function get_second() {
        return (int)strftime('%S', $this->unix);
    }
    
    
    public function strftime($format = '%Y-%m-%d %H:%M:%S') {
        return strftime($format, $this->unix);
    }
    
    public function format($format) {
        return date($format, $this->unix);
    }

    
}