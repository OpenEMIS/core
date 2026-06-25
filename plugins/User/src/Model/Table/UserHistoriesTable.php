<?php
namespace User\Model\Table;

use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use ArrayObject;

use App\Model\Table\ControllerActionTable;

class UserHistoriesTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
        $this->setTable('user_activities');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('CreatedUser', ['className' => 'User.Users', 'foreignKey'=>'created_user_id']);
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('view', false);
        $this->toggle('remove', false);
        $this->addBehavior('Activity');
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $userId = $this->getQueryString('security_user_id');
        $query->where([$this->aliasField('security_user_id') => $userId]);

    }

    public function beforeAction(EventInterface $event) {
        $this->field('security_user_id', ['visible' => false]);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $getQueryString  = $this->getQueryString();
        $institutionId   = $getQueryString['institution_id'] ?? null;
        $userId          = $getQueryString['security_user_id'] ?? null;

        $queryString = $this->paramsEncode([
            'institution_id'   => $institutionId,
            'security_user_id' => $userId,
            'model'            => 'User',
        ]);
        //POCOR-4681
        $pdfButton = [
            'url' => [
                'plugin'     => 'Institution',
                'controller' => 'Institutions',
                'action'     => 'HistoryPdf',
                0            => $queryString,
            ],
            'type'  => 'button',
            'label' => '<i class="fa fa-file-pdf-o"></i>',
            'attr'  => $this->getButtonAttr(),
        ];
        $pdfButton['attr']['title'] = __('Export PDF');
        $extra['toolbarButtons']['pdfExport'] = $pdfButton;
    }
}
