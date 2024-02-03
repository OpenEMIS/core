<?php
namespace User\Model\Table;

use ArrayObject;
use Cake\ORM\Entity; //POCOR-6353
use Cake\Event\Event; //POCOR-6353
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
        $this->addBehavior('User.SetupTab'); //POCOR-6353
    }

    /**
     * This function is used for add comment type select field
     * @author Akshay patodi <akshay.patodi@mail.valuecoders.com>
     * @ticket POCOR-6353
    */ 
    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('comment_type_id', ['type' => 'select']);
    } 

    public function findIndex(Query $query, array $options)
    {
        $user_id = $this->getUserID();
            $query->where([$this->aliasField('security_user_id') => $user_id]);
        return $query;
    }

    // Start POCOR-5188
    public function indexBeforeAction(Event $event, ArrayObject $extra)
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
    }



    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'comment_date') {
            return __('Date');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $buttons = $this->fixProfileActionButtons($entity, $buttons);
        return $buttons;
    }

    /**
     * @return |null
     */
    private function getUserID()
    {
        $queryString = $this->getQueryString();
        $userId = null;
        if (!$userId && isset($queryString['security_user_id'])) {
            $userId = $queryString['security_user_id'];
        }
        if (!$userId && isset($queryString['user_id'])) {
            $userId = $queryString['user_id'];
        }
        if (!$userId) {
            $userId = $this->request->getSession()->read('Auth.User.id');
        }
        return $userId;
    }


    /**
     * @param Entity $entity
     * @param array $buttons
     * @return array
     */
    private function fixProfileActionButtons(Entity $entity, array $buttons): array
    {
        $userID = $this->getUserID();
        $actions = ['view', 'edit'];
        foreach ($actions as $action) {
            if (isset($buttons[$action])) {
                $url = $buttons[$action]['url'];
                if ($url['plugin'] == 'Profile' && $url['controller'] == 'Profiles' && $url['action'] == 'Comments') {
                    if (isset($url[2])) {
                        unset($url[2]);
                    }
                    $queryString = $this->getQueryString();
                    $queryString['id'] = $entity->id;
                    $queryString['user_id'] = $userID;
                    $queryString['comment_type_id'] = $entity->comment_type_id;
                    $queryString['security_user_id'] = $userID;
                    $url[1] = $this->paramsEncode($queryString);
                    $buttons[$action]['url'] = $url;
                }
            }
        }
//                die('<pre>' . print_r($entity, true));
//                die('<pre>' . print_r($buttons, true));
        return $buttons;
    }

    public function deleteBeforeAction(Event $event, ArrayObject $extra)
    {
        $url = $this->url('index');
        $userId = $this->getUserID();
        if (isset($url[2])) {
            unset($url[2]);
        }
        $queryString['id'] = $userId;
        $queryString['user_id'] = $userId;
        $url[1] = $this->paramsEncode($queryString);
        $extra['redirect'] = $url;
    }


    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $url = $this->url('index');
        $userId = $this->getUserID();
        if (isset($url[2])) {
            unset($url[2]);
        }
        $queryString['id'] = $userId;
        $queryString['user_id'] = $userId;
        $url[1] = $this->paramsEncode($queryString);
        $extra['redirect'] = $url;
    }




}
