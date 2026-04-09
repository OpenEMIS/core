<div class="toolbar-responsive panel-toolbar">
	<div class="toolbar-wrapper" style="display:flex; flex-wrap:nowrap; align-items:center; gap:5px;">
		<?php
			$template = $this->ControllerAction->getFormTemplate();
			$this->Form->templates($template);

			//POCOR-9257: standalone controller — no type param needed, build filter URL directly
			$filterBaseUrl = $this->Url->build([
				'plugin' => $this->request->getParam('plugin'),
				'controller' => $this->request->getParam('controller'),
				'action' => $this->request->getParam('action'),
			]);

			//POCOR-9257: start - webhook filters only (type dropdown removed — no ConfigItemsBehavior)
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
