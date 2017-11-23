<?php
namespace App\Shell\Task;

use Cake\Console\Shell;
use Cake\I18n\Date;

/**
 * InstitutionStatus shell task.
 */
class InstitutionStatusTask extends Shell
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Institution.Statuses');
        $this->loadModel('Institution.Institutions');
    }
    /**
     * main() method.
     *
     * @return bool|int|null Success or error code.
     */
    public function main()
    {
        $inactiveStatus = $this->Statuses->findByCode('INACTIVE')->first()->id;
        $this->updateAll(['institution_status_id' => $inactiveStatus], ['date_closed < ' => new Date()]);
        return true;
    }
}
