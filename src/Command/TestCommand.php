<?php
namespace App\Command;
use Cake\Console\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;

class TestCommand extends \Cake\Command\Command
{
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $userId = (int) $args->getArgumentAt(0) ?? 0;
        try {
            $YourController = new \App\Controller\DashboardController(); // or load it properly
        } catch (\Exception $exception) {
            $io->out($exception->getMessage());
        }
        $result = $YourController->getUserSecurityRoleIds($userId);

        $io->out('User: ' . $userId);
        foreach ($result as $institutionId) {
            $io->out('User Role: ' . $institutionId);
        }
        return static::CODE_SUCCESS;
    }
}

