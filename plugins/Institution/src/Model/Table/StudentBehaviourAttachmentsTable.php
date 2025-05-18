<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Laminas\Diactoros\UploadedFile;
use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;

class StudentBehaviourAttachmentsTable extends ControllerActionTable {

    private  $tmp_file_content;
    private  $tmp_file_name;

    public function initialize(array $config): void {
        parent::initialize($config);
        $this->belongsTo('StudentBehaviours', ['className' => 'Institution.StudentBehaviours', 'foreignKey' => 'student_behaviour_id']);
        $this->addBehavior('ControllerAction.FileUpload',
            [   'name' => 'file_name',
                'content' => 'file_content',
                'size' => '2MB',
                'contentEditable' => true,
                'allowable_file_types' => 'all',
                'useDefaultName' => true
            ]);
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['StudentBehaviourAttachments' =>['id']
            ]
        ]);
   }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        return $validator->requirePresence(['file_name', 'file_content']);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Restful.Model.isAuthorized'] = ['callable' => 'isAuthorized', 'priority' => 1];
        return $events;
    }

    public function isAuthorized(Event $event, $scope, $action, $extra)
    {
        if ($action == 'download') {
            // check for the user permission to download here
            $event->stopPropagation();
            return true;
        }
    }

    public function beforeAction(Event $event, ArrayObject $extra) {
        $tabElements = $this->getStudentBehaviourTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());
        $paramPass = $this->paramsDecode($this->request->getParam('pass')[1]);
        $studentBehaviourId = $paramPass['student_behaviour_id'];
        $this->field('file_name', ['type'=>'hidden','visible' =>['index' => true, 'edit' => true]]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
        $this->field('student_behaviour_id', ['attr' => ['value' => $studentBehaviourId], 'type' => 'hidden']);
        $this->setFieldOrder([
            'name', 'description', 'file_content','student_behaviour_id'
        ]);
    }
    
    public function onUpdateFieldHiddenFileName(Event $event, array $attr, $action,  $request)
    {
        $entity = $attr['entity'];
        if ($action == 'view') {
            $attr['type'] = 'hidden';
            $attr['value'] = $entity->file_name;
        } else if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'hidden';
            $attr['value'] = $entity->file_name;
        }

        return $attr;
    }
    public function onUpdateFieldHiddenFileContent(Event $event, array $attr, $action,  $request)
    {
        $file_content = $attr['file_content'];
        if ($action == 'view') {
            $attr['type'] = 'hidden';
            $attr['value'] = base64_encode($file_content);
        } else if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'hidden';
            $attr['value'] = base64_encode($file_content);
        }

        return $attr;
    }
    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {

        if(!is_null($entity->file_content)){
            $file_content = stream_get_contents($entity->file_content);
            $this->tmp_file_content = $file_content;
            $this->tmp_file_name = $entity->file_name;
        }
        $this->field('file_name', ['type' => 'hidden', 'entity' => $entity]);
        $this->field('hidden_file_name', ['type' => 'hidden', 'entity' => $entity]);
        $this->field('hidden_file_content', ['type' => 'hidden', 'entity' => $entity, 'file_content' => $file_content]);
        $this->field('file_content', ['attr' => ['label' => __('Attachment')], 'visible' => ['add' => true, 'view' => true, 'edit' => true]]);
    }

    public function getStudentBehaviourTabElements($options = [])
    {
        $tabElements = [];
        $institutionId = $this->getInstitutionID();
        $paramPass = $this->request->getParam('pass');
        $ids = isset($paramPass[1]) ? $this->paramsDecode($paramPass[1]) : [];
        if(isset($ids['student_behaviour_id'])) {
            $studentBehaviourId = $ids['student_behaviour_id'];
            $queryString = $this->paramsEncode(['id' => $studentBehaviourId, 'institution_id' => $institutionId]);

            $tabElements = [
                'StudentBehaviours' => [
                    'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentBehaviours', 'view', $queryString],
                    'text' => __('Overview')
                ],
                'StudentBehaviourAttachments' => [
                    'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StudentBehaviourAttachments', 'index', $paramPass[1]],
                    'text' => __('Attachments')
                ]
            ];
        }
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $tmpData = $data->getArrayCopy();
        //echo "when edit";
        $tmpFile = $tmpData['hidden_file_content'];
        if($tmpFile){
            $tmpFileContent = base64_decode($tmpFile);
        }

        if($tmpFileContent){
            $data['file_content'] = $tmpFileContent;
        }else{
            $sentData = $this->request->getData(); //dd($sentData);
            $alias = $this->getAlias();
            $sentData = $sentData[$alias];

            $fileContent = 'file_content';
            $uploadedFile = $sentData[$fileContent];
            $fileName = 'file_name';

            if ($uploadedFile instanceof UploadedFile) {
                $error = $uploadedFile->getError();
                if ($error === UPLOAD_ERR_OK) {
                    // Accessing the file contents
                    $content = (string)$uploadedFile->getStream();
                }
                $name = $uploadedFile->getClientFilename();
            }

            if (isset($content) && isset($error) && $error == UPLOAD_ERR_OK) {
                $data[$fileName] = $name;
                $data[$fileContent] = $content;
            } elseif (isset($error) && $error == UPLOAD_ERR_NO_FILE) {
                $data->offsetUnset($fileContent);
                if ($data->offsetExists($fileName)) {
                    $data->offsetUnset($fileName);
                }
            } elseif (isset($data[$fileContent . '_remove']) && $data[$fileContent . '_remove'] == 1) {
                $data[$fileName] = null;
                $data[$fileContent] = null;
            } elseif (!isset($data[$fileName])) {
                $var = null;
                $data[$fileName] = null;
                $data[$fileContent] = null;
            }
        }

        if(!empty($tmpData) && !empty($tmpFileContent)){
            $data['file_content'] = $tmpFileContent;

        }
    }
}