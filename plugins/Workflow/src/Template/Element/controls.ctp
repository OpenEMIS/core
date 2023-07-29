<?php if (!empty($filterOptions) || !empty($categoryOptions) ||  !empty($areaOptions) || !empty($periodsOptions) || !empty($monthOptions) ) :  ?>
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
                        //if (in_array($key, ['filter', 'category'])) continue; //POCOR-5695
                        if (in_array($key, ['filter'])){ //POCOR-5695
                            if (empty($filterOptions)) {
                                echo $this->Form->hidden($key, [
                                    'value' => $value,
                                    'data-named-key' => $key
                                ]);
                                $dataNamedGroup[] = $key;
                            }
                            if (!empty($filterOptions)) {
                                $filterOptionsDefault = $value;
                            }
                        }
                        if (in_array($key, ['category'])){ //POCOR-5695
                            if (empty($categoryOptions)) {
                                echo $this->Form->hidden($key, [
                                    'value' => $value,
                                    'data-named-key' => $key
                                ]);
                                $dataNamedGroup[] = $key;
                            }
                            if (!empty($categoryOptions)) {
                                $categoryOptionsDefault = $value;
                            }
                        }
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
                    }
                    if($filterOptionsDefault){
                        $inputOptions['default'] = $filterOptionsDefault;
                        $inputOptions['value'] = $filterOptionsDefault;
                    }
                    $dataNamedGroup[] = 'filter';
//                    $this->log($inputOptions, 'debug');
                    echo $this->Form->input('filter', $inputOptions);
                }

                if (!empty($categoryOptions)) {
                    $inputOptions = [
                        'class' => 'form-control',
                        'label' => false,
                        'options' => $categoryOptions,
                        'url' => $baseUrl,
                        'data-named-key' => 'category',
                        'data-named-group' => 'level, area, period, month', //POCOR-5695
                        'escape' => false //POCOR-5695
                    ];
                    if (!empty($dataNamedGroup)) {
                        $inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
                    }
                    if($categoryOptionsDefault){
                        $inputOptions['default'] = $categoryOptionsDefault;
                        $inputOptions['value'] = $categoryOptionsDefault;
                    }

                    $dataNamedGroup[] = 'category';
                    echo $this->Form->input('category', $inputOptions);
                }
                //POCOR-5695 starts
                if($this->request->params['action'] == 'Sessions' || $this->request->params['action'] == 'Results'){
                    if (!empty($levelOptions)) {
                        $inputOptions = [
                            'class' => 'form-control',
                            'label' => false,
                            'options' => $levelOptions,
                            'url' => $baseUrl,
                            'data-named-key' => 'level',
                            'data-named-group' => 'category, area, period, month',
                            'escape' => false
                        ];
                        if (!empty($dataNamedGroup)) {
                            $inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
                        }
                        $dataNamedGroup[] = 'level';
                        echo $this->Form->input('level', $inputOptions);
                    }
                    if (!empty($areaOptions)) {
                        $inputOptions = [
                            'class' => 'form-control',
                            'label' => false,
                            'options' => $areaOptions,
                            'url' => $baseUrl,
                            'data-named-key' => 'area',
                            'data-named-group' => 'category, level, period, month',
                            'escape' => false
                        ];
                        if (!empty($dataNamedGroup)) {
                            $inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
                        }
                        $dataNamedGroup[] = 'area';
                        echo $this->Form->input('area', $inputOptions);
                    }

                    if (!empty($periodsOptions)) {
                        $inputOptions = [
                            'class' => 'form-control',
                            'label' => false,
                            'options' => $periodsOptions,
                            'url' => $baseUrl,
                            'data-named-key' => 'period',
                            'data-named-group' => 'category, level, area, month',
                            'escape' => false
                        ];
                        if (!empty($dataNamedGroup)) {
                            $inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
                        }
                        $dataNamedGroup[] = 'period';
                        echo $this->Form->input('period', $inputOptions);
                    }

                    if (!empty($monthOptions)) {
                        $inputOptions = [
                            'class' => 'form-control',
                            'label' => false,
                            'options' => $monthOptions,
                            'url' => $baseUrl,
                            'data-named-key' => 'month',
                            'data-named-group' => 'category, level, area, period',
                            'escape' => false
                        ];
                        if (!empty($dataNamedGroup)) {
                            $inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
                        }
                        $dataNamedGroup[] = 'month';
                        echo $this->Form->input('month', $inputOptions);
                    }
                }
                //POCOR-5695 ends
            ?>
        </div>
    </div>
<?php endif ?>
