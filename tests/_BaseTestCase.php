<?php

namespace Tests;

use Core\App;
use Maksym\Config\ConfigException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

abstract class _BaseTestCase extends TestCase
{
    /**
     * @return void
     * @throws ConfigException
     */
    public static function setUpBeforeClass()
    {
        App::init(__DIR__ . DIRECTORY_SEPARATOR . "config/main.php");
    }

    /**
     * Get a private or protected method for testing/documentation purposes.
     * @param object $object
     * @param string $methodName
     * @param array $params
     * @return mixed
     * @throws \ReflectionException
     */
    public static function invokeNonPublicMethod($object, $methodName, ...$params)
    {
        $class = new \ReflectionClass($object);
        $method = $class->getMethod($methodName);
        return $method->invoke($object, ...$params);
    }

    /**
     * @param int $length
     * @return string
     */
    public static function randomAlphanumericString($length = 8)
    {
        if ($length < 1) {
            //throw new Exception('Length must be greater than 0');
            $length = 1;
        }

        $alfa = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $num = '0123456789';

        $max = strlen($alfa) - 1;
        $string = substr($alfa, rand(0, $max), 1);

        $allChars = $alfa . $num;
        $max = strlen($allChars) - 1;
        while (strlen($string) < $length) {
            $string .= substr($allChars, rand(0, $max), 1);
        }

        return $string;
    }

    /**
     * @return int
     */
    public static function randInt()
    {
        return rand(10000, 99999);
    }

    /**
     * Sets a protected property on a given object via reflection
     * @param $object - instance in which protected value is being modified
     * @param $property - property on instance being modified
     * @param $value - new value of the property being modified
     *
     * @throws ReflectionException
     */
    public static function setProperty($object, $property, $value)
    {
        $reflection = new ReflectionClass($object);
        $reflection_property = $reflection->getProperty($property);
        $reflection_property->setValue($object, $value);
    }
}
