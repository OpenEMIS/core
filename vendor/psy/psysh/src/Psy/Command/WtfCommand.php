<?php

/*
 * This file is part of Psy Shell.
 *
 * (c) 2012-2017 Justin Hileman
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Psy\Command;

use Psy\Context;
use Psy\ContextAware;
use Psy\Input\FilterOptions;
use Psy\Output\ShellOutput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Show the last uncaught exception.
 */
class WtfCommand extends TraceCommand implements ContextAware
{
    /**
     * Context instance (for ContextAware interface).
     *
     * @var Context
     */
    protected $context;

    /**
     * ContextAware interface.
     *
     * @param Context $context
     */
    public function setContext(Context $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        list($grep, $insensitive, $invert) = FilterOptions::getOptions();

        $this
            ->setName('wtf')
            ->setAliases(array('last-exception', 'wtf?'))
            ->setDefinition(array(
                new InputArgument('incredulity', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Number of lines to show'),
                new InputOption('all', 'a',  InputOption::VALUE_NONE, 'Show entire backtrace.'),

                $grep,
                $insensitive,
                $invert,
            ))
            ->setDescription('Show the backtrace of the most recent exception.')
            ->setHelp(
                <<<'HELP'
Shows a few lines of the backtrace of the most recent exception.

If you want to see more lines, add more question marks or exclamation marks:

e.g.
<return>>>> wtf ?</return>
<return>>>> wtf ?!???!?!?</return>

To see the entire backtrace, pass the -a/--all flag:

e.g.
<return>>>> wtf -v</return>
HELP
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->filter->bind($input);

        $incredulity = implode('', $input->getArgument('incredulity'));
        if (strlen(preg_replace('/[\\?!]/', '', $incredulity))) {
            throw new \InvalidArgumentException('Incredulity must include only "?" and "!".');
        }

        $exception = $this->context->getLastException();
        $count     = $input->getOption('all') ? PHP_INT_MAX : pow(2, max(0, (strlen($incredulity) - 1)));
        $trace     = $this->getBacktrace($exception, $count);

        $shell = $this->getApplication();
        $output->page(function ($output) use ($exception, $trace, $shell) {
            do {
                $output->writeln($shell->formatException($exception));
                $output->writeln('--');
                $output->write($trace, true, ShellOutput::NUMBER_LINES);
                $output->writeln('');
            } while ($exception = $exception->getPrevious());
        });
    }
}
