<?php
namespace App\Shell\Task;

use Cake\Console\Shell;

/**
 * InstitutionStatus shell task.
 */
class InstitutionStatusTask extends Shell
{

    /**
     * main() method.
     *
     * @return bool|int|null Success or error code.
     */
    public function main()
    {
        $this->out(getmypid());
    }
}
