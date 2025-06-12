<?php
namespace App\Command;

use Cake\Console\Command;
use Cake\ORM\Locator\LocatorAwareTrait;
//use App\Command\Traits\AlertProcessingTrait;

abstract class AlertCommandBase extends Command
{
    use LocatorAwareTrait;
//    use AlertProcessingTrait;

    protected string $processName = '';
    protected string $featureName = '';

    public function initialize(): void
    {
        $this->loadModel('Alert.Alerts');
        $this->loadModel('Alert.AlertRules');
        $this->loadModel('Alert.AlertLogs');
        $this->loadModel('Institution.Institutions');
        $this->loadModel('Security.Users');
        $this->loadModel('Security.SecurityGroupUsers');
        $this->loadModel('Staff.StaffStatuses');
        $this->loadModel('Institution.Staff');

        $class = basename(str_replace('\\', '/', static::class));
        $this->processName = str_replace('Command', '', $class);
        $this->featureName = str_replace('Alert', '', $this->processName);
    }
}
