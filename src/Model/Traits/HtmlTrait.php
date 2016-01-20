<?php
namespace App\Model\Traits;

trait HtmlTrait {
	public function getDeleteButton() {
		return '<button onclick="jsTable.doRemove(this)" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';
	}

	public function getInfoIcon($msg) {
		return '<div class="tooltip-blue"><i class="fa fa-exclamation-circle fa-lg icon-blue" data-placement="right" data-toggle="tooltip" data-original-title="'.__($msg).'"></i></div>';
	}

}