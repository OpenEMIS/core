<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use App\Model\Table\AppTable;

class InstitutionPositionAssignmentsTable extends AppTable
{
    /// InstitutionPositionAssignments
    public function initialize(array $config)
    {
        $this->addBehavior('Excel', [
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.InstitutionSecurity');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
    }
}
