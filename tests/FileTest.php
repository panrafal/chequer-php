<?php

require_once __DIR__ . '/../DynamicObject.php';
require_once __DIR__ . '/../Chequer.php';
require_once __DIR__ . '/../Chequer/DynamicChequerObject.php';
require_once __DIR__ . '/../Chequer/File.php';
require_once __DIR__ . '/../Chequer/Time.php';

class TimeTest extends PHPUnit_Framework_TestCase {

    /** @dataProvider fileProvider */
    public function testFile($fileobject) {
        $file = new Chequer\File($fileobject);
        
        if ($fileobject instanceof SplFileInfo) $filepath = $fileobject->getPathname();
        else $filepath = (string)$fileobject;
        
        $unixpath = preg_replace('~^\w:~', '', strtr($filepath, '\\', '/'));
        $unixrealpath = preg_replace('~^\w:~', '', strtr(realpath($filepath), '\\', '/'));
        $spl = new SplFileInfo($filepath);
        
        $this->assertEquals($filepath, (string)$file);
        $this->assertEquals(dirname($filepath), $file->dir, 'Dir');
        $this->assertEquals($filepath, $file->path, 'Path');
        $this->assertEquals(basename($filepath), $file->name, 'Name');
        $this->assertEquals($spl->getRealPath(), $file->realpath, 'Realpath');
        
        $this->assertEquals(dirname($unixpath), $file->xdir, 'XDir');
        $this->assertEquals($unixpath, $file->xpath, 'XPath');
        $this->assertEquals($unixrealpath, $file->xrealpath, 'XRealpath');
        
        $this->assertEquals($spl->getExtension(), $file->extension, 'Ext');
        $this->assertEquals($spl->isDir(), $file->isdir, 'isDir');
        $this->assertEquals($spl->isFile(), $file->isfile, 'isFile');
        $this->assertEquals($spl->isReadable(), $file->readable, 'Readable');
        $this->assertEquals($spl->isWritable(), $file->writeable, 'Writeable');
        $this->assertEquals(file_exists($filepath), $file->exists, 'Exists');
        
        if (file_exists($filepath)) {
            $this->assertEquals($spl->getType(), $file->type, 'Type');
            $this->assertEquals($spl->getSize(), $file->size, 'Size');
            $this->assertEquals(new Chequer\Time($spl->getATime()), $file->atime, 'ATime');
            $this->assertEquals(new Chequer\Time($spl->getMTime()), $file->mtime, 'MTime');
            $this->assertEquals(new Chequer\Time($spl->getCTime()), $file->ctime, 'CTime');
            $this->assertEquals(strftime('%Y', $spl->getCTime()), $file->ctime->year, 'CTime');
        }
        
        $this->assertEquals($spl->isFile(), $file->isFile(), 'SPL');
        $this->assertEquals($spl->getExtension(), $file->getExtension(), 'SPL');
        
    }
    
    public function fileProvider() {
        $array = array(
            array(__FILE__),
            array(__DIR__),
            array('.'),
            array('../..'),
            array('missingFile.txt'),
            array(new SplFileInfo(__FILE__)),
            array(new SplFileInfo(getcwd())),
        );
        $result = array();
        foreach($array as $i => $item) {
            if (is_int($i)) $result["#$i: " . $item[0]] = $item;
            else $result[$i] = $item;
        }
        return $result;
    }
    
    
}