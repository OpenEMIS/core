<tr>
	<td>
		<?php
		echo $this->Form->hidden("$attr[0].$index.$attr[1]", array('class' => 'value-id'));
		echo $this->Form->hidden('index', array('class' => 'index', 'value' => $index));
		echo $this->Form->input('search', array(
			'label' => false,
			'div' => false,
			'class' => 'autocomplete form-control',
			'url' => 'autocomplete/'.$attr[0]
		));
		?>
	</td>
	<td><span class="icon_delete" style="margin-top: 5px;" title="<?php echo __("Delete"); ?>" onclick="jsTable.doRemove(this)"></span></td>
</tr>
