<?php 
echo $this->Html->css('table', 'stylesheet', array('inline' => false));
echo $this->Html->css('home', 'stylesheet', array('inline' => false));
?>

<?php echo $this->element('breadcrumb'); ?>

<div id="user_details" class="content_wrapper">
	<h1><?php echo __($subTitle); ?></h1>
		<div class="table help">
			<div class="table_body" style="display:table-cell;">
				<?php
				$max = 1;
				for($i=0;$i<count($images);$i++){
					$insertRow = false;
					if($i%$max==0){
						$insertRow = true;
					}
				?>
				<?php if($insertRow){?>
				<?php if($i!=0){ ?>
				</div>
				<?php } ?>
				<div class="table_row">
				<?php } ?>
					<div class="table_cell cell_name" style="text-align:left;">
					<?php
						 echo '<b>'.$images[$i]["name"].'</b>';
					?>
					</div>
					<div class="table_cell">
					<?php
						 echo $this->Html->image(array("controller" => "Config", "action" => "fetchImage", $images[$i]["id"]), array(
			                'style' => "height:186px;padding:1px;"
			            ));
					?>
					</div>
				<?php
				}
				?>
				</div>
			</div>
		</div>
</div>
