<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class StaffTransfersTable extends AppTable {
    
    private $prevPositionTitle;

    public function initialize(array $config)
    {
        $this->table('institution_staff_assignments');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('Positions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
        $this->belongsTo('PreviousInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'previous_institution_id']);
        $this->belongsTo('StaffTypes', ['className' => 'Staff.StaffTypes']);

        $this->addBehavior('Excel', ['excludes' => ['end_date', 'status', 'staff_type_id', 'FTE', 'type', 'update']]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('AcademicPeriod.Period');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $query
            ->select(['openemis_no' => 'Users.openemis_no',
                    'identity_type' => 'IdentityTypes.name',
                    'identity_number' => 'Users.identity_number',
                    'prev_institution_code' => 'PreviousInstitutions.code',
                    'next_institution_code' => 'Institutions.code',
                    'next_position_no' => 'Positions.position_no',
                    'next_position_title' => 'StaffPositionTitles.name',
                    'authorized_date' => $this->aliasField('created')
            ])
            ->contain(['Users.IdentityTypes','Positions.StaffPositionTitles','Institutions','PreviousInstitutions','CreatedUser']);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'StaffTransfers.staff_id',
            'field' => 'staff_id',
            'type' => 'integer',
            'label' => __('Staff Name')
        ];

        $newFields[] = [
            'key' => 'IdentityTypes.name',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => __('Identity Type')
        ];

        $newFields[] = [
            'key' => 'Users.identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'PreviousInstitutions.code',
            'field' => 'prev_institution_code',
            'type' => 'string',
            'label' => __('Institution Code Transferred From')
        ];

        $newFields[] = [
            'key' => 'StaffTransfers.previous_institution_id',
            'field' => 'previous_institution_id',
            'type' => 'integer',
            'label' => __('Institution Transferred From')
        ];

        $newFields[] = [
            'key' => 'StaffTransfers.previous_position_no',
            'field' => 'previous_position_no',
            'type' => 'string',
            'label' => __('Position No Transferred From')
        ];

        $newFields[] = [
            'key' => 'StaffTransfers.previous_position_title',
            'field' => 'previous_position_title',
            'type' => 'string',
            'label' => __('Position Transferred From')
        ];

        $newFields[] = [
            'key' => 'Institutions.code',
            'field' => 'next_institution_code',
            'type' => 'string',
            'label' => __('Institution code Transferred To')
        ];

        $newFields[] = [
            'key' => 'StaffTransfers.institution_id',
            'field' => 'institution_id',
            'type' => 'integer',
            'label' => __('Institution Transferred To')
        ];

        $newFields[] = [
            'key' => 'Positions.position_no',
            'field' => 'next_position_no',
            'type' => 'string',
            'label' => __('Position No Transferred To')
        ];

        $newFields[] = [
            'key' => 'StaffPositionTitles.name',
            'field' => 'next_position_title',
            'type' => 'string',
            'label' => __('Position Transferred To')
        ];

        $newFields[] = [
            'key' => 'StaffTransfers.start_date',
            'field' => 'start_date',
            'type' => 'date',
            'label' => __('Transferred On')
        ];

        $newFields[] = [
            'key' => 'StaffTransfers.created',
            'field' => 'authorized_date',
            'type' => 'date',
            'label' => __('Authorized Date')
        ];

        $newFields[] = [
            'key' => 'StaffTransfers.created_user_id',
            'field' => 'authorized_by',
            'type' => 'string',
            'label' => __('Authorized By')
        ];

        $newFields[] = [
            'key' => 'StaffTransfers.comment',
            'field' => 'comment',
            'type' => 'text',
            'label' => ''
        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetPreviousPositionNo(Event $event, Entity $entity)
    {   
        $prevPositionNo = '';
        $InstitutionStaff = TableRegistry::get('Institution.Staff');
        if ($entity->has('previous_institution_id') && !empty($entity->previous_institution_id)) {
            if ($entity->has('staff_id') && !empty($entity->staff_id)) {
                $prevInstitutionPosition = $InstitutionStaff
                                            ->find()
                                            ->contain('Positions.StaffPositionTitles')
                                            ->where([
                                                $InstitutionStaff->aliasField('institution_id') => $entity->previous_institution_id,
                                                $InstitutionStaff->aliasField('staff_id') => $entity->staff_id,
                                            ])
                                            ->order([
                                                $InstitutionStaff->aliasField('created DESC')
                                            ])
                                            ->first();

                if (!empty($prevInstitutionPosition)) {
                    if (!empty($prevInstitutionPosition->position)) {
                        $prevPositionNo = $prevInstitutionPosition->position->position_no;
                        if (!empty($prevInstitutionPosition->position->staff_position_title)) {
                            $this->prevPositionTitle = $prevInstitutionPosition->position->staff_position_title->name;
                        }
                    }
                }
            }
        }
        return $prevPositionNo;
    }

    public function onExcelGetPreviousPositionTitle(Event $event, Entity $entity)
    { 
        return $this->prevPositionTitle;
    }

    public function onExcelGetAuthorizedBy(Event $event, Entity $entity)
    { 
        return $entity->created_user->name;
    }
}
