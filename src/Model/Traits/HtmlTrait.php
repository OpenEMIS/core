<?php
namespace App\Model\Traits;

trait HtmlTrait {
	public function getDeleteButton($attr=[]) {
		$_attr = [
            'onclick' => 'jsTable.doRemove(this)',
            'aria-expanded' => 'true',
            'type' => 'button',
            'class' => 'btn btn-dropdown action-toggle btn-single-action'
        ];

        $_attr = array_merge($_attr, $attr);

        $htmlAttr = [];
        $format = '%s="%s"';
        foreach ($_attr as $key => $value) {
            $htmlAttr[] = sprintf($format, $key, $value);
        }
        $html = '<button ' . implode(' ', $htmlAttr) . '><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';

        return $html;
	}

	public function getInfoIcon($msg) {
		return '<div class="tooltip-blue"><i class="fa fa-exclamation-circle fa-lg icon-blue" data-placement="right" data-toggle="tooltip" data-original-title="'.__($msg).'"></i></div>';
	}

}