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

class DynamicChequerObject extends \DynamicObject implements Chequerable {

    public function chequerOperator($operator, $value, $rule, $caller) {
        if ($this->_isCallable('operator_' . $operator)) {
            return $this->{'operator_' . $operator}($value, $rule, $caller);
        }
        /* @var $caller Chequerable */
        return $caller->chequerOperator($operator, $value, $rule, $caller);
    }


    public function chequerTypecast($typecast, $callArgs, $caller) {
        if ($this->_isCallable('typecast_' . $typecast)) {
            return $this->{'typecast_' . $typecast}($callArgs, $caller);
        }
        /* @var $caller Chequerable */
        return $caller->chequerTypecast($typecast, $callArgs, $caller);
    }


}
