<?php

namespace App\Command\Traits;

use Cake\Console\ConsoleOptionParser;
use Cake\Console\ConsoleIo;

trait AlertProcessingTrait
{
    /** @var ConsoleIo|null */
    protected ?ConsoleIo $io = null;


    public function setIo(ConsoleIo $io): void
    {
        $this->io = $io;
    }

    public function processContactList(array $rules, array $replacements, callable $getContactListCallback): void
    {
        foreach ($rules as $rule) {
            $contacts = $getContactListCallback($rule);

            if (empty($contacts['email']) && empty($contacts['phone'])) {
                continue;
            }

            $subject = str_replace(array_keys($replacements), array_values($replacements), $rule['subject']);
            $message = str_replace(array_keys($replacements), array_values($replacements), $rule['message']);
            $methods = array_map('trim', explode(',', strtolower($rule['method'])));

// Log emails if 'email' method is specified
            if (in_array('email', $methods, true)) {
                foreach ($contacts['email'] ?? [] as $email) {
                    $this->logAlert('email', $rule['feature'], $email, $subject, $message);
                    usleep(500000); // 500,000 microseconds = 0.5 seconds
                }
            }

// Log SMS if 'sms' method is specified
            if (in_array('sms', $methods, true)) {
                foreach ($contacts['phone'] ?? [] as $phone) {
                    $this->logAlert('sms', $rule['feature'], $phone, $subject, $message);
                    usleep(500000); // 500,000 microseconds = 0.5 seconds
                }
            }
        }
    }

    abstract public function logAlert($method, $feature, $recipient, $subject, $message);

    // Abstract log method to implement per use case

    public function getOptionParser(): ConsoleOptionParser
    {
        $parser = parent::getOptionParser();

        return $parser
            ->addOption('user_id', [
                'help' => 'ID of the user triggering the alert',
                'short' => 'u',
                'required' => true,
                'default' => null
            ])
            ->addOption('rule_id', [
                'help' => 'Comma-separated list of rule IDs',
                'required' => true,
                'short' => 'r'
            ])
            ->addOption('process_id', [
                'help' => 'ID of the process',
                'required' => true,
                'short' => 'p'
            ]);
    }

    protected function logMsg(string $msg): void
    {
        if ($this->io) {
            $this->io->out($msg);
        }
    }
}
