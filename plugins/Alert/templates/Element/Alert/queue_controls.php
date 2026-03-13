<?php if (!empty($statusOptions) || !empty($channelOptions) || !empty($alertTypeOptions)) : ?>
<div class="toolbar-responsive panel-toolbar">
    <div class="toolbar-wrapper">
        <?php
            $baseUrl = $this->Url->build(array(
                'plugin' => $this->request->getParam('plugin'),
                'controller' => $this->request->getParam('controller'),
                'action' => $this->request->getParam('action'),
                'index'
            ));

            $template = $this->ControllerAction->getFormTemplate();
            $this->Form->templates($template);

            // Status filter
            if (!empty($statusOptions)) {
                echo $this->Form->input('status', array(
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $statusOptions,
                    'default' => $selectedStatus,
                    'url' => $baseUrl,
                    'data-named-key' => 'status',
                    'data-named-group' => 'channel,alert_type'
                ));
            }

            // Channel filter
            if (!empty($channelOptions)) {
                echo $this->Form->input('channel', array(
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $channelOptions,
                    'default' => $selectedChannel,
                    'url' => $baseUrl,
                    'data-named-key' => 'channel',
                    'data-named-group' => 'status,alert_type'
                ));
            }

            // Alert Type filter
            if (!empty($alertTypeOptions)) {
                echo $this->Form->input('alert_type', array(
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $alertTypeOptions,
                    'default' => $selectedAlertType,
                    'url' => $baseUrl,
                    'data-named-key' => 'alert_type',
                    'data-named-group' => 'status,channel'
                ));
            }
        ?>
    </div>
</div>
<?php endif ?>
