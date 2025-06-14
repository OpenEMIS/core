<?php

namespace App\Command\Traits;
trait AlertProcessingTrait
{
    /** @var ConsoleIo|null */
    protected ?ConsoleIo $io = null;


    public function setIo(ConsoleIo $io): void
    {
        $this->io = $io;
    }

    protected function logMsg(string $msg): void
    {
        if ($this->io) {
            $this->io->out($msg);
        }
    }
    public function processContactList($rules, $placeholders, $values, $getContactListCallback)
    {
        foreach ($rules as $rule) {
            $contactList = $getContactListCallback($rule); // e.g. getStudentAdmissionContactList() or getRoleAssociatedContactList()
            $this->logMsg(print_r($contactList, true));
            $methods = array_map('trim', explode(',', $rule->method));
            $this->logMsg(print_r($methods, true));

            $subject = str_replace($placeholders, $values, $rule->subject);
            $message = str_replace($placeholders, $values, $rule->message);

            foreach ($methods as $method) {
                if ($method === 'Email' && !empty($contactList['email'])) {
                    $this->logAlert($method, $rule->feature, implode(', ', $contactList['email']), $subject, $message);
                }

                if ($method === 'SMS' && !empty($contactList['phone'])) {
                    $this->logAlert($method, $rule->feature, implode(', ', $contactList['phone']), $subject, $message);
                }
            }
        }
    }

    // Abstract log method to implement per use case
    abstract public function logAlert($method, $feature, $recipient, $subject, $message);
}
