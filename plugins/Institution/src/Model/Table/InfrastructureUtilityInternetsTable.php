<?php
namespace Institution\Model\Table;

use Cake\I18n\Date;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Event\Event;

use App\Model\Table\AppTable;

class InfrastructureUtilityInternetsTable extends AppTable
{
    private $internetPurpose = [
        1 => 'Teaching',
        2 => 'Non-Teaching'
    ];

    public function initialize(array $config)
    {
        $this->table('infrastructure_utility_internets');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods',   ['className' => 'AcademicPeriod.AcademicPeriods', 'foreign_key' => 'academic_period_id']);
        $this->belongsTo('UtilityInternetTypes',   ['className' => 'Institution.UtilityInternetTypes', 'foreign_key' => 'utility_internet_type_id']);
        $this->belongsTo('UtilityInternetConditions',   ['className' => 'Institution.UtilityInternetConditions', 'foreign_key' => 'utility_internet_condition_id']);
        $this->belongsTo('UtilityInternetBandwidths',   ['className' => 'Institution.UtilityInternetBandwidths', 'foreign_key' => 'utility_internet_bandwidth_id']);
    }

    public function getPurposeOptions()
    {
        return $this->internetPurpose;
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->requirePresence('internet_purpose')
            ->requirePresence('utility_internet_condition_id')
        ;
    }
}
