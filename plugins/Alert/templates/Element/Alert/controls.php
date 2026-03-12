<?php if (!empty($featureOptions) || !empty($statusOptions) || !empty($channelOptions)) : ?>
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

                // Feature filter
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
