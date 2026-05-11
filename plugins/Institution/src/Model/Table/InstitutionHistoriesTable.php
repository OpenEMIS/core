<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use  Cake\Event\Event;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;

class InstitutionHistoriesTable extends ControllerActionTable
{
    public function initialize(array $config):void
    {
        $this->setTable('institution_activities');
        parent::initialize($config);

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey'=>'institution_id']);
        $this->belongsTo('CreatedUser',  ['className' => 'User.Users', 'foreignKey'=>'created_user_id']);
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('view', false);
        $this->toggle('remove', false);

        $this->addBehavior('Activity');
    }

    public function beforeAction(EventInterface $event) {
        $this->field('model_reference', ['visible' => false]);
        $this->field('field_type', ['visible' => false]);
        $this->field('operation', ['visible' => false]);
    }

    //POCOR-4681
    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $getQueryString = $this->getQueryString();
        $institutionId = $getQueryString['institution_id'];
        $queryString = $this->paramsEncode(['id' => $institutionId, 'institution_id' => $institutionId]);

        $pdfButton = [
            'url' => [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'HistoryPdf',
                 0       => $queryString,
            ],
            'type' => 'button',
            'label' => '<i class="fa fa-file-pdf-o"></i>',
            'attr' => $this->getButtonAttr(),
        ];
        $pdfButton['attr']['title'] = __('Export PDF');
        $extra['toolbarButtons']['pdfExport'] = $pdfButton;
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'created') {
            return __('Modified On');
        } else if ($field == 'created_user_id') {
            return __('Modified By');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

}
