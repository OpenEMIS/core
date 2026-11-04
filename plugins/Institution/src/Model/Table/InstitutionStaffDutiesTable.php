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
use Cake\Log\Log;
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

        // POCOR-9897: staff_id had no rule, so a blank Staff reached the insert and failed the
        // security_users FK (rendered as error404). Validate it like Duty Type, with messages
        // (the MessagesTrait lookup for these keys does not exist -> "[Message Not Found]").
        return $validator
            ->notEmptyString('staff_duties_id', __('Please select a Duty Type'))
            ->add('staff_duties_id', 'not-blank', ['rule' => 'notBlank', 'message' => __('Please select a Duty Type')])
            ->requirePresence('staff_id', 'create', __('Please select a Staff'))
            ->notEmptyString('staff_id', __('Please select a Staff'))
            // academic_period_id is NOT NULL with the same FK -> 404 risk; the dropdown has no empty
            // option, so this only guards blank/tampered posts
            ->requirePresence('academic_period_id', 'create', __('Please select an Academic Period'))
            ->notEmptyString('academic_period_id', __('Please select an Academic Period'));
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
        // POCOR-9878: one record per staff/duty type/institution/period — reactivate the existing one instead.
        $rules->add($rules->isUnique(
            ['staff_id', 'staff_duties_id', 'institution_id', 'academic_period_id'],
            __('This duty has already been assigned to this staff member for the selected academic period.')
        ));
        // POCOR-9878
        $rules->add([$this, 'checkStaffActiveForAssignment'], 'ruleStaffMustBeActiveToAssign', [
            'errorField' => 'staff_id',
            'message' => __('This duty cannot be assigned because the staff member no longer has an active assignment at this institution.')
        ]);
        return $rules;
    }

    // POCOR-9768: reactivating an existing duty (same staff) needs the staff still assigned here
    public function checkStaffActiveForActivation(Entity $entity, array $options)
    {
        if ($entity->status != self::STATUS_ACTIVE) {
            return true; // deactivating is always allowed
        }
        if ($entity->isNew() || $entity->isDirty('staff_id')) {
            return true; // POCOR-9878: covered by checkStaffActiveForAssignment(), avoid a duplicate error
        }
        return $this->isStaffAssigned($entity);
    }

    // POCOR-9878: a new duty, or one moved to another staff member (even Inactive), needs staff still assigned here
    public function checkStaffActiveForAssignment(Entity $entity, array $options)
    {
        if (!$entity->isNew() && !$entity->isDirty('staff_id')) {
            return true;
        }
        return $this->isStaffAssigned($entity);
    }

    private function isStaffAssigned(Entity $entity)
    {
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
            return __('Modified');
        }else if ($field == 'modified_user_id') {
            return __('Modified User');
        }else if ($field == 'created') {
            return __('Created');
        }else if ($field == 'created_user_id') {
            return __('Created User');
        }else if ($field == 'status') { // POCOR-9768
            return __('Status');
        }else if ($field == 'security_role') { // POCOR-9878
            return __('Security Role');
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
        // POCOR-9878: internal link to the security_group_users grant — show the granted role instead
        $this->field('security_group_user_id', ['visible' => false]);
    }

    public function viewBeforeAction(EventInterface $event)
    {
        $this->field('security_role'); // POCOR-9878
        $this->setFieldOrder(['academic_period_id', 'staff_duties_id', 'staff_id', 'status', 'security_role', 'comment','institutions.name']); // POCOR-9768
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra) {
        $this->field('Institution');
        $this->field('security_role'); // POCOR-9878
        $this->setFieldOrder(['academic_period_id', 'staff_duties_id', 'staff_id', 'status', 'security_role', 'comment','Institution']); // POCOR-9768
    }

    // POCOR-9878: the role actually granted by this duty (blank when the duty type carries no role or the duty is inactive)
    public function onGetSecurityRole(EventInterface $event, Entity $entity)
    {
        if (empty($entity->security_group_user_id)) {
            return '';
        }
        $groupUser = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers')->find()
            ->contain(['SecurityRoles'])
            ->where(['SecurityGroupUsers.id' => $entity->security_group_user_id])
            ->first();
        return !empty($groupUser->security_role) ? $groupUser->security_role->name : '';
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
        // POCOR-9878: default to the current period so the staff list below matches what the dropdown shows
        if ($entity->isNew() && empty($entity->academic_period_id)) {
            $entity->academic_period_id = $this->AcademicPeriods->getCurrent();
        }
        $staffOption = $this->getStaffList($entity);
        $this->field('academic_period_id', [
            'type' => 'select',
            'entity' => $entity,
            'onChangeReload' => true // POCOR-9878: reload the staff list for the selected period
        ]);
         $this->field('staff_duties_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
         $this->field('staff_id', [
            'type' => 'select',
            'options' => $staffOption
        ]);
        // POCOR-9768: beforeAction()'s field definition doesn't carry through to add/edit on its
        // own (same reason academic_period_id/staff_duties_id/staff_id are re-declared above) —
        // without this it renders as a plain text input instead of the Active/Inactive dropdown.
        $this->field('status', [
            'type' => 'select',
            'options' => [self::STATUS_ACTIVE => __('Active'), self::STATUS_INACTIVE => __('Inactive')]
        ]);
    }
    /**
     * Get staff list for drop down
     */
    public function getStaffList (?Entity $entity = null) {

        $institutionId = $this->getInstitutionID();
        $Staff = TableRegistry::getTableLocator()->get('Institution.Staff');
        $StaffStatuses = TableRegistry::getTableLocator()->get('Institution.StaffStatuses');
        $staffOptions = array();
        // POCOR-9878: only staff currently assigned to this institution (same check as
        // isStaffAssigned()) whose assignment falls in the selected academic period
        $result = $Staff->find()
                    ->where([
                        $Staff->aliasField('institution_id') => $institutionId,
                        $Staff->aliasField('staff_status_id') => $StaffStatuses->getIdByCode('ASSIGNED')
                    ])
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
            if (!empty($entity) && !empty($entity->academic_period_id)) {
                $result->find('academicPeriod', ['academic_period_id' => $entity->academic_period_id]);
            }
            $result->order([$this->Users->aliasField('first_name'), $this->Users->aliasField('last_name')]);
            foreach($result as $val) {

                    $staffOptions[$val->id] = $val->openemis_no .' - '.$val->first_name.' '.$val->last_name;
            }

            // POCOR-9878: on edit, keep the record's own staff (e.g. since ended) so the field isn't blanked
            if (!empty($entity) && !$entity->isNew() && !empty($entity->staff_id) && !isset($staffOptions[$entity->staff_id])) {
                $user = $this->Users->find()
                    ->select(['openemis_no', 'first_name', 'last_name'])
                    ->where([$this->Users->aliasField('id') => $entity->staff_id])
                    ->first();
                if (!empty($user)) {
                    $staffOptions[$entity->staff_id] = $user->openemis_no .' - '.$user->first_name.' '.$user->last_name;
                }
            }

            return $staffOptions;
    }

    // POCOR-9768: grant/revoke the duty type's linked security role in step with this record's status.
    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        // POCOR-9878: the existing grant belongs to the old staff/duty type — drop it before re-granting
        if (!$entity->isNew() && ($entity->isDirty('staff_id') || $entity->isDirty('staff_duties_id'))) {
            $this->revokeDutyRoleIfUnused($entity);
        }
        $this->syncDutyRole($entity);
    }

    // POCOR-9878: deleting a duty must not leave its security role behind
    public function afterDelete(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $this->revokeDutyRoleIfUnused($entity);
    }

    // POCOR-9878: a duty type may carry no security role (or have it removed later) — in that case
    // there is nothing to grant, but any grant left over from a previous role must still be revoked.
    private function syncDutyRole(Entity $entity)
    {
        $duty = $this->StaffDuties->get($entity->staff_duties_id);

        if ($entity->status == self::STATUS_ACTIVE && !empty($duty->security_role_id)) {
            $this->grantDutyRole($entity, $duty->security_role_id);
        } else {
            $this->revokeDutyRoleIfUnused($entity);
        }
    }

    // POCOR-9878: called when a duty type's security role is changed or removed — drop the grant made
    // for the old role on every active duty of that type, then grant the new role (if any).
    public function resyncDutyRoles($staffDutiesId)
    {
        $activeDuties = $this->find()
            ->where([
                $this->aliasField('staff_duties_id') => $staffDutiesId,
                $this->aliasField('status') => self::STATUS_ACTIVE
            ])
            ->all();

        foreach ($activeDuties as $dutyRecord) {
            $this->revokeDutyRoleIfUnused($dutyRecord);
            $this->syncDutyRole($dutyRecord);
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
            // POCOR-9768: keep the in-memory entity in sync with the updateAll() write above —
            // otherwise a caller reading $entity->security_group_user_id right after save() (e.g.
            // a controller, another shell step) sees stale/empty data even though the DB row is correct.
            $entity->security_group_user_id = $sharedGrant->security_group_user_id;
            $entity->setDirty('security_group_user_id', false);
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
            // POCOR-9768: same sync as the shared-grant branch above.
            $entity->security_group_user_id = $saved->id;
            $entity->setDirty('security_group_user_id', false);
        }
    }

    // POCOR-9768: only revoke the shared grant once no other active duty at this institution still needs it.
    private function revokeDutyRoleIfUnused(Entity $entity)
    {
        if (empty($entity->security_group_user_id)) {
            return;
        }

        $groupUserId = $entity->security_group_user_id;

        // POCOR-9878: match on the grant alone — it already belongs to one user/group, and the entity's
        // staff_id may have just been changed to someone else on edit
        $stillNeededByAnotherDuty = $this->find()
            ->where([
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
        // POCOR-9768: keep the in-memory entity in sync with the updateAll() write above — see
        // the matching comment in grantDutyRole().
        $entity->security_group_user_id = null;
        $entity->setDirty('security_group_user_id', false);
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
            // POCOR-9878: a failed save would leave the duty active with its role — make it visible
            if (!$this->save($dutyRecord)) {
                Log::error('InstitutionStaffDuties::deactivateDuties() failed for duty id ' . $dutyRecord->id
                    . ': ' . json_encode($dutyRecord->getErrors()));
            }
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
