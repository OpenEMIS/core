<?php

namespace App\Command;
use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\I18n\FrozenTime;

class SystemUpdatesCommand extends AlertCommandBase
{
    public function logAlert($method, $feature, $recipient, $subject, $message)
    {
        $this->AlertLogs->insertSystemUpdateAlertLog($method, $feature, $recipient, $subject, $message);
    }

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $rules = $this->getSystemUpdateAlertRules($this->featureName);
        $versionNumber = $args->getArgumentAt(0) ?? '';

        $placeholders = ['${version}'];
        $values = [$versionNumber];

        $this->processContactList($rules, $placeholders, $values, function ($rule) {
            return $this->getRoleAssociatedContactList($rule['security_roles']);
        });

        return static::CODE_SUCCESS;
    }

}
