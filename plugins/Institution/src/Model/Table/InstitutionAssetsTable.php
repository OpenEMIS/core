<?php
namespace Institution\Model\Table;

use ArrayObject;
 
use Cake\I18n\Date;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;

use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class InstitutionAssetsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AssetStatuses', ['className' => 'Institution.AssetStatuses']);
        $this->belongsTo('AssetTypes', ['className' => 'Institution.AssetTypes']);
        $this->belongsTo('AssetConditions', ['className' => 'Institution.AssetConditions']);

        // POCOR-6152 export button
        $this->addBehavior('Excel',[
            // 'excludes' => ['academic_period_id', 'institution_id'],
            'pages' => ['index'],
        ]);
        // POCOR-6152 export button
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('code', 'ruleUnique', [
                'rule' => ['validateUnique', ['scope' => ['academic_period_id', 'institution_id']]],
                'provider' => 'table'
            ]);
    }

    // POCOR06152 set breadcrumb header
    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $modelAlias = 'InstitutionAssets';
        $userType = '';
        $this->controller->changeUtilitiesHeader($this, $modelAlias, $userType);
    }
    // POCOR06152 set breadcrumb header
}
