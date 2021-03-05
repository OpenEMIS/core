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
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions','foreignKey' => 'institution_id']);           
        $this->addBehavior('Excel', [
            // 'excludes' => [
            //     'total_male_students',
            //     'total_female_students'
            // ],
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
    
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query){

        $Institutions = TableRegistry::get('Institution.Institutions');
        $InstitutionAssociationStaff = TableRegistry::get('Institution.InstitutionAssociationStaff');
        $requestData = json_decode($settings['process']['params']);
        $institution_id = $requestData->institution_id;
        $where = [];
        if ($institution_id != 0) {
            $where['InstitutionAssociations.institution_id'] = $institution_id;
        }

           $query 
				->select([
					'academic_period_id' =>  $this->aliasField('academic_period_id'),
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
                    'total_male_students' => $this->aliasField('total_male_students'),
                    'total_female_students' => $this->aliasField('total_female_students'),
                    'total_students' => $query->newExpr('InstitutionAssociations.total_male_students + InstitutionAssociations.total_female_students')
				])
                ->contain([
					'AcademicPeriods' => [
						'fields' => [
							'AcademicPeriods.name'
						]
					],
					'Institutions'
				])
                ->leftJoin(
				['InstitutionAssociationStaff' => 'institution_association_staff'],
				[
					'InstitutionAssociationStaff.institution_association_id = '. $this->aliasField('id')
				])
                ->leftJoin(
				['SecurityUsers' => 'security_users'],
				[
					'SecurityUsers.id = '. $InstitutionAssociationStaff->aliasField('security_user_id')
				]) 
                ->where($where) 
                ->group([
					'InstitutionAssociationStaff.institution_association_id'
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
            'field' => 'association_staff',
            'type' => 'string',
            'label' => __('Staff')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'total_male_students',
            'type' => 'integer',
            'label' =>  __('Male Students')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'total_female_students',
            'type' => 'integer',
            'label' =>  __('Female Students')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'total_students',
            'type' => 'integer',
            'label' => __('Total Students')
        ];

        $fields->exchangeArray($newFields);
    }
}
