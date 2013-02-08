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
        '=' => 'eq',
        '==' => 'same',
        '>' => 'gt',
        '>=' => 'gte',
        '<' => 'lt',
        '<=' => 'lte',
        '!' => 'not',
        '~' => 'regex',
        'rules' => 'rule',
    );
    
    protected $typecasts = array();
    
    protected $rules = array();
    
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


    /** Enable searching for subkeys in subarrays. Set it to the deepest level this function
     * should work.
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

    
    /** @return self */
    public function addTypecasts($typecasts) {
        $this->typecasts = array_merge($this->typecasts, $typecasts);
        return $this;
    }

    
    /** @return self */
    public function addRules($rules) {
        $this->rules = array_merge($this->rules, $rules);
        return $this;
    }
    
    
    public function getRules() {
        return $this->rules;
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
                            $query[1] == '.' ? substr($query, 1, $spacePos - 1) : substr($query, 0, $spacePos) => substr($query, $spacePos + 1)
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
            // callable queries
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
                    $matchAll = ($rule === 'OR' || $rule === 'or') ? false : $rule == true;
                } else {
                    $result = $this->operator(substr($key, 1), $value, $rule);
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

    
    protected function operator($operator, $value, $rule) {
        if (isset($this->operators[$operator])) {
            if (is_string($this->operators[$operator])) {
                $operator = $this->operators[$operator];
            } else {
                return call_user_func($this->operators[$operator], $value, $rule);
            }
        }
        return call_user_func(array($this, 'operator_' . $operator), $value, $rule);
    }


    /** Calls or returns a typecast object */
    protected function typecast($typecast, $callArgs = array()) {
        if (isset($this->typecasts[$typecast])) {
            $typecastObj = $this->typecasts[$typecast];
            if (count($callArgs) == 0 && ($typecastObj instanceof Closure) == false) {
                return $typecastObj;
            } elseif (!is_scalar($typecastObj) && is_callable($typecastObj)) {
                return call_user_func_array($typecastObj, $callArgs);
            } else {
                throw new Exception("Typecast '$typecast' cannot be called!");
            }
        }
        return call_user_func(array($this, 'typecast_' . $typecast), $callArgs);
    }
    
    
    protected function getSubkeyValue( $value, $key, $deepArrays = 0 ) {
        // dot notation
        if ($key[0] === '.') {
            $key = substr($key, 1);
            if (!$key) return $value;
            while (($nextKey = strpos($key, '.', 1)) !== false) {
                $value = $this->getSubkeyValue($value, substr($key, 0, $nextKey), $deepArrays - 1);
                if ($value === null) return null;
                $key = substr($key, $nextKey + 1);
            }
        }
        
        // @ object typecasting
        if ($key[0] === '@') {
            $typecast = substr($key, 1);
            if (($method = strstr($typecast, '(', true))) {
                // typecast current value
                return $this->typecast( $method, array($value) );
            }
            // just return the typecast's object
            return $this->typecast( $typecast );
        }
        
        if (!is_array($value) && !is_object($value))
                throw new InvalidArgumentException('Array or object required for key matching.');

        if (is_array($value) || $value instanceof ArrayAccess) {
            if (isset($value[$key])) return $value[$key];
        }
        if (is_object($value)) {
            if (isset($value->$key)) return $value[$key];
            if (($method = strstr($key, '(', true)) && method_exists($value, $method)) {
                return call_user_func(array($value, $method));
            }
        }
        if ($deepArrays > 0 && is_array($value) && isset($value[0])) {
            --$deepArrays;
            for ($i = 0, $length = count($value); $i < $length, isset($value[$i]); ++$i) {
                $subvalue = $value[$i];
                if (is_array($subvalue) || is_object($subvalue)) {
                    $subresult = $this->getSubkeyValue($subvalue, $key, $deepArrays);
                    if ($subresult !== null) return $subresult;
                }
            }
        }
        return null;
    }
    

    protected function querySubkey( $value, $key, $rule, $deepArrays = 0 ) {
        $value = $this->getSubkeyValue($value, $key, $deepArrays);
        return $this->query($value, $rule);
    }


    protected function operator_not( $value, $rule ) {
        return !$this->query($value, $rule);
    }

    
    protected function operator_eq( $value, $rule ) {
        return $value == $rule;
    }
    

    protected function operator_same( $value, $rule ) {
        return $value === $rule;
    }
    
    
    protected function operator_nc( $value, $rule ) {
        return mb_strtolower($value) === mb_strtolower($rule);
    }


    protected function operator_gt( $value, $rule ) {
        return $value > $rule;
    }


    protected function operator_gte( $value, $rule ) {
        return $value >= $rule;
    }


    protected function operator_lt( $value, $rule ) {
        return $value < $rule;
    }


    protected function operator_lte( $value, $rule ) {
        return $value <= $rule;
    }


    protected function operator_between( $value, $rule ) {
        if (count($rule) != 2)
                throw new InvalidArgumentException('Two element array required for $between!');
        return $value >= $rule[0] && $value <= $rule[1];
    }


    protected function operator_or( $value, $rule ) {
        return $this->query($value, $rule, false);
    }


    protected function operator_and( $value, $rule ) {
        return $this->query($value, $rule, true);
    }


    protected function operator_regex( $value, $rule ) {
        if (!is_scalar($value) && !method_exists($value, '__toString'))
                throw new InvalidArgumentException('String required for regex matching.');
        if ($rule[0] !== '/' && $rule[0] !== '#') {
            $rule = "#{$rule}#";
        }
        return preg_match($rule, $value) == true;
    }


    protected function operator_check( $value, $rule ) {
        return call_user_func($rule, $value);
    }


    protected function operator_size( $value, $rule ) {
        $length = is_string($value) ? mb_strlen($value) : count($value);
        return $this->query($length, $rule);
    }


    protected function operator_cmp( $value, $rule ) {
        if (is_string($rule)) $rule = explode(' ', $rule, 3);
        
        if (count($rule) > 2) {
            $value1 = array_shift($rule);
            if (is_scalar($value1)) $value1 = $this->getSubkeyValue($value, $value1);
        } else $value1 = $value;
        
        if (count($rule) > 1) {
            $operator = array_shift($rule);
        } else $operator = false;
        
        $value2 = array_shift($rule);
        if (is_scalar($value2)) $value2 = $this->getSubkeyValue($value, $value2);

        if (!$operator) {
            return $this->query($value1, $value2);
        } elseif (is_numeric($operator) || $operator[0] == '$' || $operator[0] == '.' || $operator[0] == '@') {
            return $this->query($value1, array($operator => $value2));
        } else {
            return $this->operator($operator, $value1, $value2);
        }
    }

    
    protected function operator_rule( $value, $rules ) {
        if (!is_array($rules)) $rules = preg_split('/ *,? +/', $rules);
        
        if (count($rules) > 1) {
            $query = array('$' => 'AND');
            foreach($rules as $rule) {
                if ($rule === 'AND') {
                    $query['$'] = 'AND';
                } elseif ($rule === 'OR') {
                    $query['$'] = 'OR';
                } else {
                    if (!isset($this->rules[$rule])) throw new Exception("Rule '$rule' is undefined!");
                    $query[] = $this->rules[$rule];
                }
            }
            return $this->query($value, $query);
        } else {
            if (!isset($this->rules[$rules[0]])) throw new Exception("Rule '{$rules[0]}' is undefined!");
            return $this->query($value, $this->rules[$rules[0]]);
        }
        
        return $this->query($value, $this->rules[$rules]);
    }    

}