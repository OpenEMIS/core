<?php
/**
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Migrations\Command;

use Cake\Event\EventManagerTrait;
use Migrations\ConfigurationTrait;
use Phinx\Console\Command\Migrate as MigrateCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Migrate extends MigrateCommand
{

    use ConfigurationTrait {
        execute as parentExecute;
    }
    use EventManagerTrait;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('migrate')
            ->setDescription('Migrate the database')
            ->addOption('--target', '-t', InputArgument::OPTIONAL, 'The version number to migrate to')
            ->setHelp('runs all available migrations, optionally up to a specific version')
            ->addOption('--plugin', '-p', InputArgument::OPTIONAL, 'The plugin containing the migrations')
            ->addOption('--connection', '-c', InputArgument::OPTIONAL, 'The datasource connection to use')
            ->addOption('--source', '-s', InputArgument::OPTIONAL, 'The folder where migrations are in');
    }

    /**
     * Overrides the action execute method in order to vanish the idea of environments
     * from phinx. CakePHP does not beleive in the idea of having in-app environments
     *
     * @param Symfony\Component\Console\Input\Inputnterface $input the input object
     * @param Symfony\Component\Console\Input\OutputInterface $output the output object
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $event = $this->dispatchEvent('Migration.beforeMigrate');
        if ($event->isStopped()) {
            return $event->result;
        }
        $this->parentExecute($input, $output);
        $this->dispatchEvent('Migration.afterMigrate');
    }
}
