<?php
namespace Institution\Model\Table;

use Cake\I18n\Date;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Event\Event;

use App\Model\Table\AppTable;

class InfrastructureUtilityTelephonesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('infrastructure_utility_telephones');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods',   ['className' => 'AcademicPeriod.AcademicPeriods', 'foreign_key' => 'academic_period_id']);
        $this->belongsTo('UtilityTelephoneTypes',   ['className' => 'Institution.UtilityTelephoneTypes', 'foreign_key' => 'utility_telephone_type_id']);
        $this->belongsTo('UtilityTelephoneConditions',   ['className' => 'Institution.UtilityTelephoneConditions', 'foreign_key' => 'utility_telephone_condition_id']);
    }
}
