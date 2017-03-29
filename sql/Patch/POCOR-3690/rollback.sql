-- examination_centres_examinations
DROP TABLE IF EXISTS `examination_centres_examinations`;

-- examination_centres
DROP TABLE IF EXISTS `examination_centres`;
RENAME TABLE `z_3690_examination_centres` TO `examination_centres`;

-- examination_centre_special_needs
DROP TABLE IF EXISTS `examination_centre_special_needs`;
RENAME TABLE `z_3690_examination_centre_special_needs` TO `examination_centre_special_needs`;

-- examination_centre_rooms_examinations
DROP TABLE IF EXISTS `examination_centre_rooms_examinations`;

-- examination_centre_rooms
DROP TABLE IF EXISTS `examination_centre_rooms`;
RENAME TABLE `z_3690_examination_centre_rooms` TO `examination_centre_rooms`;

-- examination_centre_rooms_examinations_invigilators
DROP TABLE IF EXISTS `examination_centre_rooms_examinations_invigilators`;
RENAME TABLE `z_3690_examination_centre_rooms_invigilators` TO `examination_centre_rooms_invigilators`;

-- examination_centre_rooms_examinations_students
DROP TABLE IF EXISTS `examination_centre_rooms_examinations_students`;
RENAME TABLE `z_3690_examination_centre_room_students` TO `examination_centre_room_students`;
