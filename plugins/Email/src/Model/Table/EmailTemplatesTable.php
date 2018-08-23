<?php
namespace Email\Model\Table;

use App\Model\Table\AppTable;

class EmailTemplatesTable extends AppTable
{
	public function initialize(array $config)
    {
		parent::initialize($config);
    }

    public function getTemplate($modelAlias, $modelReference = 0)
    {
		$idKeys = [
			'model_alias' => $modelAlias,
			'model_reference' => $modelReference
		];
    	if (!$this->exists($idKeys)) {
	    	$idKeys['model_reference'] = 0;
    	}

    	$template = $this->get($idKeys);

    	return $template;
    }
}
