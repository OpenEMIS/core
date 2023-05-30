<?php
namespace Directory\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use Cake\Event\Event;
use ArrayObject;

use App\Model\Table\ControllerActionTable;

class CounsellingsTable extends ControllerActionTable
{
    const ASSIGNED = 1;

    public function initialize(array $config)
    {
        
        $this->table('institution_counsellings');
        parent::initialize($config);
        //$this->toggle('add', true);
        //$this->addBehavior('Excel', ['pages' => ['index']]);

        //  $this->belongsTo('GuidanceTypes', ['className' => 'Student.GuidanceTypes', 'foreign_key' => 'guidance_type_id']);
        // $this->belongsTo('Counselors', ['className' => 'Security.Users', 'foreign_key' => 'counselor_id']);
        // $this->belongsTo('Requesters', ['className' => 'Security.Users', 'foreign_key' => 'requester_id']);
        // $this->addBehavior('Page.FileUpload', [
        //     'fieldMap' => ['file_name' => 'file_content'],
        //     'size' => '2MB'
        // ]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('file_content', ['visible' => false]);
        $this->field('file_name', ['visible' => false]);
        $this->field('comment', ['visible' => false]);
        $this->field('guidance_utilized', ['visible' => false]);
        
        $this->setFieldOrder(['date', 'description', 'intervention', 'counselor_id', 'guidance_type_id', 'requester_id',  'Actions']);
    }


    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        //$this->field('attachment');
        // $this->field('date', ['attr' => ['value'=> 'CURRENT_DATE'], 'type' => 'select']);

        $this->setFieldOrder(['date','counselor_id','guidance_type_id','requester_id', 'guidance_utilized', 'description', 'intervention', 'comment', 'file_content']);
        
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'file_content':
                return __('Attachment');
            
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function getGuidanceTypesOptions($institutionId)
    {
        // should be auto, if auto the reorder and visible not working
        $guidanceTypesOptions = $this->GuidanceTypes
            ->find('list')
            ->find('visible')
            ->find('order')
            ->toArray();
            
        return $guidanceTypesOptions;



    }


}
