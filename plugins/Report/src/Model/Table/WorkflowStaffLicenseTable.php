<?php

namespace Report\Model\Table;

use ArrayObject;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class WorkflowStaffLicenseTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table("staff_licenses");
        parent::initialize($config);

        $this->belongsTo('WorkflowSteps', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('InstitutionStaff', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('LicenseTypes', ['className' => 'FieldOption.LicenseTypes']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->belongsToMany('Classifications', [
            'className' => 'FieldOption.LicenseClassifications',
            'joinTable' => 'staff_licenses_classifications',
            'foreignKey' => 'staff_license_id',
            'targetForeignKey' => 'license_classification_id',
            'through' => 'Staff.StaffLicensesClassifications',
            'dependent' => true
        ]);

        $this->belongsToMany('Staff', [
            'className' => 'Institution.Staff',
            'foreignKey' => 'staff_id',
            'targetForeignKey' => 'security_user_id',
            'through' => 'User.Users',
            'dependent' => true
        ]);

        $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.WorkflowReport');
    }

    //POCOR-7637
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query)
    {
        $query = $this->addInstitutionJoinToQuery($query);
//        $this->log($query->sql(), 'debug');
    }

    public function onExcelGetOpenemisNo(Event $event, Entity $entity)
    {

        $security_user_id = $entity['security_user_id'];
        $user = self::getRelatedRecord('security_users', $security_user_id);
        return $user['openemis_no'];
    }

    public function onExcelGetSecurityUserId(Event $event, Entity $entity)
    {
//        $this->log($entity,'debug');
        $security_user_id = $entity['security_user_id'];
        $user = self::getRelatedRecord('security_users', $security_user_id);
        return $user['first_name'] . ' ' . $user['last_name'];
    }

    public function onExcelGetAssigneeId(Event $event, Entity $entity)
    {
//        $this->log($entity,'debug');
        $security_user_id = $entity['assignee_id'];
        $user = self::getRelatedRecord('security_users', $security_user_id);
        if (isset($user['first_name']) && isset($user['last_name'])) {
            return $user['first_name'] . ' ' . $user['last_name'];
        } else {
            return $entity['assignee_id'];
        }
    }

    public function onExcelGetLicenseTypeId(Event $event, Entity $entity)
    {
//        $this->log($entity,'debug');
        $security_user_id = $entity['license_type_id'];
        $user = self::getRelatedRecord('license_types', $security_user_id);
        return $user['name'];
    }

    public function onExcelGetStatusId(Event $event, Entity $entity)
    {
//        $this->log($entity,'debug');
        $security_user_id = $entity['status_id'];
        $user = self::getRelatedRecord('workflow_steps', $security_user_id);
        return $user['name'];
    }

    /**
     * common proc to show related field with id in the index table
     * @param $tableName
     * @param $relatedField
     * @author Dr Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getRelatedRecord($tableName, $relatedField)
    {
        if (!$relatedField) {
            return null;
        }
        $Table = TableRegistry::get($tableName);
        try {
            $related = $Table->get($relatedField);
            return $related->toArray();
        } catch (RecordNotFoundException $e) {
            return null;
        }
        return null;
    }

    /**
     * @param $query
     */
    private function addInstitutionJoinToQuery($query)
    {
        $InstitutionStaffTable = TableRegistry::get('Institution.Staff');
        $InstitutionsTable = TableRegistry::get('Institution.Institutions');
        $query
            ->innerJoin([$InstitutionStaffTable->alias() => $InstitutionStaffTable->table()], [
                $InstitutionStaffTable->aliasField('staff_id = ') . $this->aliasField('security_user_id')
            ]);
//        $this->log($query->sql(), 'debug');
        $query
            ->innerJoin([$InstitutionsTable->alias() => $InstitutionsTable->table()], [
                $InstitutionStaffTable->aliasField('institution_id = ') . $InstitutionsTable->aliasField('id')]);
//        $this->log($query->sql(), 'debug');
        $query->group([$this->aliasField('id')]);
//        $this->log($query->sql(), 'debug');
        return $query;
    }

}
