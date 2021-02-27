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
                'code',
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

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $InstitutionAssociationsStaff = TableRegistry::get('Staff.InstitutionAssociationStaff');
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
                'Institutions' => [
                    'fields' => [
                        'Institutions.code',
                        'Institutions.name'
                    ]
                ],
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
            'field' => 'staff_name',
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
