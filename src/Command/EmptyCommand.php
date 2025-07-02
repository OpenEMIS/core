<?php
namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\ORM\Locator\LocatorAwareTrait;

class EmptyCommand extends \Cake\Command\Command
{
    use LocatorAwareTrait;

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $io->out('<info>Empty command is working!</info>');
        return static::CODE_SUCCESS;
    }
}
