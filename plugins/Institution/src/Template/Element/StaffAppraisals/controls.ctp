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
                    if (in_array($key, ['academic_period'])) continue;
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
                'class' => 'form-control',
                'label' => false,
                'options' => $academicPeriodList,
                'url' => $baseUrl,
                'default' => $selectedAcademicPeriod,
                'data-named-key' => 'academic_period',
                'escape' => false
            ];
            if (!empty($dataNamedGroup)) {
                $inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
                $dataNamedGroup[] = 'academic_period';
            }
            echo $this->Form->input('academic_period', $inputOptions);
        ?>
    </div>
</div>
