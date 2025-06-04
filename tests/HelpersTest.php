<?php

namespace Tests;

use Core\App;
use Core\ConfigDriver;
use Core\CookieDriver;
use Core\LocalizationDriver;
use Core\SessionDriver;
use Core\WgetDriver;

class HelpersTest extends _BaseTestCase
{
    /**
     * @return void
     */
    public function testReplaceVars()
    {
        $str1 = '{%H} {%W}!';
        $str2 = ':H :W!';
        $replace_arr = [
            'H' => 'Hello',
            'W' => 'World',
        ];
        $str_res1 = replace_vars($str1, $replace_arr);
        $this->assertEquals("Hello World!", $str_res1);
        $str_res2 = replace_vars($str2, $replace_arr);
        $this->assertEquals("Hello World!", $str_res2);
    }

    /**
     * @return void
     */
    public function testConfig()
    {
        // check receive config object with null key
        $conf = config();
        $this->assertInstanceOf(ConfigDriver::class, $conf);
        $this->assertTrue(method_exists($conf, 'get') && method_exists($conf, 'exist'));
        $this->assertTrue($conf->exist('test-param'));
        $this->assertEquals('test-value', $conf->get('test-param'));

        // check receive config var with key
        $this->assertEquals('test-value', config('test-param'));
        $this->assertEquals(111, config('test-param-not-exist', 111));
    }

    /**
     * @return void
     */
    public function testSession()
    {
        // check receive config object with null key
        $sess = session();
        $this->assertInstanceOf(SessionDriver::class, $sess);
        $this->assertTrue(method_exists($sess, 'get') && method_exists($sess, 'has'));
        $_SESSION['app-small-framework']['test-param'] = self::randomAlphanumericString();
        $this->assertTrue($sess->has('test-param'));
        $this->assertEquals($_SESSION['app-small-framework']['test-param'], $sess->get('test-param'));

        // check receive config var with key
        $this->assertEquals($_SESSION['app-small-framework']['test-param'], session('test-param'));
        $this->assertEquals(111, session('test-param-not-exist', 111));
    }

    /**
     * @return void
     */
    public function testCookie()
    {
        // check receive config object with null key
        $cookie = cookie();
        $this->assertInstanceOf(CookieDriver::class, $cookie);
        $this->assertTrue(method_exists($cookie, 'get') && method_exists($cookie, 'has'));

        $value_for_test = self::randomAlphanumericString();
        $_COOKIE['test-cookie-var'] = openssl_encrypt(
            serialize($value_for_test),
            "AES-128-ECB",
            App::$config->get('cookie-enc-key')
        );
        $this->assertEquals($value_for_test, cookie('test-cookie-var'));
    }

    /**
     * @return void
     */
    public function testTranslateFunction()
    {
        App::$locale = "en";
        App::$config->set('localization', [
            'json-path' => __DIR__ . '/config/',
            'default-locale' => 'en',
            'available-locales' => [
                'en' => 'English',
                'de' => 'Deutsch',
            ],
        ]);
        App::$localization->init();

        $this->assertEquals("Name", __("Name-key"));
        $this->assertEquals("NonExisted-key", __("NonExisted-key"));
    }

    /**
     * @return void
     */
    public function testAsset()
    {
        $res = asset("///test.css");
        $this->assertEquals('/test.css', $res);
    }

    /**
     * @return void
     */
    public function testMinimize()
    {
        $str = <<<EOF
<script>
a =    1;
alert(a); 
</script>
EOF;
        $res = minimize($str);
        $this->assertEquals($str, $res);
        config()->set('minimize-plain-css-js', true);
        $res = minimize($str);
        $this->assertEquals("<script>a=1;alert(a);</script>", $res);
    }

    /**
     * @return void
     */
    public function testUrl()
    {
        $res = url("/index.html", ['a' => 1, 'b' => 2]);
        $this->assertEquals("/index.html?a=1&b=2", $res);
        $res = url("/index.html", "?c=3&d=4");
        $this->assertEquals("/index.html?c=3&d=4", $res);
    }

    /**
     * @return void
     */
    public function testReplaceMultiSpacesAndNewLine()
    {
        $str = <<<EOF
      test   string
      
      new   line   string
EOF;
        $res = replaceMultiSpacesAndNewLine($str);
        $this->assertEquals("test string new line string", $res);
    }

    /**
     * @return void
     */
    public function testHttp()
    {
        $ret = http();
        $this->assertEquals(WgetDriver::class, get_class($ret));
        $this->assertTrue(method_exists($ret, 'post'));
    }

    /**
     * @return void
     */
    public function testHttpClient()
    {
        $ret = http();
        $this->assertEquals(WgetDriver::class, get_class($ret));
        $this->assertTrue(method_exists($ret, 'post'));
    }

    /**
     * @return void
     */
    public function testSizeFormat()
    {
        $res = size_format(2000);
        $this->assertEquals("1.95 KB", $res);
        $res = size_format(2000000, 3, '-', 'KB');
        $this->assertEquals("1953.125-KB", $res);
        $res = size_format(2000000, 3, ' ');
        $this->assertEquals("1.907 MB", $res);
        $res = size_format(2000000, 3, ' ', 'KB', true);
        $this->assertEquals("1953.125", $res);
    }
}