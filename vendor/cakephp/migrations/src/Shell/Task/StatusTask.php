<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Migrations\Shell\Task;

/**
 * This task class is needed in order to provide a correct autocompletion feature
 * when using the CakePHP migrations shell plugin. It has no effect on the
 * migrations process.
 */
class StatusTask extends CommandTask
{

    /**
     * {@inheritDoc}
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();
        $parser->addOption('format', [
            'short' => 'f',
            'help' => 'The output format: text or json. Defaults to text.',
            'required' => false
        ]);

        return $parser;
    }
}
