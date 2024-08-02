<?php if (!empty($itemOptions) || !empty($periodOptions)) : ?>
    <div class="toolbar-responsive panel-toolbar">
        <div class="toolbar-wrapper">
            <?php
                // pr($this->request);
                $baseUrl = $this->Url->build([
                    'plugin' => $this->request->getParam('plugin'),
                    'controller' => $this->request->getParam('controller'),
                    'action' => $this->request->getParam('action'),
                    '0' => $this->request->getAttribute('params')['pass'][0],
                    'queryString' => $this->request->getQuery('queryString')
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