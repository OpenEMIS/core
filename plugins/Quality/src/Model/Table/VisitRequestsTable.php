<?php
namespace Quality\Model\Table;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Datasource\ResultSetInterface;
use Cake\Chronos\Date;
use Cake\Chronos\Chronos;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class VisitRequestsTable extends ControllerActionTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    public function initialize(array $config)
    {
        $this->table('institution_visit_requests');
        parent::initialize($config);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('QualityVisitTypes', ['className' => 'FieldOption.QualityVisitTypes', 'foreignKey' => 'quality_visit_type_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

        $this->addBehavior('ControllerAction.FileUpload', [
            // 'name' => 'file_name',
            // 'content' => 'file_content',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);
        $this->addBehavior('Quality.Visit');
        $this->addBehavior('Workflow.Workflow');
        // setting this up to be overridden in viewAfterAction(), this code is required
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            true
        );
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('date_of_visit', 'ruleDateWithinAcademicPeriod', [
                'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                'provider' => 'table',
            ])
            ->allowEmpty('file_content');
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('comment', ['visible' => false]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);

        $this->setFieldOrder(['academic_period_id', 'date_of_visit', 'quality_visit_type_id']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // determine if download button is shown
        $showFunc = function () use ($entity) {
            $filename = $entity->file_content;
            return !empty($filename);
        };
        $this->behaviors()->get('ControllerAction')->config(
            'actions.download.show',
            $showFunc
        );
        // End

        $this->setupFields($entity, $extra);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity, $extra);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];

            $academicPeriodOptions = $this->AcademicPeriods->getYearList(['withLevels' => true, 'isEditable' => true]);
            if ($entity->has('academic_period_id')) {
                $academicPeriodId = $entity->academic_period_id;
            } else {
                if (is_null($request->query('academic_period_id'))) {
                    $academicPeriodId = $this->AcademicPeriods->getCurrent();
                } else {
                    $academicPeriodId = $request->query('academic_period_id');
                }
                $entity->academic_period_id = $academicPeriodId;
            }

            $attr['select'] = false;
            $attr['options'] = $academicPeriodOptions;
            $attr['value'] = $academicPeriodId;
            $attr['attr']['value'] = $academicPeriodId;
            $attr['onChangeReload'] = 'changeAcademicPeriod';
        }

        return $attr;
    }

    public function onUpdateFieldDateOfVisit(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
            if ($entity->has('academic_period_id')) {
                $academicPeriodObj = $this->AcademicPeriods->get($entity->academic_period_id);

                $attr['date_options']['startDate'] = $academicPeriodObj->start_date->format('d-m-Y');
                $attr['date_options']['endDate'] = $academicPeriodObj->end_date->format('d-m-Y');
            }
        }

        return $attr;
    }

    public function addEditOnChangeAcademicPeriod(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        unset($request->query['academic_period_id']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('academic_period_id', $request->data[$this->alias()])) {
                    $request->query['academic_period_id'] = $request->data[$this->alias()]['academic_period_id'];
                }
            }
        }
    }

    public function setupFields(Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['type' => 'select', 'entity' => $entity]);
        $this->field('date_of_visit', ['entity' => $entity]);
        $this->field('quality_visit_type_id', ['type' => 'select']);
        $this->field('file_name', ['type' => 'hidden']);
        $this->field('file_content', ['visible' => ['view' => false, 'edit' => true]]);

        $this->setFieldOrder(['academic_period_id', 'date_of_visit', 'quality_visit_type_id', 'comment', 'file_name', 'file_content']);
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
                $this->aliasField('id'),
                $this->aliasField('status_id'),
                $this->aliasField('date_of_visit'),
                $this->aliasField('academic_period_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
                $this->AcademicPeriods->aliasField('name'),
                $this->QualityVisitTypes->aliasField('name'),
                $this->Institutions->aliasField('code'),
                $this->Institutions->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->AcademicPeriods->alias(), $this->QualityVisitTypes->alias(), $this->Institutions->alias(), $this->CreatedUser->alias()])
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->where([$this->aliasField('assignee_id') => $userId])
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                    $url = [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'VisitRequests',
                        'view',
                        $this->paramsEncode(['id' => $row->id]),
                        'institution_id' => $row->institution_id
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    $row['request_title'] = sprintf(__('%s in %s on %s'), $row->quality_visit_type->name, $row->academic_period->name, $this->formatDate($row->date_of_visit));
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }
}
