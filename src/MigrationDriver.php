<?php

namespace Core;

use Core\Exceptions\DbException;
use Exception;

class MigrationDriver
{
    /** @var MigrationDriver */
    private static $instance;
    /** @var array */
    private $errors = [];

    /** @var string */
    private static $lock_file;
    /** @var string */
    const PATH_CLASS = "Db\\migrations\\";
    /** @var string */
    private static $FOLDER;
    /** @var string */
    private static $TPL;
    /** @var string */
    const TABLE_LIST_MIGRATIONS = "database_migrations";
    /** @var string */
    const COLUMN_NAME = "migration_name";

    /** @var array */
    private $done_list;
    /** @var array */
    private $undone_list;


    /**
     * Constructor
     * @throws DbException
     */
    private function __construct()
    {
        $this->init();
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        @unlink(self::$lock_file);
    }

    /**
     * @param string $migration_dir
     * @param bool $delete_lock
     * @return MigrationDriver
     * @throws Exception
     */
    public static function getInstance($migration_dir, $delete_lock = false)
    {
        self::$FOLDER = $migration_dir;
        self::$TPL = self::$FOLDER . "/tpl/mTPL.php";
        self::$lock_file = sys_get_temp_dir() . "/migration-app.lock";
        if ($delete_lock) {
            @unlink(self::$lock_file);
        }
        if (file_exists(self::$lock_file)) {
            LogDriver::error("Now another migration process has been started. It is not possible to start a new process until the previous one is finished.");
            throw new Exception('', 500);
        } else {
            @file_put_contents(self::$lock_file, date("Y-m-d H:i:s"));
        }

        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialization before migrations begin
     * Get data abut done and undone migrations
     * @return void
     * @throws DbException
     */
    private function init()
    {
        $fs_list = [];
        $this->done_list = [];
        $this->undone_list = [];

        $list_in_folder = scandir(self::$FOLDER, SCANDIR_SORT_ASCENDING);
        foreach ($list_in_folder as $v) {
            if (is_dir(self::$FOLDER . DIRECTORY_SEPARATOR . $v)) {
                continue;
            }
            if (strrpos($v, '.php') === false) {
                continue;
            }
            if (strpos($v, 'm') !== 0) {
                continue;
            }
            $fs_list[] = $v;
        }

        try {
            $list_in_db = App::$db->getAll("SELECT " . self::COLUMN_NAME . " as m FROM {{" . self::TABLE_LIST_MIGRATIONS . "}} ORDER BY migration_name");
            if ($list_in_db) {
                foreach ($list_in_db as $v) {
                    $this->done_list[] = $v['m'];
                }
            }

            $this->undone_list = array_diff($fs_list, $this->done_list);
        } catch (DbException $e) {
            @unlink(self::$lock_file);
            throw new DbException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Execute migration Class
     * @param string $file
     * @param string $method
     * @return mixed
     */
    private function execute($file, $method)
    {
        try {
            $class = self::PATH_CLASS . mb_substr($file, 0, strpos($file, '.'));
            $migration = new $class();
            if ($migration->{$method}()) {
                return true;
            } else {
                $this->errors = array_merge($this->errors, $migration->getErrors());
                return false;
            }
        } catch (Exception $e) {
            LogDriver::error($e->getMessage(), 1);
            LogDriver::error($e->getFile() . " (Line: " . $e->getLine() . ")", 2);
            LogDriver::error($e->getTraceAsString(), 3);
            @unlink(self::$lock_file);
            return false;
        }
    }

    /**
     * Execute UP method for all (or count=steps) undone migration classes
     * @param int|null $steps
     * @return bool
     * @throws DbException
     */
    public function up($steps = null)
    {
        if (!sizeof($this->undone_list)) {
            LogDriver::success("All migrations already up-to-date.", 0);
        } else {
            //App::$db->beginTransaction(); // CREATE, ALTER, DROP can't use with transaction in MySQL
            $cnt = 0;
            foreach ($this->undone_list as $v) {
                if ($steps === null || $cnt < $steps) {
                    $executeMigration = LogDriver::executingMessage("Installing migration [warn]{$v}[/warn]", 0);
                    if ($this->execute($v, 'up') !== false) {
                        App::$db->exec("INSERT INTO {{" . self::TABLE_LIST_MIGRATIONS . "}} (" . self::COLUMN_NAME . ") VALUES (:name)", ['name' => $v]);
                        $executeMigration->showSuccess();
                    } else {
                        App::$db->rollBack();
                        $executeMigration->showError();
                        //LogDriver::warning(App::$db->getErrors(), 0);
                        if (count($this->errors)) {
                            LogDriver::warning($this->errors, 1);
                        }
                        return false;
                    }
                    $cnt++;
                }
            }
            App::$db->commit();
            LogDriver::success("Successfully finished. [warn]{$cnt}[/warn] migrations applied.", 0);
        }

        return true;
    }

    /**
     * Execute DOWN method for last one (or last count=steps) done migration classes
     * @param int $steps
     * @return bool
     * @throws DbException
     */
    public function down($steps = null)
    {
        if (!sizeof($this->done_list)) {
            LogDriver::success("All migrations already uninstalled.", 0);
        } else {
            //App::$db->beginTransaction(); // CREATE, ALTER, DROP can't use with transaction in MySQL
            if ($steps === null) $steps = 1;
            $cnt = 0;
            while (null !== ($v = array_pop($this->done_list))) {
                if ($cnt < $steps) {
                    $executeMigration = LogDriver::executingMessage("Uninstalling migration [warn]{$v}[/warn]", 0);
                    if ($this->execute($v, 'down') !== false) {
                        App::$db->exec("DELETE FROM {{" . self::TABLE_LIST_MIGRATIONS . "}} WHERE " . self::COLUMN_NAME . " =  :name", ['name' => $v]);
                        $executeMigration->showSuccess();
                    } else {
                        App::$db->rollBack();
                        $executeMigration->showError();
                        //LogDriver::warning(App::$db->getErrors(), 0);
                        LogDriver::warning($this->errors, 0);
                        return false;
                    }
                    $cnt++;
                }
            }
            App::$db->commit();
            LogDriver::success("Successfully finished. [warn]{$cnt}[/warn] migration uninstalled", 0);
        }

        return true;
    }

    /**
     * Create new migration class
     * @param string $name
     * @return bool
     */
    public function create($name)
    {
        /**/
        $pattern = "/^[a-z_]+[a-z0-9\_]{0,99}$/i";
        if (!preg_match($pattern, $name)) {
            LogDriver::error("The name you given is not valid for migration-name (table-name). Use pattern = $pattern", 0);
            return false;
        }

        /**/
        if (!file_exists(self::$TPL)) {
            LogDriver::error("System error: the template for create migration file not found.", 0);
            return false;
        }

        /**/
        if (!file_exists(self::$FOLDER) || !is_dir(self::$FOLDER) || !is_writable(self::$FOLDER))
        {
            LogDriver::error("System error: the folder for store migrations is not writable.", 0);
            return false;
        }

        /**/
        $name = "m" . date('Ymd_His_') . $name;
        $content = file_get_contents(self::$TPL);
        $content = str_replace("__NEW_CLASS_NAME__", $name, $content);
        $file_name = self::$FOLDER . "/{$name}.php";
        file_put_contents($file_name, $content);
        chmod($file_name, 0777);
        LogDriver::success("Successfully created. You can edit [warn]" . realpath($file_name) . "[/warn] now, and then run migrate.", 0);
        return true;
    }

    /**
     * Undone all migration and then done all migrations
     * @return void
     * @throws DbException
     */
    public function reset()
    {
        LogDriver::warning('Execute uninstall all migrations');
        $this->down(sizeof($this->done_list));
    }

    /**
     * Undone all migrations
     * @return void
     * @throws DbException
     */
    public function refresh()
    {
        $this->reset();
        $this->init();

        LogDriver::warning('Execute install all migrations');
        $this->up();
    }
}