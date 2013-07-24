
<?php 

if(count($data)>0){
foreach($data as $obj) { ?>
<div class="table_row <?php //echo $obj['status']==0 ? 'inactive' : ''; ?>" row-id="<?php echo $obj['basename']; ?>">
	<div class="table_cell"><?php echo $obj['basename']; ?></div>
	<div class="table_cell center"><?php echo $obj['time'] ; ?></div>
	<div class="table_cell center"><?php echo $obj['size'] ; ?></div>
</div>
<?php }

}else{ ?>
	<div class="table_row>
		<div class="table_cell fullwdith">No Files</div>
	</div>
<?php } ?>
