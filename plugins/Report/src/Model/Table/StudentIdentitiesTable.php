<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class StudentIdentitiesTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('user_identities');
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('IdentityTypes', ['className' => 'FieldOption.IdentityTypes']);
		
		$this->addBehavior('Excel', [
			'excludes' => [],
			'pages' => false,
            'autoFields' => false
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
		$query
            ->select([
                'identity_type' => 'IdentityTypes.name',
                'identity_number' => 'StudentIdentities.number',
                'issue_date' => 'StudentIdentities.issue_date',
                'expiry_date' => 'StudentIdentities.expiry_date',
                'issue_location' => 'StudentIdentities.issue_location',
                'comments' => 'StudentIdentities.comments',
                'student_first_name' => 'Users.first_name',
                'student_middle_name' => 'Users.middle_name',
                'student_third_name' => 'Users.third_name',
                'student_last_name' => 'Users.last_name'
            ])
            ->contain(['IdentityTypes', 'Users'])
			->where(['Users.is_student' => 1]);
	}

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
    {
        foreach ($fields as $key => $field) { 
            //get the value from the table, but change the label to become default identity type.
            if ($field['field'] == 'identity_type_id') { 
                $fields[$key] = [
                    'key' => 'IdentityTypes.name',
                    'field' => 'identity_type',
                    'type' => 'string',
                    'label' => __('Identity Type')
                ];
            }

            if ($field['field'] == 'number') { 
                $fields[$key] = [
                    'key' => 'StudentIdentities.number',
                    'field' => 'identity_number',
                    'type' => 'string',
                    'label' => __('Identity Number')
                ];
            }

            if ($field['field'] == 'security_user_id') { 
                $fields[$key] = [
                    'key' => 'Users.first_name',
                    'field' => 'student_name',
                    'type' => 'string',
                    'label' => __('Student Name')
                ];
            }
        }
    }

    public function onExcelGetStudentName(Event $event, Entity $entity) 
    {
        //cant use $this->Users->get() since it will load big data and cause memory allocation problem
        $studentName = [];
        ($entity->student_first_name) ? $studentName[] = $entity->student_first_name : '';
        ($entity->student_middle_name) ? $studentName[] = $entity->student_middle_name : '';
        ($entity->student_third_name) ? $studentName[] = $entity->student_third_name : '';
        ($entity->student_last_name) ? $studentName[] = $entity->student_last_name : '';
        
        return implode(' ', $studentName);
    }
}
