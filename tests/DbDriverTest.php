<?php

namespace Tests;

use Core\DbDriver;
use Core\QueryBuilderDriver;

class DbDriverTest extends _BaseTestCase
{
    /** @var DbDriver */
    private static $instance;

    /**
     * @return void
     * @throws \Core\Exceptions\ConfigException
     * @throws \Core\Exceptions\IntegrityException
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        config()->set('databases', [
            'default-db-connection-name' => 'sqlite-for-developing',
            'sqlite-for-developing' => [
                'dsn' => "sqlite:/tmp/test-sqlite-for-developing.sq3",
                'user' => 'any',
                'password' => 'any',
                'table_prefix' => 'tbl_',
                'charset' => 'utf8',
            ]
        ]);
        self::$instance = DbDriver::getInstance('sqlite-for-developing');
    }

    /**
     * @return void
     */
    public function testGetInstance()
    {
        $this->assertInstanceOf(DbDriver::class, self::$instance);
    }

    /**
     * @return void
     */
    public function testGetDriver()
    {
        $this->assertEquals('sqlite', self::$instance->getDriver());
    }

    /**
     * @return void
     */
    public function testGetConnectionName()
    {
        $this->assertEquals('sqlite-for-developing', self::$instance->getConnectionName());
    }

    /**
     * @return void
     */
    public function testGetSqlQuote()
    {
        $this->assertEquals("", self::$instance->getSqlQuote());
    }

    /**
     * @return void
     */
    public function testGetTablePrefix()
    {
        $this->assertEquals("tbl_", self::$instance->getTablePrefix());
    }

    /**
     * @return void
     * @throws \Core\Exceptions\DbException
     */
    public function testGetAll()
    {
        $res = self::$instance->getAll("SELECT date() as d");
        $this->assertArrayHasKey(0, $res);
        $this->assertArrayHasKey('d', $res[0]);
        $this->assertContains(date('Y-m-d'), $res[0]['d']);
    }

    /**
     * @return void
     * @throws \Core\Exceptions\DbException
     */
    public function testGetOne()
    {
        $res = self::$instance->getOne("SELECT date() as d");
        $this->assertArrayHasKey('d', $res);
        $this->assertContains(date('Y-m-d'), $res['d']);
    }

    /**
     * @return void
     */
    public function testTable()
    {
        $res = self::$instance->table("test");
        $this->assertInstanceOf(QueryBuilderDriver::class, $res);
    }

    /**
     * @return void
     * @throws \Core\Exceptions\DbException
     */
    public function testPrepareValType()
    {
        $res = self::$instance->prepareValType(10);
        $this->assertTrue(is_int($res));
        $this->assertEquals(10, $res);
        $res = self::$instance->prepareValType("10");
        $this->assertTrue(is_string($res));
        $this->assertEquals("'10'", $res);
        $res = self::$instance->prepareValType(10.10);
        $this->assertTrue(is_string($res));
        $this->assertEquals("'10.1'", $res);
        $res = self::$instance->prepareValType(true);
        $this->assertTrue(is_int($res));
        $this->assertEquals(1, $res);
        $res = self::$instance->prepareValType(false);
        $this->assertTrue(is_int($res));
        $this->assertEquals(0, $res);
        $res = self::$instance->prepareValType(null);
        $this->assertTrue(is_string($res));
        $this->assertEquals("null", $res);
    }

    /**
     * @return void
     * @throws \Core\Exceptions\DbException
     */
    public function testPrepareSql()
    {
        $res = self::$instance->prepareSql("SELECT a, b, c FROM {{test}} WHERE a=:aa AND b=:bb AND c=:cc", [
            'aa' => 1, 'bb' => "b1", 'cc' => null
        ]);
        $this->assertEquals("SELECT a, b, c FROM tbl_test WHERE a=1 AND b='b1' AND c=null", $res);
    }

    /**
     * @return void
     * @throws \Core\Exceptions\DbException
     */
    public function testExec()
    {
        $res = self::$instance->exec("SELECT d FROM test");
        $this->assertEquals(false, $res);
        $err = self::$instance->getErrors();
        $this->assertArrayHasKey(0, $err);
        $this->assertContains("no such table", $err[0]);
        $res = self::$instance->exec("SELECT date() as d, :aa as a, :bb as b, :cc as c", [
            'aa' => 1,
            'bb' => 'b1',
            'cc' => null,
            'dd' => 'd1'
        ]);
        $this->assertInstanceOf(\PDOStatement::class, $res);
        $this->assertAttributeContains("SELECT date() as d, :aa as a, :bb as b, :cc as c", "queryString", $res);
        $this->assertEquals([
            'd' => date('Y-m-d'),
            'a' => 1,
            'b' => "b1",
            'c' => null,
        ], $res->fetch(\PDO::FETCH_ASSOC));
    }

    /**
     * @return void
     */
    public function testLastInsert()
    {
        $this->assertEquals(0, self::$instance->lastInsert());
    }

    /**
     * @return void
     */
    public function testAffectedRows()
    {
        $this->assertEquals(0, self::$instance->affectedRows());
    }
}