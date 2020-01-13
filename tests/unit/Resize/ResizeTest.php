<?php

namespace Appwrite\Tests;

use Resize\Resize;
use PHPUnit\Framework\TestCase;

class ResizeTest extends TestCase
{
    public function setUp()
    {
    }

    public function tearDown()
    {
    }

    public function testCrop100x100()
    {
        $resize = new Resize(\file_get_contents(__DIR__ . '/../../resources/disk-a/kitten-1.jpg'));
        $target = __DIR__.'/100x100.jpg';
        $original = __DIR__.'/../../resources/resize/100x100.jpg';

        $resize->crop(100, 100);

        $resize->save($target, 'jpg', 100);

        $this->assertEquals(is_readable($target), true);
        $this->assertEquals(\md5(\file_get_contents($target)), \md5(\file_get_contents($original)));

        \unlink($target);
    }

    public function testCrop100x400()
    {
        $resize = new Resize(\file_get_contents(__DIR__ . '/../../resources/disk-a/kitten-1.jpg'));
        $target = __DIR__.'/100x400.jpg';
        $original = __DIR__.'/../../resources/resize/100x400.jpg';

        $resize->crop(100, 400);

        $resize->save($target, 'jpg', 100);

        $this->assertEquals(is_readable($target), true);
        $this->assertEquals(\md5(\file_get_contents($target)), \md5(\file_get_contents($original)));

        \unlink($target);
    }

    public function testCrop400x100()
    {
        $resize = new Resize(\file_get_contents(__DIR__ . '/../../resources/disk-a/kitten-1.jpg'));
        $target = __DIR__.'/400x100.jpg';
        $original = __DIR__.'/../../resources/resize/400x100.jpg';

        $resize->crop(400, 100);

        $resize->save($target, 'jpg', 100);

        $this->assertEquals(is_readable($target), true);
        $this->assertEquals(\md5(\file_get_contents($target)), \md5(\file_get_contents($original)));

        \unlink($target);
    }

    public function testCrop100x100WEBP()
    {
        $resize = new Resize(\file_get_contents(__DIR__ . '/../../resources/disk-a/kitten-1.jpg'));
        $target = __DIR__.'/100x100.webp';
        $original = __DIR__.'/../../resources/resize/100x100.webp';

        $resize->crop(100, 100);

        $resize->save($target, 'webp', 100);

        $this->assertEquals(is_readable($target), true);
        $this->assertEquals(\md5(\file_get_contents($target)), \md5(\file_get_contents($original)));

        \unlink($target);
    }

    public function testCrop100x100PNG()
    {
        $resize = new Resize(\file_get_contents(__DIR__ . '/../../resources/disk-a/kitten-1.jpg'));
        $target = __DIR__.'/100x100.png';
        $original = __DIR__.'/../../resources/resize/100x100.png';

        $resize->crop(100, 100);

        $resize->save($target, 'png', 100);

        $this->assertEquals(is_readable($target), true);
        $this->assertEquals(\filesize($target), \filesize($original));
        $this->assertEquals(\mime_content_type($target), \mime_content_type($original));

        \unlink($target);
    }

    public function testCrop100x100PNGQuality30()
    {
        $resize = new Resize(\file_get_contents(__DIR__ . '/../../resources/disk-a/kitten-1.jpg'));
        $target = __DIR__.'/100x100-q30.jpg';
        $original = __DIR__.'/../../resources/resize/100x100-q30.jpg';

        $resize->crop(100, 100);

        $resize->save($target, 'jpg', 10);

        $this->assertEquals(is_readable($target), true);
        $this->assertEquals(\filesize($target), \filesize($original));
        $this->assertEquals(\mime_content_type($target), \mime_content_type($original));

        \unlink($target);
    }

    public function testCrop100x100GIF()
    {
        $resize = new Resize(\file_get_contents(__DIR__ . '/../../resources/disk-a/kitten-3.gif'));
        $target = __DIR__.'/100x100.gif';
        $original = __DIR__.'/../../resources/resize/100x100.gif';

        $resize->crop(100, 100);

        $resize->save($target, 'gif', 100);

        $this->assertEquals(is_readable($target), true);
        $this->assertEquals(\filesize($target), \filesize($original));
        $this->assertEquals(\mime_content_type($target), \mime_content_type($original));

        \unlink($target);
    }

    public function testCrop100x100Output()
    {
        $resize = new Resize(\file_get_contents(__DIR__ . '/../../resources/disk-a/kitten-1.jpg'));
        $original = __DIR__.'/../../resources/resize/100x100.jpg';

        $resize->crop(100, 100);

        $result = $resize->output('jpg', 100);

        $this->assertEquals(!empty($result), true);
        $this->assertEquals(\md5($result), \md5(\file_get_contents($original)));
    }
}