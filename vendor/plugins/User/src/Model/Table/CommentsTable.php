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
use Cake\Network\Request;

use App\Model\Table\AppTable;
/**
     * This class is used for change url structure and permission for tab element
     * @author Akshay patodi <akshay.patodi@mail.valuecoders.com>
     * @ticket POCOR-6353
*/
class CommentsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('user_comments');
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
        $querystring = $options['querystring'];
        if (array_key_exists('security_user_id', $querystring) && !empty($querystring['security_user_id'])) {
            $query->where([$this->aliasField('security_user_id') => $querystring['security_user_id']]);
        }
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
    // End POCOR-5188

}
