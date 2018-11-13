<?php if (!empty($academicPeriodOptions)) : ?>
    <div class="toolbar-responsive panel-toolbar">
        <div class="toolbar-wrapper">
            <?php
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
                        if (in_array($key, ['academic_period_id'])) continue;
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
                    'options' => $academicPeriodOptions,
                    'default' => $selectedAcademicPeriod,
                    'url' => $baseUrl,
                    'data-named-key' => 'academic_period_id'
                ];

                if (!empty($dataNamedGroup)) {
                    $inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
                    $dataNamedGroup[] = 'academic_period_id';
                }

                if (!empty($academicPeriodOptions)) {
                    echo $this->Form->input('academic_period_id', $inputOptions);
                }
            ?>
        </div>
    </div>
<?php endif ?>
