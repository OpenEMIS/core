<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;

class DataQualityTable extends AppTable {
	public function initialize(array $config): void {
		$this->setTable('security_users');
		parent::initialize($config);
		
		$this->belongsTo('Genders', ['className' => 'User.Genders']);
		$this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
		$this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
		
		$this->addBehavior('Excel', [
			'excludes' => ['is_student', 'is_staff', 'is_guardian', 'photo_name', 'super_admin', 'status'],
			'pages' => false
		]);
		$this->addBehavior('Report.ReportList');
	}

	public function beforeAction(Event $event) {
		$controllerName = $this->controller->getName();
		$reportName = __('Data Quality');
		$this->controller->Navigation->substituteCrumb($this->getAlias(), $reportName);
		$this->controller->set('contentHeader', __($controllerName).' - '.$reportName);
		$this->fields = [];
		$this->ControllerAction->field('feature', ['select' => false]);
		$this->ControllerAction->field('academic_period_id', ['select' => false]);
		$this->ControllerAction->field('format');
	}

	/*public function onUpdateFieldFeature(Event $event, array $attr, $action, ServerRequest $request) {
		$attr['options'] = $this->controller->getFeatureOptions($this->alias());
		return $attr;
	}*/

	public function onUpdateFieldFeature(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->controller->getFeatureOptions($this->getAlias());
            $attr['onChangeReload'] = true;
            if (!(isset($this->request->getData($this->getAlias())['feature']))) {
                $option = $attr['options'];
                reset($option);
                $this->request->getData($this->getAlias())['feature'] = key($option);
            }
            return $attr;
        }
    }

    /*public function addAfterAction(Event $event, Entity $entity)
    {
    	if ($entity->has('feature')) { 
            $feature = $entity->feature;
            $fieldsOrder = ['feature'];
            switch ($feature) {
                case 'Report.EnrollmentOutliers':
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'format';
                    break;
                }
             $this->ControllerAction->setFieldOrder($fieldsOrder);
       	}
    }*/

    //POCOR-7211
    public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
    }


    /**
     * add academic period id
     * POCOR-7211
     */
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, ServerRequest $request)
    {
    	if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array($feature,['Report.EnrollmentOutliers','Report.AgeOutliers'])){
            	$AcademicPeriodTable = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
                $academicPeriodOptions = $AcademicPeriodTable->getYearList();
                $currentPeriod = $AcademicPeriodTable->getCurrent();
                $attr['options'] = $academicPeriodOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['onChangeReload'] = true;
                if (empty($request->getData($this->getAlias())['academic_period_id'])) {
                    $request->getData($this->getAlias())['academic_period_id'] = $currentPeriod;
                }
                return $attr;
            }
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
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

	    
}
