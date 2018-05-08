<?php
namespace Scholarship\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Network\Request;
use Cake\Controller\Component;
use Cake\Validation\Validator;
use Cake\Utility\Security;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\ResultSet;
use Cake\Utility\Inflector;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class ScholarshipApplicationsTable extends ControllerActionTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    private $applicantName = null;

    private $workflowEvents = [
        [
            'value' => 'Workflow.onApprove',
            'text' => 'Approval of Scholaship Application',
            'description' => 'Performing this action will create an identical record in the ScholarshipRecipientTable.',
            'method' => 'OnApprove'
        ]
    ];

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Applicants', ['className' => 'User.Users', 'foreignKey' => 'applicant_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->hasMany('InstitutionChoices', [
            'className' => 'Scholarship.InstitutionChoices',
            'foreignKey' => ['applicant_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('ApplicationAttachments', [
            'className' => 'Scholarship.ApplicationAttachments',
            'foreignKey' => ['applicant_id', 'scholarship_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('OpenEmis.Section');
        $this->addBehavior('Workflow.Workflow');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator;
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        return $events;
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Applicants.Genders']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        if (isset($extra['toolbarButtons']['add']['url'])) {
            $extra['toolbarButtons']['add']['url']['controller'] = 'ScholarshipApplicationDirectories';
            $extra['toolbarButtons']['add']['url']['action'] = 'index';
            $extra['toolbarButtons']['add']['attr']['title'] = __('Apply');
            unset($extra['toolbarButtons']['add']['url'][0]);
        }

        $this->setupFields();
    }

    public function addAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->controller->set('contentHeader', __('Scholarship') . ' - ' .__('Scholarship Applications'));
        $query = $this->ControllerAction->getQueryString();

        if (isset($extra['toolbarButtons']['back']['url'])) {
            $extra['toolbarButtons']['back']['url']['controller'] = 'ScholarshipApplicationDirectories';
            $extra['toolbarButtons']['back']['url']['action'] = 'index';
            unset($extra['toolbarButtons']['back']['url'][0]);
            unset($extra['toolbarButtons']['back']['url']['queryString']);
        }

        if ($query) {
            $applicantId = $query['applicant_id'];

            $applicantEntity = $this->Applicants->get($applicantId, [
                'contain' => ['Genders']
            ]);

            if (!empty($applicantEntity)) {
                $this->setupFields($applicantEntity);
            } else {
                $this->Alert->error('general.notExists', ['reset' => 'override']);
                $url = $this->url('index');
                $event->stopPropagation();
                return $this->controller->redirect($url);
            }
        } else {
            $url = $this->url('index');
            $event->stopPropagation();
            return $this->controller->redirect($url);
        }
    }


    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'Scholarships.AcademicPeriods',
            'Scholarships.FinancialAssistanceTypes'
        ]);
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        // Set the header of the page
        $this->controller->set('contentHeader', $this->applicantName. ' - ' .__('Overview'));

        $tabElements = $this->controller->getScholarshipTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());

        $this->setupFields();
    }


    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        if ($this->action == 'index') {
            return $entity->applicant->openemis_no;
        }
    }

    public function onGetDateOfBirth(Event $event, Entity $entity)
    {
        if ($this->action == 'index') {
            return $this->formatDate($entity->applicant->date_of_birth);
        }
    }

    public function onGetGenderId(Event $event, Entity $entity)
    {
        if ($this->action == 'index') {
            return $entity->applicant->gender->name;
        }
    }

    public function onGetIdentityType(Event $event, Entity $entity)
    {
        if ($this->action == 'index') {
            return $entity->applicant->identity_type_id;
        }
    }

    public function onGetIdentityNumber(Event $event, Entity $entity)
    {
        if ($this->action == 'index') {
            return $entity->applicant->identity_number;
        }
    }

    public function onGetAcademicPeriodId(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->scholarship->academic_period->name;
        }
    }

    public function onGetCode(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->scholarship->code;
        }
    }

    public function onGetScholarshipId(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->scholarship->name;
        }
    }

    public function onGetFinancialAssistanceTypeId(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->scholarship->financial_assistance_type->name;
        }
    }

    public function onGetDescription(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
          return $entity->scholarship->description;
        }
    }

    public function onGetMaxAwardAmount(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->scholarship->max_award_amount;
        }
    }

    public function onGetBond(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->scholarship->bond.' Years';
        }
    }

    public function onGetRequirement(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
          return $entity->scholarship->requirement;
        }
    }

    public function onGetInstruction(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return $entity->scholarship->instruction;
        }
    }

    public function onUpdateFieldOpenemisNo(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'disabled';
            $attr['attr']['value'] = $attr['entity']->openemis_no;
        }

        return $attr;
    }

    public function onUpdateFieldApplicantId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $attr['attr']['value'] = $attr['entity']->name;
            $attr['value'] = $attr['entity']->id;
        }

        return $attr;
    }

    public function onUpdateFieldDateOfBirth(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'disabled';
            $attr['attr']['value'] = $this->formatDate($attr['entity']->date_of_birth);
        }

        return $attr;
    }

    public function onUpdateFieldGenderId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'disabled';
            $attr['attr']['value'] = $attr['entity']->gender->name;
        }

        return $attr;
    }

    public function onUpdateFieldIdentityTypeId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'disabled';
            $attr['attr']['value'] = $attr['entity']->identity_type_id;
        }

        return $attr;
    }

    public function onUpdateFieldIdentityNumber(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'disabled';
            $attr['attr']['value'] = $attr['entity']->identity_number;
        }

        return $attr;
    }

    public function onUpdateFieldFinancialAssistanceTypeId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $financialAssistanceTypeOptions = TableRegistry::get('Scholarship.FinancialAssistanceTypes')->getList()->toArray();

            $attr['type'] = 'select';
            $attr['options'] = $financialAssistanceTypeOptions;
            $attr['onChangeReload'] = 'changeFinancialAssistanceTypeId';

        }

        return $attr;
    }

    public function addOnChangeFinancialAssistanceTypeId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (array_key_exists($this->alias(), $data)) {
            if (array_key_exists('scholarship_id', $data[$this->alias()])) {
                unset($data[$this->alias()]['scholarship_id']);
            }
        }
    }

    public function onUpdateFieldScholarshipId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $scholarshipOptions = [];

            if (!empty($request->data[$this->alias()]['financial_assistance_type_id']) && !empty($request->data[$this->alias()]['applicant_id'])) {

                $financialAssistanceTypeOptions = TableRegistry::get('Scholarship.FinancialAssistanceTypes')->getList()->toArray();
                $applicantId = $request->data[$this->alias()]['applicant_id'];
                $financialTypeId =  $request->data[$this->alias()]['financial_assistance_type_id'];

                $options = [
                    'applicant_id' => $applicantId,
                    'financial_type_id' => $financialTypeId
                ];

                $scholarshipOptions = $this->Scholarships->getAvailableScholarships($options);

                if (!empty($request->data[$this->alias()]['scholarship_id'])) {
                     switch ($financialAssistanceTypeOptions[$financialTypeId]) {
                        case 'Scholarship':
                            // No implementation
                            break;
                        case 'Loan':
                            $this->field('requested_amount', [
                                'visible' => true
                            ]);
                            $this->field('interest_rate', [
                                'type' => 'disabled',
                                'attr' => ['label' => __('Interest rate %')]
                            ]);
                            $this->field('interest_rate_type', [
                                'type' => 'disabled',
                                'attr' => ['label' => __('Interest Rate Type')]
                            ]);
                            $this->field('payment_frequency_id', [
                                'type' => 'disabled',
                                'attr' => ['label' => __('Payment Frequency')]
                            ]);
                            $this->field('loan_term', [
                                'type' => 'disabled',
                                'attr' => ['label' => __('Loan Term')]
                            ]);
                            break;
                        case 'Grant':
                            // No implementation
                            break;
                        case 'Workstudy':
                            // No implementation
                            break;
                    }
                }
            }

            $attr['type'] = 'select';
            $attr['onChangeReload'] = true;
            $attr['options'] = $scholarshipOptions;
        }
        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'disabled';

            if (!empty($request->data[$this->alias()]['scholarship_id'])) {

                $scholarshipId = $request->data[$this->alias()]['scholarship_id'];
                $scholarshipEntity = $this->Scholarships->get($scholarshipId, [
                    'contain' => ['AcademicPeriods']
                ]);

                $attr['attr']['value'] = $scholarshipEntity->academic_period->name;
            }
        }
        return $attr;
    }

    public function onUpdateFieldDescription(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'disabled';

            if (!empty($request->data[$this->alias()]['scholarship_id'])) {

                $scholarshipId = $request->data[$this->alias()]['scholarship_id'];
                $scholarshipEntity = $this->Scholarships->get($scholarshipId);

                $attr['attr']['value'] = $scholarshipEntity->description;
            }
        }
        return $attr;
    }

    public function onUpdateFieldMaxAwardAmount(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'disabled';

            if (!empty($request->data[$this->alias()]['scholarship_id'])) {

                $scholarshipId = $request->data[$this->alias()]['scholarship_id'];
                $scholarshipEntity = $this->Scholarships->get($scholarshipId);

                $attr['attr']['value'] = $scholarshipEntity->max_award_amount;
            }
        }
        return $attr;
    }

    public function onUpdateFieldBond(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
             $attr['type'] = 'disabled';

            if (!empty($request->data[$this->alias()]['scholarship_id'])) {

                $scholarshipId = $request->data[$this->alias()]['scholarship_id'];
                $scholarshipEntity = $this->Scholarships->get($scholarshipId);

                $attr['attr']['value'] = $scholarshipEntity->bond . 'years';
            }
        }
        return $attr;
    }

    public function onUpdateFieldRequirement(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'disabled';

            if (!empty($request->data[$this->alias()]['scholarship_id'])) {

                $scholarshipId = $request->data[$this->alias()]['scholarship_id'];
                $scholarshipEntity = $this->Scholarships->get($scholarshipId);

                $attr['attr']['value'] = $scholarshipEntity->requirement;
            }
        }
        return $attr;
    }

    public function onUpdateFieldInterestRate(Event $event, array $attr, $action, $request)
    {

        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['scholarship_id'])) {
                $scholarshipId = $request->data[$this->alias()]['scholarship_id'];
                $attr['attr']['value'] = 'TBC';
           }
        }

        return $attr;
    }
    public function onUpdateFieldInterestRateType(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['scholarship_id'])) {
                $scholarshipId = $request->data[$this->alias()]['scholarship_id'];
                $attr['attr']['value'] = 'TBC';
            }
        }

        return $attr;
    }
    public function onUpdateFieldPaymentFrequencyId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['scholarship_id'])) {
                $scholarshipId = $request->data[$this->alias()]['scholarship_id'];
                $attr['attr']['value'] = 'TBC';
            }
        }

        return $attr;
    }

    public function onUpdateFieldloanTerm(Event $event, array $attr, $action, $request)
    {
        if ($action == 'add') {
            if (!empty($request->data[$this->alias()]['scholarship_id'])) {

                $scholarshipId = $request->data[$this->alias()]['scholarship_id'];
                $attr['attr']['value'] = 'TBC';
            }
        }

        return $attr;
    }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        if ($this->action == 'add') {
            $applicantId = $this->ControllerAction->getQueryString('applicant_id');
            $applicantName = $this->Applicants->get($applicantId)->name;
            $Navigation->removeCrumb('Overview');
            $Navigation->removeCrumb($applicantName);
            $Navigation->substituteCrumb('Applicants', 'Single Application');
        }
    }

    public function setupFields($entity = null)
    {
        $this->field('requested_amount', ['visible' => false]);
        $this->field('assignee_id', ['visible' => false]);

        if(in_array($this->action, ['index', 'add'])) {
            $this->field('applicant_id',['type' => 'readonly', 'entity' => $entity]);
            $this->field('openemis_no',['entity' => $entity]);
            $this->field('date_of_birth',['entity' => $entity]);
            $this->field('gender_id',['entity' => $entity]);
            $this->field('identity_type_id',['entity' => $entity]);
            $this->field('identity_number',['entity' => $entity]);

            $this->setFieldOrder([
                'status_id', 'openemis_no', 'applicant_id', 'date_of_birth', 'gender_id', 'identity_type_id', 'identity_number'
            ]);

            if($this->action == 'add') {
                $this->field('status_id', ['type' => 'hidden']);
                $this->field('scholarship_details_header', ['type' => 'section', 'title' => __('Apply for Scholarship')]);
            }
        }

        if (in_array($this->action, ['view', 'add'])) {

            $this->field('financial_assistance_type_id');
            $this->field('scholarship_id', ['type' => 'select']);
            $this->field('academic_period_id');
            $this->field('description');
            $this->field('max_award_amount');
            $this->field('bond');
            $this->field('requirement');

            if($this->action == 'view') {
                $this->field('code');
                $this->field('instruction');
                $this->setFieldOrder([
                    'academic_period_id', 'status_id', 'code', 'scholarship_id', 'financial_assistance_type_id', 'description', 'max_award_amount', 'bond', 'requirement', 'instruction'
                ]);
            }
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        $params = [
                'applicant_id' => $entity->applicant_id,
                'scholarship_id' => $entity->scholarship_id
            ];

        if (isset($buttons['view']['url'])) {
            $buttons['view']['url'] = $this->ControllerAction->setQueryString($buttons['view']['url'], $params);
        }

        return $buttons;
    }


    public function onApprove(Event $event, $id, Entity $workflowTransitionEntity)
    {
        $ScholarshipRecipient = TableRegistry::get('Institution.ScholarshipRecipient');

        $entity = $this->get($id);

        $recipient = [
            'recipient_id' => $entity->applicant_id,
            'scholarship_id' => $entity->scholarship_id
        ];

        $newEntity = $ScholarshipRecipient->newEntity($recipient);
        $ScholarshipRecipient->save($newEntity);
    }

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $session = $controller->request->session();

        $userId = $session->read('Auth.User.id');
        $Statuses = $this->Statuses;
        $doneStatus = self::DONE;

        $query
            ->select([
                $this->aliasField('applicant_id'),
                $this->aliasField('scholarship_id'),
                $this->aliasField('status_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->CreatedUser->alias()])
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->where([$this->aliasField('assignee_id') => $userId])
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {
                   return $results->map(function ($row) {
                    $url = [
                        'plugin' => 'Scholarship',
                        'controller' => 'ScholarshipApplications',
                        'action' => 'ScholarshipApplications',
                        'view',
                         $this->paramsEncode([
                            'applicant_id' => $row->applicant_id,
                            'scholarship_id' => $row->scholarship_id
                        ]),
                        'queryString' => $this->paramsEncode([
                            'applicant_id' => $row->applicant_id,
                            'scholarship_id' => $row->scholarship_id
                        ])
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }
                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    $row['request_title'] = $row->applicant_id; // TBC
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }

}
