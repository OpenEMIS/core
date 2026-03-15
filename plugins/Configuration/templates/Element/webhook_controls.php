<div class="toolbar-responsive panel-toolbar">
	<div class="toolbar-wrapper" style="display:flex; flex-wrap:nowrap; align-items:center; gap:5px;">
		<?php
			$template = $this->ControllerAction->getFormTemplate();
			$this->Form->templates($template);

			//POCOR-9257: type dropdown base URL (no type param — JS adds it via data-named-key)
			$typeBaseUrl = $this->Url->build([
				'plugin' => $this->request->getParam('plugin'),
				'controller' => $this->request->getParam('controller'),
				'action' => $this->request->getParam('action'),
			]);

			//POCOR-9257: webhook filter base URL preserves type=49 so Configurations scope is kept
			$typeParam = $this->request->getQuery('type');
			$filterBaseUrl = $this->Url->build([
				'plugin' => $this->request->getParam('plugin'),
				'controller' => $this->request->getParam('controller'),
				'action' => $this->request->getParam('action'),
				'?' => array_filter(['type' => $typeParam]),
			]);

			//POCOR-9257: start - combined type filter + webhook filters in one toolbar line
			// Type dropdown — rendered from typeOptions set by ConfigItemsBehavior
			if (!empty($typeOptions)) {
				echo $this->Form->select('config_item_type', ['-1' => __('-- Select Type --')] + $typeOptions, [
					'class' => 'form-control',
					'style' => 'flex:0 0 auto; min-width:150px;',
					'value' => $this->request->getQuery('type'),
					'url' => $typeBaseUrl,
					'data-named-key' => 'type',
					// No data-named-group: changing type navigates away, don't preserve webhook filters
				]);
			}

			// Webhook filters — share filterBaseUrl which keeps type=49
			echo $this->Form->select('event_key', $eventKeyOptions, [
				'class' => 'form-control',
				'style' => 'flex:0 0 auto; min-width:150px;',
				'value' => $selectedEventKey ?? 'all',
				'url' => $filterBaseUrl,
				'data-named-key' => 'event_key',
				'data-named-group' => 'status,method,external_data_source_id',
			]);
			echo $this->Form->select('status', $statusOptions, [
				'class' => 'form-control',
				'style' => 'flex:0 0 auto; min-width:120px;',
				'value' => $selectedStatus ?? 'all',
				'url' => $filterBaseUrl,
				'data-named-key' => 'status',
				'data-named-group' => 'event_key,method,external_data_source_id',
			]);
			echo $this->Form->select('method', $methodOptions, [
				'class' => 'form-control',
				'style' => 'flex:0 0 auto; min-width:110px;',
				'value' => $selectedMethod ?? 'all',
				'url' => $filterBaseUrl,
				'data-named-key' => 'method',
				'data-named-group' => 'event_key,status,external_data_source_id',
			]);
			echo $this->Form->select('external_data_source_id', $externalSourceOptions, [
				'class' => 'form-control',
				'style' => 'flex:0 0 auto; min-width:130px;',
				'value' => $selectedExternalSource ?? 'all',
				'url' => $filterBaseUrl,
				'data-named-key' => 'external_data_source_id',
				'data-named-group' => 'event_key,status,method',
			]);
			//POCOR-9257: end
		?>
	</div>
</div>
