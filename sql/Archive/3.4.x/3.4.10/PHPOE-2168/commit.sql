INSERT INTO `db_patches` VALUES ('PHPOE-2168', NOW());

-- SELECT * FROM institution_section_classes WHERE institution_section_id NOT IN (SELECT id FROM institution_sections);
DELETE FROM institution_section_classes WHERE NOT EXISTS (SELECT id FROM institution_sections WHERE institution_sections.id = institution_section_classes.institution_section_id);

-- SELECT * FROM institution_classes WHERE id NOT IN (SELECT institution_class_id FROM institution_section_classes);
DELETE FROM institution_classes WHERE NOT EXISTS (SELECT institution_class_id FROM institution_section_classes WHERE institution_section_classes.institution_class_id = institution_classes.id);

-- SELECT * FROM institution_class_students WHERE institution_class_id NOT IN (SELECT id FROM institution_classes);
DELETE FROM institution_class_students WHERE NOT EXISTS (SELECT id FROM institution_classes WHERE institution_classes.id = institution_class_students.institution_class_id);

-- SELECT * FROM institution_class_staff WHERE institution_class_id NOT IN (SELECT id FROM institution_classes);
DELETE FROM institution_class_staff WHERE NOT EXISTS (SELECT id FROM institution_classes WHERE institution_classes.id = institution_class_staff.institution_class_id);
