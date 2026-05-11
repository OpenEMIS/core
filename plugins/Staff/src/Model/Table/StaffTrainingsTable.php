<?php
namespace Staff\Model\Table;

use ArrayObject;

use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Datasource\ConnectionManager;

use App\Model\Table\ControllerActionTable;

class StaffTrainingsTable extends ControllerActionTable
{
    public function initialize(array $config): void
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
        $this->addBehavior('Excel',[
            'excludes' => ['description','file_name','staff_id'],
            'pages' => ['index'],
        ]);
        $this->addBehavior('Institution.InstitutionTab');
    }

    public function validationDefault(Validator $validator): Validator
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

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'training_field_of_study_id') {
            return __('Field of Study');
        }elseif ($field == 'code') {
            return __('Code');
        } elseif ($field == 'name') {
            return __('Name');
        } elseif ($field == 'completed_date') {
            return __('Completed Date');
        } elseif ($field == 'staff_training_category_id') {
            return __('Staff Training Categories');
        } elseif ($field == 'credit_hours') {
            return __('Credit Hours');
        }elseif ($field == 'description') {
            return __('Description');
        }elseif ($field == 'credit_hours') {
            return __('Credit Hours');
        }elseif ($field == 'file_content') {
            return __('Attachment');
        }elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        }else {

            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $staffId = $this->getStaffID();
        if (empty($staffId)) {
            $staffId = $this->request->getSession()->read('Auth.User.id');
        }
        if (!empty($staffId)) {
            $query->where([$this->aliasField('staff_id') => $staffId]);
        }
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('description', ['visible' => false]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('staff_id', ['visible' => false]); //POCOR-9018

        // Start POCOR-5188
        if($this->request->getParam('controller') == 'Staff'){
            $is_manual_exist = $this->getManualUrl('Institutions','Courses','Staff - Training');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }
        }elseif($this->request->getParam('controller') == 'Directories'){
            $is_manual_exist = $this->getManualUrl('Directory','Courses','Staff - Training');
            if(!empty($is_manual_exist)){
                $btnAttr = [
                    'class' => 'btn btn-xs btn-default icon-big',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false,
                    'target'=>'_blank'
                ];

                $helpBtn['url'] = $is_manual_exist['url'];
                $helpBtn['type'] = 'button';
                $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
                $helpBtn['attr'] = $btnAttr;
                $helpBtn['attr']['title'] = __('Help');
                $extra['toolbarButtons']['help'] = $helpBtn;
            }

        }
        // End POCOR-5188
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    private function setupTabElements()
    {
        if ($this->controller->getName() == 'Staff') {
            $tabElements = $this->controller->getInstitutionTrainingTabElements(); // Staff controller
        } else {
            $tabElements = $this->controller->getTrainingTabElements(); // Directories controller
        }
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Courses');
    }

    //POCOR-9018, 9049
    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $connection = ConnectionManager::get('default');
        $connection->execute('SET foreign_key_checks = 0');
        $session = $this->request->getSession();
        $queryString = $this->getQueryString();
        $data['staff_id'] = $queryString['staff_id'];
        if(empty($data['staff_id'])){
            $data['staff_id'] = $session->read('Auth.User.id');
        }
        $this->field('staff_id', ['type' => 'hidden', 'value' => $data['staff_id']]);
        // $this->field('staff_id', ['type' => 'hidden', 'value' => $this->getStaffID()]);
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $data)
    {
        $entity->staff_id = $entity['staff_id'];
    }

    public function afterAction(EventInterface $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    public function onGetTrainingFieldOfStudyId(EventInterface $event, Entity $entity)
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
        $this->field('staff_id', ['type' => 'hidden']); //POCOR-9018

        // Attachment field
        $this->field('file_name', [
            'type' => 'hidden',
            'visible' => ['view' => false, 'edit' => true]
        ]);
        $this->field('file_content', [
            'visible' => ['view' => true, 'edit' => true],
            'attr' => ['label' => __('Attachment')]
        ]);

    }

    public function getModelAlertData($threshold)
    {
        $thresholdArray = json_decode($threshold, true);
        $Licenses = TableRegistry::getTableLocator()->get('Staff.Licenses');
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
            ->disableHydration() // POCOR-8533
            ;

        // get the records of staff training within licence period
        if (!empty($licensesRecords)) {
            foreach ($licensesRecords as $record) {
                $licenseId= $record['id'];
                $licenseIssueDate = $record['issue_date'];
                $licenseExpiryDate = $record['expiry_date'];
                $staffId = $record['user']['id'];

                $query = $this->find()->where([$this->aliasField('staff_id') => $staffId]);

                if ($query->all()->isEmpty()) {
                    // if no training records on the staff id, still add the license to the data list
                    $data[$licenseId] = $record;
                    $data[$licenseId]['total_credit_hours'] = 0;
                } else {
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
                        ->first()
                    ;

                    // have training records but not fall into the category in the alert rule, will be add to the data list
                    $totalCreditHours = !empty($trainingRecords['total_credit_hours']) ? $trainingRecords['total_credit_hours'] : 0;

                    // if the credit hour is less than the hour threshold will add to the data list
                    if ($totalCreditHours < $thresholdArray['hour']) {
                        $data[$licenseId] = $record;
                        $data[$licenseId]['total_credit_hours'] = $totalCreditHours;
                    }
                }
            }

            return $data;
        }
    }

    // POCOR-6137 start
    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $session = $this->request->getSession();
        $staffId = $this->getStaffID();

        $query
        ->where([
            $this->aliasField('staff_id') => $staffId
        ]);
    }





}
