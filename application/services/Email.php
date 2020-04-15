<?php

class Application_Service_Email
{
    /** @var self */
    protected static $_instance = null;
    private function __clone() {}
    public static function getInstance() { return null === self::$_instance ? new self : self::$_instance; }

    /** @var Zend_Mail_Transport_Smtp $smtp */
    protected $smtp;

    protected $transports = [];

    const SENDERS = [
        // default
        1 => [
            'server' => 'wordpress1604453.home.pl',
            'from_name' => 'Kryptos - powiadomienia',
            'from_email' => 'partner@kryptos24.pl',
            'smtp_config' => [
                'port' => 25,
                'username' => 'partner@kryptos24.pl',
                'password' => 'b&J7l1FH*GvY',
                'auth'  => 'plain',
                /*'ssl' => 'tls',*/
            ]
        ],
    ];

    private function __construct()
    {
        self::$_instance = $this;
    }

    /**
     * @param $senderId
     * @return Zend_Mail_Transport_Smtp
     * @throws Exception
     */
    protected function getTransport($senderId)
    {
        if (isset($this->transports[$senderId])) {
            return $this->transports[$senderId];
        }

        if (!array_key_exists($senderId, self::SENDERS)) {
            Throw new Exception('Invalid email sender', 500);
        }

        $config = self::SENDERS[$senderId];

        $transport = new Zend_Mail_Transport_Smtp($config['server'], $config['smtp_config']);

        $this->transports[$senderId] = $transport;

        return $transport;
    }

    public function send($data)
    {
        Application_Service_Utilities::requireKeys($data, ['recipient_address', 'title', 'text', 'sender_id']);

        $mail = new Zend_Mail('UTF-8');
        $transport = $this->getTransport($data['sender_id']);
        $senderConfig = self::SENDERS[$data['sender_id']];

        $mail->setFrom($senderConfig['from_email'], $senderConfig['from_name']);
        $mail->setSubject($data['title']);
        $mail->setBodyHtml($data['text']);
        $mail->setHeaderEncoding(Zend_Mime::ENCODING_BASE64);

        if (is_array($data['recipient_address'])) {
            $mail->addTo($data['recipient_address'][0], $data['recipient_address'][1]);
        } else {
            $mail->addTo($data['recipient_address']);
        }

        try {
            $transport->send($mail);
        } catch (Exception $e) {
            vdie($e);
            Throw new Exception('E-mail send error', 500, $e);
        }
    }
}