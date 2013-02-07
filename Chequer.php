<?php
/*
 * CHEQUER for PHP
 * 
 * Copyright (c)2013 Rafal Lindemann <rl@stamina.pl>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code. * 
 */
class Chequer {

    protected $query;
    protected $matchAll;
    protected $deepArrays;
    protected $shorthandSyntax = true;

    protected $operators = array(
        '>' => 'gt',
        '>=' => 'gte',
        '<' => 'lt',
        '<=' => 'lte',
        '!' => 'not',
        '~' => 'regex',
    );
    
    /**
     * @param $query Default query
     * @param $matchAll Default matchAll
     * @param $deepArrays Enable searching for subkeys in arrays
     */
    function __construct( $query = false, $matchAll = null, $deepArrays = true ) {
        $this->query = $query;
        $this->matchAll = $matchAll;
        $this->deepArrays = $deepArrays;
    }


    public function getQuery() {
        return $this->query;
    }


    /** @return self */
    public function setQuery( $query ) {
        $this->query = $query;
        return $this;
    }


    public function getDeepArrays() {
        return $this->deepArrays;
    }


    /** Enable searching for subkeys in subarrays 
     * @return self
     */
    public function setDeepArrays( $deepArrays ) {
        $this->deepArrays = $deepArrays;
        return $this;
    }


    public function getMatchAll() {
        return $this->matchAll;
    }


    /** @return self */
    public function setMatchAll( $matchAll ) {
        $this->matchAll = $matchAll;
        return $this;
    }

    
    public function getShorthandSyntax() {
        return $this->shorthandSyntax;
    }


    /** 
     * Enables or disables support for shorthand syntax:
     * 
     * "$gt 25"
     * 
     * @return self */
    public function setShorthandSyntax( $shorthandSyntax ) {
        $this->shorthandSyntax = $shorthandSyntax;
        return $this;
    }    

    
    /** Checks rules against current server environment. 
     * Available keys are everything from $_SERVER, _ENV, _COOKIE, _SESSION, _GET, _POST, _REQUEST.
     * 
     *  */
    public static function checkEnvironment( $query, $matchAll = true ) {
        $ch = new Chequer($query, $matchAll);
        $table = array(
            $_SERVER,
            '_SERVER' => $_SERVER,
            '_ENV' => $_ENV,
            '_COOKIE' => isset($_COOKIE) ? $_COOKIE : array(),
            '_SESSION' => isset($_SESSION) ? $_SESSION : array(),
            '_GET' => isset($_GET) ? $_GET : array(),
            '_POST' => isset($_POST) ? $_POST : array(),
            '_REQUEST' => isset($_REQUEST) ? $_REQUEST : array(),
        );
        return $ch->check($table);
    }


    public static function checkValue( $value, $query, $matchAll = null ) {
        $ch = new static($query, $matchAll);
        return $ch->check($value);
    }


    public function __invoke( $value ) {
        return $this->check($value);
    }


    /** Checks the value against the instance's query
     * 
     * See Readme.md for documentation
     */
    public function check( $value, $matchAll = null ) {
        return $this->query($value, $this->query, $matchAll === null ? $this->matchAll : $matchAll);
    }


    /** Checks the value against provided query
     * 
     * See Readme.md for documentation
     * 
     * @param $value Value to check
     * @param $query Query to match
     * @param $matchAll TRUE to require all first level queries, FALSE to require only one
     *  */
    public function query( $value, $query, $matchAll = null ) {
        if ($query === null || $query === false) {
            // null and false are compared strictly
            return $value === $query;
        } elseif (is_scalar($query)) {
            if ($this->shorthandSyntax && is_string($query) && !empty($query) && $query{0} == '$') {
                // shorthand syntax
                if (($spacePos = strpos($query, ' ')) > 0) {
                    if ($spacePos > 1) {
                        // rebuild the query
                        $query = array(
                            // handle '$.subkey' syntax
                            $query[1] == '.' ? substr($query, 2, $spacePos - 2) : substr($query, 0, $spacePos) => substr($query, $spacePos + 1)
                        );
                    } else {
                        // '$ $something' should be escaped to '$something'
                        return $value == substr($query, 2);
                    }
                } else {
                    // if there is no space, the query is NOT a shorthand syntax!
                    return $value == $query;
                }
            } elseif (is_array($value) && is_bool($query) == false) {
                // string/number queries are searched in arrays
                return in_array($query, $value);
            } else {
                // other scalar queries are compared non-strictly
                return $value == $query;
            }
        } elseif (is_object($query) && is_callable($query)) {
            if ($query instanceof Chequer) return $query->check($value, $matchAll);
            return call_user_func($query, $value, $query, $matchAll);
        }
        
        // query is an array....
        
        if ($matchAll === null)
                $matchAll = false === (isset($query[0]) && is_scalar($query[0]));
        
        foreach ($query as $key => $rule) {
            $result = null;
            if (is_int($key)) {
                $result = $this->query($value, $rule);
            } elseif ($key{0} === '$') {
                if ($key === '$') {
                    $matchAll = ($rule === 'OR' || $rule === 'or') ? false : $rule;
                } else {
                    $result = $this->queryOperator(substr($key, 1), $value, $rule);
                }
            } else { // look in the array/hashmap
                $result = $this->querySubkey($value, $key, $rule, $this->deepArrays);
                // for unknown keys check null value
                if ($result === null) $result = $this->query(null, $rule);
            }
            if ($result === null) continue;
            if ($matchAll && !$result) return false;
            if (!$matchAll && $result) return true;
        }

        return $matchAll;
    }

    
    protected function queryOperator($operator, $value, $rule) {
        if (isset($this->operators[$operator])) {
            if (is_string($this->operators[$operator])) {
                $operator = $this->operators[$operator];
            } else {
                return call_user_func($this->operators[$operator], $value, $rule);
            }
        }
        return call_user_func(array($this, 'queryOperator' . ucfirst($operator)), $value, $rule);
    }
    

    protected function querySubkey( $value, $key, $rule, $deepArrays = false ) {
        if (!is_array($value) && !is_object($value))
                throw new InvalidArgumentException('Array or object required for key matching.');

        if (is_array($value) || $value instanceof ArrayAccess) {
            if (isset($value[$key])) return $this->query($value[$key], $rule);
        }
        if (is_object($value)) {
            if (isset($value->$key)) return $this->query($value[$key], $rule);
            if (($method = strstr($key, '(', true)) && method_exists($value, $method)) {
                return $this->query(call_user_func(array($value, $method)), $rule);
            }
        }
        if ($deepArrays && is_array($value) && isset($value[0])) {
            for ($i = 0, $length = count($value); $i < $length, isset($value[$i]); ++$i) {
                $subvalue = $value[$i];
                if (is_array($subvalue) || is_object($subvalue)) {
                    $subresult = $this->querySubkey($subvalue, $key, $rule);
                    if ($subresult !== null) return $subresult;
                }
            }
        }
        return null;
    }


    protected function queryOperatorNot( $value, $rule ) {
        return !$this->query($value, $rule);
    }


    protected function queryOperatorEq( $value, $rule ) {
        return $value === $rule;
    }
    
    
    protected function queryOperatorNc( $value, $rule ) {
        return mb_strtolower($value) === mb_strtolower($rule);
    }


    protected function queryOperatorGt( $value, $rule ) {
        return $value > $rule;
    }


    protected function queryOperatorGte( $value, $rule ) {
        return $value >= $rule;
    }


    protected function queryOperatorLt( $value, $rule ) {
        return $value < $rule;
    }


    protected function queryOperatorLte( $value, $rule ) {
        return $value <= $rule;
    }


    protected function queryOperatorBetween( $value, $rule ) {
        if (count($rule) != 2)
                throw new InvalidArgumentException('Two element array required for $between!');
        return $value >= $rule[0] && $value <= $rule[1];
    }


    protected function queryOperatorOr( $value, $rule ) {
        return $this->query($value, $rule, false);
    }


    protected function queryOperatorAnd( $value, $rule ) {
        return $this->query($value, $rule, true);
    }


    protected function queryOperatorRegex( $value, $rule ) {
        if (!is_scalar($value) && !method_exists($value, '__toString'))
                throw new InvalidArgumentException('String required for regex matching.');
        if ($rule[0] !== '/' && $rule[0] !== '#') {
            $rule = "#{$rule}#";
        }
        return preg_match($rule, $value) == true;
    }


    protected function queryOperatorCheck( $value, $rule ) {
        return call_user_func($rule, $value);
    }


    protected function queryOperatorSize( $value, $rule ) {
        $length = is_string($value) ? mb_strlen($value) : count($value);
        return $this->query($length, $rule);
    }


}