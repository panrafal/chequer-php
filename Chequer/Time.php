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

class File extends DynamicChequerObject {

    protected $file;

    function __construct($object) {
        parent::__construct($object);
    }


    protected function expand() {
        if ($this->file === null) {
            if ($this->object instanceof SplFileInfo) {
                $this->file = $this->object;
            }
            new \SplFileInfo($this->object);
        }
        return $this->file;
    }


}