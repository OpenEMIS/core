<%
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
%>
<?php
namespace <%= $namespace %>\Shell\Task;

use Cake\Console\Shell;

/**
 * <%= $name %> shell task.
 */
class <%= $name %>Task extends Shell
{

    /**
     * main() method.
     *
     * @return bool|int|null Success or error code.
     */
    public function main()
    {
    }
}
