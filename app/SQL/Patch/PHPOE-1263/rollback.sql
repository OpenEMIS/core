UPDATE config_items SET name = 'yearbook_school_year' WHERE name = 'yearbook_academic_period';
UPDATE config_items SET option_type = 'database:SchoolYear' WHERE name = 'yearbook_school_year';
UPDATE config_items SET label = 'School Year' WHERE name = 'yearbook_school_year';

UPDATE config_item_options SET option_type  = 'database:SchoolYear', config_item_options.option = 'SchoolYear.name', value = 'SchoolYear.id' WHERE option_type  = 'database:AcademicPeriod';

-- need to set default school year to latest one
SELECT id INTO @currentSchoolYear FROM school_years ORDER BY current DESC, available DESC, end_date DESC, id DESC LIMIT 1;
UPDATE config_items SET config_items.value = @currentSchoolYear, config_items.default_value = @currentSchoolYear WHERE name = 'yearbook_school_year';