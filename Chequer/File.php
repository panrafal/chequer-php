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
use SplFileInfo;
use SplFileObject;

/** File information.
 * 
 * You can use a filepath string, SplFileInfo object, or any object whose __toString() will
 * will return the filepath.
 * 
 * All properties and methods of the passed object are still accessible. If you don't pass an
 * object, but a string - SplFileinfo methods will be accessible.
 * 
 * @property-read int $size File size in bytes
 * @property-read Time $atime Access time
 * @property-read Time $mtime Mod time
 * @property-read Time $ctime Create time
 * @property-read string $extension
 * @property-read string $ext Alias for extension
 * @property-read string $name File name, without directory
 * @property-read string $path Full file path
 * @property-read string $dir Directory name, without file name
 * @property-read string $realpath Absolute file path
 * @property-read string $xpath Full file path normalized as unix path
 * @property-read string $xdir Directory name, without file name - normalized as unix path
 * @property-read string $xrealpath Absolute file path normalized as unix path
 * @property-read string $type Type of the file. Possible values are file, dir and link
 * @property-read boolean $isdir
 * @property-read boolean $isfile
 * @property-read boolean $writeable
 * @property-read boolean $readable
 * @property-read boolean $executable
 * 
 * @method SplFileObject openFile ($open_mode = r, $use_include_path = false , $context = NULL )
 */
class File extends DynamicObject {

    protected $filepath;
    
    /** Getters are predeclared for speed. To override them use setGetter().
     */
    protected $__getters = array(
        'size' => 'get_size',
        'atime' => 'get_atime',
        'mtime' => 'get_mtime',
        'ctime' => 'get_ctime',
        'extension' => 'get_extension',
        'ext' => 'get_ext',
        'name' => 'get_name',
        'path' => 'get_path',
        'dir' => 'get_dir',
        'realpath' => 'get_realpath',
        'xpath' => 'get_xpath',
        'xdir' => 'get_xdir',
        'xrealpath' => 'get_xrealpath',
        'type' => 'get_type',
        'isdir' => 'get_isdir',
        'isfile' => 'get_isfile',
        'writeable' => 'get_writeable',
        'readable' => 'get_readable',
        'executable' => 'get_executable',
        'group' => 'get_group',
        'owner' => 'get_owner',
        'perms' => 'get_perms',
    );

    protected $__methods = array(
        '__toString' => 'get_path',
    );
    

    /** @return File */
    public static function create($file) {
        if ($file instanceof File) return $file;
        return new File($file);
    }
    
    
    function __construct($file) {
        if (is_string($file)) {
            $this->filepath = $file;
        } elseif ($file instanceof SplFileInfo) {
            /* @var $file SplFileInfo */
            $this->filepath = $file->getPathname();
        } else {
            // maybe the __toString() will give us the path?
            $this->filepath = (string)$file;
        }
        parent::__construct($file);
    }

    public function __call( $name, $arguments ) {
        // instantiate SplFileInfo only when it's needed
        if ($this->__parent === null) {
            $this->__parent = new SplFileInfo($this->filepath);
        }
        return parent::__call($name, $arguments);
    }
    
    /** Converts \\ to / and removes the drive part */
    public static function unixPath($path) {
        if (!$path || strlen($path) < 2) return $path;
        if ($path[1] == ':') $path = substr($path, 2);
        return strtr($path, '\\', '/');
    }

    public function get_size() {
        return filesize($this->filepath);
    }

    public function get_mtime() {
        return new Time( filemtime($this->filepath) );
    }

    public function get_ctime() {
        return new Time( filectime($this->filepath) );
    }

    public function get_atime() {
        return new Time( fileatime($this->filepath) );
    }

    public function get_extension() {
        if ($this->__parent instanceof SplFileInfo) return $this->__parent->getExtension();
        return pathinfo($this->filepath, PATHINFO_EXTENSION);
    }

    public function get_name() {
        return basename($this->filepath);
    }

    public function get_path() {
        return $this->filepath;
    }

    public function get_dir() {
        return dirname($this->filepath);
    }

    public function get_realpath() {
        return realpath($this->filepath);
    }
    
    public function get_xdir() {
        return self::unixPath($this->get_dir());
    }
    
    public function get_xpath() {
        return self::unixPath($this->filepath);
    }

    public function get_xrealpath() {
        return self::unixPath($this->get_realpath());
    }

    public function get_group() {
        return filegroup($this->filepath);
    }

    public function get_owner() {
        return fileowner($this->filepath);
    }

    public function get_perms() {
        return fileperms($this->filepath);
    }

    public function get_type() {
        return filetype($this->filepath);
    }
    
    public function get_isdir() {
        return is_dir($this->filepath);
    }
    
    public function get_isfile() {
        return is_file($this->filepath);
    }
    
    public function get_exists() {
        return file_exists($this->filepath);
    }
    
    public function get_readable() {
        return is_readable($this->filepath);
    }
    
    public function get_writeable() {
        return is_writeable($this->filepath);
    }
    
    public function get_executable() {
        return is_executable($this->filepath);
    }
    
}