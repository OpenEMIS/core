UPDATE config_items SET name = 'yearbook_academic_period' WHERE name = 'yearbook_school_year';
UPDATE config_items SET option_type = 'database:AcademicPeriod' WHERE name = 'yearbook_academic_period';
UPDATE config_items SET label = 'Academic Period' WHERE name = 'yearbook_academic_period';


UPDATE config_item_options SET option_type  = 'database:AcademicPeriod', config_item_options.option = 'AcademicPeriod.name', value = 'AcademicPeriod.id' WHERE option_type  = 'database:SchoolYear';

-- need to set default academic period to latest one
SELECT id INTO @currentAcademicPeriod FROM academic_periods WHERE parent_id >0 ORDER BY current DESC, available DESC, end_date DESC, id DESC LIMIT 1;
UPDATE config_items SET config_items.value = @currentAcademicPeriod, config_items.default_value = @currentAcademicPeriod WHERE name = 'yearbook_academic_period';