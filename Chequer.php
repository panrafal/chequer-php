<?php

class Chequer {

    /** Checks rules against current server environment. 
     * Available keys are everything from $_SERVER, _ENV, _COOKIE, _SESSION, _GET, _POST, _REQUEST.
     * 
     *  */
    public static function checkEnvironment($query, $matchAll = true) {
        $ch = new Chequer;
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
        
        return $ch->check($table, $query, $matchAll);
    }
    
    public static function checkValue($value, $query, $matchAll = null) {
        $ch = new static;
        return $ch->check($value, $query, $matchAll);
    }
    
    /** 
     * See Readme.md for documentation
     * 
     * @param $value Value to check
     * @param $query Query to match
     * @param $matchAll TRUE to require all first level queries, FALSE to require only one
     *  */
    public function check($value, $query, $matchAll = null) {
        if ($query === null || $query === false) {
            return $value === $query;
        } elseif (is_scalar($query)) {
            if (is_array($value) && is_bool($query) == false) {
                return in_array($query, $value);
            } else {
                return $value == $query;
            }
        } elseif ($query instanceof Closure) {
            return call_user_func($query, $value, $query, $matchAll);
        } else {
            if ($matchAll === null) $matchAll = false === (isset($query[0]) && is_scalar($query[0]));
            foreach($query as $key => $rule) {
                $result = null;
                if (is_int($key)) {
                    $result = $this->check($value, $rule);
                } elseif ($key{0} === '$') {
                    if ($key === '$') {
                        $matchAll = $rule;
                    } else {
                        $result = call_user_func(array($this, 'checkOperand'.ucfirst(substr($key, 1))), $value, $rule);
                    }
                } else { // look in the array/hashmap
                    $result = $this->checkSubkey($value, $key, $rule, true);
                    if ($result === null) $result = $this->check(null, $rule);
                }
                if ($result === null) continue;
                if ($matchAll && !$result) return false;
                if (!$matchAll && $result) return true;
            }
            
            return $matchAll;
        }
    }
    
    protected function checkSubkey($value, $key, $rule, $goDepper = false) {
        if (!is_array($value) && !is_object($value)) throw new InvalidArgumentException('Array or object required for key matching.');
        
        if (is_array($value) || $value instanceof ArrayAccess) {
            if (isset($value[$key])) return $this->check($value[$key], $rule);
        }
        if (is_object($value)) {
            if (isset($value->$key)) return $this->check($value[$key], $rule);
        }
        if ($goDepper && is_array($value) && isset($value[0])) {
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
    
    protected function checkOperandNot($value, $rule) {
        return !$this->check($value, $rule);
    }
    
    protected function checkOperandEq($value, $rule) {
        return $value === $rule;
    }
    
    protected function checkOperandOr($value, $rule) {
        return $this->check($value, $rule, false);
    }
    
    protected function checkOperandAnd($value, $rule) {
        return $this->check($value, $rule, true);
    }
    
    protected function checkOperandRegex($value, $rule) {
        if (!is_string($value)) throw new InvalidArgumentException('String required for regex matching.');
        return preg_match($rule, $value) == true;
    }

    protected function checkOperandCall($value, $rule) {
        return call_user_func($rule, $value);
    }
    
}