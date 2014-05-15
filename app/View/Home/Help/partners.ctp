<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));

$this->extend('/Elements/layout/container');
$this->assign('contentHeader', __($subTitle));

$this->start('contentBody');
?>

<div class="table-responsive">
	<table class="table table-striped table-bordered">
		<tbody>
			<?php
			$max = 1;
			for($i=0;$i<count($images);$i++){
				$insertRow = false;
				if($i%$max==0){
					$insertRow = true;
				}
				if($insertRow) {
					if($i!=0) {
						echo '</tr>';
					}
					echo '<tr>';
				}
				echo '<td style="text-align:left;">';
				echo '<b>'.$images[$i]["name"].'</b>';
				echo '</td>';
				echo '<td>';
				echo $this->Html->image(
					array("controller" => "Config", "action" => "fetchImage", $images[$i]["id"]),
					array('style' => "height:186px;padding:1px;")
				);
				echo '</td>';
			}
			?>
			</tr>
		</tbody>
	</table>
</div>

<?php $this->end(); ?>
