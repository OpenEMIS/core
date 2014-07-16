<?php 
if($newRowIndex == 0){
?>
<tbody>
<?php 
}
?>
<tr class="" record-id="0">
	<td>
		<?php
		echo $this->Form->hidden('id', array(
			'label' => false,
			'div' => false,
			'after' => false,
			'between' => false,
			'class' => 'form-control',
			'id' => 'PopulationId',
			'name' => 'data[Population][' . $newRowIndex . '][id]',
			'value' => 0
		));
		echo $this->Form->input('source', array(
			'label' => false,
			'div' => false,
			'after' => false,
			'between' => false,
			'class' => 'form-control',
			'id' => 'PopulationSource',
			'name' => 'data[Population][' . $newRowIndex . '][source]',
			'value' => ''
		));
		?>
	</td>
	<td>
		<?php
		echo $this->Form->input('age', array(
			'label' => false,
			'div' => false,
			'after' => false,
			'between' => false,
			'class' => 'form-control',
			'id' => 'PopulationAge',
			'name' => 'data[Population][' . $newRowIndex . '][age]',
			'value' => 0,
			'onkeypress' => 'return utility.integerCheck(event)'
		));
		?>
	</td>
	<td>
		<?php
		echo $this->Form->input('male', array(
			'label' => false,
			'div' => false,
			'after' => false,
			'between' => false,
			'class' => 'form-control',
			'id' => 'PopulationMale',
			'name' => 'data[Population][' . $newRowIndex . '][male]',
			'value' => 0,
			'onkeypress' => 'return utility.integerCheck(event)',
			'onkeyup' => 'population.computeSubtotal(this)'
		));
		?>
	</td>
	<td>
		<?php
		echo $this->Form->input('female', array(
			'label' => false,
			'div' => false,
			'after' => false,
			'between' => false,
			'class' => 'form-control',
			'id' => 'PopulationFemale',
			'name' => 'data[Population][' . $newRowIndex . '][female]',
			'value' => 0,
			'onkeypress' => 'return utility.integerCheck(event)',
			'onkeyup' => 'population.computeSubtotal(this)'
		));
		?>
	</td>
	<td class="cell-total">0</td>
	<td><span class="icon_delete" title="'+i18n.General.textDelete+'" onclick="population.removeRow(this)"></span></td>
</tr>
<?php 
if($newRowIndex == 0){
?>
</tbody>
<?php 
}
?>