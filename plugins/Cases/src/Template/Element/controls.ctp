<?php if (!empty($featureOptions)) : ?>
    <div class="toolbar-responsive panel-toolbar">
        <div class="toolbar-wrapper">
            <?php
                $baseUrl = $this->Url->build([
                    'plugin' => $this->request->params['plugin'],
                    'controller' => $this->request->params['controller'],
                    'action' => $this->request->params['action']
                ]);
                $template = $this->ControllerAction->getFormTemplate();
                $this->Form->templates($template);

                if (!empty($featureOptions)) {
                    echo $this->Form->input('feature', array(
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $featureOptions,
                        'default' => $selectedFeature,
                        'url' => $baseUrl,
                        'data-named-key' => 'feature'   
                    ));
                }
            ?>
        </div>
    </div>
<?php endif ?>
