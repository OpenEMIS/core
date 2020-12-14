<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;

class ProfilesTable extends ControllerActionTable
{
	// for status
    CONST NEW_REPORT = 1;
    CONST IN_PROGRESS = 2;
    CONST GENERATED = 3;
    CONST PUBLISHED = 4;

    public function initialize(array $config)
    {
        $this->table('institution_report_cards');

        parent::initialize($config);
		
		$this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
		
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period');
        $this->field('profile_name');
        $this->field('file_name');
		$this->field('status', ['visible' => false]);
		$this->field('file_content', ['visible' => false]);
		$this->field('file_content_pdf', ['visible' => false]);
		$this->field('started_on', ['visible' => false]);
		$this->field('completed_on', ['visible' => false]);
        $this->setFieldOrder([
            'academic_period',
            'profile_name',
            'file_name'
        ]);
    }
	
	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {        
		$institutionId = $this->Session->read('Institution.Institutions.id');

		$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$ProfileTemplates = TableRegistry::get('ProfileTemplate.ProfileTemplates');
		
		$where[$this->aliasField('status')] = self::PUBLISHED;
		$where[$this->aliasField('institution_id')] = $institutionId;

        $query
            ->select([
                'file_name' => $this->aliasField('file_name'),
                'academic_period' => $AcademicPeriods->aliasField('name'),
                'profile_name' => $ProfileTemplates->aliasField('name'),
            ])
			->innerJoin([$AcademicPeriods->alias() => $AcademicPeriods->table()],
                [
                    $AcademicPeriods->aliasField('id = ') . $this->aliasField('academic_period_id'),
                ]
            )
			->innerJoin([$ProfileTemplates->alias() => $ProfileTemplates->table()],
                [
                    $ProfileTemplates->aliasField('id = ') . $this->aliasField('report_card_id'),
                ]
            )
            ->autoFields(true)
			->order([
                $this->aliasField('file_name'),
            ])
            ->where($where)
            ->all();

    }
	
	public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
		$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$ProfileTemplates = TableRegistry::get('ProfileTemplate.ProfileTemplates');
				
        $query
            ->select([
                'file_name' => $this->aliasField('file_name'),
                'academic_period' => $AcademicPeriods->aliasField('name'),
                'profile_name' => $ProfileTemplates->aliasField('name'),
            ])
			->innerJoin([$AcademicPeriods->alias() => $AcademicPeriods->table()],
                [
                    $AcademicPeriods->aliasField('id = ') . $this->aliasField('academic_period_id'),
                ]
            )
			->innerJoin([$ProfileTemplates->alias() => $ProfileTemplates->table()],
                [
                    $ProfileTemplates->aliasField('id = ') . $this->aliasField('report_card_id'),
                ]
            )
            ->autoFields(true);
    }
	
    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('academic_period');
        $this->field('profile_name');
        $this->field('file_name');
		$this->field('status', ['visible' => false]);
		$this->field('file_content', ['visible' => false]);
		$this->field('file_content_pdf', ['visible' => false]);
		$this->field('started_on', ['visible' => false]);
		$this->field('completed_on', ['visible' => false]);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        $indexAttr = ['role' => 'menuitem', 'tabindex' => '-1', 'escape' => false];
        $downloadUrl = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => $this->alias,
            'institutionId' => $this->paramsEncode(['id' => $entity->institution_id]),
            '0' => 'download',
            '1' => $this->paramsEncode(['id' => $entity->id])
        ];
        $buttons['download'] = [
            'label' => '<i class="fa kd-download"></i>'.__('Download'),
            'attr' => $indexAttr,
            'url' => $downloadUrl
        ];

        return $buttons;
    }
}
