<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class StaffIdentitiesTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('user_identities');
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('IdentityTypes', ['className' => 'FieldOption.IdentityTypes']);
		
		$this->addBehavior('Excel', [
			'excludes' => [],
			'pages' => false
		]);
		$this->addBehavior('Report.ReportList');
	}

	public function beforeAction(Event $event) {
		$this->fields = [];
		$this->ControllerAction->field('feature');
		$this->ControllerAction->field('format');
	}

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->controller->getFeatureOptions($this->alias());
		return $attr;
	}

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
		$requestData = json_decode($settings['process']['params']);
        $areaId = $requestData->area_education_id;
        $institutionId = $requestData->institution_id;
        $academicPeriodId = $requestData->academic_period_id;
        $InstitutionsTable = TableRegistry::get('Institution.Institutions');
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $periodEntity = $AcademicPeriods->get($academicPeriodId);
        $startDate = $periodEntity->start_date->format('Y-m-d');
        $endDate = $periodEntity->end_date->format('Y-m-d');
        $conditions = [];
        if (!empty($academicPeriodId)) {
                $conditions['OR'] = [
                    'OR' => [
                        [
                            'InstitutionStaff.end_date' . ' IS NOT NULL',
                            'InstitutionStaff.start_date' . ' <=' => $startDate,
                            'InstitutionStaff.end_date' . ' >=' => $startDate
                        ],
                        [
                            'InstitutionStaff.end_date' . ' IS NOT NULL',
                            'InstitutionStaff.start_date' . ' <=' => $endDate,
                            'InstitutionStaff.end_date' . ' >=' => $endDate
                        ],
                        [
                            'InstitutionStaff.end_date' . ' IS NOT NULL',
                            'InstitutionStaff.start_date' . ' >=' => $startDate,
                            'InstitutionStaff.end_date' . ' <=' => $endDate
                        ]
                    ],
                    [
                        'InstitutionStaff.end_date' . ' IS NULL',
                        'InstitutionStaff.start_date' . ' <=' => $endDate
                    ]
                ];
        }
        if (!empty($institutionId) && $institutionId > 0) {
            $conditions['InstitutionStaff.institution_id'] = $institutionId; 
        }
        if (!empty($areaId) && $areaId != -1) {
            $conditions[$InstitutionsTable->aliasField('area_id')] = $areaId; 
        }
		$query
			->contain(['IdentityTypes', 'Users'])
		    ->leftJoin(['InstitutionStaff' => 'institution_staff'], [
                'InstitutionStaff.staff_id = ' . $this->aliasField('security_user_id')
            ])
            ->leftJoin([$InstitutionsTable->alias() => $InstitutionsTable->table()], [
                $InstitutionsTable->aliasField('id = ') . 'InstitutionStaff.institution_id'
            ])
            ->where(['Users.is_staff' => 1, $conditions]);
	}
}
