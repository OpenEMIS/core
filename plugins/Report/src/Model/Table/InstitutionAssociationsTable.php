<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class InstitutionAssociationsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institution_associations');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions','foreignKey' => 'institution_id']);             $this->addBehavior('Excel', [
            'excludes' => [
                'total_male_students',
                'total_female_students'
            ],
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.InstitutionSecurity');
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->controller->getFeatureOptions('Institutions');
        return $attr;
    }

    public function onExcelBeforeQueryABK(Event $event, ArrayObject $settings, Query $query)
    {
        $InstitutionAssociationsStaff = TableRegistry::get('Institution.InstitutionAssociationStaff');
        $InstitutionAssociationsStudent = TableRegistry::get('Student.InstitutionAssociationStudent');
        $institutions = TableRegistry::get('institutions');
   
        $requestData = json_decode($settings['process']['params']);
        $institution_id = $requestData->institution_id;
        $where = [];
        if ($institution_id != 0) {
            $where['Institutions.id'] = $institution_id;
        }
       

        $query
            ->select([
                'academic_period_id' => $this->aliasField('academic_period_id'),
                'institution_code' => 'Institutions.code',
                'instituion_name' => 'Institutions.name',
                'name' => $this->aliasField('name'),
                'staff_name' => $query->func()->group_concat([ 
                    'SecurityUsers.openemis_no' => 'literal',
                    " - ",
                    'SecurityUsers.first_name' => 'literal',
                    " ",
                    'SecurityUsers.last_name' => 'literal']),
                'total_male_students' => 'InstitutionAssociations.total_male_students',
                'total_female_students' => 'InstitutionAssociations.total_female_students',
                'total_students' => $query->newExpr('InstitutionAssociations.total_male_students + InstitutionAssociations.total_female_students')                  
            ])
            ->contain([
                'Institutions.Types',
                'AcademicPeriods' => [
                    'fields' => [
                        'AcademicPeriods.name'
                    ]
                ],
            ])
             ->leftJoin(
            ['InstitutionAssociationStaff' => 'institution_association_staff'],
            [
                'InstitutionAssociationStaff.institution_association_id = '. $this->aliasField('id')
            ])
            ->leftJoin(['SecurityUsers' => 'security_users'],[
                'SecurityUsers.id  = '. $InstitutionAssociationsStaff->aliasField('security_user_id')
            ])
            ->group([
                'InstitutionAssociations.id'
            ])
            ->order([
                'AcademicPeriods.order',
                'Institutions.code',
                'InstitutionAssociations.id'
            ]);
            
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query){

        $InstitutionAssociations = TableRegistry::get('Institution.InstitutionAssociations');
        $Institutions = TableRegistry::get('Institution.Institutions');
        $InstitutionAssociationStaff = TableRegistry::get('Institution.InstitutionAssociationStaff');
        $InstitutionStudents = TableRegistry::get('Student.InstitutionAssociationStudent');
        $requestData = json_decode($settings['process']['params']);
        $institution_id = $requestData->institution_id;
        $condition = [];
        // $conditions = [
        //  $InstitutionAssociations->aliasField('InstitutionAssociations.institution_id') => $institution_id
        // ];
           $query 
				->select([
					'academic_period_id' => 'InstitutionAssociations.academic_period_id',
					'institution_code' => 'Institutions.code',
					'institution_name' => 'Institutions.name',
                    'name' => $this->aliasField('name'),
					'association_staff' => $query->func()->group_concat([
						'SecurityUsers.openemis_no' => 'literal',
						" - ",
						'SecurityUsers.first_name' => 'literal',
						" ",
						'SecurityUsers.last_name' => 'literal'
					]),
                'total_male_students' => 'InstitutionAssociations.total_male_students',
                'total_female_students' => 'InstitutionAssociations.total_female_students',
                'total_students' => $query->newExpr('InstitutionAssociations.total_male_students + InstitutionAssociations.total_female_students')
				])
				->contain([
					'AcademicPeriods' => [
						'fields' => [
							'AcademicPeriods.name'
						]
					],
					'Institutions.Types'
				])
				->leftJoin(
				['InstitutionAssociationStaff' => 'institution_association_staff'],
				[
					'InstitutionAssociationStaff.institution_association_id = '. $this->aliasField('id')
				]
				)
				->leftJoin(
				['SecurityUsers' => 'security_users'],
				[
					'SecurityUsers.id = '. $InstitutionAssociationStaff->aliasField('security_user_id')
				]
				)                
                //->where($conditions)
				// ->group([
				// 	'InstitutionAssociationStaff.id'
				// ])
				->order([
					'AcademicPeriods.order',
					'Institutions.code',
					'InstitutionAssociations.id'
				]);
                //POCOR-5852 starts
                // $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                //     return $results->map(function ($row) {
                //         $Users = TableRegistry::get('security_users');
                //         $user_data= $Users
                //                     ->find()
                //                     ->where(['security_users.openemis_no' => $row->openEMIS_ID])
                //                     ->first();
                //         $UserIdentities = TableRegistry::get('user_identities');//POCOR-5852 starts
                //         $IdentityTypes = TableRegistry::get('identity_types');//POCOR-5852 ends
                //         $conditions = [
                //             $UserIdentities->aliasField('security_user_id') => $user_data->id,
                //         ];
                //         $data = $UserIdentities
                //                     ->find()    
                //                     ->select([
                //                         'identity_type' => $IdentityTypes->alias().'.name',//POCOR-5852 starts
                //                         'identity_number' => $UserIdentities->alias().'.number',
                //                         'default' => $IdentityTypes->alias().'.default'
                //                         //POCOR-5852 ends
                //                     ])
                //                     ->leftJoin(
                //                     [$IdentityTypes->alias() => $IdentityTypes->table()],
                //                         [
                //                             $IdentityTypes->aliasField('id = '). $UserIdentities->aliasField('identity_type_id')
                //                         ]
                //                     )
                //                     ->where($conditions)->toArray();
                //         $row['identity_type'] = '';            
                //         $row['identity_number'] = '';            
                //         if(!empty($data)){
                //             $identity_type_name = '';
                //             $identity_type_number = '';
                //             foreach ($data as $key => $value) {
                //                 if($value->default == 1){
                //                    $identity_type_name =  $value->identity_type;    
                //                    $identity_type_number =  $value->identity_number;   
                //                    break; 
                //                 }
                //             }
                //             if(!empty($identity_type_name) && !empty($identity_type_number)){
                //                 $row['identity_type'] = $identity_type_name;
                //                 $row['identity_number'] = $identity_type_number;
                //             }else{
                //                 $row['identity_type'] = $data[0]->identity_type;
                //                 $row['identity_number'] = $data[0]->identity_number;
                //             }
                //         }
                //         return $row;           
                //     });
                // });
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        //redeclare all for sorting purpose.
        $newFields[] = [
            'key' => 'InstitutionAssociations.academic_period_id',
            'field' => 'academic_period_id',
            'type' => 'integer',
            'label' => ''
        ];
        $newFields[] = [
            'key' => 'Institutions.institution_code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Institutions.institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionAssociations.name',
            'field' => 'name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'association_staff',
            'type' => 'string',
            'label' => 'Staff'
        ];

        $newFields[] = [
            'key' => 'InstitutionAssociations.total_male_students',
            'field' => 'total_male_students',
            'type' => 'integer',
            'label' => 'Male Students'
        ];

        $newFields[] = [
            'key' => 'InstitutionAssociations.total_male_students',
            'field' => 'total_female_students',
            'type' => 'integer',
            'label' => 'Female Students'
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'total_students',
            'type' => 'integer',
            'label' => 'Total Students'
        ];

        $fields->exchangeArray($newFields);
    }
}
