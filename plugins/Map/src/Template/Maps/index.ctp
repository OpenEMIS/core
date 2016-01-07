<?php
$this->extend('OpenEmis./Layout/Panel');

$this->start('panelBody');

	echo $this->Html->css('Map.map', ['block' => true]);
	echo $this->Html->script('http://maps.google.com/maps/api/js', ['block' => true]); 
	echo $this->Html->script('Map./js/gmaps/gmaps.min', ['block' => true]);
	echo $this->Html->script('Map./js/fontawesome-markers/fontawesome-markers.min', ['block' => true]);
	echo $this->Html->script('Map.map', ['block' => true]);
?>
	<div class="row dashboard-container">
		<div>
			<h5><?php echo __('Institution Type'); ?></h5>
			<div class="dashboard-content">

				<?php foreach ($institutionTypes as $key=>$type):?>
				<div style="float: left;display: block;margin-right: 5px;margin-left: 5px;margin-top: 5px;margin-bottom: 5px;padding: 5px;" >		
					<input type="checkbox" class="icheck-input" style="float: left;display: block;" name="" value="<?= $key?>"/>
					<span style="float: right;display: block;"><?= $type ?></span>
				</div>
				<?php endforeach;?>
			
			</div>
		</div>
	</div>

	<div id="map" class="large" default-lng="" default-lat=""></div>

	<div class="hidden">
		<span class="marker_body">
			<p></p>
			
		</span>

		<span class="zoom">19</span>
	</div>
	
	<div class="clearfix"><br/></div>

	<div class="table-wrapper full-width hidden">
		<div class="table-responsive">
		    <table class="table table-curved table-checkable table-input">
				<thead>
					<tr>
						<th><?= $this->Label->get( $model->aliasField('code') ) ?></th>
						<th><?= $this->Label->get( $model->aliasField('name') ) ?></th>
						<th><?= $this->Label->get( $model->aliasField('address') ) ?></th>
						<th><?= $this->Label->get( $model->aliasField('postal_code') ) ?></th>
						<th><?= $this->Label->get( $model->aliasField('longitude') ) ?></th>
						<th><?= $this->Label->get( $model->aliasField('latitude') ) ?></th>
					</tr>
				</thead>

				<tbody id="markers" >
					
					<?php foreach ($institutions as $institution): ?>

						<tr class="marker" id="<?= $institution->id ?>">

							<td class="code"><?= $institution->code ?></td>
							<td class="name"><?= $institution->name ?></td>
							<td class="address"><?= $institution->address ?></td>
							<td class="postal_code"><?= $institution->postal_code ?></td>
							<td class="longitude"><?= $institution->longitude ?></td>
							<td class="latitude"><?= $institution->latitude ?></td>
						
						</tr>
					
					<?php endforeach; ?>

				</tbody>

			</table>
		</div>
	</div>

<?php
$this->end();
?>

