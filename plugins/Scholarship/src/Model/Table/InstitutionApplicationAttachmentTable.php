<?php
namespace Scholarship\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\Controller\Component;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Log\Log;
use Cake\ORM\ResultSetInterface;

class InstitutionApplicationAttachmentTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('scholarship_application_attachments');
        parent::initialize($config);

        $this->belongsTo('Applications', ['className' => 'Scholarship.Applications', 'foreignKey' => ['applicant_id', 'scholarship_id']]);
        
        $this->belongsTo('ScholarshipAttachmentType', ['className' => 'Scholarship.ScholarshipAttachmentType', 'foreignKey' => 'scholarship_attachment_type_id']);
        $this->belongsTo('Applicants', ['className' => 'User.Users', 'foreignKey' => 'applicant_id']);
        $this->belongsTo('Scholarships', ['className' => 'Scholarship.Scholarships']);
         $this->behaviors()->get('ControllerAction')->setConfig(
            'actions.download.show',
            true
        );
         $this->addBehavior('ControllerAction.Download');
         $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'file_name',
            'content' => 'file_content',
            'size' => '2MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
      //  $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        $events['ControllerAction.Model.download'] = 'download';
        return $events;
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $queryString  = $this->getQueryString('scholarship_id');
        $scholarshipId = $queryString;

        $query 
            ->contain(['Scholarships.AcademicPeriods'])
            ->where([$this->aliasField('scholarship_id IS') => $scholarshipId])
            ->order(['AcademicPeriods.name' => 'DESC']);
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $encodedQueryString = $this->request->getParam('pass')[1];
        $tabElements = $this->ScholarshipTabs->getScholarshipApplicationTabs();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());
        $extra['toolbarButtons']['back']['url'] = [
            'plugin' => 'Scholarship',
            'controller' => 'Scholarships',
            'action' => 'ScholarshipApplicationAttachments',
            0 => 'index',
            1 => $encodedQueryString
        ];
        $extra['toolbarButtons']['list']['url'] = [
            'plugin' => 'Scholarship',
            'controller' => 'Scholarships',
            'action' => 'ScholarshipApplicationAttachments',
            0 => 'index',
            1 => $encodedQueryString
        ];
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('is_mandatory', ['attr' => ['label' => __('Mandatory')]]);
        $this->field('created', ['visible' => true]);
        $this->field('created_user_id', ['visible' => true]);
        $this->field('file_content', ['visible' => false]);
        $this->field('file_name', ['visible' => false]);
        $this->field('applicant_id', ['visible' => false]);
        $this->field('scholarship_id', ['visible' => false]);
        $this->field('scholarship_attachment_type_id', ['visible' => true]);
        $this->setFieldOrder(['is_mandatory', 'scholarship_attachment_type_id', 'created','created_user_id']);
        
    }

    public function onGetBreadcrumb(EventInterface $event, ServerRequest $request, Component $Navigation, $persona)
    {   
        $this->Navigation->substituteCrumb($this->getHeader($this->getAlias()), __('Attachments'));
    }

    public function onGetAcademicPeriodId(EventInterface $event, Entity $entity)
    {
        return $entity->scholarship->academic_period->name;
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'scholarship_attachment_type_id') {
            return __('Type');
        }else if ($field == 'created') {
            return __('Uploaded By');
        } else if ($field == 'created_user_id') {
            return __('Uploaded On');
        } else if ($field == 'applicant_id') {
            return __('Applicant');
        }else if ($field == 'start_date') {
            return __('Commencement Date');
        }else if ($field == 'end_date') {
            return __('Completion Date');
        }else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }


    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        
        if ($entity->getDirty('is_selected')) {
            if ($entity->is_selected == 1) {
                $this->updateAll(
                    ['is_selected' => 0],
                    [
                        'applicant_id' => $entity->applicant_id,
                        'scholarship_id' => $entity->scholarship_id,
                        'id <> ' => $entity->id
                    ]
                 );
            } 
        }
        $encodedQueryString = $this->request->getParam('pass')[1];
        $url = [
                'plugin' => 'Scholarship',
                'controller' => 'Scholarships',
                'action' => 'ScholarshipApplicationAttachments',
                '0' => 'index',
                 $encodedQueryString,

            ];
        return $this->controller->redirect($url);            

    }

    public function beforeDelete(EventInterface $event, Entity $entity)
    {
        $connection = $this->getConnection();
        $connection->getDriver()->enableAutoQuoting();
    }

    public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons) 
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $applicantId = $this->getQueryString('applicant_id');
        $scholarshipId = $this->getQueryString('scholarship_id');
        $encodedQueryString = $this->paramsEncode(['id' => $entity->id,'applicant_id' => $applicantId,'scholarship_id' => $scholarshipId]);
        $url['plugin'] = 'Scholarship';
        $url['controller'] = 'Scholarships';
        $url['action'] = 'ScholarshipApplicationAttachments';
        $url[0] = 'view';
        $url[1] = $encodedQueryString; 
        $buttons['view']['url'] = $url;
        
        $buttons['remove']['url'] = [
            'plugin' => 'Scholarship',
            'controller' => 'Scholarships',
            'action' => 'ScholarshipApplicationAttachments',
            0 => 'remove',
            1 => $encodedQueryString
        ];
        
        $buttons['edit']['url'] = [
            'plugin' => 'Scholarship',
            'controller' => 'Scholarships',
            'action' => 'ScholarshipApplicationAttachments',  // Specify the action
            0 => 'edit',  
            1 => $encodedQueryString          
        ];
        // Ensure 'options' is an array
        $buttons['edit']['label'] = $buttons['edit']['label'] ?? '<i class="fa fa-edit"></i>' . __('Edit');
        $buttons['edit']['attr'] = $buttons['edit']['attr'] ?? [
            'role' => 'menuitem',
            'tabindex' => '-1',
            'escape' => false,
        ];
        
        $encodedQueryString = $this->paramsEncode(['id' => $entity->id, 'applicant_id' => $entity->applicant_id, 'scholarship_id' => $entity->scholarship_id]);

        // Setup the remove button
        
        return $buttons;

    }


    public function addEditBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('country_id', ['visible' => 'false']);
        $this->field('file_name', ['visible' => 'false']);
        $this->field('country_id', ['visible' => 'false']);
        $this->field('applicant_id', ['visible' => 'false']);
        $this->field('scholarship_id', ['visible' => 'false']);
        $this->field('country_id', ['visible' => 'false']);
        $this->field('scholarship_attachment_type_id', ['type'=>'select','visible' => true]);
        $this->setFieldOrder(['scholarship_attachment_type_id', 'file_content']);
        
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $applicantId = $this->getQueryString('applicant_id');
        $scholarshipId = $this->getQueryString('scholarship_id');
        $this->AttachmentTypes = TableRegistry::getTableLocator()->get('Scholarship.AttachmentTypes');
        $listOptions = $this->AttachmentTypes
            ->find('availableAttachmentTypes', [
                'applicant_id' => $applicantId,
                'scholarship_id' => $scholarshipId
            ])
            ->formatResults(function ($results) { // No type hinting
                $returnArr = [];
                foreach ($results as $result) {
                    $name = $result->name;
                    if (!$result->is_mandatory) {
                        $name .= ' (' . __('Optional') . ')';
                    }
                    $returnArr[$result->id] = $name;
                }
                  return $returnArr; // Ensure the array is returned
            })
            ->toArray();
            $this->field('scholarship_attachment_type_id', [
            'type' => 'select',
            'options' => $listOptions,
            'empty' => 'Select' 
        ]);
        $this->setFieldOrder(['scholarship_attachment_type_id', 'file_content']);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);

        return $validator
            ->requirePresence('scholarship_attachment_type_id', 'create', __('This field is required.'))  // Ensures the field is present when creating a new record
            ->notEmptyString('scholarship_attachment_type_id', __('Please select a scholarship attachment type.'));  // Ensures the field is not empty
    }

    public function onGetIsMandatory(EventInterface $event, Entity $entity)
    {
        
        $this->ScholarshipsScholarshipAttachmentTypes = TableRegistry::getTableLocator()->get('Scholarship.ScholarshipsScholarshipAttachmentTypes');
        $attachmentType = $this->ScholarshipsScholarshipAttachmentTypes
            ->find()
            ->where([
                'scholarship_id' => $entity->scholarship_id,
                'scholarship_attachment_type_id' => $entity->scholarship_attachment_type_id
            ])
            ->first(); // Retrieve the first matching record

        $isMandatory = false; // Default value
        if ($attachmentType) {
            $isMandatory = $attachmentType->is_mandatory;
        }
            return $isMandatory ? "<i class='fa fa-check'></i>" : "<i class='fa fa-close'></i>";
    }

    public function viewBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('created', ['attr' => ['label' => __('Created By')]]);
        $this->field('created_user_id', ['attr' => ['label' => __('Created On')]]);
        $this->field('modified', ['attr' => ['label' => __('Modified By')]]);
        $this->field('modified_user_id', ['attr' => ['label' => __('Modified On')]]);
        $this->field('file_content', ['visible' => true]);
        $this->field('file_name', ['visible' => false]);
        $this->field('applicant_id', ['visible' => false]);
        $this->field('scholarship_id', ['visible' => false]);
        $this->field('scholarship_attachment_type_id', ['visible' => true]);
        $this->setFieldOrder(['scholarship_attachment_type_id','file_content','modified', 'modified_user_id','created','created_user_id']);
    }

    public function onGetFileContent(EventInterface $event, Entity $entity)
    {
       return $entity->file_name;
    }

    public function onUpdateFieldScholarshipAttachmentTypeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'edit') {
        $recordId = $this->getQueryString('id');
        $ScholarshipAttachmentType = TableRegistry::getTableLocator()->get('Scholarship.ScholarshipAttachmentType');

        $scholarshipAttachmentTypeRecord = $this->find()
            ->select(['id' => $ScholarshipAttachmentType->aliasField('id'),'name' => $ScholarshipAttachmentType->aliasField('name')])
            ->leftJoin(
                [$ScholarshipAttachmentType->getAlias() => $ScholarshipAttachmentType->getTable()],
                [
                    $ScholarshipAttachmentType->aliasField('id') . ' = ' . $this->aliasField('scholarship_attachment_type_id')
                ]
            )
            ->where([$this->getAlias() . '.id' => $recordId])
            ->first();
            $entity = $attr['entity'];
            $attr['type'] = 'readonly';
            $attr['value'] = $scholarshipAttachmentTypeRecord->id;
            $attr['attr']['value'] =  $scholarshipAttachmentTypeRecord->name;
            $attr['visible'] = true;
        }
        return $attr;
    }

    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $queryString = $this->getQueryString();
        $entity->applicant_id = $queryString['applicant_id']; 
        $entity->scholarship_id = $queryString['scholarship_id']; 
        // Check if file_content is an instance of UploadedFile
        if ($entity->file_content instanceof \Laminas\Diactoros\UploadedFile) {
            $uploadedFile = $entity->file_content;
            if ($uploadedFile->getError() === 4) {
            // No new file was uploaded, retain original values
            $entity->file_name = $entity->getOriginal('file_name');
            $entity->file_content = $entity->getOriginal('file_content');
            }else{
                $uploadedFile = $entity->file_content;
                $filename = $uploadedFile->getClientFilename();

                // Read the file content (as a string)
                $fileStream = $uploadedFile->getStream();
                $fileContent = $fileStream->getContents();
                $entity->file_name = $filename; // Adjust according to your field name
                $entity->file_content = $fileContent;
                
            }
        }
    }

    public function deleteBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        
        if($this->action == 'remove'){
            $applicantId = $this->getQueryString('applicant_id');
            $scholarshipId = $this->getQueryString('scholarship_id');
            $encodedQueryString = $this->paramsEncode(['applicant_id' => $applicantId,'scholarship_id' => $scholarshipId,'security_user_id' => $applicantId]);
             if(!empty($encodedQueryString)){
                $session = $this->request->getSession();
                $session->write('urlRequest', $encodedQueryString);
            }
            if(empty($encodedQueryString)){
                $session = $this->request->getSession();
                $encodedQueryString = $session->read('urlRequest');
            }
            $url = [
                'plugin' => 'Scholarship',
                'controller' => 'Scholarships',
                'action' => 'ScholarshipApplicationAttachments',
                0 => 'index',
                1 => $encodedQueryString
            ];
            $extra['redirect'] = $url;
        }
        
    }

}
