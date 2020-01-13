<?php

namespace Appwrite\Tests;

use OpenSSL\OpenSSL;
use PHPUnit\Framework\TestCase;

class OpenSSLTest extends TestCase
{
    public function setUp()
    {
    }

    public function tearDown()
    {
    }

    public function testEncryptionAndDecryption()
    {
        $key = 'my-secret-key';
        $iv = '';
        $method = OpenSSL::CIPHER_AES_128_GCM;
        $iv = OpenSSL::randomPseudoBytes(OpenSSL::cipherIVLength($method));
        $tag = null;
        $secret = 'my secret data';
        $data = OpenSSL::encrypt($secret, OpenSSL::CIPHER_AES_128_GCM, $key, 0, $iv, $tag);

        $this->assertEquals(OpenSSL::decrypt($data, $method, $key, 0, $iv, $tag), $secret);
    }
}