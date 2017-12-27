<?php
namespace App\Shell\Task;

use Cake\Console\Shell;
use Cake\I18n\Date;
use Cake\I18n\Time;

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
        $this->out(getmypid() . ' - Running Institution Status Update');
        $inactiveStatus = $this->Statuses->findByCode('INACTIVE')->first()->id;
        $rowUpdated = $this->Institutions->updateAll(['institution_status_id' => $inactiveStatus], ['date_closed < ' => new Date()]);
        $this->out(getmypid() . ' - ' . $rowUpdated . ' rows updated');
        $this->out(getmypid() . ' - Finish Institution Status Update');
        return true;
    }
}
