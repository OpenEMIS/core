<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\ORM\Entity; //POCOR-6353
use Cake\Event\EventInterface; //POCOR-6353
use Cake\ORM\TableRegistry; //POCOR-6353
use App\Model\Table\ControllerActionTable; //POCOR-6353
use Cake\I18n\Time; //POCOR-6353
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Http\ServerRequest;

use App\Model\Table\AppTable;
/**
     * This class is used for change url structure and permission for tab element
     * @author Akshay patodi <akshay.patodi@mail.valuecoders.com>
     * @ticket POCOR-6353
*/
class CommentsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('user_comments');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('CommentTypes', ['className' => 'User.CommentTypes', 'foreignKey' => 'comment_type_id']);
        $this->addBehavior('Institution.InstitutionTab',
            ['implementedMethods' =>
                [
                    'setUserTabElements' => 'setUserTabElements',
                ],
            ]);
        $this->addBehavior('User.SetupTab'); //POCOR-6353
        $this->addBehavior('User.UserTab');
    }

    // The bootstrap-datepicker "date" field for comment_date renders/accepts text in whatever
    // format is configured in System Configurations > Date Format (e.g. "July 31, 2026"), not just
    // 'Y-m-d'. Cake's DateType::marshal() only ever accepts the strict 'Y-m-d' format, so with any
    // other configured format the submitted value was marshalled to null - and since comment_date
    // has no default, saving then failed with "Field 'comment_date' doesn't have a default value".
    // Normalize the submitted value to 'Y-m-d' here, before patchEntity()/marshal() runs.
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        $field = 'comment_date';
        if (!array_key_exists($field, (array)$data) || empty($data[$field])) {
            return;
        }

        $rawValue = $data[$field];
        if (!is_string($rawValue) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawValue)) {
            // already Y-m-d (or not a string we can parse) - leave untouched
            return;
        }

        $ConfigItems = TableRegistry::getTableLocator()->get('Configuration.ConfigItems');
        $systemDateFormat = $ConfigItems->value('date_format') ?: 'd-m-Y';
        // bootstrap-datepicker cannot emit ordinals; parse without "S" (strip legacy "31st" input too)
        $editableDateFormat = preg_replace('/\s+/', ' ', trim(str_replace('S', '', $systemDateFormat))) ?: 'd-m-Y';
        $normalized = preg_replace('/(\d+)(st|nd|rd|th)\b/i', '$1', $rawValue);

        try {
            try {
                $date = \Cake\Chronos\Chronos::createFromFormat($editableDateFormat, $normalized);
            } catch (\Exception $e) {
                $date = \Cake\Chronos\Chronos::createFromFormat($systemDateFormat, $rawValue);
            }
            if ($date !== false && $date !== null) {
                $data[$field] = $date->format('Y-m-d');
            }
        } catch (\Exception $e) {
            \Cake\Log\Log::warning("CommentsTable: Invalid date '{$rawValue}' for field '{$field}' with format '{$systemDateFormat}'");
        }
    }

    /**
     * This function is used for add comment type select field
     * @author Akshay patodi <akshay.patodi@mail.valuecoders.com>
     * @ticket POCOR-6353
    */
    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $user_id = $this->getUserID();
        $this->field('comment_type_id', ['type' => 'select']);
        if($this->request->getParam('controller') == 'Staff') {
            $this->field('security_user_id', ['attr' => ['value' => $user_id], 'type' => 'hidden']);
        }
        
    }

    public function findIndex(Query $query, array $options)
    {
        $user_id = $this->getUserID();
            $query->where([$this->aliasField('security_user_id') => $user_id]);
        return $query;
    }

    // Start POCOR-5188
    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
		$is_manual_exist = $this->getManualUrl('Personal','Comments','General');
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
        if($this->request->getParam('controller') == 'Directories' || $this->request->getParam('controller') == 'Staff') {
            $this->field('security_user_id', ['visible' => false]);
            $this->setFieldOrder([
                'comment_date', 'comment_type_id', 'title', 'comment'
            ]);
        }
        
    }



    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'comment_date') {
            return __('Date');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    public function afterSave()
    {
        $url = $this->url('index');
        return $this->controller->redirect($url);
    }
    public function afterDelete()
    {
        $url = $this->url('index');
        return $this->controller->redirect($url);
    }

}
