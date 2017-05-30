<?php if (!empty($itemOptions) || !empty($periodOptions)) : ?>
    <div class="toolbar-responsive panel-toolbar">
        <div class="toolbar-wrapper">
            <?php
                // pr($this->request);
                $baseUrl = $this->Url->build([
                    'plugin' => $this->request->params['plugin'],
                    'controller' => $this->request->params['controller'],
                    'action' => $this->request->params['action'],
                    '0' => $this->request->params['pass'][0],
                    'queryString' => $this->request->query['queryString']
                ]);
                $template = $this->ControllerAction->getFormTemplate();
                $this->Form->templates($template);

                if (!empty($itemOptions)) {
                    echo $this->Form->input('item', array(
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $itemOptions,
                        'default' => $selectedItem,
                        'url' => $baseUrl,
                        'data-named-key' => 'item'
                    ));
                }

                if (!empty($periodOptions) && !empty($itemOptions)) {
                    echo $this->Form->input('period', array(
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $periodOptions,
                        'default' => $selectedPeriod,
                        'url' => $baseUrl,
                        'data-named-key' => 'period',
                        'data-named-group' => 'item'
                    ));
                }
            ?>
        </div>
    </div>
<?php endif ?>