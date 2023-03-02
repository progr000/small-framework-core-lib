<?php

namespace Core;

use finfo;
use Exception;


class SendmailDriver
{
    /** @var int */
    private static $instancesCount = 0;

    /** @var string */
    private $bodyHtml = '';
    /** @var string */
    private $bodyText = '';
    /** @var string */
    private $attachments = '';
    /** @var string */
    private static $newLine = "\r\n";

    /** @var array */
    private $from = [];
    /** @var array */
    private $to = [];
    /** @var string|null */
    private $cc = null;
    /** @var string|null */
    private $bcc = null;
    /** @var string|null */
    private $replyTo = null;
    /** @var string|null */
    private $XMailer = null;
    /** @var string */
    private $subject = "";
    /** @var array */
    private $replaceData = [];

    /** @var array */
    private $headers = [];
    /** @var string  */
    private $random_hash = '';

    /** @var array */
    private $errors = [];

    /**
     * Constructor
     */
    private function __construct()
    {
        self::$instancesCount++;
        $this->random_hash = md5(date('r', time()));
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        self::$instancesCount--;
    }

    /**
     * Return errors-stack
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Create new instance
     * @return SendmailDriver
     */
    public static function init()
    {
        $instance = new self();
        return $instance;
    }

    /**
     * @param string $email
     * @param string $name
     * @return $this
     */
    public function setFrom($email, $name)
    {
        $this->from['name'] = $name;
        $this->from['email'] = $email;
        return $this;
    }

    /**
     * @param string $email
     * @param string $name
     * @return $this
     */
    public function setTo($email, $name)
    {
        $this->to['name'] = $name;
        $this->to['email'] = $email;
        return $this;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setCC($email)
    {
        $this->cc = $email;
        return $this;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setBCC($email)
    {
        $this->bcc = $email;
        return $this;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setReplyTo($email)
    {
        $this->replyTo = $email;
        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setXMailer($name)
    {
        $this->XMailer = $name;
        return $this;
    }

    /**
     * @param string $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     *
     */
    private function setHeaders()
    {
        $this->headers = [
            "To: {$this->to['name']} <{$this->to['email']}>",
            "From: {$this->from['name']} <{$this->from['email']}>",
            "X-Sender: <{$this->from['name']}>",
            "X-Mailer: {$this->XMailer}",
            "Reply-To: {$this->from['email']}",
            "Subject: " . substr(iconv_mime_encode('Subject', $this->subject, [
                'input-charset' => 'UTF-8',
                'output-charset' => 'UTF-8',
            ]), strlen('Subject: ')),
        ];
        if (!empty($this->cc)) $this->headers[] = "CC: {$this->cc}";
        if (!empty($this->bcc)) $this->headers[] = "BCC: {$this->bcc}";
        if (!empty($this->replyTo)) $this->headers[] = "Reply-To: {$this->replyTo}";
    }

    /**
     *
     */
    private function replaceData()
    {
        foreach ($this->replaceData as $key => $val) {
            $replace_arr = [
                '{' . $key . '}',
                '{{' . $key . '}}',
                '[' . $key . ']',
                '%' . $key . '%',
                '{%' . $key . '}',
                '{{%' . $key . '}}',
                '[%' . $key . ']',

                '{' . mb_strtoupper($key) . '}',
                '{{' . mb_strtoupper($key) . '}}',
                '[' . mb_strtoupper($key) . ']',
                '%' . mb_strtoupper($key) . '%',
                '{%' . mb_strtoupper($key) . '}',
                '{{%' . mb_strtoupper($key) . '}}',
                '[%' . mb_strtoupper($key) . ']',

                '{' . mb_strtolower($key) . '}',
                '{{' . mb_strtolower($key) . '}}',
                '[' . mb_strtolower($key) . ']',
                '%' . mb_strtolower($key) . '%',
                '{%' . mb_strtolower($key) . '}',
                '{{%' . mb_strtolower($key) . '}}',
                '[%' . mb_strtolower($key) . ']',
            ];
            $this->subject  = str_replace($replace_arr, "$val", $this->subject);
            $this->bodyText = str_replace($replace_arr, "$val", $this->bodyText);
            $this->bodyHtml = str_replace($replace_arr, "$val", $this->bodyHtml);
        }
        $this->bodyText = utf8_decode($this->bodyText);
        $this->bodyHtml = utf8_decode($this->bodyHtml);
    }

    /**
     * @param string $html
     * @return $this
     */
    public function setBodyHtml($html)
    {
        $this->bodyHtml = $html;
        return $this;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setBodyText($text)
    {
        $this->bodyText = $text;
        return $this;
    }

    /**
     * @param string $path
     * @return $this
     * @throws Exception
     */
    public function attachFile($path)
    {
        if (file_exists($path) && is_readable($path) && is_file($path)) {
            $type = mime_content_type($path);
            return $this->addAttachment(basename($path), file_get_contents($path), $type);
        } else {
            throw new Exception('File is not available.');
        }
    }

    /**
     * Add attachment into message from string
     * @param string $name
     * @param string $content
     * @param string $type
     * @return $this
     */
    public function addAttachment($name, $content, $type = null)
    {
        if (!$type) {
            try {
                $type = (new finfo(FILEINFO_MIME))->buffer($content);
            } catch (Exception $e) {
                $this->errors[] = $e->getMessage();
                $type = 'application/octet-stream';
            }
        }
        $this->attachments .= "--PHP-mixed-$this->random_hash" . self::$newLine;
        $this->attachments .= "Content-Type: {$type}; name=\"{$name}\"" . self::$newLine;
        $this->attachments .= "Content-Transfer-Encoding: base64" . self::$newLine;
        $this->attachments .= "Content-Disposition: attachment; filename=\"{$name}\"" . self::$newLine;
        $this->attachments .= self::$newLine;
        $this->attachments .= chunk_split(base64_encode($content)) . self::$newLine;
        $this->attachments .= self::$newLine;
        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setReplaceData(array $data)
    {
        $this->replaceData = $data;
        return $this;
    }

    /**
     * Generate full message based on all set*() functions
     * @return string
     */
    private function prepareLetter()
    {
        $body =
            "Content-Type: multipart/alternative; boundary=\"PHP-alt-{$this->random_hash}\"" . self::$newLine .
            self::$newLine .
            "--PHP-alt-{$this->random_hash}" . self::$newLine .
            "Content-Type: text/plain; charset=\"utf-8\"" . self::$newLine .
            "Content-Transfer-Encoding: 7bit" . self::$newLine .
            self::$newLine .
            $this->bodyText . self::$newLine .
            self::$newLine .
            "--PHP-alt-{$this->random_hash}" . self::$newLine .
            "Content-Type: text/html . charset=\"utf-8\"" . self::$newLine .
            "Content-Transfer-Encoding: 7bit" . self::$newLine .
            self::$newLine .
            $this->bodyHtml . self::$newLine .
            self::$newLine .
            "--PHP-alt-$this->random_hash--" . self::$newLine;

        if (!empty($this->attachments)) {
            $body =
                "Content-Type: multipart/mixed; boundary=\"PHP-mixed-{$this->random_hash}\"" . self::$newLine .
                self::$newLine .
                "--PHP-mixed-{$this->random_hash}" . self::$newLine .
                $body .
                $this->attachments .
                "--PHP-mixed-{$this->random_hash}--" . self::$newLine;
        }

        return $body;
    }

    /**
     * Create sign for message by openssl
     * @return string
     * @throws Exceptions\ConfigException
     */
    private function signLetter()
    {
        $file_name_unsigned = sys_get_temp_dir() . "/sendmail-{$this->random_hash}.unsigned.txt";
        $file_name_signed = sys_get_temp_dir() . "/sendmail-{$this->random_hash}.signed.txt";
        file_put_contents($file_name_unsigned, $this->prepareLetter());
        chmod($file_name_unsigned, 0666);

        $cert_dir = App::$config->get('sendmail_cert_dir');
        if (
            file_exists("{$cert_dir}/{$this->from['email']}.crt.pem") &&
            file_exists("{$cert_dir}/{$this->from['email']}.key.pem") &&
            file_exists("{$cert_dir}/ca-certs.pem")
        ) {
            try {
                openssl_pkcs7_sign(
                    $file_name_unsigned,
                    $file_name_signed,
                    "file://{$cert_dir}/{$this->from['email']}.crt.pem",
                    array("file://{$cert_dir}/{$this->from['email']}.key.pem", ""),
                    $this->headers,
                    PKCS7_DETACHED | PKCS7_BINARY,
                    "$cert_dir/ca-certs.pem"
                );
            } catch (Exception $e) {
                $this->errors[] = $e->getMessage();
                $sign_failed = true;
            }
        } else {
            $this->errors[] = "sign failed";
            $sign_failed = true;
        }
        if (isset($sign_failed)) {
            file_put_contents(
                $file_name_signed,
                implode(self::$newLine, $this->headers) . self::$newLine . file_get_contents($file_name_unsigned)
            );
        }
        @unlink($file_name_unsigned);

        return $file_name_signed;
    }

    /**
     * Try to send message
     * @return false|array
     * @throws Exception
     */
    public function send()
    {
        /* do not anything if no sender or receiver */
        if (empty($this->from['email']) || empty($this->to['email'])) {
            $this->errors[] = "email-to or email-from are not set.";
            return false;
        }

        /* replace data in body, subject and prepare letter-headers */
        $this->replaceData();
        $this->setHeaders();

        /* sign letter if cert enabled and prepare letter-file for sendmail program */
        if (App::$config->exist('sendmail_cert_dir')) {
            $letter_file = $this->signLetter();
        } else {
            $letter_file = sys_get_temp_dir() . "/sendmail-{$this->random_hash}.unsigned.txt";
            file_put_contents($letter_file, implode(self::$newLine, $this->headers) . self::$newLine . $this->prepareLetter());
        }

        /* just check previous step */
        if (!file_exists($letter_file)) {
            $this->errors[] = "letter-file not found. Sending impossible";
            return false;
        }

        /* do send throw sendmail program */
        $sendmail = ini_get("sendmail_path");
        //$cmd = "{$sendmail} -vv -f {$this->from['email']} < {$letter_file}  2>&1 ; rm -f {$letter_file}";
        $cmd = "{$sendmail} -vv -f {$this->from['email']} < {$letter_file}  2>&1";
        exec($cmd, $output, $code);

        /* processing answer from sendmail-program */
        return self::getAnswer($output);
    }

    /**
     * Processing the answer from mailer system after send function
     * @param mixed $output
     * @return array|null
     */
    private static function getAnswer($output)
    {
        if (is_array($output)) {
            $full_answer = implode("\n", $output);
            foreach ($output as $k => $v) {
                if (strrpos($v, 'queue_id') !== false && strrpos($v, 'name:')) {
                    if (isset($output[$k + 1]) && strrpos($output[$k + 1], 'value:') !== false) {
                        $tmp = explode('value:', $output[$k + 1]);
                        if (isset($tmp[1])) {
                            return [
                                'full_answer' => $full_answer,
                                'queue_id' => trim($tmp[1]),
                                'status' => 'QUEUED',
                            ];
                        }
                    }
                }
            }
            return [
                'full_answer' => $full_answer,
                'queue_id' => null,
                'status' => 'UNKNOWN',
            ];
        }
        return [
            'full_answer' => null,
            'queue_id' => null,
            'status' => 'UNKNOWN',
        ];
    }
}