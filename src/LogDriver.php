<?php

namespace Core;

class LogDriver
{
    /** @var array[] */
    private $replace = [
        'default' => ['cli' => "\033[0m", 'html' => '<span style="color: #cccccc">'],
        'success' => ['cli' => "\033[32m", 'html' => '<span style="color: #6caa36">'],
        'info' => ['cli' => "\033[36m", 'html' => '<span style="color: #0e90d2">'],
        'warning' => ['cli' => "\033[33m", 'html' => '<span style="color: #cc9900">'],
        'warn' => ['cli' => "\033[33m", 'html' => '<span style="color: #cc9900">'],
        'danger' => ['cli' => "\033[33m", 'html' => '<span style="color: #cc9900">'],
        'error' => ['cli' => "\033[31m", 'html' => '<span style="color: #d02a2c">'],
    ];
    /** @var string */
    private $cli_or_html;
    /** @var null|false|resource */
    public static $log_resource = null;
    /** @var int */
    private static $verbose_level = 1;
    /** @var \stdClass */
    private $msg;
    /** @var int */
    private static $instancesCount = 0;
    /** @var string */
    private static $path_to_log_file;
    /** @var bool */
    public static $execute_ob_end_flush = true;

    /**
     * Constructor
     */
    private function __construct()
    {
        //dump('construct');
        $this->cli_or_html = (PHP_SAPI === 'cli') ? 'cli' : 'html';
        if (self::$log_resource === null && self::$path_to_log_file !== null) {
            //dump('open file');
            if (false === (self::$log_resource = @fopen(self::$path_to_log_file, 'a'))) {
                self::$log_resource = false;
                echo "Can't create log file '" . self::$path_to_log_file . "', continue without log.\n";
            }
        }
    }

    /**
     * Converted any data into string
     * @param mixed $data
     * @return string
     */
    public static function dumpIntoStr($data)
    {
        if (gettype($data) !== 'string') {
            ob_start();
            var_dump($data);
            $data = ob_get_contents();
            ob_end_clean();
        }
        return $data;
    }

    /**
     * Set level of output into console (not in log)
     * @param int $level
     * @return void
     */
    public static function setVerboseLevel($level)
    {
        self::$verbose_level = intval($level);
    }

    /**
     * Get level of output into console (not in log)
     * @return int
     */
    public static function getVerboseLevel()
    {
        return self::$verbose_level;
    }

    /**
     * Set log file for output
     * @param string $path_to_log_file
     * @return bool
     */
    public static function setLog($path_to_log_file)
    {
        if (self::$log_resource && self::$path_to_log_file) {
            fflush(self::$log_resource);
            fclose(self::$log_resource);
            self::$log_resource = null;
            @chmod(self::$path_to_log_file, 0666);
        }
        self::$path_to_log_file = $path_to_log_file;
        if (!file_exists(self::$path_to_log_file)) {
            @touch(self::$path_to_log_file);
            @chmod(self::$path_to_log_file, 0666);
        }
        return true;
    }

    /**
     * Draw html-console (open it)
     * @param $height
     * @param $width
     * @return void
     */
    public static function beginConsole($height = "97%", $width = "98%")
    {
        if (PHP_SAPI !== 'cli') {
            ob_implicit_flush(true);
            echo '<div class="console" style="background-color: #333333; color: #cccccc; border: 1px solid #000; padding: 10px; height: ' . $height . '; width: ' . $width . '; overflow: auto"><pre style="margin: 0; padding: 0;">';
            echo "<script>let elements = document.getElementsByClassName('console');</script>";
        }
    }

    /**
     * Close html-console
     * @return void
     */
    public static function endConsole()
    {
        if (PHP_SAPI !== 'cli') {
            echo "</pre></div>";
        }
    }

    /** *************** for easy message creation  **************** */
    /**
     * Do success message
     * @param mixed $message
     * @param int $level
     * @param bool $store
     * @param bool $prepend_date
     */
    public static function success($message, $level = 1, $prepend_date = true, $store = true)
    {
        self::createMessage($message, $level, 'success')->show($prepend_date, $store);
    }

    /**
     * Do info message
     * @param mixed $message
     * @param int $level
     * @param bool $store
     * @param bool $prepend_date
     */
    public static function info($message, $level = 1, $prepend_date = true, $store = true)
    {
        self::createMessage($message, $level, 'info')->show($prepend_date, $store);
    }

    /**
     * Do warning message
     * @param mixed $message
     * @param int $level
     * @param bool $store
     * @param bool $prepend_date
     */
    public static function warning($message, $level = 1, $prepend_date = true, $store = true)
    {
        self::createMessage($message, $level, 'warning')->show($prepend_date, $store);
    }

    /**
     * Do error message
     * @param mixed $message
     * @param int $level
     * @param bool $store
     * @param bool $prepend_date
     */
    public static function error($message, $level = 1, $prepend_date = true, $store = true)
    {
        self::createMessage($message, $level, 'error')->show($prepend_date, $store);
    }


    /** *********** for easy Execution-message  creation *********** */
    /**
     * Open new executing-object-message
     * @param mixed $message
     * @param int $level
     * @return $this
     */
    public static function executingMessage($message, $level = 1)
    {
        return self::createMessage($message, $level)->setType('info');
    }

    /**
     * Close and show opened executing-object-message as success
     * @param bool $prepend_date
     * @param bool $store
     * @param string $text
     * @return void
     */
    public function showSuccess($prepend_date = true, $store = true, $text = "[OK]")
    {
        $this->msg->isExecute = "[success]{$text}[/success]";
        $this->show($prepend_date, $store);
    }

    /**
     * Close and show opened executing-object-message as error
     * @param bool $prepend_date
     * @param bool $store
     * @param string $text
     * @return void
     */
    public function showError($prepend_date = true, $store = true, $text = "[FAIL]")
    {
        $this->msg->isExecute = "[error]{$text}[/error]";
        $this->show($prepend_date, $store);
    }

    /**
     * Close and show opened executing-object-message as warning
     * @param bool $prepend_date
     * @param bool $store
     * @param string $text
     * @return void
     */
    public function showWarn($prepend_date = true, $store = true, $text = "[WARN]")
    {
        $this->msg->isExecute = "[warn]{$text}[/warn]";
        $this->show($prepend_date, $store);
    }

    /** ************************************************************* */
    /**
     * Message constructor
     * @param mixed $message
     * @param int $level
     * @param string $type [success|error|warning|warn|danger|info|default]
     * @return $this
     */
    public static function createMessage($message, $level = 1, $type = 'default')
    {
        $instance = new self();
        $message = self::dumpIntoStr($message);
        unset($instance->msg);
        $instance->msg = new \stdClass();
        $instance->msg->text = $message;
        $instance->msg->type = $type;
        $instance->msg->level = $level;
        $instance->msg->endSymbol = "\n";
        $instance->msg->countEnd = 1;
        $instance->msg->isExecute = false;
        $instance->msg->replacment = [];

        self::$instancesCount++;

        return $instance;
    }

    /**
     * Append any text message created by createMessage()
     * @param mixed $text
     * @return $this
     */
    public function messageAppend($text)
    {
        $this->msg->text .= self::dumpIntoStr($text);
        return $this;
    }

    /**
     * Prepend any text message created by createMessage()
     * @param mixed $text
     * @return $this
     */
    public function messagePrepend($text)
    {
        $this->msg->text = self::dumpIntoStr($text) . $this->msg->text;
        return $this;
    }

    /**
     * Set type of message created by createMessage()
     * @param string $type [success|error|warning|warn|danger|info|default]
     * @return $this
     */
    public function setType($type)
    {
        $this->msg->type = $type;
        return $this;
    }

    /**
     * Set log level for message created by createMessage()
     * @param int $level
     * @return $this
     */
    public function setLevel($level)
    {
        $this->msg->level = $level;
        return $this;
    }

    /**
     * Set data for replace in  message created by createMessage()
     * @param array $data replace data in message
     * @return $this
     */
    public function setData(array $data)
    {
        $this->msg->replacment = array_merge($this->msg->replacment, $data);
        return $this;
    }

    /**
     * Set string or symbol new line for text message created by createMessage()
     * @param int $count
     * @param string $new_line
     * @return $this
     */
    public function setEndWithNewLine($count = 1, $new_line = "\n")
    {
        $this->msg->endSymbol = $new_line;
        $this->msg->countEnd = $count;
        return $this;
    }

    /**
     * Close and show opened object-message created by createMessage()
     * @param bool $prepend_date
     * @param bool $store
     * @return void
     */
    public function show($prepend_date = true, $store = true)
    {
        /* replace data in message */
        $this->msg->text = str_replace(array_keys($this->msg->replacment), array_values($this->msg->replacment), $this->msg->text);

        /* replace for colored message */
        $close_tag = ($this->cli_or_html === 'cli') ? "\033[0m" : '</span>';
        $a_s = [];
        $a_r = [];
        foreach ($this->replace as $k => $v) {
            $a_s[] = "[{$k}]";
            $a_r[] = $v[$this->cli_or_html];
            $a_s[] = "[/{$k}]";
            $a_r[] = $close_tag . ($this->cli_or_html === 'cli' ? (isset($this->replace[$this->msg->type][$this->cli_or_html]) ? $this->replace[$this->msg->type][$this->cli_or_html] : '') : '');
        }

        /* execute message formatting*/
        if ($this->msg->isExecute) {
            $message_test = $this->msg->text;
            $message_test = str_replace("\t", "++++++++", $message_test);
            $message_test = str_replace($a_s, '', $message_test);
            $min_length = 120;
            //$message_test .= ' ...';
            $len = mb_strlen($message_test);
            $diff = $min_length - $len;
            if ($diff > 0) {
                $append = " ";
                for ($i = 1; $i < $diff; $i++) {
                    $append .= ".";
                }
            } else {
                $append = ' ...';
            }
            $append .= " ";
            $this->msg->text .= $append . $this->msg->isExecute;
        }

        /* new line */
        if (isset($this->msg->endSymbol, $this->msg->countEnd)) {
            for ($i = 1; $i <= $this->msg->countEnd; $i++) {
                $this->msg->text .= $this->msg->endSymbol;
            }
        }

        /* store */
        if (self::$log_resource && $store) {
            fwrite(self::$log_resource, ($prepend_date ? date('Y-m-d, H:i:s') . "  " : '') . str_replace($a_s, '', $this->msg->text));
            fflush(self::$log_resource);
        }

        /* display */
        if ($this->msg->level <= self::$verbose_level) {
            if (PHP_SAPI === 'cli') {
                $this->msg->text = str_replace('&amp;', '&', htmlentities($this->msg->text));
            }
            echo (isset($this->replace[$this->msg->type][$this->cli_or_html]) ? $this->replace[$this->msg->type][$this->cli_or_html] : '') . str_replace($a_s, $a_r, $this->msg->text) . $close_tag;
            if (PHP_SAPI !== 'cli') {
                echo "<script>for (let i = 0; i < elements.length; i++) { elements[i].scrollTop = elements[i].scrollHeight; }</script>";
                if (ob_get_contents() && self::$execute_ob_end_flush) { ob_end_flush(); }
            }
        }
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        self::$instancesCount--;

        //dump('destruct');
        if (self::$log_resource) {
            fflush(self::$log_resource);
            //if (self::$instancesCount == 0) {
                //dump('log file is closed');
                //fclose(self::$log_resource);
                //self::$log_resource = null;
            //}
        }
    }
}