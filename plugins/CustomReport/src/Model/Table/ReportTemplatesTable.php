<?php
namespace CustomReport\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class ReportTemplatesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);

		$this->addBehavior('ControllerAction.FileUpload', [
			// 'name' => 'file_name',
			// 'content' => 'file_content',
			'size' => '10MB',
			'contentEditable' => true,
			'allowable_file_types' => 'document',
			'useDefaultName' => true
		]);
        $this->addBehavior('CustomReport.ReportTemplate');

        $this->toggle('add', false);
        $this->toggle('remove', false);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->allowEmpty('file_content');
    }
}
