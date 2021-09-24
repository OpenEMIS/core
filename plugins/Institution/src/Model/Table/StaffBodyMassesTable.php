<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;
use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;

class StaffBodyMassesTable extends ControllerActionTable
{
    use OptionsTrait;
    const COMMENT_MAX_LENGTH = 350;
    const POWER = 2;
    public function initialize(array $config)
    {
        $this->table('user_body_masses');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods',   ['className' => 'AcademicPeriod.AcademicPeriods', 'foreign_key' => 'academic_period_id']);

        /* $this->addBehavior('Page.FileUpload', [
            'fieldMap' => ['file_name' => 'file_content'],
            'size' => '2MB'
        ]); */

        $this->addBehavior('Health.Health');

        $this->toggle('search', false);

        $this->addBehavior('Excel',[
            'excludes' => ['comment, security_user_id'],
            'pages' => ['index'],
        ]);
    }

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
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $modelAlias = 'StaffBodyMasses';
        $userType = '';
        $this->controller->changeHealthHeader($this, $modelAlias, $userType);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id', ['attr' => ['label' => __('Academic Period')]]);
        $this->field('comment',['visible' => false]);
        $this->field('security_user_id',['visible' => false]);
        /*POCOR-6307 Starts*/
        $this->field('file_name',['visible' => false]);
        $this->field('file_content',['visible' => false]);
        /*POCOR-6307 Ends*/
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        $staffUserId = $session->read('Institution.StaffUser.primaryKey.id');
        $query->where([$this->aliasField('security_user_id') => $staffUserId])
        ->orderDesc($this->aliasField('id'));
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $extra, Query $query){
        $session = $this->request->session();
        $staffUserId = $session->read('Institution.StaffUser.primaryKey.id');
        $query->where([$this->aliasField('security_user_id') => $staffUserId])
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
