<?php
namespace Report\Model\Table;

use ArrayObject;
use DateTime;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\I18n\Time;
use Cake\Validation\Validator;
use Cake\I18n\FrozenTime;
use Cake\I18n\FrozenDate;

/**
 * POCOR-9382
 * Develop institution students audit report
 * This table maps to `institution_students`. 
 * Generate xlsx report
 * */

class AuditInstitutionStudentsTable extends AppTable
{ 
    public function initialize(array $config): void
    {
        $this->setTable('institution_students');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        
        $this->belongsTo('ModifiedUser', [
            'className' => 'Security.Users',
            'foreignKey' => 'modified_user_id'
        ]);
        $this->belongsTo('CreatedUser', [
            'className' => 'Security.Users',
            'foreignKey' => 'created_user_id'
        ]);
        $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $startDate   = $requestData->report_start_date ?? null;
        $endDate     = $requestData->report_end_date ?? null;
        //Apply  start_date and end_date filters
        if (!empty($startDate)) {
            $startDateObj = FrozenTime::createFromFormat('d-m-Y H:i:s', $startDate);

            // If endDate is empty, default to end of current year
            if (empty($endDate)) {
                $endDateObj = FrozenTime::createFromFormat('d-m-Y H:i:s', '31-12-' . date('Y') . ' 23:59:59');
            } else {
                $endDateObj = FrozenTime::createFromFormat('d-m-Y H:i:s', $endDate);
            }

            if ($startDateObj && $endDateObj) {
                $where[] = [
                    $this->aliasField('start_date') . ' <=' => $endDateObj->format('Y-m-d H:i:s'),
                    $this->aliasField('end_date') . ' >=' => $startDateObj->format('Y-m-d H:i:s')
                ];
            }
        }

        //Select only required fields
        $query
            ->select([
                $this->aliasField('id'),
                'academic_period'   => 'AcademicPeriods.name',
                'institution_code'  => 'Institutions.code',
                'institution_name'  => 'Institutions.name',
                'status_name'            => 'StudentStatuses.name',
                'openemis_no'           => 'Users.openemis_no',
                'student_name'           => 'Users.first_name',
                'education_grade'   => 'EducationGrades.name',
                $this->aliasField('start_date'),
                $this->aliasField('end_date'),
                'start_year'        => $this->aliasField('start_year'),
                'end_year'          => $this->aliasField('end_year'),
                'modified_user'     => 'ModifiedUser.username',
                $this->aliasField('modified'),
                'created_user'      => 'CreatedUser.username',
                $this->aliasField('created'),
            ])
            ->contain([
                'AcademicPeriods',
                'Institutions',
                'StudentStatuses',
                'EducationGrades',
                'Users',
                'ModifiedUser',   
                'CreatedUser' 
            ])->where([$where])
            ->order([$this->aliasField('education_grade_id') => 'ASC']);
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'academic_period',
            'field' => 'academic_period',
            'type' => 'string',
            'label' => __('Academic Period')
        ];
        $newFields[] = [
            'key' => 'institution_code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];
        $newFields[] = [
            'key' => 'institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];
        $newFields[] = [
            'key' => 'status_name',
            'field' => 'status_name',
            'type' => 'string',
            'label' => __('Status')
        ];
         $newFields[] = [
            'key' => 'openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('Openemis Number')
        ];
        $newFields[] = [
            'key' => 'student_name',
            'field' => 'student_name',
            'type' => 'string',
            'label' => __('Student')
        ];
        $newFields[] = [
            'key' => 'education_grade',
            'field' => 'education_grade',
            'type' => 'string',
            'label' => __('Education Grade')
        ];
        $newFields[] = [
            'key' => 'start_date',
            'field' => 'start_date',
            'type' => 'string',
            'label' => __('Start Date')
        ];
        $newFields[] = [
            'key' => 'start_year',
            'field' => 'start_year',
            'type' => 'string',
            'label' => __('Start Year')
        ];
         $newFields[] = [
            'key' => 'end_date',
            'field' => 'end_date',
            'type' => 'string',
            'label' => __('End Date')
        ];
        $newFields[] = [
            'key' => 'end_year',
            'field' => 'end_year',
            'type' => 'string',
            'label' => __('End Year')
        ];
        $newFields[] = [
            'key' => 'modified_user',
            'field' => 'modified_user',
            'type' => 'string',
            'label' => __('Modified User')
        ];
        $newFields[] = [
            'key' => 'modified',
            'field' => 'modified',
            'type' => 'string',
            'label' => __('Modified')
        ];
        $newFields[] = [
            'key' => 'created_user',
            'field' => 'created_user',
            'type' => 'string',
            'label' => __('Created User')
        ];
        $newFields[] = [
            'key' => 'created',
            'field' => 'created',
            'type' => 'string',
            'label' => __('Created')
        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetStartDate(EventInterface $event, Entity $entity) {
        if (!empty($entity->start_date)) {
            return $this->formatDate($entity->start_date);
        }
    }
    public function onExcelGetEndDate(EventInterface $event, Entity $entity) {
        if (!empty($entity->end_date)) {
            return $this->formatDate($entity->end_date);
        }
    }
    public function onExcelGetModified(EventInterface $event, Entity $entity) {
        if (!empty($entity->modified)) {
            return $this->formatDate($entity->modified);
        }
    }
    public function onExcelGetCreated(EventInterface $event, Entity $entity) {
        if (!empty($entity->created)) {
            return $this->formatDate($entity->created);
        }
    }
}
