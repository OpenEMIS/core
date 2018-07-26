<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class StaffPositionTitlesGradesTable extends AppTable
{
	public function initialize(array $config)
	{
		parent::initialize($config);

		$this->belongsTo('StaffPositionTitles', ['className' => 'Institution.StaffPositionTitles']);
		$this->belongsTo('StaffPositionGrades', ['className' => 'Institution.StaffPositionGrades']);
	}
}
