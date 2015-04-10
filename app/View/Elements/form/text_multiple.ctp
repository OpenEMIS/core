<?php 
$suffixAttr = (isset($suffixAttr))? $suffixAttr: array();
$prefixAttr = (isset($prefixAttr))? $prefixAttr: array();
$options = array_merge(
	array(
		'class' => 'form-control input-size-half space',
		'label' => array(
			'class' => 'col-md-3 control-label', 
			'text' => $this->Label->get('SecurityUser.openemis_no')
		),
		'after' =>  
			$this->Form->input(
				$fieldName.'_suffix', 
				array_merge(
					array(
						'class' => 'form-control input-size-half',
						'div' => false,
						'label' => false,
						'between' => false,
					),
					$suffixAttr
				)
			)
	),
	$prefixAttr
);

echo $this->Form->input($fieldName.'_prefix', $options); 
?>