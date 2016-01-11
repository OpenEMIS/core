<?php
$this->extend('OpenEmis./Layout/Panel');

$this->start('panelBody');

	echo $this->Html->css('Map.map', ['block' => true]);
	echo $this->Html->script('//maps.google.com/maps/api/js', ['block' => true]); 
	echo $this->Html->script('Map./js/gmaps/gmaps.min', ['block' => true]);
	echo $this->Html->script('Map./js/fontawesome-markers/fontawesome-markers.min', ['block' => true]);
	echo $this->Html->script('Map.map', ['block' => true]);
?>

	<div id="config" class="hidden">
		
		<span class="default">
			<meta class="lat" data-value="<?= $centerLat?>"/>
			<meta class="lng" data-value="<?= $centerLng?>"/>
			<meta class="zoom" data-value="<?= $defaultZoom?>"/>
		</span>

		<div class="marker-body">
			<p class="name"></p>
			<p class="code"></p>
			<?= $this->Html->link(__('View Details'), ['plugin'=>'Institution', 'controller'=>'Institutions', 'action'=>'view'])?>
		</div>

		<?= $this->Html->link('', ['plugin'=>'Map', 'controller'=>'Map'], ['class'=>'plugin-url'])?>
	</div>
	

	<div class="row dashboard-container">
		<div>
			<h5><?php echo __('Institution Types'); ?></h5>
			<div class="dashboard-content" id="institution-types">

				<?php 
				$colorCount = 0;
				foreach ($institutionTypes as $key=>$type):
				?>

				<div style="float: left;display: block;margin-right: 5px;margin-left: 5px;margin-top: 5px;margin-bottom: 5px;padding: 5px;" class="institution-type" data-type-code="<?= $key?>">		
					<input type="checkbox" class="icheck-input" style="float: left;display: block;" name="" value="<?= $key?>" checked />
					<span style="float: right;display: block;margin-top: 2px;">
						<i class="fa fa-map-marker fa-lg" style="color: <?= $iconColors[$colorCount]?>;"
							data-icon-style-scale="0.4" 
							data-icon-style-stroke-weight="2" 
							data-icon-style-stroke-color="#FFFFFF" 
							data-icon-style-stroke-opacity="0.7" 
							data-icon-style-fill-color="<?= $iconColors[$colorCount]?>" 
							data-icon-style-fill-opacity="0.9"></i> <?= $type ?> (<?= $institutionTypeTotal[$key]?>)
					</span>
				</div>
				
				<?php 
				$colorCount++;
				endforeach;
				?>
			
			</div>
			<h6><?= __('Total Institutions: '). $totalInstitutions ?></h6>
		</div>
	</div>

	<div id="map"></div>

	<div class="clearfix"><br/></div>

	<!-- <div class="hidden"> -->
		<?php
			$json = json_encode($institutionByType);
			echo '<script>';
			echo 'var institutionsData = ';print_r($json);
			echo '</script>';
		?>
	<!-- </div> -->

	<div class="table-wrapper full-width hidden">
		<div class="table-responsive">
		    <table class="table table-curved table-checkable table-input">
				<thead>
					<tr>
						<th><?= __('Code')  ?></th>
						<th><?= __('Name')  ?></th>
						<th><?= __('Type')  ?></th>
						<th><?= __('Address')  ?></th>
						<th><?= __('Postal Code')  ?></th>
						<th><?= __('Longitude')  ?></th>
						<th><?= __('Latitude')  ?></th>
					</tr>
				</thead>

				<tbody id="markers" >
					
					<?php foreach ($institutions as $institution): ?>
						<?php //pr($institution);die; ?>
						<tr class="marker" data-id="<?php echo $institution->id ?>">

							<td class="code"><?php echo $institution->code ?></td>
							<td class="name"><?php echo $institution->name ?></td>
							<td class="type"><?php echo $institution->institution_type_id ?></td>
							<td class="address"><?php echo $institution->address ?></td>
							<td class="postal_code"><?php echo $institution->postal_code ?></td>
							<td class="longitude"><?php echo $institution->longitude ?></td>
							<td class="latitude"><?php echo $institution->latitude ?></td>
						
						</tr>
					
					<?php endforeach; ?>

				</tbody>

			</table>
		</div>
	</div>

<?php
$this->end();
?>

