<?php

/*
  @OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

  OpenEMIS
  Open Education Management Information System

  Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by the Free Software Foundation
  , either version 3 of the License, or any later version.  This program is distributed in the hope
  that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
  or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should
  have received a copy of the GNU General Public License along with this program.  If not, see
  <http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
 */

App::uses('AppHelper', 'View/Helper');

class RubricsViewHelper extends AppHelper {

    public $helpers = array('Form');
    public $defaultNoOfColumns = 4;
    public $defaultNoOfRows = 5;
    public $fieldSetupOptions = array();
    public $wrapperName = 'RubricsTemplateDetail';

    /*
     * Div format
     */

    public function insertRubricHeader($data = NULL, $key = 0, $mode = 'edit') {
        $modalName = key($data);
        $isFirst = ($key == 0 ) ? 'first' : "default";
        //  $display = '<tr>';
        // pr($data);
        $display = '';
        if ($mode == 'edit') {
            $content = '';

            $_fieldOptions = $this->fieldSetupOptions;
            $_fieldOptions['placeholder'] = 'Header / Sub-Header / Title';
            $content = $this->Form->input($this->wrapperName . '.' . $key . '.' . $modalName . '.title', $_fieldOptions);

            if (!empty($data[$modalName]['id'])) {
                $display .= $this->Form->hidden($this->wrapperName . '.' . $key . '.' . $modalName . '.id');
            }
            if (!empty($data[$modalName]['rubric_template_header_id'])) {
                $display .= $this->Form->hidden($this->wrapperName . '.' . $key . '.' . $modalName . '.rubric_template_header_id');
            }

          //  if (!empty($data[$modalName]['order'])) {
                $display .= $this->Form->hidden($this->wrapperName . '.' . $key . '.' . $modalName . '.order', array('id' => 'order', 'value' => ($key+1)));
           // }
            
           
        } else {
            $display .= '<div class="cell cell-rubric-order">' . $data[$modalName]['display_num'] . '</div>';
            $content = !empty($data[$modalName]['title']) ? $data[$modalName]['title'] : "";
        }


        $display .= '<div class="cell cell-rubric-row input ' . $isFirst . '" colspan="' . $this->defaultNoOfColumns . '">' . $content . '</div>';


        if ($mode == 'edit') {
            $display .= $this->getOrderFormat($key);
        }
        //$display .= '</tr>';

        return $display;
    }

    public function insertRubricQuestionRow($data = NULL, $key = 0, $mode = 'edit') {
        $headerColumnData = $data['columnHeader'];
        $isFirst = ($key == 0 ) ? 'first' : "default";

        $width = 607 - 2;
        $singleColumnSize = (1 / $this->defaultNoOfColumns) * $width;
       
        $display = '';
       /* if (!empty($data['RubricsTemplateItem']['order'])) {
            $display .= $this->Form->hidden($this->wrapperName . '.' . $key . '.RubricsTemplateItem.' . 'order', array('id' => 'order', 'value' => $data['RubricsTemplateItem']['order']));
        }
        else{*/
            $display .= $this->Form->hidden($this->wrapperName . '.' . $key . '.RubricsTemplateItem.' . 'order', array('id' => 'order', 'value' => ($key+1)));
        //}

        $display .= '<div class="cell cell-rubric-row cell-rubric-row-custom cell-rubric-row-height ' . $isFirst . '">'; //minus 1 px due to margine-left : -1px;
        $display .= $this->getRowFormat('Criteria', $singleColumnSize, true);
        $display .= $this->getRowFormat('Descriptors', $singleColumnSize * ($this->defaultNoOfColumns - 1));
        $display .= $this->getRowFormat('Level', $singleColumnSize, true);

        foreach ($headerColumnData as $column) {
            $display .= $this->getRowFormat($column['RubricsTemplateColumnInfo']['name'], $singleColumnSize);
        }

        $criteriaQuestion = $this->_setupRubricQuestionField($key, $data, $mode);
        $display .= $this->getTextareaFormat($criteriaQuestion, $singleColumnSize, true);

        foreach ($headerColumnData as $k => $column) {
            $criteriaAnswer = '';
            if (!empty($data['RubricsTemplateAnswer'])) { //With template answer
                // pr($data['RubricsTemplateAnswer']);
                $isNotMatch = true;
                for ($i = 0; $i < $this->defaultNoOfColumns - 1; $i++) {

                    if (!empty($data['RubricsTemplateAnswer'][$i]['rubrics_template_column_info_id'])) {
                        if ($column['RubricsTemplateColumnInfo']['id'] == $data['RubricsTemplateAnswer'][$i]['rubrics_template_column_info_id']) {
                            $isNotMatch = false;
                            $criteriaAnswer = $this->_setupRubricAnswerField($key, $i, $column['RubricsTemplateColumnInfo']['id'], $data, $mode);
                        }
                    }

                    if ($isNotMatch) {
                        $criteriaAnswer = $this->_setupRubricAnswerField($key, $i, $column['RubricsTemplateColumnInfo']['id'], $data, $mode);
                    }
                }
            } else {
                $criteriaAnswer = $this->_setupRubricAnswerField($key, $k, $column['RubricsTemplateColumnInfo']['id'], $data, $mode);
            }
            $display .= $this->getTextareaFormat($criteriaAnswer, $singleColumnSize);
        }

        $display .= $this->getRowFormat('Weighting', $width, true);
        $display .= $this->getRowFormat('&nbsp;', $singleColumnSize, true);

        foreach ($headerColumnData as $column) {
            $display .= $this->getRowFormat($column['RubricsTemplateColumnInfo']['weighting'], $singleColumnSize);
        }

        $display .= '</div>';
        if ($mode == 'edit') {
            $display .= $this->getOrderFormat($key, true);
        }
        return $display;
    }

    private function getOrderFormat($key, $expandHeight = false) {
        $isFirst = ($key == 0 ) ? 'first' : "default";
        $height = ($expandHeight) ? 'cell-rubric-row-height' : "";

        $display = '<div class="cell ' . $height . ' cell-rubric-order  ' . $isFirst . '">
				<span class="icon_up" onclick="jsList.doSort(this)"></span>
				<span class="icon_down" onclick="jsList.doSort(this)"></span>
			</div>';

        return $display;
    }

    private function getRowFormat($name, $width, $isFirst = false) {
        $isFirst = ($isFirst) ? 'first' : "";
        $display = '<div class="cell-rubric-col ' . $isFirst . '" style="width:' . $width . 'px"><span>' . $name . '</span></div>';

        return $display;
    }

    private function getTextareaFormat($name, $width, $isFirst = false) {
        $isFirst = ($isFirst) ? 'first' : "";

        $display = '<div class="cell-rubric-col cell-rubric-col-textarea-height ' . $isFirst . '" style="width:' . $width . 'px"><span>' . $name . '</span></div>';
        return $display;
    }

    /* --------- End Div Format-------------- */

    /*
     * Table format
     * 
     */

    public function insertRubricTableHeader($data = NULL, $key = 0, $mode = 'edit') {
        $modalName = key($data);
        $isFirst = ($key == 0 ) ? 'first' : "default";
        $display = '<tr>';
        // pr($data);
        $display = '';
        if ($mode == 'edit') {
            $content = '';

            $_fieldOptions = $this->fieldSetupOptions;
            $_fieldOptions['placeholder'] = 'Header / Sub-Header / Title';
            $content = $this->Form->input($this->wrapperName . '.' . $key . '.' . $modalName . '.title', $_fieldOptions);

            if (!empty($data[$modalName]['id'])) {
                $display .= $this->Form->hidden($this->wrapperName . '.' . $key . '.' . $modalName . '.id');
            }
            if (!empty($data[$modalName]['rubric_template_header_id'])) {
                $display .= $this->Form->hidden($this->wrapperName . '.' . $key . '.' . $modalName . '.rubric_template_header_id');
            }

            if (!empty($data[$modalName]['order'])) {
                $display .= $this->Form->hidden($this->wrapperName . '.' . $key . '.' . $modalName . '.order', array('id' => 'order'));
            }
        } else {
            $display .= '<td class="cell cell-rubric-order">' . $data[$modalName]['display_num'] . '</td>';
            $content = !empty($data[$modalName]['title']) ? $data[$modalName]['title'] : "";
        }


        $display .= '<td class="cell cell-rubric-row input ' . $isFirst . '" colspan="' . $this->defaultNoOfColumns . '">' . $content . '</td>';

        $display .= '</tr>';

        return $display;
    }

    public function insertRubricTableQuestionRow($data = NULL, $key = 0, $mode = 'edit') {
        $headerColumnData = $data['columnHeader'];

        $display = $this->_setupQuestionCriteriaHeaderField($headerColumnData, 'action', $data, $mode);

        //3rd row
        $criteriaQuestion = $this->_setupRubricQuestionField($key, $data, $mode);
        $display .= '<tr><td>' . $criteriaQuestion . '</td>';

        foreach ($headerColumnData as $k => $column) {
            $criteriaAnswer = '';
            if (!empty($data['RubricsTemplateAnswer'])) { //With template answer
                for ($i = 0; $i < $this->defaultNoOfColumns - 1; $i++) {
                    if (!empty($data['RubricsTemplateAnswer'][$i]['rubrics_template_column_info_id'])) {
                        if ($column['RubricsTemplateColumnInfo']['id'] == $data['RubricsTemplateAnswer'][$i]['rubrics_template_column_info_id']) {
                            $criteriaAnswer = $this->_setupRubricAnswerField($key, $i, $column['RubricsTemplateColumnInfo']['id'], $data, $mode);
                        }
                    }
                }
            } else {
                $criteriaAnswer = $this->_setupRubricAnswerField($key, $k, $column['RubricsTemplateColumnInfo']['id'], $data, $mode);
            }
            $display .= '<td>' . $criteriaAnswer . '</td>';
        }

        $display .= '</tr>';

        $display .= $this->_setupWeightingField($headerColumnData);
        return $display;
    }

    function _setupQuestionCriteriaHeaderField($headerColumnData, $type, $data = NULL, $mode = NULL) {
        $display = '<tr>';
        if ($mode != 'edit') {
            $display = '<td rowspan="' . $this->defaultNoOfRows . '" class="rubric-left-col-nav">' . $data['RubricsTemplateItem']['display_num'] . '</td>';
        }
        $display .= '<td class="rubric-col-header">Criteria</td>';
        $display .= '<td class="rubric-col-header" colspan="' . ($this->defaultNoOfColumns - 1) . '">Descriptors</td>';
        if ($type == 'action' && $mode == 'edit') {
            $display .= '<td rowspan="' . $this->defaultNoOfRows . '" class="rubric-left-col-nav">&nbsp;' . '</td>';
        }
        $display .= '</tr>';

        $display .= '<tr><td class="rubric-col-header">Level</td>';
        //for($i = 0; $i < count($headerColumnData); $i++){
        foreach ($headerColumnData as $column) {
            $display .= '<td>' . $column['RubricsTemplateColumnInfo']['name'] . '</td>';
        }

        $display .= '</tr>';

        return $display;
    }

    function _setupWeightingField($headerColumnData) {
        //-------------------4th row-------------------
        $display = '<tr>
                        <td class="rubric-col-header" colspan="' . $this->defaultNoOfColumns . '">Weightings</td>
                </tr>';

        //-------------------5th row-------------------
        $display .= '<tr><td></td>';
        foreach ($headerColumnData as $column) {
            $display .= '<td>' . $column['RubricsTemplateColumnInfo']['weighting'] . '</td>';
        }
        $display .= '</tr>';


        return $display;
    }

    /* End tablr */

    function _setupRubricQuestionField($key, $data, $mode) {
        if ($mode == 'edit') {
           
            $fieldName = $this->wrapperName . '.' . $key . '.RubricsTemplateItem.';

            $_fieldOptions = $this->fieldSetupOptions;
            $_fieldOptions['placeholder'] = 'Criteria';
            $_fieldOptions['type'] = 'textarea';

            $criteriaQuestion = $this->Form->input($fieldName . 'title', $_fieldOptions);
            if (!empty($data['RubricsTemplateItem']['id'])) {
                $criteriaQuestion .= $this->Form->hidden($fieldName . 'id');
            }
            if (!empty($data['RubricsTemplateItem']['rubric_template_subheader_id'])) {
                $criteriaQuestion .= $this->Form->hidden($fieldName . 'rubric_template_subheader_id');
            }
            
            
        } else {
            $criteriaQuestion = (!empty($data['RubricsTemplateItem']['title'])) ? $data['RubricsTemplateItem']['title'] : '';
        }

        return $criteriaQuestion;
    }

    function _setupRubricAnswerField($key, $i, $criteriaId, $data, $mode) {
        if ($mode == 'edit') {
            $fieldName = $this->wrapperName . '.' . $key . '.RubricsTemplateAnswer.' . $i;
 //pr($data);
            $_fieldOptions = $this->fieldSetupOptions;
            $_fieldOptions['placeholder'] = 'Criteria Level Description';
            $_fieldOptions['type'] = 'textarea';

            $criteriaAnswer = $this->Form->input($fieldName . '.title', $_fieldOptions);

            if (!empty($data['RubricsTemplateAnswer'][$i]['rubric_template_item_id'])) {
                $criteriaAnswer .= $this->Form->hidden($fieldName . '.rubric_template_item_id');
            }
            else if(!empty($data['RubricsTemplateItem']['id'])){
                $criteriaAnswer .= $this->Form->hidden($fieldName . '.rubric_template_item_id', array('value'=> $data['RubricsTemplateItem']['id']));
            }
            if (!empty($data['RubricsTemplateAnswer'][$i]['id'])) {
                $criteriaAnswer .= $this->Form->hidden($fieldName . '.id');
            }
            $answerOptions = array();
            if (empty($data['RubricsTemplateAnswer'][$i]['rubrics_template_column_info_id']) && $criteriaId != NULL) {
                $answerOptions = array('value' => $criteriaId);
            }

            $criteriaAnswer .= $this->Form->hidden($fieldName . '.rubrics_template_column_info_id', $answerOptions);
        } else {
            $criteriaAnswer = (!empty($data['RubricsTemplateAnswer'][$i]['title'])) ? $data['RubricsTemplateAnswer'][$i]['title'] : '';
        }

        return $criteriaAnswer;
    }

    /*
     * User view (With Selection
     */

    public function insertQualityRubricHeader($data = NULL, $key = 0) {
        $modalName = key($data);
        $content = !empty($data[$modalName]['title']) ? $data[$modalName]['title'] : "";

        $display = '<tr>
                        <td class="rubric-left-col-nav"></td>
                        <td class="rubric-row-header" colspan="' . $this->defaultNoOfColumns . '">' . $content . '</td>
                </tr>';

        return $display;
    }

    public function insertQualityRubricQuestionRow($data = NULL, $key = 0) {
        $headerColumnData = $data['columnHeader'];

        $display = $this->_setupQuestionCriteriaHeaderField($headerColumnData, 'number', $data);

        //-------------------4th row-------------------
        $qualityInstitution = 'SelectedAnswer.' . $data['RubricsTemplateItem']['id'] . '.QualityInstitutionRubricsAnswer.';
        $options = array();
        $inputField = $this->Form->hidden($qualityInstitution . 'rubric_template_answer_id', array('class' => 'answer'));

        if (empty($this->data['SelectedAnswer'][$data['RubricsTemplateItem']['id']])) {
            $options = array('value' => $data['RubricsTemplateItem']['id']);
            $selectedAnswer = false;
        } else {
            $selectedAnswer = true;
            $inputField .= $this->Form->hidden($qualityInstitution . 'id');
        }

        $inputField .= $this->Form->hidden($qualityInstitution . 'rubric_template_item_id', $options);
        $criteriaQuestion = (!empty($data['RubricsTemplateItem']['title'])) ? $data['RubricsTemplateItem']['title'] : '';

        $display .= '<tr><td class="cell_align_left">' . $criteriaQuestion . $inputField . '</td>';
        foreach ($headerColumnData as $k => $column) {
            $color = $column['RubricsTemplateColumnInfo']['color'];
            $highlighted = ''; //endable the color of the selected column
            $selectedColumn = ''; // indicate the status of the question
            $criteriaAnswer = '';

            $isMatch = false;
            for ($i = 0; $i < count($data['RubricsTemplateAnswer']); $i++) {


                if (!empty($data['RubricsTemplateAnswer'][$i]['rubrics_template_column_info_id'])) {
                    if ($column['RubricsTemplateColumnInfo']['id'] == $data['RubricsTemplateAnswer'][$i]['rubrics_template_column_info_id']) {
                        $isMatch = true;
                        //$criteriaAnswer = $this->_setupRubricAnswerField($key, $i, $column['RubricsTemplateColumnInfo']['id'], $data, $mode);
                        $answerId = $data['RubricsTemplateAnswer'][$i]['id'];
                        if ($selectedAnswer) {
                            if ($answerId == $this->data['SelectedAnswer'][$data['RubricsTemplateItem']['id']]['QualityInstitutionRubricsAnswer']['rubric_template_answer_id']) {
                                $selectedColumn = 'selected';
                                $highlighted = 'style="background-color:#' . $color . '"';
                            }
                        }
                    }
                }

                if ($isMatch) {
                    $criteriaAnswer = (!empty($data['RubricsTemplateAnswer'][$i]['title'])) ? $data['RubricsTemplateAnswer'][$i]['title'] : '';
                    break;
                }
            }
            $display .= '<td class="cell_align_left answer-options ' . $selectedColumn . '" ' . $highlighted . ' onmouseover="QualityRubric.overRubricAnswer(this, \'' . $color . '\')" onmouseout="QualityRubric.outRubricAnswer(this)" onclick="QualityRubric.selectRubricAnswer(this, ' . $answerId . ',\'' . $color . '\')"><div>' . $criteriaAnswer . '</div></td>';
        }

        $display .= '</tr>';

        $display .= $this->_setupWeightingField($headerColumnData);
        return $display;
    }

    /* End User */
}

?>