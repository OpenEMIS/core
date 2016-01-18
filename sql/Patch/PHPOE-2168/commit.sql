INSERT INTO `db_patches` VALUES ('PHPOE-2168', NOW());

-- SELECT * FROM institution_site_section_classes WHERE institution_site_section_id NOT IN (SELECT id FROM institution_site_sections);
DELETE FROM institution_site_section_classes WHERE NOT EXISTS (SELECT id FROM institution_site_sections WHERE institution_site_sections.id = institution_site_section_classes.institution_site_section_id);

-- SELECT * FROM institution_site_classes WHERE id NOT IN (SELECT institution_site_class_id FROM institution_site_section_classes);
DELETE FROM institution_site_classes WHERE NOT EXISTS (SELECT institution_site_class_id FROM institution_site_section_classes WHERE institution_site_section_classes.institution_site_class_id = institution_site_classes.id);

-- SELECT * FROM institution_site_class_students WHERE institution_site_class_id NOT IN (SELECT id FROM institution_site_classes);
DELETE FROM institution_site_class_students WHERE NOT EXISTS (SELECT id FROM institution_site_classes WHERE institution_site_classes.id = institution_site_class_students.institution_site_class_id);

-- SELECT * FROM institution_site_class_staff WHERE institution_site_class_id NOT IN (SELECT id FROM institution_site_classes);
DELETE FROM institution_site_class_staff WHERE NOT EXISTS (SELECT id FROM institution_site_classes WHERE institution_site_classes.id = institution_site_class_staff.institution_site_class_id);
