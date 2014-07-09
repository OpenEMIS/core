<?php 
foreach ($data AS $row):
	?>
	<tr class="<?php echo $row['data_source'] == 0 ? '' : 'row_estimate'; ?>">
		<td><?php echo $row['source']; ?></td>
		<td><?php echo $row['age']; ?></td>
		<td><?php echo $row['male']; ?></td>
		<td><?php echo $row['female']; ?></td>
		<td class="cell_total"><?php echo $row['male'] + $row['female']; ?></td>
	</tr>
	<?php
endforeach;
?>