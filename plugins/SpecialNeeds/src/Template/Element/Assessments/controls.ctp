<?php if (!empty($monthOptions) || !empty($periodsOptions)) : ?>
    <div class="toolbar-responsive panel-toolbar">
        <div class="toolbar-wrapper">
        <?php
            if (!empty($periodsOptions)){
            
                $url = [
                    'plugin' => $this->request->params['plugin'],
                    'controller' => $this->request->params['controller'],
                    'action' => $this->request->params['action']
                ];
                if (!empty($this->request->pass)) {
                    $url = array_merge($url, $this->request->pass);
                }

                $dataNamedGroup = [];
                if (!empty($this->request->query)) {
                    foreach ($this->request->query as $key => $value) {
                        if (in_array($key, ['period'])) continue;
                        echo $this->Form->hidden($key, [
                            'value' => $value,
                            'data-named-key' => $key
                        ]);
                        $dataNamedGroup[] = $key;
                    }
                }

                $baseUrl = $this->Url->build($url);
                $template = $this->ControllerAction->getFormTemplate();
                $this->Form->templates($template);

                $inputOptions = [
                    'type' => 'select',
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $periodsOptions,
                    'default' => $selectedPeriods,
                    'url' => $baseUrl,
                    'data-named-key' => 'period',
                    'id'=>'period_dropdown'
                ];

                if (!empty($dataNamedGroup)) {
                    $inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
                    $dataNamedGroup[] = 'period';
                }

                if (!empty($periodsOptions)) {
                    echo $this->Form->input('period', $inputOptions);
                }

            }
            ?>

<?php
            if (!empty($monthOptions)){
                $url = [
                    'plugin' => $this->request->params['plugin'],
                    'controller' => $this->request->params['controller'],
                    'action' => $this->request->params['action']
                ];
                if (!empty($this->request->pass)) {
                    $url = array_merge($url, $this->request->pass);
                }

                $dataNamedGroup1 = [];
                if (!empty($this->request->query)) {
                    foreach ($this->request->query as $key => $value) {
                        if (in_array($key, ['month'])) continue;
                        echo $this->Form->hidden($key, [
                            'value' => $value,
                            'data-named-key' => $key
                        ]);
                        $dataNamedGroup1[] = $key;
                    }
                }

                $baseUrl = $this->Url->build($url);
                $template = $this->ControllerAction->getFormTemplate();
                $this->Form->templates($template);

                $inputOptions = [
                    'type' => 'select',
                    'class' => 'form-control',
                    'label' => false,
                    'options' => $monthOptions,
                    'default' => $selectedmonth,
                    'url' => $baseUrl,
                    'data-named-key' => 'month',
                    'id'=>'month_dropdown'
                ];

                if (!empty($dataNamedGroup1)) {
                    $inputOptions['data-named-group'] = implode(',', $dataNamedGroup1);
                    $dataNamedGroup1[] = 'month';
                }

                if (!empty($monthOptions)) {
                    echo $this->Form->input('month', $inputOptions);
                }

            }
            ?>

           
        </div>
    </div>
<?php endif ?>
