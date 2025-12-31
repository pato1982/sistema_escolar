<?php
/**
 * SMTP class for PHPMailer
 * Handles SMTP protocol communication
 */

namespace PHPMailer\PHPMailer;

class SMTP
{
    const DEBUG_OFF = 0;
    const DEBUG_CLIENT = 1;
    const DEBUG_SERVER = 2;
    const DEBUG_CONNECTION = 3;
    const DEBUG_LOWLEVEL = 4;

    protected $smtp_conn = null;
    protected $error = ['error' => '', 'detail' => '', 'smtp_code' => '', 'smtp_code_ex' => ''];
    protected $last_reply = '';
    protected $do_debug = 0;
    protected $Debugoutput = 'echo';

    public function setDebugLevel($level = 0)
    {
        $this->do_debug = $level;
    }

    public function setDebugOutput($method = 'echo')
    {
        $this->Debugoutput = $method;
    }

    protected function edebug($str, $level = 0)
    {
        if ($level > $this->do_debug) {
            return;
        }

        if ($this->Debugoutput === 'echo') {
            echo gmdate('Y-m-d H:i:s') . "\t" . $str . "\n";
        }
    }

    public function connect($host, $port = 25, $timeout = 30, $options = [])
    {
        $this->edebug("Connection: opening to $host:$port", self::DEBUG_CONNECTION);

        $errno = 0;
        $errstr = '';

        $context = stream_context_create();

        if (!empty($options)) {
            foreach ($options as $wrapper => $opts) {
                foreach ($opts as $opt => $value) {
                    stream_context_set_option($context, $wrapper, $opt, $value);
                }
            }
        }

        $this->smtp_conn = @stream_socket_client(
            $host . ':' . $port,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!is_resource($this->smtp_conn)) {
            $this->setError('Failed to connect to server', '', (string)$errno, $errstr);
            $this->edebug('SMTP ERROR: ' . $this->error['error'] . ': ' . $errstr . ' (' . $errno . ')', self::DEBUG_CLIENT);
            return false;
        }

        stream_set_timeout($this->smtp_conn, $timeout);

        $response = $this->getLines();
        $this->edebug('SERVER -> CLIENT: ' . $response, self::DEBUG_SERVER);

        return true;
    }

    public function hello($host = '')
    {
        return $this->sendHello('EHLO', $host) || $this->sendHello('HELO', $host);
    }

    protected function sendHello($hello, $host)
    {
        $response = $this->sendCommand($hello, $hello . ' ' . $host, 250);
        return $response;
    }

    public function startTLS()
    {
        if (!$this->sendCommand('STARTTLS', 'STARTTLS', 220)) {
            return false;
        }

        $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;

        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
        }

        if (!stream_socket_enable_crypto($this->smtp_conn, true, $crypto_method)) {
            return false;
        }

        return true;
    }

    public function authenticate($username, $password)
    {
        // Try LOGIN authentication
        if (!$this->sendCommand('AUTH', 'AUTH LOGIN', 334)) {
            return false;
        }

        if (!$this->sendCommand('Username', base64_encode($username), 334)) {
            return false;
        }

        if (!$this->sendCommand('Password', base64_encode($password), 235)) {
            return false;
        }

        return true;
    }

    public function mail($from)
    {
        return $this->sendCommand('MAIL FROM', 'MAIL FROM:<' . $from . '>', 250);
    }

    public function recipient($address)
    {
        return $this->sendCommand('RCPT TO', 'RCPT TO:<' . $address . '>', [250, 251]);
    }

    public function data($msg_data)
    {
        if (!$this->sendCommand('DATA', 'DATA', 354)) {
            return false;
        }

        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $msg_data));

        $field = substr($lines[0], 0, strpos($lines[0], ':'));
        $in_headers = false;
        if (!empty($field) && strpos($field, ' ') === false) {
            $in_headers = true;
        }

        foreach ($lines as $line) {
            $lines_out = [];

            if ($in_headers && $line === '') {
                $in_headers = false;
            }

            while (isset($line[998])) {
                $pos = strrpos(substr($line, 0, 998), ' ');
                if (!$pos) {
                    $pos = 997;
                    $lines_out[] = substr($line, 0, $pos);
                    $line = substr($line, $pos);
                } else {
                    $lines_out[] = substr($line, 0, $pos);
                    $line = substr($line, $pos + 1);
                }
                if ($in_headers) {
                    $line = "\t" . $line;
                }
            }
            $lines_out[] = $line;

            foreach ($lines_out as $line_out) {
                if (!empty($line_out) && $line_out[0] === '.') {
                    $line_out = '.' . $line_out;
                }
                $this->clientSend($line_out . "\r\n");
            }
        }

        return $this->sendCommand('DATA END', '.', 250);
    }

    public function quit()
    {
        $this->sendCommand('QUIT', 'QUIT', 221);
        $this->close();
        return true;
    }

    public function close()
    {
        if (is_resource($this->smtp_conn)) {
            fclose($this->smtp_conn);
            $this->smtp_conn = null;
        }
    }

    protected function sendCommand($command, $commandstring, $expect)
    {
        if (!is_resource($this->smtp_conn)) {
            $this->setError('Not connected');
            return false;
        }

        $this->clientSend($commandstring . "\r\n");

        $this->last_reply = $this->getLines();

        $this->edebug('CLIENT -> SERVER: ' . $commandstring, self::DEBUG_CLIENT);
        $this->edebug('SERVER -> CLIENT: ' . $this->last_reply, self::DEBUG_SERVER);

        $code = (int)substr($this->last_reply, 0, 3);

        if (is_array($expect)) {
            $match = in_array($code, $expect);
        } else {
            $match = ($code === $expect);
        }

        if (!$match) {
            $this->setError($command . ' command failed', $this->last_reply, (string)$code);
            return false;
        }

        return true;
    }

    protected function clientSend($data)
    {
        return fwrite($this->smtp_conn, $data);
    }

    protected function getLines()
    {
        if (!is_resource($this->smtp_conn)) {
            return '';
        }

        $data = '';
        $endtime = time() + 30;

        stream_set_timeout($this->smtp_conn, 30);

        while (is_resource($this->smtp_conn) && !feof($this->smtp_conn)) {
            $str = @fgets($this->smtp_conn, 515);

            $data .= $str;

            if (isset($str[3]) && $str[3] === ' ') {
                break;
            }

            $info = stream_get_meta_data($this->smtp_conn);
            if ($info['timed_out']) {
                break;
            }

            if (time() > $endtime) {
                break;
            }
        }

        return $data;
    }

    protected function setError($message, $detail = '', $smtp_code = '', $smtp_code_ex = '')
    {
        $this->error = [
            'error' => $message,
            'detail' => $detail,
            'smtp_code' => $smtp_code,
            'smtp_code_ex' => $smtp_code_ex
        ];
    }

    public function getError()
    {
        return $this->error;
    }
}
