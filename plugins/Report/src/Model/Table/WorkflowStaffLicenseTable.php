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
        $query->contain(['Users', 'Assignees', 'LicenseTypes', 'WorkflowSteps']);
        $query->select(['id', 'license_number', 'issue_date', 'expiry_date', 'issuer', 'comments']);
        $query = $this->addInstitutionStaffToQuery($query);
        $query = $this->addInstitutionToQuery($query);
        $query = $this->addGroupingToQuery($query);
        $query = $this->addUserBasicFields($query);
        $query = $this->addAssigneeBasicFields($query);
        $query = $this->addLicenseTypeField($query);
        $query = $this->addWorkflowStepField($query);
        $query = $this->addInstitutionFields($query);
        $this->log($query, 'debug');
        return $query;

    }


    /**
     * @param Query $query
     * @return Query
     */
    private function addUserBasicFields(Query $query)
    {

        $query = $query->select([
            'staff_name' => 'CONCAT(Users.first_name, " ", Users.last_name)',
            'staff_openemis_no' => 'Users.openemis_no',
        ]);
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function addInstitutionFields(Query $query)
    {
        $InstitutionsTable = TableRegistry::get('Institution.Institutions');
        $query = $query->select([
            'institution_name' => $InstitutionsTable->aliasField('name'),
            'institution_code' => $InstitutionsTable->aliasField('code')
        ]);
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function addAssigneeBasicFields(Query $query)
    {

        $query = $query->select([
            'assignee_name' => 'CONCAT(Assignees.first_name, " ", Assignees.last_name)',
            'assignee_openemis_no' => 'Assignees.openemis_no',
        ]);
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function addLicenseTypeField(Query $query)
    {

        $query = $query->select([
            'license_type' => 'LicenseTypes.name',
        ]);
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function addWorkflowStepField(Query $query)
    {

        $query = $query->select([
            'workflow_step' => 'WorkflowSteps.name',
        ]);
        return $query;
    }


    /**
     * @param Query $query
     * @return Query
     */
    private function addInstitutionStaffToQuery(Query $query)
    {
        $InstitutionStaffTable = TableRegistry::get('Institution.Staff');
        $query
            ->innerJoin([$InstitutionStaffTable->alias() => $InstitutionStaffTable->table()], [
                $InstitutionStaffTable->aliasField('staff_id = ') . $this->aliasField('security_user_id')
            ]);
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function addInstitutionToQuery(Query $query)
    {
        $InstitutionStaffTable = TableRegistry::get('Institution.Staff');
        $InstitutionsTable = TableRegistry::get('Institution.Institutions');
        $query
            ->innerJoin([$InstitutionsTable->alias() => $InstitutionsTable->table()], [
                $InstitutionStaffTable->aliasField('institution_id = ') . $InstitutionsTable->aliasField('id')]);
        $query->group([$this->aliasField('id')]);
        return $query;
    }

    /**
     * @param Query $query
     * @return Query
     */
    private function addGroupingToQuery(Query $query)
    {

        $query->group([$this->aliasField('id')]);
        return $query;

    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        //redeclare fields for sorting purpose.
        $extraField[] = [
            'key' => '',
            'field' => 'staff_openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];

        $extraField[] = [
            'key' => '',
            'field' => 'staff_name',
            'type' => 'string',
            'label' => __('Staff')
        ];

        $extraField[] = [
            'key' => '',
            'field' => 'workflow_step',
            'type' => 'string',
            'label' => __('Status')
        ];

        $extraField[] = [
            'key' => '',
            'field' => 'assignee_name',
            'type' => 'string',
            'label' => __('Assignee')
        ];

        $extraField[] = [
            'key' => '',
            'field' => 'license_number',
            'type' => 'string',
            'label' => __('License Number')
        ];

        $extraField[] = [
            'key' => 'issue_date',
            'field' => 'issue_date',
            'type' => 'date',
            'label' => __('Issue Date')
        ];

        $extraField[] = [
            'key' => 'expiry_date',
            'field' => 'expiry_date',
            'type' => 'date',
            'label' => __('Expiry')
        ];

        $extraField[] = [
            'key' => 'issuer',
            'field' => 'issuer',
            'type' => 'string',
            'label' => __('Issuer')
        ];

        $extraField[] = [
            'key' => 'comments',
            'field' => 'comments',
            'type' => 'string',
            'label' => __('Comments')
        ];

        $extraField[] = [
            'key' => '',
            'field' => 'license_type',
            'type' => 'string',
            'label' => __('License Type')
        ];

         $extraField[] = [
            'key' => '',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $extraField[] = [
            'key' => '',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $fields->exchangeArray($extraField);
    }

}
