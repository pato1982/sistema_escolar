<?php
/**
 * PHPMailer - PHP email creation and transport class.
 * Simplified version for SMTP sending
 */

namespace PHPMailer\PHPMailer;

class PHPMailer
{
    const CHARSET_UTF8 = 'UTF-8';
    const ENCODING_BASE64 = 'base64';
    const ENCODING_QUOTED_PRINTABLE = 'quoted-printable';

    public $Host = 'localhost';
    public $Port = 25;
    public $SMTPAuth = false;
    public $Username = '';
    public $Password = '';
    public $SMTPSecure = '';
    public $From = '';
    public $FromName = '';
    public $CharSet = 'UTF-8';
    public $Encoding = 'base64';
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $isHTML = false;
    public $SMTPDebug = 0;
    public $Debugoutput = 'echo';
    public $SMTPOptions = [];

    protected $to = [];
    protected $cc = [];
    protected $bcc = [];
    protected $ReplyTo = [];
    protected $attachments = [];
    protected $ErrorInfo = '';
    protected $smtp = null;

    public function __construct($exceptions = null)
    {
        $this->smtp = new SMTP();
    }

    public function isSMTP()
    {
        // Set mailer to use SMTP
    }

    public function setFrom($address, $name = '', $auto = true)
    {
        $this->From = $address;
        $this->FromName = $name;
        return true;
    }

    public function addAddress($address, $name = '')
    {
        $this->to[] = [$address, $name];
        return true;
    }

    public function addReplyTo($address, $name = '')
    {
        $this->ReplyTo[] = [$address, $name];
        return true;
    }

    public function addCC($address, $name = '')
    {
        $this->cc[] = [$address, $name];
        return true;
    }

    public function addBCC($address, $name = '')
    {
        $this->bcc[] = [$address, $name];
        return true;
    }

    public function isHTML($isHtml = true)
    {
        $this->isHTML = $isHtml;
    }

    public function send()
    {
        try {
            // Connect to SMTP server
            $this->smtp->setDebugLevel($this->SMTPDebug);
            $this->smtp->setDebugOutput($this->Debugoutput);

            $secure = $this->SMTPSecure;
            $host = $this->Host;

            if ($secure === 'tls' || $secure === 'ssl') {
                $host = $secure . '://' . $host;
            }

            if (!$this->smtp->connect($host, $this->Port, 30, $this->SMTPOptions)) {
                $this->ErrorInfo = 'SMTP connect failed: ' . $this->smtp->getError()['error'];
                return false;
            }

            if (!$this->smtp->hello(gethostname())) {
                $this->ErrorInfo = 'EHLO failed: ' . $this->smtp->getError()['error'];
                return false;
            }

            // STARTTLS if needed
            if ($this->SMTPSecure === 'tls') {
                if (!$this->smtp->startTLS()) {
                    $this->ErrorInfo = 'STARTTLS failed';
                    return false;
                }
                $this->smtp->hello(gethostname());
            }

            // Authenticate
            if ($this->SMTPAuth) {
                if (!$this->smtp->authenticate($this->Username, $this->Password)) {
                    $this->ErrorInfo = 'SMTP authenticate failed: ' . $this->smtp->getError()['error'];
                    return false;
                }
            }

            // Set sender
            if (!$this->smtp->mail($this->From)) {
                $this->ErrorInfo = 'MAIL FROM failed: ' . $this->smtp->getError()['error'];
                return false;
            }

            // Set recipients
            foreach ($this->to as $recipient) {
                if (!$this->smtp->recipient($recipient[0])) {
                    $this->ErrorInfo = 'RCPT TO failed: ' . $this->smtp->getError()['error'];
                    return false;
                }
            }

            // Send data
            if (!$this->smtp->data($this->createMessage())) {
                $this->ErrorInfo = 'DATA failed: ' . $this->smtp->getError()['error'];
                return false;
            }

            $this->smtp->quit();
            return true;

        } catch (\Exception $e) {
            $this->ErrorInfo = $e->getMessage();
            return false;
        }
    }

    protected function createMessage()
    {
        $boundary = md5(time());
        $headers = [];

        $headers[] = 'Date: ' . date('r');
        $headers[] = 'From: ' . $this->formatAddress($this->From, $this->FromName);

        $toAddresses = [];
        foreach ($this->to as $recipient) {
            $toAddresses[] = $this->formatAddress($recipient[0], $recipient[1]);
        }
        $headers[] = 'To: ' . implode(', ', $toAddresses);

        $headers[] = 'Subject: =?UTF-8?B?' . base64_encode($this->Subject) . '?=';
        $headers[] = 'MIME-Version: 1.0';

        if ($this->isHTML) {
            $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

            $body = '--' . $boundary . "\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($this->AltBody ?: strip_tags($this->Body))) . "\r\n";

            $body .= '--' . $boundary . "\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($this->Body)) . "\r\n";

            $body .= '--' . $boundary . '--';
        } else {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
            $headers[] = 'Content-Transfer-Encoding: base64';
            $body = chunk_split(base64_encode($this->Body));
        }

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    protected function formatAddress($address, $name = '')
    {
        if (empty($name)) {
            return $address;
        }
        return '=?UTF-8?B?' . base64_encode($name) . '?= <' . $address . '>';
    }

    public function getError()
    {
        return $this->ErrorInfo;
    }

    public function clearAddresses()
    {
        $this->to = [];
    }

    public function clearAllRecipients()
    {
        $this->to = [];
        $this->cc = [];
        $this->bcc = [];
    }
}
