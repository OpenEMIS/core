<?php
namespace Meal\Controller;

use ArrayObject;
use Exception;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Network\Response;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use Cake\Routing\Router;
use Cake\I18n\Date;
use Cake\Controller\Exception\SecurityException;
use Cake\Core\Configure;
use App\Model\Traits\OptionsTrait;
use Meal\Controller\AppController;
use ControllerAction\Model\Traits\UtilityTrait;
use Cake\Datasource\ConnectionManager;



class MealsController extends AppController
{

    public function initialize(){
        parent::initialize();
    }

    public function onInitialize(Event $event, Table $model, ArrayObject $extra) {

		$header = 'Meal Programme';    
        $this->Navigation->addCrumb($header, ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $this->request->action]);

        
        //Customize header because model name created was different and POCOR-5692 requirement was modified.
        if($this->request->action == 'Programme'){
            $header = __('Meal Programme') . ' - ' . __('Programme');
            $this->Navigation->addCrumb('Programme');
        }

        if($this->request->action == 'MealProgramme'){
            $header = __('Meal Programme') . ' - ' . __('MealProgramme');
            $this->Navigation->addCrumb('MealProgramme');
        }

        $this->set('contentHeader', $header); 
    }

    

    public function programme(){
        $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Meal.MealProgrammes']);
    }

}
