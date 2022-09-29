<?php
namespace Report\Model\Table;

use ArrayObject;
use DateTime;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Log\Log;

class InstitutionPositionsSummariesTable extends AppTable
{
    private $features = [];

    public function initialize(array $config)
    {
        $this->table('institution_positions');
        parent::initialize($config);
        
        $this->addBehavior('Excel', [
            'autoFields' => false
        ]);
        $this->addBehavior('Report.InstitutionSecurity');
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('AcademicPeriod.Period');


    }

}