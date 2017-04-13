<?php
namespace Staff\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;

class StaffTrainingsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffTrainingCategories', ['className' => 'Staff.StaffTrainingCategories', 'foreignKey' => 'staff_training_category_id']);
        $this->belongsTo('TrainingFieldStudies', ['className' => 'Training.TrainingFieldStudies', 'foreignKey' => 'training_field_of_study_id']);

        // for file upload
        $this->addBehavior('ControllerAction.FileUpload', [
            // 'name' => 'file_name',
            // 'content' => 'file_content',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);

        // setting this up to be overridden in viewAfterAction(), this code is required for file download
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            true
        );
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->requirePresence('staff_training_category_id')
            ->add('credit_hours', [
                'ruleRange' => [
                    'rule' => ['range', 0, 99]
                ]
            ])
            ->allowEmpty('file_content')
        ;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'training_field_of_study_id') {
            return __('Field of Study');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('description', ['visible' => false]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // determine if download button is shown
        $showFunc = function() use ($entity) {
            $filename = $entity->file_content;
            return !empty($filename);
        };
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            $showFunc
        );
        // End

        $this->setupFields($entity);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    private function setupTabElements()
    {
        $tabElements = $this->controller->getInstitutionTrainingTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Trainings');
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    public function onGetTrainingFieldOfStudyId(Event $event, Entity $entity)
    {
        if ($entity->training_field_of_study_id == 0) {
            return __('None');
        }
    }

    public function setupFields(Entity $entity)
    {
        $this->field('code');
        $this->field('name');
        $this->field('description');
        $this->field('staff_training_category_id', ['type' => 'select']);
        $this->field('training_field_of_study_id', ['type' => 'select']);
        $this->field('credit_hours', ['attr' => ['min' => 0, 'max' => 99]]);
        $this->field('completed_date');

        // Attachment field
        $this->field('file_name', [
            'type' => 'hidden',
            'visible' => ['view' => false, 'edit' => true]
        ]);
        $this->field('file_content', [
            'visible' => ['view' => false, 'edit' => true],
            'attr' => ['label' => __('Attachment')]
        ]);
    }

    public function getModelAlertData($threshold)
    {
        $thresholdArray = json_decode($threshold, true);
        $Licenses = TableRegistry::get('Staff.Licenses');
        $data = [];

        $conditions = [
            1 => ('DATEDIFF(' . $Licenses->aliasField('expiry_date') . ', NOW())' . ' BETWEEN 0 AND ' . $thresholdArray['value']), // before
        ];

        // get the license data for $vars
        $licensesRecords = $Licenses->find()
            ->select([
                'id',
                'license_number',
                'issue_date',
                'expiry_date',
                'issuer',
                'LicenseTypes.name',
                'Users.id',
                'Users.openemis_no',
                'Users.first_name',
                'Users.middle_name',
                'Users.third_name',
                'Users.last_name',
                'Users.preferred_name',
                'Users.email',
                'Users.address',
                'Users.postal_code',
                'Users.date_of_birth',
            ])
            ->contain(['Users', 'LicenseTypes'])
            ->where([
                $Licenses->aliasField('license_type_id') => $thresholdArray['license_type'],
                $Licenses->aliasField('expiry_date') . ' IS NOT NULL',
                $conditions[$thresholdArray['condition']]
            ])
            ->hydrate(false)
            ;

        // get the records of staff training within licence period
        if (!empty($licensesRecords)) {
            foreach ($licensesRecords as $record) {
                $licenseId= $record['id'];
                $licenseIssueDate = $record['issue_date'];
                $licenseExpiryDate = $record['expiry_date'];
                $staffId = $record['user']['id'];

                // get the total credit hours of all the staff training within license validity
                $trainingRecords = $this->find()
                    ->select([
                        'total_credit_hours' => $this->find()
                            ->func()->sum($this->aliasField('credit_hours')),
                    ])
                    ->contain(['StaffTrainingCategories', 'TrainingFieldStudies'])
                    ->where([
                        $this->aliasField('staff_id') => $staffId,
                        $this->aliasField('completed_date') . ' >= ' => $licenseIssueDate,
                        $this->aliasField('completed_date') . ' <= ' => $licenseExpiryDate,
                        $this->aliasField('staff_training_category_id') . ' IN ' => $thresholdArray['training_categories'],
                    ])
                    ->having(['total_credit_hours' . ' < ' => $thresholdArray['hour']])
                    ->first()
                    ;

                // any license not fullfilled the condition above will trigger the alert.
                if (!empty($trainingRecords)) {
                    $data[$licenseId] = $record;
                    $data[$licenseId]['total_credit_hours'] = $trainingRecords['total_credit_hours'];
                }
            }

            return $data;
        }
    }
}
