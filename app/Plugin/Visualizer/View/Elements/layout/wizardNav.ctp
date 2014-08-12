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
					
					$tabStr = __($tabValue['name']);
					if(isset($tabValue['showStep']) && $tabValue['showStep']){
						$tabStr = __('Step '.$count).':<br/>'.$tabStr;
					}
						
					?>
					<li class='<?php echo $state; ?> center'><a <?php echo $url?> class='<?php echo $class; ?>' data-toggle="tab<?php echo $tabId; ?>"><?php echo $tabStr; ?></a></li>
						<?php
						$count++;
					}
					?>
			</ul>
		</div>
	</div>
</div>