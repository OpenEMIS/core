<?php
namespace App\Model\Traits;

trait HtmlTrait {
	public function getDeleteButton() {
		return '<button onclick="jsTable.doRemove(this)" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';
	}
}
