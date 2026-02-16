<?php
namespace App\Mailer\Transport;

use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Message;
use Cake\Log\Log;
use Cake\Mailer\TransportFactory;

class LogTransport extends AbstractTransport
{
    protected int $dailyLimit = 50;
    protected string $counterFile = TMP . 'email_counter.txt';

    public function send(Message $message): array
    {
        $emailData = [
            'subject' => $message->getSubject(),
            'to' => $message->getTo(),
            'cc' => $message->getCc(),
            'bcc' => $message->getBcc(),
            'from' => $message->getFrom(),
            'headers' => $message->getHeaders(),
            'body' => $message->getBodyString(),
        ];
        Log::write('info', print_r($emailData, true), ['scope' => ['email']]);

        if (!$this->canSendToday()) {
            Log::write('warning', '📉 Email limit reached for today, not sending.', ['scope' => ['email']]);
            return [
                'headers' => $message->getHeaders(),
                'message' => $message->getBody()
            ];
        }

        $message->setSubject($message->getSubject() . ' : ' . print_r($message->getTo(), true));
        $message->setTo('hello@demomailtrap.co');
        $message->setFrom('hello@demomailtrap.co');
        $message->setReturnPath('hello@demomailtrap.co');
        $message->setReplyTo('hello@demomailtrap.co');

        $realTransport = TransportFactory::get('openemis');
        $result = $realTransport->send($message);

        $this->incrementCounter();

        return $result;
    }

    protected function canSendToday(): bool
    {
        $today = date('Y-m-d');
        if (!file_exists($this->counterFile)) {
            return true;
        }

        [$date, $count] = explode('|', trim(file_get_contents($this->counterFile))) + [null, 0];

        return $date !== $today || (int)$count < $this->dailyLimit;
    }

    protected function incrementCounter(): void
    {
        $today = date('Y-m-d');
        $count = 1;

        if (file_exists($this->counterFile)) {
            [$date, $oldCount] = explode('|', trim(file_get_contents($this->counterFile))) + [null, 0];
            $count = ($date === $today) ? (int)$oldCount + 1 : 1;
        }

        file_put_contents($this->counterFile, "$today|$count");
    }
}
