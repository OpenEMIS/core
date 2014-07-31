<tr>
	<td>
		<?php
		echo $this->Form->hidden("$attr[0].$index.$attr[1]", array('class' => 'value-id'));
		echo $this->Form->hidden('index', array('class' => 'index', 'value' => $index));
		echo $this->Form->input('search', array(
			'label' => false,
			'div' => false,
			'class' => 'autocomplete form-control',
			'url' => 'Security/SecurityGroup/autocomplete/'.$attr[0]
		));
		?>
	</td>
	<?php if ($attr[0] == 'SecurityGroupUser') : ?>
	<td>
		<?php
		echo $this->Form->input('security_role_id', array(
			'label' => false,
			'div' => false,
			'class' => 'form-control',
			'options' => $roleOptions
		));
		?>
	</td>
	<?php endif ?>
	<td><span class="icon_delete" style="margin-top: 5px;" title="<?php echo __("Delete"); ?>" onclick="jsTable.doRemove(this)"></span></td>
</tr>
