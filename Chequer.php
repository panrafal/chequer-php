<?php
/*
 * CHEQUER for PHP
 * 
 * Copyright (c)2013 Rafal Lindemann <rl@stamina.pl>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code. * 
 */
namespace Chequer {
    interface Chequerable {

        /**
         * @param $operator Operator name
         * @param $value Value to operate on
         * @param $rule Operator parameter
         * @param $caller Chequer object calling this. If this function doesn't support this operator, 
         *                it should call $caller->chequerOperator(..., $caller)
         */
        function chequerOperator( $operator, $value, $rule, $caller );

        /**
         * @param $typecast Typecast name
         * @param $callArgs Empty array for @typecast, array($value) for @typecast()
         * @param $caller Chequer object calling this. If this function doesn't support this typecast, 
         *                it should call $caller->chequerTypecast(..., $caller)
         */
        function chequerTypecast( $typecast, $callArgs, $caller );

    }
}
namespace {
    class Chequer implements \Chequer\Chequerable {

        protected $query;
        protected $matchAll;
        protected $deepArrays;
        protected $shorthandSyntax = true;
        protected $strictMode = false;

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
        
        protected $specialChars = '$.@';

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


        public function getStrictMode() {
            return $this->strictMode;
        }


        /** 
         * Strict mode will throw exceptions
         * 
         * @return self */
        public function setStrictMode( $strictMode ) {
            $this->strictMode = $strictMode;
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
                if ($this->shorthandSyntax && is_string($query) && strlen($query) > 1 && ($query{0} === '$' || $query{0} === '\\')) {
                    // shorthand syntax
                    if ($query{0} === '\\' && $query{1} === '$') {
                        // unescape and compare
                        return $value == substr($query, 1);
                    }
                    // make '$ $op...' into '$op...'
                    if ($query[1] === ' ') $query = substr($query, 2);
                    return $this->shorthandQuery($value, $query);
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
                        $result = $this->chequerOperator(substr($key, 1), $value, $rule);
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


        public function chequerOperator($operator, $value, $rule, $caller = null) {
            if (isset($this->operators[$operator])) {
                if (is_string($this->operators[$operator])) {
                    $operator = $this->operators[$operator];
                } else {
                    return call_user_func($this->operators[$operator], $value, $rule);
                }
            }
            // if it's not aliased or user-defined, we try to ask the value itself
            if ($value instanceof \Chequer\Chequerable && $caller === null) {
                return $value->chequerOperator($operator, $value, $rule, $this);
            }
            return call_user_func(array($this, 'operator_' . $operator), $value, $rule);
        }


        /** Calls or returns a typecast object */
        public function chequerTypecast($typecast, $callArgs = array(), $caller = null) {
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
            // if it's not user-defined, we try to ask the value itself
            if ($callArgs && $callArgs[0] instanceof \Chequer\Chequerable && $caller === null) {
                return $callArgs[0]->chequerTypecast($typecast, $callArgs, $this);
            }
            return call_user_func(array($this, 'typecast_' . $typecast), $callArgs);
        }


        protected function getSubkeyValue( $value, $key, $deepArrays = 0, $findMethod = false ) {

            if (!is_array($value) && !is_object($value)) {
                if ($this->strictMode) 
                    throw new InvalidArgumentException('Array or object required for key matching.');
                else
                    return null;
            }

            if (is_array($value) || $value instanceof ArrayAccess) {
                if (isset($value[$key])) return $value[$key];
            }
            if (is_object($value)) {
                if ($findMethod && method_exists($value, $key)) {
                    return array($value, $key);
                }
                if (isset($value->$key)) return $value[$key];
            }
            if ($deepArrays > 0 && is_array($value) && isset($value[0])) {
                --$deepArrays;
                for ($i = 0, $length = count($value); $i < $length, isset($value[$i]); ++$i) {
                    $subvalue = $value[$i];
                    if (is_array($subvalue) || is_object($subvalue)) {
                        $subresult = $this->getSubkeyValue($subvalue, $key, $deepArrays, $findMethod);
                        if ($subresult !== null) return $subresult;
                    }
                }
            }
            return null;
        }


        protected function querySubkey( $value, $key, $rule, $deepArrays = 0 ) {
            if ($key[0] === '.' || $key[0] === '@') {
                // handle complex subkeys
                $value = $this->shorthandQuery($value, $key);
            } else {
                // handle simple ones
                $value = $this->getSubkeyValue($value, $key, $deepArrays);
            }
            return $this->query($value, $rule);
        }

        
        protected function shorthandQuery($value, $query) {
            // split query into tokens
            $tokens = new \Chequer\Tokenizer($query, '/\$[a-z!~&^*\-+=\/|%<>]+|[!~&\^*\-+=\/|%<>]{1,3}|(?<!\.)\d+\.\d+|\d+|[a-z]+|\s+|./i');
            return $this->shorthandParse($tokens, $value);
        }

        
        protected static $shcOperator = array(
            '$' => 1, '!' => 1, '~' => 1, '&' => 1, '^' => 1, '*' => 1, '-' => 1, '+' => 1, 
            '=' => 1, '/' => 1, '|' => 1, '%' => 1, '<' => 1, '>' => 1
        );
        protected static $shcWhitespace = array(
            ' ' => 1, "\t" => 1, "\r" => 1, "\n" => 1
        );
        protected static $shcStopchar = array(
            '$' => 1, '.' => 1, '@' => 1, 
            '(' => 1, ')' => 1, 
            '\'' => 1, '"' => 1, 
            ' ' => 1, "\t" => 1, "\r" => 1, "\n" => 1
        );
        
        
        /** Collects the value from tokens. Stops only on operators or EOT. */
        protected function shorthandCollectValues(\Chequer\Tokenizer $tokens, $contextValue, &$value) {
            $collected = false;
            $collectedItem = false;
            $whitespace = '';
            do {
                $char = $tokens->current[0];
                $valueItem = null;
                if ($char === '(') {
                    // parenthesis! read it...
                    $tokens->getToken();
                    // evaluate
                    $valueItem = $this->shorthandParse($tokens, $contextValue);
                } elseif (isset(self::$shcOperator[$char])) {
                    // an operator
                    return $collected;
                } elseif (isset(self::$shcWhitespace[$char])) {
                    // a whitespace
                    $whitespace .= $tokens->getToken();
                    continue;
                } elseif ($char === '"' || $char === '\'') {
                    // quoted string
                    $valueItem = substr($tokens->getToken(array($char => true)), 1, -1);
                    continue;
                } elseif ($char === ',') {
                    // make it into an array
                    if (!is_array($value)) $value = $collected ? array($value) : array();
                    $value[] = null;
                    $collectedItem = false;
                } elseif ($char === '.' || $char === '@') {
                    // subkeys!
                    $subkeyValue = $contextValue;
                    do {
                        $subkey = $tokens->getToken(self::$shcStopchar);
                    
                        if ($subkey === '.') {
                            // do nothing, use current subkeyValue
                        } elseif ($subkey === '@') {
                            throw new InvalidArgumentException("Empty @ operator!");
                        } elseif ($subkey[0] === '@') {
                            // object typecasting
                            $typecast = substr($subkey, 1);
                            if ($tokens->current === '(') {
                                // method call
                                $tokens->getToken();
                                $arguments = $this->shorthandParse($tokens, $contextValue);
                                if (!$arguments) $arguments = array($subkeyValue);
                                $subkeyValue = $this->chequerTypecast( $typecast, $arguments );
                            } else {
                                // just return the typecast's object
                                $subkeyValue = $this->chequerTypecast( $typecast );
                            }
                        } else {
                            // standard subkey
                            $subkey = substr($subkey, 1);
                            if ($tokens->current === '(') {
                                // method call
                                $tokens->getToken();
                                $arguments = $this->shorthandParse($tokens, $contextValue);
                                $method = $this->getSubkeyValue($subkeyValue, $subkey, $this->deepArrays, true);
                                if (!is_callable($method)) {
                                    if ($this->strictMode) {
                                        throw new InvalidArgumentException("Subkey '$subkey' is not callable!");
                                    } else $subkeyValue = null;
                                }
                                if (!$arguments) $arguments = array();
                                $subkeyValue = call_user_func_array($method, $arguments);
                            } else {
                                $subkeyValue = $this->getSubkeyValue($subkeyValue, $subkey, $this->deepArrays);
                            }
                        }
                        
                    } while ($subkeyValue !== null && $tokens->current === '.' || $tokens->current === '@');
                        
                    $valueItem = $subkeyValue;
                    
                } else {
                    // the rest is a string/number/bool/null
                    $valueItem = $tokens->getToken();
                    if (is_numeric($valueItem)) {
                        $valueItem = $valueItem + 0;
                    } elseif ($valueItem === 'TRUE') {
                        $valueItem = true;
                    } elseif ($valueItem === 'FALSE') {
                        $valueItem = false;
                    } elseif ($valueItem === 'NULL') {
                        $valueItem = null;
                    }
                }
                
                // collect the value
                if (is_array($value)) {
                    $value[count($value)-1] = $collectedItem ? $whitespace . $valueItem : $valueItem;
                } else {
                    $value = $collected ? $whitespace . $valueItem : $valueItem;
                }
                $collected = true;
                $collectedItem = true;
                $whitespace = '';
                
            } while ($tokens->current !== null && $tokens->current !== ')');
            return $collected;
        }
        
        protected function shorthandParse(\Chequer\Tokenizer $tokens, $contextValue) {
            try {
                $value = null;
                do {
                    $operator = null;
                    $parameter = null;
                    // collect the values
                    if (!$this->shorthandCollectValues($tokens, $contextValue, $value))
                            $value = $contextValue;
                    // return on EOT or parenthesis close...
                    if ($tokens->current === null || $tokens->current === ')') {
                        // read ')'
                        $tokens->getToken();
                        return $value;
                    }
                    // we have an operator for sure. 
                    if ($tokens->current[0] !== '$') {
                        // shorthand syntax, read as is
                        $tokens->getToken();
                    } else {
                        // full syntax, Read until whitespace
                        $operator = $tokens->getToken(self::$shcStopchar);
                    }
                    // collect the parameters
                    if (!$this->shorthandCollectValues($tokens, $contextValue, $parameter)) {
                        // another operator?
                        if ($tokens->current !== null && $tokens->current !== ')') {
                            $parameter = $this->shorthandParse($tokens, $contextValue);
                        }
                    }
                    $value = $this->chequerOperator($operator, $value, $parameter);
                } while ($tokens->current !== null && $tokens->current !== ')');
                // read ')'
                $tokens->getToken();
                    
            } catch (\Chequer\ParseBreakException $e) {
                return $e->result;
            }
            return $value;
        }
        
        /* ---------- operators ------------------------------------------------- */

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
            } elseif (is_numeric($operator) || strpos($this->specialChars, $operator[0]) !== false) {
                return $this->query($value1, array($operator => $value2));
            } else {
                return $this->chequerOperator($operator, $value1, $value2);
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
}
namespace Chequer {
    class ParseBreakException extends Exception {
        public $result;
        function __construct( $result ) {
            $this->result = $result;
        }
    }
    
    class Tokenizer {
        public $tokens;
        public $position;
        public $count;
        public $current;
        
        public $escapeChar = '\\';
        
        public function __construct($text, $regexp) {
            // split query into tokens
            preg_match($regexp, $text, $this->tokens);
            $this->count = count($this->tokens);
            $this->current = $this->tokens[0];
        }

        /** Returns concatenated tokens from current, until first one starting with character in $stopOn */
        public function getToken($stopOn = false) {
            $token = $this->current;
            while(++$this->position < $this->count) {
                if (($this->current = $this->tokens[$this->position]) === $this->escapeChar) continue;
                if (!$stopOn || isset($stopOn[$this->current])) return $token;
                $token .= $this->current;
            }
            $this->current = null;
            return $token;
        }
        
        public function eot() {
            return $this->current === null;
        }
        
        public function peek() {
            return $this->current;
        }
    }
}