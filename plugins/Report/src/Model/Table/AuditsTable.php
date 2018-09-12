<?php
namespace Report\Model\Table;

use ArrayObject;
use DateTime;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\I18n\Time;
use Cake\Validation\Validator;

use Directory\Model\Table\DirectoriesTable as UserTypeOption;

class AuditsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('security_users');
        parent::initialize($config);
        $this->addBehavior('Report.ReportList');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->add('report_start_date', [
                'ruleCompareDate' => [
                    'rule' => ['compareDate', 'report_end_date', true],
                    'on' => function ($context) {
                        if (array_key_exists('feature', $context['data'])) {
                            $feature = $context['data']['feature'];
                            return in_array($feature, [
                                'Report.AuditLogins',
                                'Report.AuditInstitutions',
                                'Report.AuditUsers'
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

    public function addBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $options)
    {
        $this->checkForDateFields($requestData);
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->controller->getFeatureOptions($this->alias());
            $attr['onChangeReload'] = true;
            if (!(isset($this->request->data[$this->alias()]['feature']))) {
                $option = $attr['options'];
                reset($option);
                $this->request->data[$this->alias()]['feature'] = key($option);
            }
            return $attr;
        }
    }

    public function onUpdateFieldUserType(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
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

    public function onUpdateFieldSortBy(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, [
                'Report.AuditLogins'
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

    public function onUpdateFieldReportStartDate(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.AuditUsers', 'Report.AuditLogins', 'Report.AuditInstitutions'])) {
                $attr['type'] = 'date';
            }
            return $attr;
        }
    }

    public function onUpdateFieldReportEndDate(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.AuditUsers', 'Report.AuditLogins', 'Report.AuditInstitutions'])) {
                $attr['type'] = 'date';
                $attr['value'] = Time::now();
            }
            return $attr;
        }
    }

    private function checkForDateFields(ArrayObject $requestData)
    {
        if (array_key_exists("report_start_date",$requestData[$this->alias()]) && !empty($requestData[$this->alias()]['report_start_date'])) {
            $requestData[$this->alias()]['report_start_date'] = $requestData[$this->alias()]['report_start_date'].' 00:00:00';
        }

        if (array_key_exists("report_end_date",$requestData[$this->alias()]) && !empty($requestData[$this->alias()]['report_end_date'])) {
            $requestData[$this->alias()]['report_end_date'] = $requestData[$this->alias()]['report_end_date'].' 23:59:59';
        }
    }
}
