<div class="navbar">
	<div class="navbar-inner">
		<div class="container-wizard">
			<ul class="nav nav-tabs">
				<?php
				$count = 1;
				foreach ($tabs as $key => $tabValue) {
					$state = isset($tabValue['state']) ? $tabValue['state'] : 'disabled';
					$tabId = $count;
					
					$class = (!isset($tabValue['url']) || $state == 'disabled')?'void':'';
					$url = isset($tabValue['url'])? 'href=\''.$tabValue['url'].'\'':'';
					?>
					<li class='<?php echo $state; ?> center'><a <?php echo $url?> class='<?php echo $class; ?>' data-toggle="tab<?php echo $tabId; ?>"><?php echo __($tabValue['name']); ?></a></li>
						<?php
						$count++;
					}
					?>
			</ul>
		</div>
	</div>
</div>