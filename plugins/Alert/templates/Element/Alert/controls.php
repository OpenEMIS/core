<?php if (!empty($featureOptions) || !empty($statusOptions) || !empty($channelOptions) || !empty($alertTypeOptions)) : ?>
    <div class="toolbar-responsive panel-toolbar">
        <div class="toolbar-wrapper">
            <?php
                $baseUrl = $this->Url->build([
                    'plugin' => $this->request->getParam('plugin'),
                    'controller' => $this->request->getParam('controller'),
                    'action' => $this->request->getParam('action'),
                    'index'
                ]);

                $template = $this->ControllerAction->getFormTemplate();
                $this->Form->templates($template);

                // Feature filter (for AlertLogs)
                if (!empty($featureOptions)) {
                    echo $this->Form->input('feature', [
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $featureOptions,
                        'default' => $selectedFeature ?? null,
                        'url' => $baseUrl,
                        'data-named-key' => 'feature'
                    ]);
                }

                // Alert Type filter (for AlertsQueue)
                if (!empty($alertTypeOptions)) {
                    echo $this->Form->input('alert_type', [
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $alertTypeOptions,
                        'default' => $selectedAlertType ?? null,
                        'url' => $baseUrl,
                        'data-named-key' => 'alert_type'
                    ]);
                }

                // Status filter
                if (!empty($statusOptions)) {
                    echo $this->Form->input('status', [
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $statusOptions,
                        'default' => $selectedStatus ?? null,
                        'url' => $baseUrl,
                        'data-named-key' => 'status'
                    ]);
                }

                // Channel filter
                if (!empty($channelOptions)) {
                    echo $this->Form->input('channel', [
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $channelOptions,
                        'default' => $selectedChannel ?? null,
                        'url' => $baseUrl,
                        'data-named-key' => 'channel'
                    ]);
                }
            ?>
        </div>
    </div>
<?php endif ?>
