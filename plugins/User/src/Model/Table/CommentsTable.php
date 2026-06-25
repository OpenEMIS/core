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
