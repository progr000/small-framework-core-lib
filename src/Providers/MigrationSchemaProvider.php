<?php

namespace Core\Providers;

use Core\Contracts\MigrationSchema\mysqlSchemaDriver;
use Core\DbDriver;
use Core\Exceptions\DbException;
use Core\Interfaces\MigrationSchemaInterface;

class MigrationSchemaProvider
{
    /**
     * @param DbDriver $db
     * @return MigrationSchemaInterface|mysqlSchemaDriver|sqlsrvSchemaDriver|pqsqlSchemaDriver|sqliteSchemaDriver
     * @throws DbException
     */
    public function register(DbDriver $db)
    {
        $className = "Core\\Contracts\\MigrationSchema\\{$db->getDriver()}SchemaDriver";
        if (class_exists($className)) {
            return new $className($db);
        } else {
            throw new DbException('MigrationSchemaProvider::register(): method for this DbDriver not exists');
        }
    }
}