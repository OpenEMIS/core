<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Network\Request;

use App\Model\Table\ControllerActionTable;

class CommitteeAttachmentsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_committee_attachments');
        parent::initialize($config);

        $this->addBehavior('ControllerAction.FileUpload', ['size' => '2MB', 'contentEditable' => false, 'allowable_file_types' => 'all', 'useDefaultName' => true]);
        $this->belongsTo('InstitutionCommittees', ['className' => 'Institution.InstitutionCommittees', 'foreignKey' =>'institution_committee_id']);
         $this->toggle('search', false);
        //change behaviour config
        // if ($this->behaviors()->has('ControllerAction')) {
        //     $this->behaviors()->get('ControllerAction')->config([
        //         'actions' => [
        //             'download' => ['show' => true] //to show download on toolbar
        //         ]
        //     ]);
        // }
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => true]);
        $this->field('file_content', ['type' => 'binary', 'visible' => false]);
        $this->field('file_type', ['visible' => false]);

        $this->setFieldOrder([
            'name', 'description','file_content'
        ]);
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);        
        
        $query = $this->request->query['querystring']; 
        $this->setupTabElements($encodedInstitutionId, $query);
    }

     public function setupTabElements($encodedInstitutionId, $query)
    {
        $tabElements = [];
        $decodeCommitteeId = $this->paramsDecode($query);
        $committeeId = $decodeCommitteeId['institution_committee_id'];
        $encodeCommitteeId = $this->paramsEncode(['id' => $committeeId]);

        $tabElements = [
            'InstitutionCommittees' => [
                 'url' => ['plugin' => 'Institution', 'institutionId' => $encodedInstitutionId, 'controller' => 'Institutions', 'action' => 'Committees','view', $encodeCommitteeId],
                'text' => __('Overview')
            ],
            'Attachments' => [
                'url' => ['plugin' => 'Institution', 'institutionId' => $encodedInstitutionId, 'controller' => 'Institutions', 'action' => 'CommitteeAttachments', 'querystring' => $query],
                'text' => __('Attachments')
            ]
        ];
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction','Attachments');
    }

/******************************************************************************************************************
**
** index action logics
**
******************************************************************************************************************/
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        
        $this->field('file_content', ['visible' => false]);
        $this->field('institution_committee_id');
        $this->setFieldOrder([
            'name', 'description','file_name', 'institution_committee_id'
        ]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
      
        $queryString = $this->paramsDecode($this->request->query['querystring']);
        $institutionCommitteeId = $queryString['institution_committee_id'];
        $query->where([$this->aliasField('institution_committee_id') => $institutionCommitteeId]);
      
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('file_content', ['type' => 'binary', 'visible' => true]);
        $this->field('file_name', ['visible' => false]);
        $this->setFieldOrder(['name', 'description', 'file_content']);

        $queryString = $this->paramsDecode($this->request->query['querystring']);
        $institutionCommitteeId = $queryString['institution_committee_id'];
        $this->field('institution_committee_id',['type'=>'hidden','value'=>$institutionCommitteeId]);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_content', ['type' => 'binary', 'visible' => true]);
        $this->field('file_name', ['visible' => false]);
        $this->setFieldOrder(['name', 'description', 'file_content']);

        $queryString = $this->paramsDecode($this->request->query['querystring']);
        $institutionCommitteeId = $queryString['institution_committee_id'];
        $this->field('institution_committee_id',['type'=>'hidden','value'=>$institutionCommitteeId]);
       

       
    }


/******************************************************************************************************************
**
** adding download button to index page
**
******************************************************************************************************************/
    // public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    // {
    //     $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
    //     ///echo '<pre>';print_r($entity->id);die;
    //     $downloadAccess = $this->AccessControl->check([$this->controller->name, 'CommitteeAttachments', 'download']);

    //     if ($downloadAccess) {
    //         $indexAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];

    //         $buttons['download']['label'] = '<i class="kd-download"></i>' . __('Download');
    //         $buttons['download']['attr'] = $indexAttr;
    //         $buttons['download']['url']['action'] = $this->alias;
    //         $buttons['download']['url'][0] = 'download';
    //         $buttons['download']['url'][1] = $this->paramsEncode(['id' => $entity->id]);
    //     }

    //     return $buttons;
    // }

    
}
