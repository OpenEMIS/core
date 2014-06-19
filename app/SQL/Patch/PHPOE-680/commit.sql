UPDATE `tst_openemisv2_demo`.`batch_reports` SET `query` = 'App::import(''Model'', ''InstitutionSite'');
        App::import(''Model'', ''Quality.QualityBatchReport'');

        $qbr = new QualityBatchReport();
        $InstitutionSite = new InstitutionSite();

        $fields = $qbr->getLocalSchoolDisplayFieldTable();
        $joins = $qbr->getLocalSchoolJoinTableData();


        $dbo = $InstitutionSite->getDataSource();
        $queryFinal = $dbo->buildStatement(array(
            ''fields'' => $fields,
            ''table'' => $dbo->fullTableName($InstitutionSite),
            ''alias'' => $InstitutionSite->alias,
            {cond},
            ''joins'' => $joins,
            ''conditions'' => null,
            ''group'' => array(''SchoolYear.name'', ''InstitutionSite.id'', ''InstitutionSiteClass.id'',/* ''EducationGrade.id'',*/''RubricTemplate.id'', ''RubricTemplateHeader.id''),
            ''order'' => array(''SchoolYear.name DESC'', ''InstitutionSite.name'', ''EducationGrade.name'', ''InstitutionSiteClass.name'', ''RubricTemplate.id'', ''RubricTemplateHeader.order'')

            
                ), $InstitutionSite);


        $queryCount = $dbo->buildStatement(array(
            ''fields'' => array(''COUNT(*) as TotalCount''),
            ''table'' => ''('' . $queryFinal . '')'',
            ''alias'' => $InstitutionSite->alias . ''Fliter'',
            ''limit'' => null,
            ''offset'' => null,
            ''joins'' => array(),
            ''conditions'' => null,
            ''group'' => null,
            ''order'' => null
                ), $InstitutionSite);

        $data = $dbo->fetchAll($queryFinal);
        pr($data);
        $data = $qbr->generateQASchoolsReport($data);
 
        $settings[''custom3LayerFormat''] = true;' WHERE `batch_reports`.`id` = 3000;


UPDATE `tst_openemisv2_demo`.`batch_reports` SET `query` = 'App::import(''Model'', ''InstitutionSite'');
        App::import(''Model'', ''Quality.QualityBatchReport'');
        $qbr = new QualityBatchReport();
        $InstitutionSite = new InstitutionSite();

        $fields = $qbr->getResultDisplayFieldTable(''base'');
        $joins = $qbr->getResultJoinTableData();

        $dbo = $InstitutionSite->getDataSource();
        $query = $dbo->buildStatement(array(
            ''fields'' => $fields,
            ''table'' => $dbo->fullTableName($InstitutionSite),
            ''alias'' => $InstitutionSite->alias,
            ''limit'' => null,
            ''offset'' => null,
            ''joins'' => $joins,
            ''conditions'' => null,
            ''group'' => array(''InstitutionSiteClass.id'', ''RubricTemplate.id''),
            ''order'' => array(/*''Institution.name'',*/ ''InstitutionSite.name'', ''SchoolYear.name DESC'', ''EducationGrade.name'', ''InstitutionSiteClass.name'', ''RubricTemplate.id'')
                ), $InstitutionSite);

        $query = ''('' . $query . '')'';

        $fields2 = $qbr->getResultDisplayFieldTable(''search'');

        $queryFinal = $dbo->buildStatement(array(
            ''fields'' => $fields2,
            ''table'' => $query,
            ''alias'' => $InstitutionSite->alias . ''Fliter'',
            {cond},
            ''joins'' => array(),
            ''conditions'' => null,
            ''group'' => array(''Year'', ''RubricId'', ''InstitutionSiteId'', ''GradeId''),
            ''order'' => array(''InstitutionName'', ''Year DESC'', ''Grade'', ''Class'')
                ), $InstitutionSite);


        $queryCount = $dbo->buildStatement(array(
            ''fields'' => array(''COUNT(*) as TotalCount''),
            ''table'' => ''('' . $queryFinal . '')'',
            ''alias'' => $InstitutionSite->alias . ''Fliter'',
            ''limit'' => null,
            ''offset'' => null,
            ''joins'' => array(),
            ''conditions'' => null,
            ''group'' => null,
            ''order'' => null
                ), $InstitutionSite);

        $data = $dbo->fetchAll($queryFinal);
 
        $data = $qbr->generateQAResultReport($data);' WHERE `batch_reports`.`id` = 3001;


UPDATE `tst_openemisv2_demo`.`batch_reports` SET `query` = 'App::import(''Model'', ''InstitutionSite'');
        App::import(''Model'', ''Quality.QualityBatchReport'');
 
$settings[''custom3LayerFormat''] = true;

        $qbr = new QualityBatchReport();
        $InstitutionSite = new InstitutionSite();

        $fields = $qbr->getNotCompleteDisplayFieldTable(''base'');
        $joins = $qbr->getNotCompleteJoinTableData();

        $dbo = $InstitutionSite->getDataSource();
        $query = $dbo->buildStatement(array(
            ''fields'' => $fields,
            ''table'' => $dbo->fullTableName($InstitutionSite),
            ''alias'' => $InstitutionSite->alias,
            ''limit'' => null,
            ''offset'' => null,
            ''joins'' => $joins,
            ''conditions'' => null,
            ''group'' => array(''InstitutionSiteClass.id'', ''RubricTemplate.id'',  ''RubricTemplateSubheader.id''),
            ''order'' => array(''SchoolYear.name DESC'', ''InstitutionSite.name'', ''EducationGrade.name'', ''InstitutionSiteClass.name'', ''RubricTemplate.id'', ''RubricTemplateHeader.order'')
                ), $InstitutionSite);

        $query = ''('' . $query . '')'';

        $fields2 = $qbr->getNotCompleteDisplayFieldTable(''search'');

        $queryFinal = $dbo->buildStatement(array(
            ''fields'' => $fields2,
            ''table'' => $query,
            ''alias'' => $InstitutionSite->alias . ''Fliter'',
            {cond},
            ''joins'' => array(),
            ''conditions'' => null,
            ''group'' => array(''ClassId'', ''RubricId HAVING TotalQuestions != TotalAnswered''),
            ''order'' => null
                ), $InstitutionSite);


        $queryCount = $dbo->buildStatement(array(
            ''fields'' => array(''COUNT(*) as TotalCount''),
            ''table'' => ''('' . $queryFinal . '')'',
            ''alias'' => $InstitutionSite->alias . ''Fliter'',
            ''limit'' => null,
            ''offset'' => null,
            ''joins'' => array(),
            ''conditions'' => null,
            ''group'' => null,
            ''order'' => null
                ), $InstitutionSite);

        $data = $dbo->fetchAll($queryFinal);

' WHERE `batch_reports`.`id` = 3002;