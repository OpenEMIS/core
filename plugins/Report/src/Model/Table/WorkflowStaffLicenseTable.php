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
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
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

        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.WorkflowReport');
        $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false
        ]);
    }

    //POCOR-7637
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query)
    {
        $query = $this->addInstitutionJoinToQuery($query);
    }

    public function onExcelGetOpenemisNo(Event $event, Entity $entity)
    {
//        $this->log('onExcelGetOpenemisNo', 'debug');
//        $this->log($entity, 'debug');
//        $this->log($this->query()->sql(), 'debug');
        $security_user_id = $entity['security_user_id'];
        $user = self::getRelatedRecord('security_users', $security_user_id);
        $entity['security_user'] = $user['first_name'] . ' ' . $user['last_name'];
        return $user['openemis_no'];
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
        $InstitutionStaffTable = TableRegistry::get('Institution.InstitutionStaff');
        $InstitutionsTable = TableRegistry::get('Institution.Institutions');
        $query
            ->leftJoin([$InstitutionStaffTable->alias() => $InstitutionStaffTable->table()], [
                $InstitutionStaffTable->aliasField('staff_id') => $this->aliasField('security_user_id')
            ])->leftJoin([$InstitutionsTable->alias() => $InstitutionsTable->table()], [
                $InstitutionsTable->aliasField('id') => $InstitutionStaffTable->aliasField('institution_id')
            ]);
    }

}
