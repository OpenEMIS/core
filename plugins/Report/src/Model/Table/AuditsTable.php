<?php
namespace Report\Model\Table;

use ArrayObject;
use DateTime;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\I18n\Time;
use Cake\Validation\Validator;

use Directory\Model\Table\DirectoriesTable as UserTypeOption;

class AuditsTable extends AppTable
{ 
    public function initialize(array $config): void
    {
        $this->setTable('security_users');
        parent::initialize($config);
        $this->addBehavior('Report.ReportList');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $validator
            ->add('report_start_date', [
                'ruleCompareDate' => [
                    'rule' => ['compareDate', 'report_end_date', true],
                    'on' => function ($context) {
                        if (array_key_exists('feature', $context['data'])) {
                            $feature = $context['data']['feature'];
                            return in_array($feature, [
                                'Report.AuditLogins',
                                'Report.AuditLastLogins',
                                'Report.AuditInstitutions',
                                'Report.AuditUsers',
                                'Report.AuditSecuritiesRolesPermissions', // POCOR-499
                                'Report.AuditSecuritiesGroupUserRoles' // POCOR-499
                            ]);         
                        }
                        return true;
                    }
                ],
            ]);

        return $validator;
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
        $this->ControllerAction->field('user_type', ['type' => 'hidden']);
        $this->ControllerAction->field('report_start_date', ['type' => 'hidden']);
        $this->ControllerAction->field('report_end_date', ['type' => 'hidden']);
        $this->ControllerAction->field('sort_by', ['type' => 'hidden']);
    }
    //POCOR-6637::START
    public function addAfterAction(Event $event, Entity $entity)
    {
        if ($entity->has('feature')) {
            $feature = $entity->feature;
            $fieldsOrder = ['feature'];
            switch ($feature) { 
                case 'Report.AuditLogins':
                    $fieldsOrder[] = 'report_start_date';
                    $fieldsOrder[] = 'report_end_date';
                    $fieldsOrder[] = 'sort_by';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.AuditLastLogins':
                    $fieldsOrder[] = 'report_start_date';
                    $fieldsOrder[] = 'report_end_date';
                    $fieldsOrder[] = 'sort_by';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.AuditInstitutions':
                    $fieldsOrder[] = 'report_start_date';
                    $fieldsOrder[] = 'report_end_date';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.AuditUsers': 
                    $fieldsOrder[] = 'user_type';
                    $fieldsOrder[] = 'report_start_date';
                    $fieldsOrder[] = 'report_end_date';
                    $fieldsOrder[] = 'format';
                    break;
                // Start POCOR-499
                case 'Report.AuditSecuritiesRolesPermissions': 
                    // $fieldsOrder[] = 'user_type';
                    $fieldsOrder[] = 'report_start_date';
                    $fieldsOrder[] = 'report_end_date';
                    $fieldsOrder[] = 'format';
                    break;
                case 'Report.AuditSecuritiesGroupUserRoles': 
                    // $fieldsOrder[] = 'user_type';
                    $fieldsOrder[] = 'report_start_date';
                    $fieldsOrder[] = 'report_end_date';
                    $fieldsOrder[] = 'format';
                    break;

                // End POCOR-499
                default:
                    break;
            }
            $this->ControllerAction->setFieldOrder($fieldsOrder);
        }else{
            $fieldsOrder = ['feature'];
            $fieldsOrder[] = 'report_start_date';
            $fieldsOrder[] = 'report_end_date';
            $fieldsOrder[] = 'sort_by';
            $fieldsOrder[] = 'format';
            $this->ControllerAction->setFieldOrder($fieldsOrder);
        }
    }
    //POCOR-6637::END
    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $options)
    {
        $this->checkForDateFields($requestData);
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->controller->getFeatureOptions($this->getAlias());
            $attr['onChangeReload'] = true;
            if (!(isset($this->request->getData($this->getAlias())['feature']))) {
                $option = $attr['options'];
                reset($option);
                $defaultFeatureValue = key($option);
                $this->request = $this->request->withData($this->getAlias() . '.feature', $defaultFeatureValue);
            }
            return $attr;
        }
    }

    public function onUpdateFieldUserType(Event $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array($feature, ['Report.AuditUsers'])) {
                $userTypeOptions = [
                    UserTypeOption::ALL => __('All Type'),
                    UserTypeOption::STUDENT => __('Student'),
                    UserTypeOption::STAFF => __('Staff'),
                    UserTypeOption::GUARDIAN => __('Guardian')
                ];
        

                $attr['options'] = $userTypeOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;
            }
            return $attr;
        }
    }

    public function onUpdateFieldSortBy(Event $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array($feature, [
                'Report.AuditLogins', 'Report.AuditLastLogins'
            ])) {

                $userSortByOptions = [
                    "DefaultSort" => __("Default Order"),
                    "LastLoginDESC" => __('Last Login - Descending Order'),
                    "LastLoginASC" => __('Last Login - Ascending Order')
                ];

                $attr['options'] = $userSortByOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;
            }

            return $attr;
        }
    }

    public function onUpdateFieldReportStartDate(Event $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            // Start POCOR-499
            if (in_array($feature, ['Report.AuditSecuritiesRolesPermissions', 'Report.AuditSecuritiesGroupUserRoles', 'Report.AuditUsers', 'Report.AuditLogins','Report.AuditLastLogins', 'Report.AuditInstitutions'])) {
                $attr['type'] = 'date';
            }
            return $attr;
        }
    }

    public function onUpdateFieldReportEndDate(Event $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            // Start POCOR-499
            if (in_array($feature, ['Report.AuditSecuritiesRolesPermissions', 'Report.AuditSecuritiesGroupUserRoles','Report.AuditUsers', 'Report.AuditLogins','Report.AuditLastLogins', 'Report.AuditInstitutions'])) {
                $attr['type'] = 'date';
                $attr['value'] = Time::now();
            }
            return $attr;
        }
    }

    private function checkForDateFields(ArrayObject $requestData)
    {
        if (array_key_exists("report_start_date",$requestData[$this->getAlias()]) && !empty($requestData[$this->getAlias()]['report_start_date'])) {
            $requestData[$this->getAlias()]['report_start_date'] = $requestData[$this->getAlias()]['report_start_date'].' 00:00:00';
        }

        if (array_key_exists("report_end_date",$requestData[$this->getAlias()]) && !empty($requestData[$this->getAlias()]['report_end_date'])) {
            $requestData[$this->getAlias()]['report_end_date'] = $requestData[$this->getAlias()]['report_end_date'].' 23:59:59';
        }
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'feature':
                return __('Feature');
            case 'format':
                return __('Format');
            case 'academic_period_id':
                return __('Academic Period');
            case 'report_start_date':
                return __('Start Date');
            case 'report_end_date':
                return __('End Date');
            case 'sort_by':
                return __('Sort by');
            case 'user_type':
                return __('User Type');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
