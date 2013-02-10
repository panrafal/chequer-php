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
            '&&' => 'and',
            '||' => 'or',
            '+' => 'add',
            '-' => 'sub',
            '*' => 'mult',
            '/' => 'div',
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
        public function addTypecast($name, $typecast) {
            $this->typecasts[$name] = $typecast;
            return $this;
        }


        /** 
         * Adds predefined rules.
         * 
         * @return self */
        public function addRules($rules) {
            $this->rules = array_merge($this->rules, $rules);
            return $this;
        }

        /** 
         * Adds a predefined rule.
         * 
         * @return self */
        public function addRule($name, $rule) {
            $this->rules[$name] = $rule;
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

        
        /** Creates Chequer instance with query in shorthand syntax. This way you don't have to prepend it with '$ '.
         * @param $query Any non-string query will be queried traditionally. 
         * @return Chequer
         *  */
        public static function shorthand($value, $query, $matchAll = null) {
            if (is_string($query) && $query && $query{0} !== '$') $query = '$ ' . $query;
            return new static($value, $query, $matchAll = null);
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

        
        /** Checks the value against provided shorthand query.
         * @param $query Any non-string query will be queried traditionally. You don't have to prepend it with '$ '
         *  */
        public function shorthandQuery($value, $query) {
            if (is_string($query) && ($length = strlen($query)) > 0) {
                // remove '$ '
                if ($length > 2 && $query{0} === '$' && $query{1} === ' ') $query = substr($query, 2);
                return $this->shorthandQueryRun($value, $query);
            } else {
                return $this->query($value, $query);
            }
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
                    return $this->shorthandQueryRun($value, $query);
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

            try {
                foreach ($query as $key => $rule) {
                    $result = null;
                    if (is_int($key)) {
                        $result = $this->query($value, $rule);
                    } elseif ($key{0} === '$') {
                        if ($key === '$') {
                            $matchAll = ($rule === 'OR' || $rule === 'or') ? false : $rule == true;
                        } elseif ($key{1} === ' ') {
                            $result = $this->query($this->shorthandQueryRun($value, substr($key, 2)), $rule);
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
            } catch (\Chequer\ParseBreakException $e) {
                $result = $e->result;
            }
            return count($query) == 1 ? $result : $matchAll;
        }


        public function chequerOperator($operator, $value, $rule, $caller = null) {
            if ($operator[0] === '$') $operator = substr($operator, 1);
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
                    throw new \Chequer\ParseException("Typecast '$typecast' cannot be called!");
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
                    throw new \Chequer\ParseException('Array or object required for key matching.');
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
                $value = $this->shorthandQueryRun($value, $key);
            } else {
                // handle simple ones
                $value = $this->getSubkeyValue($value, $key, $deepArrays);
            }
            return $this->query($value, $rule);
        }

        
        protected function shorthandQueryRun($value, $query) {
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
            '$' => 1, '.' => 1, '@' => 1, ':' => 1,
            '(' => 1, ')' => 1, 
            '\'' => 1, '"' => 1, 
            ' ' => 1, "\t" => 1, "\r" => 1, "\n" => 1
        );
        
        
        /** Collects the value from tokens. Stops only on operators or EOT. */
        protected function shorthandCollectValues(\Chequer\Tokenizer $tokens, $contextValue, &$value) {
            // was anything collected?
            $collected = false;
            // was anything collected for current array item?
            $collectedItem = false;
            // value to concatenate on
            $collectingValue =& $value;
            $whitespace = '';
            while ($tokens->current !== null && $tokens->current !== ')') {
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
                    $whitespace = '';
                    $valueItem = substr($tokens->getToken(array($char => true)), 1);
                    // read outstanding quote
                    $tokens->getToken();
                } elseif ($char === ',') {
                    // make it into an array
                    if (!is_array($value)) {
                        $value = $collected ? array($value) : array();
                    }
                    $value[count($value)] = null;
                    $whitespace = '';
                    $collectingValue =& $value[count($value)-1];
                    $collectedItem = false;
                    $tokens->getToken();
                    continue;
                } elseif ($char === '.' || $char === '@') {
                    // subkeys!
                    $subkeyValue = $contextValue;
                    do {
                        $subkey = $tokens->getToken(self::$shcStopchar);
                    
                        if ($subkey === '.') {
                            // do nothing, use current subkeyValue
                        } elseif ($subkey === '@') {
                            throw new \Chequer\ParseException("Empty @ operator!");
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
                                        throw new \Chequer\ParseException("Subkey '$subkey' is not callable!");
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
                    
                } elseif ($tokens->current !== ':') {
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
                
                if ($tokens->current === ':') {
                    // key
                    $tokens->getToken();
                    $whitespace = '';
                    $collectedItem = false;
                    if (!is_array($value)) {
                        $value = $collected ? array($value) : array();
                    }
                    $value[$valueItem] = null;
                    $collectingValue =& $value[$valueItem];
                    continue;
                } else {
                    // collect the value
                    if (is_array($valueItem)) {
                        if (is_array($collectingValue)) {
                            $collectingValue[] = $valueItem;
                        } else {
                            $collectingValue = $collectedItem ? array($collectingValue, $valueItem) : $valueItem;
                        }
                    } else {
                        if (is_array($collectingValue)) {
                            // add new item and collect from there...
                            $collectingValue[count($collectingValue)] = null;
                            $whitespace = '';
                            $collectingValue =& $collectingValue[count($collectingValue)-1];
                            $collectedItem = false;
                        }
                        $collectingValue = $collectedItem ? $collectingValue . $whitespace . $valueItem : $valueItem;
                    }
                    $collected = true;
                    $collectedItem = true;
                }
                $whitespace = '';
                
            };
            return $collected;
        }
        
        protected function shorthandParse(\Chequer\Tokenizer $tokens, $contextValue) {
            try {
                $value = null;
                $hasResult = false;
                while ($tokens->current !== null && $tokens->current !== ')') {
                    $operator = null;
                    $parameter = null;
                    // collect the values
                    if (!$this->shorthandCollectValues($tokens, $contextValue, $value) 
                            && !$hasResult
                    ) {
                        $value = $contextValue;
                    }
                    // return on EOT or parenthesis close...
                    if ($tokens->current === null || $tokens->current === ')') {
                        // read ')'
                        $tokens->getToken();
                        return $value;
                    }
                    // we have an operator for sure. 
                    if ($tokens->current[0] !== '$') {
                        // shorthand syntax, read as is
                        $operator = $tokens->getToken();
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
                    $hasResult = true;
                };
                // read ')'
                $tokens->getToken();
                    
            } catch (\Chequer\ParseBreakException $e) {
                $this->shorthandFastforward($tokens, array(')' => true));
                return $e->result;
            }
            return $value;
        }
        
        protected function shorthandFastforward(Nette\Utils\Tokenizer $tokens, $until) {
            
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
                    throw new Chequer\ParseBreakException('Two element array required for $between!');
            return $value >= $rule[0] && $value <= $rule[1];
        }

        protected function operator_in( $value, $rule ) {
            if (is_scalar($rule)) return $value == $rule;
            return in_array($value, $rule);
        }        

        protected function operator_or( $value, $rule ) {
            if (is_scalar($rule)) {
                if ($value || $rule) {
                    throw new Chequer\ParseBreakException(true);
                } else {
                    return false;
                }
            } else {
                return $this->query($value, $rule, false);
            }
        }


        protected function operator_and( $value, $rule ) {
            if (is_scalar($rule)) {
                if ($value && $rule) {
                    return true;
                } else {
                    throw new Chequer\ParseBreakException(false);
                }
            } else {
                return $this->query($value, $rule, true);
            }
        }


        protected function operator_regex( $value, $rule ) {
            if (!is_scalar($value) && !method_exists($value, '__toString'))
                    throw new \Chequer\ParseException('String required for regex matching.');
            if ($rule[0] !== '/' && $rule[0] !== '#' && $rule[0] !== '~') {
                $rule = "~{$rule}~";
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

        protected function operator_eval( $value, $rule ) {
            if (!is_array($rule)) throw new \Chequer\ParseException('$eval rule should be an array!');
            foreach($rule as $query) {
                $value = $this->query($value, $query);
            }
            return $value;
        }        

        protected function operator_rule( $value, $rules ) {
            if (!is_array($rules)) $rules = preg_split('/\s*,?\s+/', trim($rules));

            if (count($rules) > 1) {
                $query = array('$' => 'AND');
                foreach($rules as $rule) {
                    if ($rule === 'AND') {
                        $query['$'] = 'AND';
                    } elseif ($rule === 'OR') {
                        $query['$'] = 'OR';
                    } else {
                        if (($not = ($rule{0} === '!'))) {
                            $rule = substr($not, 1);
                        }
                        if (!isset($this->rules[$rule])) throw new \Chequer\ParseException("Rule '$rule' is undefined!");
                        $query[] = $not ? ['$not' => $this->rules[$rule]] : $this->rules[$rule];
                    }
                }
                return $this->query($value, $query);
            } else {
                if (!isset($this->rules[$rules[0]])) throw new \Chequer\ParseException("Rule '{$rules[0]}' is undefined!");
                return $this->query($value, $this->rules[$rules[0]]);
            }
        }    

        protected function operator_add( $value, $operand ) {
            if (is_numeric($value) && is_numeric($operand)) {
                return $value + $operand;
            }
            if (is_array($operand)) {
                return array_merge((array)$value, $operand);
            }
            if (is_array($value)) {
                $value[] = $operand;
                return $value;
            }
            return $value . $operand;
        }        
        

        protected function operator_sub( $value, $operand ) {
            if (is_numeric($value) && is_numeric($operand)) {
                return $value - $operand;
            }
            if (is_array($operand)) {
                return array_diff((array)$value, $operand);
            }
            if (is_array($value)) {
                return array_filter($value, function($a) use ($operand) {return $a !== $operand;});
            }
            return str_replace($operand, '', $value);
        }        
        
        protected function operator_mult( $value, $operand ) {
            if (is_numeric($value) && is_numeric($operand)) {
                return $value * $operand;
            }
            if ($this->strictMode) 
                throw new \Chequer\ParseException("Bad operand types for *");
            else 
                return null;
        }        
        
        protected function operator_div( $value, $operand ) {
            if (is_numeric($value) && is_numeric($operand)) {
                return $value / $operand;
            }
            if ($this->strictMode) 
                throw new \Chequer\ParseException("Bad operand types for *");
            else 
                return null;
        }        
        
    }
}
namespace Chequer {
    class ParseBreakException extends \Exception {
        public $result;
        function __construct( $result ) {
            $this->result = $result;
        }
    }
    
    class ParseException extends \Exception {
        
    }
    
    /** Ultra-fast tokenizer */
    class Tokenizer {
        public $tokens;
        public $position;
        public $count;
        public $current;
        public $escaped = false;
        
        public $escapeChar = '\\';
        
        public function __construct($text, $regexp) {
            // split query into tokens
            preg_match_all($regexp, $text, $this->tokens);
            $this->tokens = $this->tokens[0];
            $this->count = count($this->tokens);
            $this->current = $this->tokens[0];
        }

        /** Returns concatenated tokens from current, until first one starting with character in $stopOn.
         * Moves the current token to new position.
         * 
         * @param $stopOn Hashmap of characters to stop on. array('char' => 1). If FALSE, will fetch only current token.
         */
        public function getToken($stopOn = false) {
            $token = $this->escaped ? null : $this->current;
            while(++$this->position < $this->count) {
                if (($this->current = $this->tokens[$this->position]) === $this->escapeChar && !$this->escaped) {
                    $this->escaped = true;
                    if (!$stopOn) return $token;
                    continue;
                } elseif ($this->escaped) {
                    $this->escaped = false;
                } elseif (!$stopOn || isset($stopOn[$this->current])) return $token;
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