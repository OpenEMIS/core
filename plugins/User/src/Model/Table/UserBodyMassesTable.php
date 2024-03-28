<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\I18n\Date;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Validation\Validator;

use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;

class UserBodyMassesTable extends ControllerActionTable
{
    const POWER = 2;
    public function initialize(array $config)
    {
        $this->table('user_body_masses');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        
        $this->addBehavior('Health.Health');

        $this->toggle('search', false);

        $this->addBehavior('Excel',[
            'excludes' => ['comment, security_user_id'],
            'pages' => ['index'],
        ]);
        //POCOR-6255 start
        // $this->addBehavior('Page.FileUpload', [
        //     'fieldMap' => ['file_name' => 'file_content'],
        //     'size' => '2MB'
        // ]);//POCOR-6255 end
    }
    //POCOR-6255 start
    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Restful.Model.isAuthorized'] = ['callable' => 'isAuthorized', 'priority' => 1];
        return $events;
    }

    public function isAuthorized(Event $event, $scope, $action, $extra)
    {
        if ($action == 'download' || $action == 'image') {
            // check for the user permission to download here
            $event->stopPropagation();
            return true;
        }
    }//POCOR-6255 end

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('height', [
                'notZero' => [
                    'rule' => ['comparison', '>', 0],
                    'last' => true
                ],
                'validHeight' => [
                    'rule' => ['range', 0, 300],
                    'last' => true
                ],
                'validateDecimal' => [
                    'rule' => ['decimal', null, '/^[0-9]+(\.[0-9]{1,2})?$/'],
                ],
            ])
            ->add('height', 'ruleValidateCustomMinimumHeight', [
                'rule' => ['validateCustomMinimumHeight', 'StudentMinimumHeight'],
                'provider' => 'table',
                'last' => true
            ])
            ->add('height', 'ruleValidateCustomMaximumHeight', [
                'rule' => ['validateCustomMaximumHeight', 'StudentMaximumHeight'],
                'provider' => 'table',
                'last' => true
            ])
            ->add('weight', [
                'notZero' => [
                    'rule' => ['comparison', '>', 0],
                    'last' => true
                ],
                'validWeight' => [
                    'rule' => ['range', 0, 500],
                    'last' => true
                ],
                'validateDecimal' => [
                    'rule' => ['decimal', null, '/^[0-9]+(\.[0-9]{1,2})?$/'],
                ],
            ])
            ->add('weight', 'ruleValidateCustomMinimumWeight', [
                'rule' => ['validateCustomMinimumWeight', 'StudentMinimumWeight'],
                'provider' => 'table',
                'last' => true
            ])
            ->add('weight', 'ruleValidateCustomMaximumWeight', [
                'rule' => ['validateCustomMaximumWeight', 'StudentMaximumWeight'],
                'provider' => 'table',
                'last' => true
            ])
            ->add('date', [
                'ruleUnique' => [
                    'rule' => ['validateUnique', ['scope' => ['security_user_id', 'date']]],
                    'provider' => 'table'
                ],
                'dateWithinPeriod' => [
                    'rule' => function ($value, $context) {
                        $inputDate = new Date ($value);

                        if (!empty($context['data']['academic_period_id'])) {
                            $academicPeriodEntity = $this->AcademicPeriods
                                ->find()
                                ->where([$this->AcademicPeriods->aliasField('id') => $context['data']['academic_period_id']])
                                ->first();

                            if (!is_null($academicPeriodEntity)) {
                                $academicStartDate = $academicPeriodEntity->start_date;
                                $academicEndDate = $academicPeriodEntity->end_date;

                                if ($inputDate >= $academicStartDate && $inputDate <= $academicEndDate) {
                                    return true;
                                } else {
                                    $startDate = date('d-m-Y', strtotime($academicStartDate));
                                    $endDate = date('d-m-Y', strtotime($academicEndDate));

                                    return $this->getMessage('UserBodyMasses.dateNotWithinPeriod', ['sprintf' => [$startDate, $endDate]]);
                                }
                            } else {
                                return __('Invalid academic period');
                            }
                        } else {
                            return true;
                        }
                    },
                ],
            ]);
    }

    public function findIndex(Query $query, array $options)
    {
        if (array_key_exists('sort', $options) && $options['sort'] == 'date') {
            $direction = $options['direction'];
            $query->order([$this->aliasField($options['sort']) => $direction, $this->aliasField('created') => 'desc']);

        }
        return $query;
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if (!empty($data['height']) && !empty($data['weight'])) {
            $height = round($data['height']/100, 2);
            $weight = round($data['weight'], 2);

            $denominator = $height * $height;

            // to prevent the division by 0
            if ($denominator > 0) {
                $bmi = round(($weight / ($denominator)), 2);
            } else {
                $bmi = 0;
            }

            $data['body_mass_index'] = $bmi;
        }
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $modelAlias = 'UserBodyMasses';
        $userType = '';
        $this->controller->changeStudentHealthHeader($this, $modelAlias, $userType);

		// Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Institutions','Student Body Mass','Students - Health');       
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
		// End POCOR-5188


    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['attr' => ['label' => __('Academic Period')]]);
        $this->field('comment',['visible' => false]);
        $this->field('security_user_id',['visible' => false]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['after' => 'comment','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        $studentUserId = $session->read('Institution.StudentUser.primaryKey.id');
        $query->where([$this->aliasField('security_user_id') => $studentUserId])
        ->orderDesc($this->aliasField('id'));
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $extra, Query $query){
        $session = $this->request->session();
        $studentUserId = $session->read('Institution.StudentUser.primaryKey.id');
        $query->where([$this->aliasField('security_user_id') => $studentUserId])
        ->orderDesc($this->aliasField('created'));
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();

        $this->fields['academic_period_id']['type'] = 'select';
        $this->fields['academic_period_id']['options'] = $academicPeriodOptions;
        $this->field('academic_period_id', ['attr' => ['label' => __('Academic Period')]]);

        $this->field('date');

        $this->fields['height']['type'] = 'integer';
        $this->field('height', ['attr' => ['label' => __('Height') . $this->tooltipMessage(__('Within 0 to 300 centimetres'))]]);

        $this->fields['weight']['type'] = 'integer';
        $this->field('weight', ['attr' => ['label' => __('Weight') . $this->tooltipMessage(__('Within 0 to 500 kilograms'))]]);

        $this->field('body_mass_index', ['visible' => false]);
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['after' => 'comment','attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
    }

    protected function tooltipMessage($message)
    {
        $tooltipMessage = '&nbsp&nbsp;<i class="fa fa-info-circle fa-lg table-tooltip icon-blue" data-placement="right" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="' . $message . '"></i>';
        return $tooltipMessage;
    }

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
        $weight =  $entity['weight'];
        //convert height centimeter to meter
        $height =  ($entity['height'] / 100);
        //get power of the height
        $height = pow($height, self::POWER);

        $body_mass_index = ($weight / $height);
        $entity['body_mass_index'] = $body_mass_index;
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $extraField[] = [
            'key'   => 'academic_period_id',
            'field' => 'academic_period_id',
            'type'  => 'string',
            'label' => __('Academic Period')
        ];

        $extraField[] = [
            'key'   => 'date',
            'field' => 'date',
            'type'  => 'date',
            'label' => __('Date')
        ];

        $extraField[] = [
            'key'   => 'height',
            'field' => 'height',
            'type'  => 'string',
            'label' => __('Height')
        ];

        $extraField[] = [
            'key'   => 'weight',
            'field' => 'weight',
            'type'  => 'string',
            'label' => __('Weight')
        ];

        $extraField[] = [
            'key'   => 'body_mass_index',
            'field' => 'body_mass_index',
            'type'  => 'integer',
            'label' => __('Body Mass Index
            ')
        ];

        $fields->exchangeArray($extraField);
    }
}
