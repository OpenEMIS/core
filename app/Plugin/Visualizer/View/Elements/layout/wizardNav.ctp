<div class="navbar">
	<div class="navbar-inner">
		<div class="container-wizard">
			<ul class="nav nav-tabs">
				<?php
				$count = 1;
				foreach ($tabs as $key => $tabValue) {
					if($this->action != 'visualization'){
						$icon = $this->Html->tag('i', '', array('class' => 'fa fa-2x ' . $this->Visualizer->getTabIcon($key)));
						//$iconDiv = $this->Html->div('iconDiv', $icon);
					}
					else{
						$icon = $this->Html->tag('i', '', array('class' =>  $this->Visualizer->getVisualizarionTabIcon($key)));
						
						//$iconDiv = '';
					}$iconDiv = $this->Html->div('iconDiv', $icon);
					
					$state = isset($tabValue['state']) ? $tabValue['state'] : 'disabled';
					$tabId = $count;

					$class = (!isset($tabValue['url']) || $state == 'disabled') ? 'void' : '';
					$url = isset($tabValue['url']) ? 'href=\'' . $tabValue['url'] . '\'' : '';

					$tabStr = __($tabValue['name']);
					if (isset($tabValue['showStep']) && $tabValue['showStep']) {
						$tabStr = __('Step ' . $count) . ':<br/>' . $tabStr;
					}
					?>
					<li class='<?php echo $state; ?> center'>
						<a <?php echo $url ?> class='<?php echo $class; ?>' data-toggle="tab<?php echo $tabId; ?>">
							<!--<div style="display: inline-block; vertical-align: top; padding: 3px 5px 0 0;"><?php echo $icon;?></div>-->
							<?php echo $iconDiv;?>
							<div style="display: inline-block; text-align: left"><?php echo $tabStr; ?></div>
						</a>
					</li>
					<?php $count++;
				} ?>
			</ul>
		</div>
	</div>
</div>