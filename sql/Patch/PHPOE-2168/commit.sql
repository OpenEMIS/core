-- SELECT * FROM institution_site_section_classes WHERE institution_site_section_id NOT IN (SELECT id FROM institution_site_sections);
DELETE FROM institution_site_section_classes WHERE institution_site_section_id NOT IN (SELECT id FROM institution_site_sections);

-- SELECT * FROM institution_site_classes WHERE id NOT IN (SELECT institution_site_class_id FROM institution_site_section_classes);
DELETE FROM institution_site_classes WHERE id NOT IN (SELECT institution_site_class_id FROM institution_site_section_classes);

-- SELECT * FROM institution_site_class_students WHERE institution_site_class_id NOT IN (SELECT id FROM institution_site_classes);
DELETE FROM institution_site_class_students WHERE institution_site_class_id NOT IN (SELECT id FROM institution_site_classes);

-- SELECT * FROM institution_site_class_staff WHERE institution_site_class_id NOT IN (SELECT id FROM institution_site_classes);
DELETE FROM institution_site_class_staff WHERE institution_site_class_id NOT IN (SELECT id FROM institution_site_classes);
