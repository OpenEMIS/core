<?php
namespace Institution\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Event\Event;

use App\Model\Table\AppTable;

class CounsellingsTable extends AppTable
{
    const ASSIGNED = 1;

    public function initialize(array $config)
    {
        $this->table('institution_counsellings');
        parent::initialize($config);

        $this->belongsTo('GuidanceTypes', ['className' => 'Student.GuidanceTypes', 'foreign_key' => 'guidance_type_id']);
        $this->belongsTo('Counselors', ['className' => 'Security.Users', 'foreign_key' => 'counselor_id']);
        $this->belongsTo('Requesters', ['className' => 'Security.Users', 'foreign_key' => 'requester_id']);
        $this->addBehavior('Page.FileUpload', [
            'fieldMap' => ['file_name' => 'file_content'],
            'size' => '2MB'
        ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Restful.Model.isAuthorized'] = ['callable' => 'isAuthorized', 'priority' => 1];
        return $events;
    }

    public function isAuthorized(Event $event, $scope, $action, $extra)
    {
        if ($action == 'download' || $action == 'image') {
            // check for the user permission to download here
            $event->stopPropagation();
            return true;
        }
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator->allowEmpty('file_content');
    }

    public function getDefaultConfig()
    {
        return $this->defaultConfig;
    }

    public function getGuidanceTypesOptions($institutionId)
    {
        // should be auto, if auto the reorder and visible not working
        $guidanceTypesOptions = $this->GuidanceTypes
            ->find('list')
            ->find('visible')
            ->find('order')
            ->toArray();

        return $guidanceTypesOptions;
    }

    public function getCounselorOptions($institutionId)
    {
        // get the staff that assigned from the institution from security user
        $InstitutionStaff = TableRegistry::get('Institution.Staff');

        $counselorOptions = $this->Counselors
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'name_with_id'
            ])
            ->innerJoin(
                    [$InstitutionStaff->alias() => $InstitutionStaff->table()],
                    [
                        $InstitutionStaff->aliasField('staff_id = ') . $this->Counselors->aliasField('id'),
                        $InstitutionStaff->aliasField('institution_id') => $institutionId,
                        $InstitutionStaff->aliasField('staff_status_id') => self::ASSIGNED
                    ]
                )
            ->order([
                $this->Counselors->aliasField('first_name'),
                $this->Counselors->aliasField('last_name')
            ])
            ->toArray();

        return $counselorOptions;
    }

    //POCOR-7054 change dropdown query
    public function getRequesterOptions($institutionId)
    {        
        $InstitutionStaff = TableRegistry::get('institution_staff');
        $InstitutionStudents = TableRegistry::get('institution_students');
        $Institutions = TableRegistry::get('Institution.Institutions');
        $UserData = TableRegistry::get('User.Users');
        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $academicPeriodId = $this->AcademicPeriods->getCurrent();
        $join = [];
        $join[''] = [
        'type' => 'inner',
        'table' => "(SELECT institution_students.student_id user_id
                        FROM institution_students
                        INNER JOIN academic_periods
                        ON academic_periods.id = institution_students.academic_period_id
                        WHERE academic_periods.id = $academicPeriodId
                        AND institution_students.institution_id = $institutionId
                        AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_students.student_status_id = 1, institution_students.student_status_id IN (1, 7, 6, 8))
                        GROUP BY institution_students.student_id

                        UNION ALL

                        SELECT institution_staff.staff_id user_id
                        FROM institution_staff
                        INNER JOIN academic_periods
                        ON (((institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.start_date AND institution_staff.end_date >= academic_periods.start_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.end_date AND institution_staff.end_date >= academic_periods.end_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date >= academic_periods.start_date AND institution_staff.end_date <= academic_periods.end_date)) OR (institution_staff.end_date IS NULL AND institution_staff.start_date <= academic_periods.end_date))
                        WHERE academic_periods.id = $academicPeriodId
                        AND institution_staff.institution_id = $institutionId
                        AND institution_staff.staff_status_id = 1
                        GROUP BY institution_staff.staff_id
                            ) subq",
                            'conditions' => ['subq.user_id = Users.id'],
                ];
        $requestorOptions = $UserData
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'name_with_id'
            ])
            ->select([
                    'id'=> $UserData->aliasField('id'),
                    $UserData->aliasField('openemis_no'),
                    $UserData->aliasField('first_name'),
                    $UserData->aliasField('middle_name'),
                    $UserData->aliasField('third_name'),
                    $UserData->aliasField('last_name')
            ]);

          $data =   $requestorOptions->join($join)->toArray();
            return $data;
    }
}
