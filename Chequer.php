<?php

class Chequer {

    protected $query;
    protected $matchAll;
    protected $lookInsideArray;

    /**
     * @param $query Default query
     * @param $matchAll Default matchAll
     * @param $lookInsideArray Enable searching for subkey in arrays
     */
    function __construct( $query = false, $matchAll = null, $lookInsideArray = true ) {
        $this->query = $query;
        $this->matchAll = $matchAll;
        $this->lookInsideArray = $lookInsideArray;
    }


    public function getQuery() {
        return $this->query;
    }


    public function setQuery( $query ) {
        $this->query = $query;
    }


    public function getLookInsideArray() {
        return $this->lookInsideArray;
    }


    public function setLookInsideArray( $lookInsideArray ) {
        $this->lookInsideArray = $lookInsideArray;
    }


    public function getMatchAll() {
        return $this->matchAll;
    }


    public function setMatchAll( $matchAll ) {
        $this->matchAll = $matchAll;
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
            '_COOKIE' => $_COOKIE,
            '_SESSION' => $_SESSION,
            '_GET' => $_GET,
            '_POST' => $_POST,
            '_REQUEST' => $_REQUEST,
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
            return $value === $query;
        } elseif (is_scalar($query)) {
            if (is_array($value) && is_bool($query) == false) {
                return in_array($query, $value);
            } else {
                return $value == $query;
            }
        } elseif (is_object($query) && is_callable($query)) {
            if ($query instanceof Chequer) return $query->check($value, $matchAll);
            return call_user_func($query, $value, $query, $matchAll);
        } else {
            if ($matchAll === null)
                    $matchAll = false === (isset($query[0]) && is_scalar($query[0]));
            foreach ($query as $key => $rule) {
                $result = null;
                if (is_int($key)) {
                    $result = $this->query($value, $rule);
                } elseif ($key{0} === '$') {
                    if ($key === '$') {
                        $matchAll = $rule;
                    } else {
                        $result = call_user_func(array($this, 'checkOperator' . ucfirst(substr($key, 1))), $value, $rule);
                    }
                } else { // look in the array/hashmap
                    $result = $this->checkSubkey($value, $key, $rule, $this->lookInsideArray);
                    if ($result === null) $result = $this->query(null, $rule);
                }
                if ($result === null) continue;
                if ($matchAll && !$result) return false;
                if (!$matchAll && $result) return true;
            }

            return $matchAll;
        }
    }


    protected function checkSubkey( $value, $key, $rule, $lookInsideArray = false ) {
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
        if ($lookInsideArray && is_array($value) && isset($value[0])) {
            for ($i = 0, $length = count($value); $i < $length, isset($value[$i]); ++$i) {
                $subvalue = $value[$i];
                if (is_array($subvalue) || is_object($subvalue)) {
                    $subresult = $this->checkSubkey($subvalue, $key, $rule);
                    if ($subresult !== null) return $subresult;
                }
            }
        }
        return null;
    }


    protected function checkOperatorNot( $value, $rule ) {
        return !$this->query($value, $rule);
    }


    protected function checkOperatorEq( $value, $rule ) {
        return $value === $rule;
    }


    protected function checkOperatorGt( $value, $rule ) {
        return $value > $rule;
    }


    protected function checkOperatorGte( $value, $rule ) {
        return $value >= $rule;
    }


    protected function checkOperatorLt( $value, $rule ) {
        return $value < $rule;
    }


    protected function checkOperatorLte( $value, $rule ) {
        return $value <= $rule;
    }


    protected function checkOperatorBetween( $value, $rule ) {
        if (count($rule) != 2)
                throw new InvalidArgumentException('Two element array required for $between!');
        return $value >= $rule[0] && $value <= $rule[1];
    }


    protected function checkOperatorOr( $value, $rule ) {
        return $this->query($value, $rule, false);
    }


    protected function checkOperatorAnd( $value, $rule ) {
        return $this->query($value, $rule, true);
    }


    protected function checkOperatorRegex( $value, $rule ) {
        if (!is_string($value))
                throw new InvalidArgumentException('String required for regex matching.');
        return preg_match($rule, $value) == true;
    }


    protected function checkOperatorCheck( $value, $rule ) {
        return call_user_func($rule, $value);
    }


    protected function checkOperatorSize( $value, $rule ) {
        $length = is_string($value) ? mb_strlen($value) : count($value);
        return $this->query($length, $rule);
    }


}