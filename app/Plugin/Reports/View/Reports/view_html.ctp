<?php
/**
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Layouts
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

//pr($params);

$row = 1;
if (($handle = fopen($params['path'] . $params['id'], "r")) !== FALSE) {
	?>
	<div class="table reportHtml">
		<?php 
		while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
			$num = count($data);
			if ($row === 1) {
				?>
				<div class="table_head">
					<div class="table_row">
						<?php 
						for ($c = 0; $c < $num; $c++) {
							echo '<div class="table_cell">' . $data[$c] . '</div>';
						}
						?>
					</div>
				</div>
				<?php
			} else if ($row === 2) {
				?>
				<div class="table_body">
					<div class="table_row">
						<?php 
						for ($c = 0; $c < $num-1; $c++) {
							echo '<div class="table_cell">' . $data[$c] . '</div>';
						}
						?>
					</div>
					<?php 
				} else {
					?>
					<div class="table_row">
						<?php 
						for ($c = 0; $c < $num-1; $c++) {
							echo '<div class="table_cell">' . $data[$c] . '</div>';
						}
						?>
					</div>
					<?php 
				}
				
				$row++;
			}
			
			fclose($handle);
			
			if($row > 1){
				?>
					</div>
				<?php 
			}
			?>
	</div>
	<?php 
} else {
	echo 'Error. Failed to open file.';
}
?>