<?php if (!empty($filterOptions) || !empty($categoryOptions)) : ?>
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
                        if (in_array($key, ['filter', 'category'])) continue;
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

                if (!empty($filterOptions)) {
                    $inputOptions = [
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $filterOptions,
                        'url' => $baseUrl,
                        'data-named-key' => 'filter'
                    ];
                    if (!empty($dataNamedGroup)) {
                        $inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
                        $dataNamedGroup[] = 'filter';
                    }
                    echo $this->Form->input('filter', $inputOptions);
                }

                if (!empty($categoryOptions)) {
                    $inputOptions = [
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $categoryOptions,
                        'url' => $baseUrl,
                        'data-named-key' => 'category'
                    ];
                    if (!empty($dataNamedGroup)) {
                        $inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
                        $dataNamedGroup[] = 'category';
                    }
                    echo $this->Form->input('category', $inputOptions);
                }
            ?>
        </div>
    </div>
<?php endif ?>
