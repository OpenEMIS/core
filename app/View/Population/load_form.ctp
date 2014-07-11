<?php
if (!empty($data)):
	?>
	<tbody><?php 
		$recordIndex = 0;
		foreach ($data AS $row):
			?>
			<tr class="<?php echo $row['data_source'] == 0 ? '' : 'row_estimate'; ?>" record-id="<?php echo $row['id']; ?>">
				<td>
					<?php 
						echo $this->Form->hidden('id', array(
							'label' => false,
							'div' => false,
							'after' => false,
							'between' => false,
							'class' => 'form-control',
							'id' => 'PopulationId',
							'name' => 'data[Population][' . $recordIndex . '][id]',
							'value' => $row['id']
						));
						echo $this->Form->input('source', array(
							'label' => false,
							'div' => false,
							'after' => false,
							'between' => false,
							'class' => 'form-control',
							'id' => 'PopulationSource',
							'name' => 'data[Population][' . $recordIndex . '][source]',
							'value' => $row['source']
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
							'name' => 'data[Population][' . $recordIndex . '][age]',
							'value' => $row['age']
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
							'name' => 'data[Population][' . $recordIndex . '][male]',
							'value' => $row['male']
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
							'name' => 'data[Population][' . $recordIndex . '][female]',
							'value' => $row['female']
						));
					?>
				</td>
				<td class="cell_total"><?php echo $row['male'] + $row['female']; ?></td>
				<td><span class="icon_delete" title="'+i18n.General.textDelete+'" onclick="population.removeRow(this)"></span></td>
			</tr>
			<?php 
			$recordIndex ++;
		endforeach;
		?>
	</tbody>
	<?php
endif;
?>