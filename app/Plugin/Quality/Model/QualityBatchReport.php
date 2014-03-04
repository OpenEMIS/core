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

class QualityBatchReport extends QualityAppModel {

    public $useTable = false;

    //   public $actsAs = array('ControllerAction');
    //App::import('Model', 'Quality.QualityBatchReport');
    public function generateLocalSchool() {
        App::import('Model', 'InstitutionSite');
        
        $InstitutionSite = new InstitutionSite();
        $data = $InstitutionSite->find('all', array(
        //$data = $this->InstitutionSite->find('all', array(
            'fields' => array(
                'SchoolYear.name',
                'Area.name',
                'Area.code',
                'InstitutionSite.name',
                'InstitutionSite.code',
                'InstitutionSite.id',
                'InstitutionSiteClass.name',
                'InstitutionSiteClass.id',
                'EducationGrade.name',
                'RubricTemplate.name',
                'RubricTemplate.id',
                'RubricTemplateHeader.title',
                'COALESCE(SUM(RubricTemplateColumnInfo.weighting),0)'
            ),
            'order' => array('SchoolYear.name DESC', 'InstitutionSite.name', 'EducationGrade.name', 'InstitutionSiteClass.name', 'RubricTemplate.id', 'RubricTemplateHeader.order'),
            'group' => array('InstitutionSiteClass.id', 'RubricTemplate.id', 'RubricTemplateHeader.id'),
            'joins' => array(
                array(
                    'table' => 'areas',
                    'alias' => 'Area',
                    'conditions' => array('Area.id = InstitutionSite.area_id')
                ),
                array(
                    'table' => 'institution_site_classes',
                    'alias' => 'InstitutionSiteClass',
                    'conditions' => array('InstitutionSiteClass.institution_site_id = InstitutionSite.id')
                ),
                array(
                    'table' => 'institution_site_class_grades',
                    'alias' => 'InstitutionSiteClassGrade',
                    'conditions' => array('InstitutionSiteClassGrade.institution_site_class_id = InstitutionSiteClass.id')
                ),
                array(
                    'table' => 'education_grades',
                    'alias' => 'EducationGrade',
                    'conditions' => array('EducationGrade.id = InstitutionSiteClassGrade.education_grade_id')
                ),
                array(
                    'table' => 'school_years',
                    'alias' => 'SchoolYear',
                    'type' => 'LEFT',
                    'conditions' => array('InstitutionSiteClass.school_year_id = SchoolYear.id')
                ),
                array(
                    'table' => 'quality_statuses',
                    'alias' => 'QualityStatus',
                    'conditions' => array('QualityStatus.year = SchoolYear.name')
                ),
                array(
                    'table' => 'rubrics_templates',
                    'alias' => 'RubricTemplate',
                    'type' => 'LEFT',
                    'conditions' => array('RubricTemplate.id = QualityStatus.rubric_template_id')
                ),
                array(
                    'table' => 'quality_institution_rubrics',
                    'alias' => 'QualityInstitutionRubric',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'QualityInstitutionRubric.institution_site_class_id = InstitutionSiteClass.id',
                        'RubricTemplate.id = QualityInstitutionRubric.rubric_template_id',
                        'SchoolYear.id = QualityInstitutionRubric.school_year_id'
                    )
                ),
                array(
                    'table' => 'rubrics_template_headers',
                    'alias' => 'RubricTemplateHeader',
                    'type' => 'LEFT',
                    'conditions' => array('RubricTemplate.id = RubricTemplateHeader.rubric_template_id')
                ),
                array(
                    'table' => 'rubrics_template_subheaders',
                    'alias' => 'RubricTemplateSubheader',
                    'type' => 'LEFT',
                    'conditions' => array('RubricTemplateSubheader.rubric_template_header_id = RubricTemplateHeader.id')
                ),
                array(
                    'table' => 'rubrics_template_items',
                    'alias' => 'RubricTemplateItem',
                    'type' => 'LEFT',
                    'conditions' => array('RubricTemplateItem.rubric_template_subheader_id = RubricTemplateSubheader.id')
                ),
                array(
                    'table' => 'rubrics_template_answers',
                    'alias' => 'RubricTemplateAnswer',
                    'type' => 'LEFT',
                    'conditions' => array('RubricTemplateAnswer.rubric_template_item_id = RubricTemplateItem.id')
                ),
                array(
                    'table' => 'quality_institution_rubrics_answers',
                    'alias' => 'QualityInstitutionRubricAnswer',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'QualityInstitutionRubricAnswer.quality_institution_rubric_id = QualityInstitutionRubric.id',
                        'QualityInstitutionRubricAnswer.rubric_template_header_id = RubricTemplateHeader.id',
                        'QualityInstitutionRubricAnswer.rubric_template_item_id = RubricTemplateItem.id',
                        'QualityInstitutionRubricAnswer.rubric_template_answer_id = RubricTemplateAnswer.id',
                        'InstitutionSiteClass.id = QualityInstitutionRubric.institution_site_class_id'
                    )
                ),
                array(
                    'table' => 'rubrics_template_column_infos',
                    'alias' => 'RubricTemplateColumnInfo',
                    'type' => 'LEFT',
                    'conditions' => array(
                        'RubricTemplateAnswer.rubrics_template_column_info_id = RubricTemplateColumnInfo.id',
                        'QualityInstitutionRubricAnswer.rubric_template_item_id = RubricTemplateItem.id',
                    ),
                )
            ),
            'recursive' => -1
                //  , {cond}
        ));

        App::import('Model', 'Quality.RubricsTemplate');
        $rt = new RubricsTemplate();

        $data = $rt->processDataToCSVFormat($data, 'yes', 'yes');

        pr($data);
    }

}
