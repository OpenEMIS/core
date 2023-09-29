<?php
namespace App\Model\Behavior;

use ArrayObject;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;

class YearBehavior extends Behavior
{
    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.beforeAction'] = 'beforeAction';
        return $events;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $config = $this->config();
        foreach ($config as $date => $year) {
            if ($entity->has($date) && !empty($entity->{$date})) {
                if ($entity->{$date} instanceof Date || $entity->{$date} instanceof Time) {
                    $entity->{$year} = $entity->{$date}->year;
                } else {
                    $entity->{$year} = date('Y', strtotime($entity->{$date}));
                }
            } else {
                $entity->{$year} = null;
            }
        }
    }

    public function beforeAction(Event $event)
    {
        $config = $this->config();
        foreach ($config as $date => $year) {
            $this->_table->fields[$year]['visible'] = false;
        }
    }

    public function getYearOptionsByConfig()
    {
        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $lowestYear = $ConfigItems->value('lowest_year');
        $currentYear = date("Y");

        for ($i=$currentYear; $i >= $lowestYear; $i--) {
            $yearOptions[$i] = $i;
        }

        return $yearOptions;
    }
}
