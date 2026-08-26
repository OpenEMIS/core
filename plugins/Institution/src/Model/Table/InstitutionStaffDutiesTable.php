<?php
namespace Institution\Model\Table;
use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\ORM\RulesChecker;
use App\Model\Table\ControllerActionTable;

class InstitutionStaffDutiesTable extends ControllerActionTable
{
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    public function initialize(array $config): void
    {
        $this->setTable('institution_staff_duties');
        parent::initialize($config);
        $this->belongsTo('StaffDuties', ['className' => 'Institution.StaffDuties', 'foreignKey' => 'staff_duties_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);

        $this->addBehavior('Excel',[
           // 'excludes' => ['institution_id'],
            'pages' => ['index'],
        ]);
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['StaffDuties' =>['id']
            ]
        ]);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        // POCOR-9768: staff end-of-assignment/transfer/removal must auto-deactivate their duties
        $events['Model.Staff.afterSave'] = 'staffAfterSave';
        $events['Model.InstitutionStaff.afterDelete'] = 'institutionStaffAfterDelete';
        return $events;
    }

    public function validationDefault(Validator $validator): Validator {
		$validator = parent::validationDefault($validator);

		return $validator
			->add('staff_duties_id', 'not-blank', ['rule' => 'notBlank']);
	}

    // POCOR-9768: Application Rule (not a Validator rule) so this is enforced on every save() —
    // checkRules() runs unconditionally inside Table::_processSave(), regardless of whether the
    // entity was built via patchEntity()/newEntity() or by mutating an already-fetched entity
    // directly (e.g. AccountBehavior::editAfterSaveDuties() reactivating an existing row).
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules = parent::buildRules($rules);
        $rules->add([$this, 'checkStaffActiveForActivation'], 'ruleStaffMustBeActiveToActivate', [
            'errorField' => 'status',
            'message' => __('This duty cannot be activated because the staff member no longer has an active assignment at this institution.')
        ]);
        return $rules;
    }

    // POCOR-9768
    public function checkStaffActiveForActivation(Entity $entity, array $options)
    {
        if ($entity->status != self::STATUS_ACTIVE) {
            return true; // deactivating is always allowed
        }

        $staffId = $entity->staff_id;
        $institutionId = $entity->institution_id;
        if (empty($staffId) || empty($institutionId)) {
            return true; // let the not-blank/required rules handle missing data
        }

        $Staff = TableRegistry::getTableLocator()->get('Institution.Staff');
        $StaffStatuses = TableRegistry::getTableLocator()->get('Institution.StaffStatuses');
        return $Staff->find()
            ->where([
                $Staff->aliasField('institution_id') => $institutionId,
                $Staff->aliasField('staff_id') => $staffId,
                $Staff->aliasField('staff_status_id') => $StaffStatuses->getIdByCode('ASSIGNED')
            ])
            ->count() > 0;
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'academic_period_id') {
            return __('Academic Period');
        }
        else if ($field == 'staff_duties_id') {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
        else if ($field == 'staff_id') {
            return __('Staff');
        } else if ($field == 'comment') {
            return __('Comment');
        }else if ($field == 'Institution') {
            return __('Institution');
        }else if ($field == 'modified') {
            return __('Modified On');
        }else if ($field == 'modified_user_id') {
            return __('Modified By');
        }else if ($field == 'created') {
            return __('Created On');
        }else if ($field == 'created_user_id') {
            return __('Created By');
        }else if ($field == 'status') { // POCOR-9768
            return __('Status');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
        //print_r($field); exit;

    }

    // POCOR-9768
    public function onGetStatus(EventInterface $event, Entity $entity)
    {
        return $entity->status == self::STATUS_ACTIVE ? __('Active') : __('Inactive');
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra) // POCOR-9768
    {
        $this->field('status', [
            'type' => 'select',
            'options' => [self::STATUS_ACTIVE => __('Active'), self::STATUS_INACTIVE => __('Inactive')],
            'select' => false
        ]);
    }

    public function viewBeforeAction(EventInterface $event)
    {

        $this->setFieldOrder(['academic_period_id', 'staff_duties_id', 'staff_id', 'status', 'comment','institutions.name']); // POCOR-9768
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra) {
        $this->field('Institution');
        $this->setFieldOrder(['academic_period_id', 'staff_duties_id', 'staff_id', 'status', 'comment','Institution']); // POCOR-9768
    }

    public function onGetStaffId(EventInterface $event, Entity $entity)
    {
        $Users = TableRegistry::getTableLocator()->get('User.Users');
        $result = $Users
            ->find()
            ->select(['first_name','last_name'])
            ->where(['id' => $entity->staff_id])
            ->first();
        return $entity->staff_id = $entity->staff_id = $entity['user']->openemis_no .' - '.$result->first_name.' '.$result->last_name;
    }

    /******************************************************************************************************************
    **
    ** addEdit action methods
    **
    ******************************************************************************************************************/
    public function addEditBeforeAction(EventInterface $event)
    {

        $this->setFieldOrder([
            'academic_period_id', 'staff_duties_id',
            'staff_id', 'status', 'comment','institution_id' // POCOR-9768
        ]);
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $staffOption = $this->getStaffList();
//        print_r($staffOption);die();
        $this->field('academic_period_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
         $this->field('staff_duties_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
         $this->field('staff_id', [
            'type' => 'select',
            'options' => $staffOption
        ]);
    }
    /**
     * Get staff list for drop down
     */
    public function getStaffList () {

        $institutionId = $this->getInstitutionID();
        $Staff = TableRegistry::getTableLocator()->get('Institution.Staff');
        $staffOptions = array();
        $result = $Staff->find()
                    ->where([$Staff->aliasField('institution_id')=>$institutionId])
                    ->select([
                        'first_name' => 'Users.first_name',
                        'openemis_no' =>'Users.openemis_no',
                        'id' => 'Users.id',
                        'last_name' => 'Users.last_name',
                    ])
                    ->leftJoin(
                    ['Users' => 'security_users'], [
                        'Users.id = '. $Staff->aliasField('staff_id')
                    ]);
            $result->order([$this->Users->aliasField('first_name'), $this->Users->aliasField('last_name')]);
            foreach($result as $val) {

                    $staffOptions[$val->id] = $val->openemis_no .' - '.$val->first_name.' '.$val->last_name;
            }

            return $staffOptions;
    }

    // POCOR-9768: grant/revoke the duty type's linked security role in step with this record's status.
    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $this->syncDutyRole($entity);
    }

    private function syncDutyRole(Entity $entity)
    {
        $duty = $this->StaffDuties->get($entity->staff_duties_id);
        if (empty($duty->security_role_id)) {
            return; // this duty type carries no security role
        }

        if ($entity->status == self::STATUS_ACTIVE) {
            $this->grantDutyRole($entity, $duty->security_role_id);
        } else {
            $this->revokeDutyRoleIfUnused($entity);
        }
    }

    // POCOR-9768: if another active duty for this staff at this institution already grants the same
    // security role, reuse its grant instead of creating a duplicate security_group_users row.
    private function grantDutyRole(Entity $entity, $securityRoleId)
    {
        if (!empty($entity->security_group_user_id)) {
            return; // already has a grant (own or shared)
        }

        $sharedGrant = $this->find()
            ->matching('StaffDuties', function ($q) use ($securityRoleId) {
                return $q->where(['StaffDuties.security_role_id' => $securityRoleId]);
            })
            ->where([
                $this->aliasField('staff_id') => $entity->staff_id,
                $this->aliasField('institution_id') => $entity->institution_id,
                $this->aliasField('status') => self::STATUS_ACTIVE,
                $this->aliasField('id !=') => $entity->id,
            ])
            ->whereNotNull($this->aliasField('security_group_user_id'))
            ->first();

        if (!empty($sharedGrant)) {
            $this->updateAll(
                ['security_group_user_id' => $sharedGrant->security_group_user_id],
                ['id' => $entity->id]
            );
            return;
        }

        $institution = TableRegistry::getTableLocator()->get('Institution.Institutions')->get($entity->institution_id);
        $SecurityGroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
        $newGroupUser = $SecurityGroupUsers->newEntity([
            'security_role_id' => $securityRoleId,
            'security_group_id' => $institution->security_group_id,
            'security_user_id' => $entity->staff_id
        ]);
        $saved = $SecurityGroupUsers->save($newGroupUser);
        if ($saved) {
            $this->updateAll(['security_group_user_id' => $saved->id], ['id' => $entity->id]);
        }
    }

    // POCOR-9768: only revoke the shared grant once no other active duty at this institution still needs it.
    private function revokeDutyRoleIfUnused(Entity $entity)
    {
        if (empty($entity->security_group_user_id)) {
            return;
        }

        $groupUserId = $entity->security_group_user_id;

        $stillNeededByAnotherDuty = $this->find()
            ->where([
                $this->aliasField('staff_id') => $entity->staff_id,
                $this->aliasField('institution_id') => $entity->institution_id,
                $this->aliasField('status') => self::STATUS_ACTIVE,
                $this->aliasField('id !=') => $entity->id,
                $this->aliasField('security_group_user_id') => $groupUserId
            ])
            ->count() > 0;

        if (!$stillNeededByAnotherDuty) {
            $SecurityGroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
            $groupUser = $SecurityGroupUsers->find()
                ->where(['id' => $groupUserId])
                ->first();
            if (!empty($groupUser)) {
                $SecurityGroupUsers->delete($groupUser);
            }
        }

        $this->updateAll(['security_group_user_id' => null], ['id' => $entity->id]);
    }

    // POCOR-9768: auto-deactivate duties when the staff member's assignment ends (edit path).
    public function staffAfterSave(EventInterface $event, $staffEntity)
    {
        $isStillActive = empty($staffEntity->end_date) || $staffEntity->end_date->isToday() || $staffEntity->end_date->isFuture();
        if (!$isStillActive) {
            $this->deactivateDuties($staffEntity->staff_id, $staffEntity->institution_id);
        }
    }

    // POCOR-9768: auto-deactivate duties when the staff assignment record is deleted outright (e.g. transfer).
    public function institutionStaffAfterDelete(EventInterface $event, $staffEntity)
    {
        $this->deactivateDuties($staffEntity->staff_id, $staffEntity->institution_id);
    }

    // POCOR-9768: public so bulk/cron paths that end an assignment via updateAll() instead of
    // save() (e.g. StaffTable::removeInactiveStaffSecurityRole()) can still trigger this directly.
    public function deactivateDuties($staffId, $institutionId)
    {
        $activeDuties = $this->find()
            ->where([
                $this->aliasField('staff_id') => $staffId,
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('status') => self::STATUS_ACTIVE
            ])
            ->all();

        foreach ($activeDuties as $dutyRecord) {
            $dutyRecord->status = self::STATUS_INACTIVE;
            $this->save($dutyRecord);
        }
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
     
        $extraField[] = [
            'key'   => 'academic_period_id',
            'field' => 'academic_period_id',
            'type'  => 'integer',
            'label' => __('Academic Period')
        ];

        $extraField[] = [
            'key'   => 'staff_duties_id',
            'field' => 'staff_duties_id',
            'type'  => 'string',
            'label' => __('Duty Type')
        ];

        $extraField[] = [
            'key'   => 'staff_id',
            'field' => 'staff_id',
            'type'  => 'string',
            'label' => __('Staff')
        ];

        $extraField[] = [
            'key'   => 'comment',
            'field' => 'comment',
            'type'  => 'string',
            'label' => __('Comment')
        ];
         $extraField[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution')
        ];

        $fields->exchangeArray($extraField);
    }

    public function onExcelGetInstitutionName(EventInterface $event, Entity $entity)
    {
        $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $InstitutionName=$Institutions->find()->select('name')->where(['id' => $entity->institution_id])->first();
        return $InstitutionName['name'];
    }
    public function onGetInstitution(EventInterface $event, Entity $entity)
    {
        $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $InstitutionName=$Institutions->find()->select('name')->where(['id' => $entity->institution_id])->first();
        return $InstitutionName['name'];
    }

    public function onExcelGetStaffId(EventInterface $event, Entity $entity)
    {
        $Users = TableRegistry::getTableLocator()->get('User.Users');
        $result = $Users
            ->find()
            ->select(['first_name','last_name'])
            ->where(['id' => $entity->staff_id])
            ->first();
        return $entity->staff_id = $entity->staff_id = $entity['staff']->openemis_no .' - '.$result->first_name.' '.$result->last_name;
    }
}
