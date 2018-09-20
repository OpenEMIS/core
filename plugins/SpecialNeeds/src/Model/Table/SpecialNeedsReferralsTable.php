<?php
namespace SpecialNeeds\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class SpecialNeedsReferralsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('user_special_needs_referrals');
        parent::initialize($config);

        $this->addBehavior('SpecialNeeds.SpecialNeeds');
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'file_name',
            'content' => 'file_content',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);
    }
}
