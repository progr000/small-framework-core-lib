<?php

namespace Core;

use Exception;

abstract class ConsoleDriver
{
    /** @var array */
    protected $available_params = [];
    /** @var string[]  */
    protected $available_params_sys = [
        "\n-0 or -v[v[v[v]]]" => "\t\tverbose level",
        //'--log-file' => "=/path\t\tWhere to save log file, by default '{log_file}'",
        '--help' => "\t\t\t\tShow this help",
    ];

    /** @var int */
    protected $verbose_level = 1;
    /** @var string */
    protected $className;
    /** @var array */
    private $timer = [];
    /** @var bool */
    protected $is_usage = false;
    /** @var bool */
    protected $validated = false;

    /**
     * Validation, should be implemented by child
     * Started in starter()
     * @param array $actions
     * @return bool
     */
    abstract protected function validate(array $actions);

    /**
     * Initialization, should be implemented by child
     * Started in starter()
     * @return bool
     */
    abstract protected function init();

    /**
     * Start timer
     * @param string $timer_name
     * @return void
     */
    protected function timerStart($timer_name='default')
    {
        $this->timer[$timer_name]['start'] = microtime(true);
        $this->timer[$timer_name]['stop'] = 0;
    }

    /**
     * Stop timer
     * @param string $timer_name
     * @return string
     */
    protected function timerStop($timer_name='default')
    {
        if (!isset($this->timer[$timer_name]['start']))
            return 0;

        $this->timer[$timer_name]['stop'] = microtime(true);
        $diff = $this->timer[$timer_name]['stop'] - $this->timer[$timer_name]['start'];
        if ($diff < 0)
            $diff = 0;

        $this->timer[$timer_name]['diff'] = number_format($diff, 7);
        return $this->timer[$timer_name]['diff'];
    }

    /**
     * Constructor
     * @param array $arguments
     */
    final function __construct(array $arguments)
    {
        /**/
        $this->className = basename(str_replace('\\', '/', static::class));

        /* Start global timer */
        $this->timerStart('global');

        /* Start main process */
        $this->starter($arguments);
    }

    /**
     * Set value for property
     * @param string $prop_name
     * @param string $prop_val
     * @return void
     */
    private function setValueToProperty($prop_name, $prop_val)
    {
        if (property_exists($this, $prop_name)) {
            try {
                $type = gettype($this->$prop_name);
            } catch (Exception $e) {
                $type = 'string';
            }
            switch ($type) {
                case 'integer':
                    $this->$prop_name = intval($prop_val);
                    break;
                case 'double':
                    $this->$prop_name = doubleval($prop_val);
                    break;
                case 'boolean':
                    $this->$prop_name = in_array(mb_strtolower($prop_val), ['yes', '1', 'enable', 'true']);
                    break;
                default:
                    $this->$prop_name = $prop_val;
                    break;
            }
        }
    }

    /**
     * Init, start validation and then start processes
     * @param array $arguments
     * @return array|bool
     */
    protected function starter(array $arguments)
    {
        try {

            /**/
            $actions = [];
            foreach ($arguments as $v) {
                if ($v === '-0') $this->verbose_level = 0;
                elseif ($v === '-v') $this->verbose_level = 1;
                elseif ($v === '-vv') $this->verbose_level = 2;
                elseif ($v === '-vvv') $this->verbose_level = 3;
                elseif ($v === '-vvvv') $this->verbose_level = 100;
                else {
                    $tmp = explode('=', $v);
                    //$key = str_replace('-', '', trim($tmp[0]));
                    $key = trim($tmp[0]);
                    if (preg_match('/[^a-z0-9_\-]/i', $key)) {
                        $show_usage = true;
                        break;
                    }
                    $val = isset($tmp[1]) ? trim($tmp[1]) : null;
                    $actions[$key] = $val;
                    $taskName = basename(str_replace('\\', "/", $this->className)) . ':';
                    if (!isset($this->available_params[$key]) &&
                        !isset($this->available_params_sys[$key]) &&
                        !isset($this->available_params[$taskName.$key])) {
                        $show_usage = true;
                    }
                }
            }

            /* set verbose level for log driver */
            LogDriver::setVerboseLevel($this->verbose_level);

            /* set other parameters values and start processMethod */
            foreach ($actions as $prop_key => $prop_val) {

                if (strrpos($prop_key, '--set-') !== false) {

                    /* this is set variable value */
                    $prop_name = str_replace(['--set-', '-'], ['', '_'], $prop_key);
                    $this->setValueToProperty($prop_name, $prop_val);

                } elseif ($prop_val === null) {

                    /* this is name of process */
                    $prop_name = str_replace(['--', '-'], ['', '_'], $prop_key);
                    $this->setValueToProperty($prop_name, $prop_val);

                    /**/
                    $tmp = explode('_', $prop_name);
                    foreach ($tmp as $kp => $mp) {
                        if ($kp > 0) {
                            $tmp[$kp] = ucfirst($mp);
                        }
                    }
                    $process_method_names[] = implode('', $tmp);

                }

            }

            /**/
            if (empty($actions) || isset($actions['--usage']) || isset($actions['--help']) || isset($show_usage)) {
                $this->is_usage = true;
                $this->usage();
                return false;
            }

            /* initialization */
            if (!$this->init()) {
                LogDriver::error("Task initialization failed", 0, false, false);
                return false;
            }

            /* validation */
            $this->validated = $this->validate($actions);
            if ($this->validated && isset($process_method_names)) {
                foreach ($process_method_names as $process) {
                    if (method_exists($this, $process)) {
                        $this->{$process}();
                    }
                }
                return true;
            }

            /**/
            return $actions;

        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            echo "Please repair errors and try again.\n";
            return false;
        }
    }

    /**
     * Show base usage
     * @return void
     */
    public function usage()
    {
        $params = array_merge($this->available_params, $this->available_params_sys);

        $Usage = LogDriver::createMessage("\n")
            ->messageAppend("Usage: [warn]./run {$this->className} params[/warn]\n")
            ->messageAppend("\n")
            ->messageAppend("params:\n");
        foreach ($params as $k => $v) {
            $v = "[warn]{$k}[/warn]$v";
            unset($matches);
            preg_match_all('/{([a-z0-9_]*)}/i', $v, $matches);
            if (isset($matches[1][0]) && property_exists($this, $matches[1][0])) {
                $v = str_replace("{{$matches[1][0]}}", $this->{$matches[1][0]}, $v);
            }
            $Usage->messageAppend("{$v}\n");
        }
        $Usage->setType("info")->show(false, false);
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $execution_time = $this->timerStop('global');
    }
}
