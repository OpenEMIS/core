-- POCOR-3303
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3303', NOW());

-- security_functions
UPDATE `security_functions` SET `_execute` = 'Assessments.excel|ClassStudents.excel' WHERE `id` = 1015;


-- POCOR-3080
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3080', NOW());

-- assessment_items_grading_types
DROP TABLE IF EXISTS `assessment_items_grading_types`;
CREATE TABLE IF NOT EXISTS `assessment_items_grading_types` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id',
  `assessment_grading_type_id` int(11) NOT NULL COMMENT 'links to assessment_grading_types.id',
  `assessment_id` int(11) NOT NULL COMMENT 'links to assessments.id',
  `assessment_period_id` int(11) NOT NULL COMMENT 'links to assessment_periods.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for table `assessment_items_grading_types`
ALTER TABLE `assessment_items_grading_types`
  ADD PRIMARY KEY (`assessment_grading_type_id`,`assessment_id`,`education_subject_id`,`assessment_period_id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD KEY `modified_user_id` (`modified_user_id`),
  ADD KEY `created_user_id` (`created_user_id`);

-- assessment_items
-- backup assessment_items / assessment_grading_type_id cloumn
RENAME TABLE `assessment_items` TO `z_3080_assessment_items`;

CREATE TABLE IF NOT EXISTS `assessment_items` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `weight` decimal(6,2) DEFAULT '0.00',
  `assessment_id` int(11) NOT NULL,
  `education_subject_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for table `assessment_items`
ALTER TABLE `assessment_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assessment_id` (`assessment_id`),
  ADD KEY `education_subject_id` (`education_subject_id`),
  ADD KEY `modified_user_id` (`modified_user_id`),
  ADD KEY `created_user_id` (`created_user_id`);

-- restore from backup
INSERT INTO `assessment_items` (`id`, `weight`, `assessment_id`, `education_subject_id`,
  `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `weight`, `assessment_id`, `education_subject_id`,
  `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_3080_assessment_items`;

INSERT INTO `assessment_items_grading_types` (`id`, `education_subject_id`, `assessment_grading_type_id`, `assessment_id`, `assessment_period_id`,
  `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT uuid(), AI.`education_subject_id`, AI.`assessment_grading_type_id`, AI.`assessment_id`, AP.`id`,
  AI.`modified_user_id`, AI.`modified`, AI.`created_user_id`, AI.`created`
FROM `z_3080_assessment_items`AI
INNER JOIN `assessment_periods` AP ON AP.`assessment_id` = AI.`assessment_id`;

-- assessment_periods
ALTER TABLE `assessment_periods` CHANGE `weight` `weight` DECIMAL(6,2) NULL DEFAULT '0.00';

-- for institution_shift POCOR-2602
ALTER TABLE `institution_shifts` CHANGE `shift_option_id` `shift_option_id` INT(11) NOT NULL COMMENT 'links to shift_options.id';


-- POCOR-2760
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-2760', NOW());

UPDATE `security_functions` SET _delete = NULL WHERE id = 5003;
-- SELECT * FROM `security_functions` WHERE id = 5003;

-- BACKING UP
CREATE TABLE z_2760_security_role_functions LIKE security_role_functions;
INSERT INTO z_2760_security_role_functions SELECT * FROM security_role_functions WHERE security_function_id = 5003;

-- DELETING ASSOCIATED RECORDS
UPDATE security_role_functions SET _delete = 0 WHERE security_function_id = 5003;


-- POCOR-3264
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3264', NOW());

UPDATE translations SET es = 'Inicio' WHERE en = 'Home';
UPDATE translations SET es = 'Bienvenido' WHERE en = 'Welcome';
UPDATE translations SET es = 'Cuenta' WHERE en = 'Account';
UPDATE translations SET es = 'Ayuda' WHERE en = 'Help';
UPDATE translations SET es = 'Cerrar sesión' WHERE en = 'Logout';
UPDATE translations SET es = 'Instituciones' WHERE en = 'Institutions';
UPDATE translations SET es = 'Estudiantes' WHERE en = 'Students';
UPDATE translations SET es = 'Docentes' WHERE en = 'Teachers';
UPDATE translations SET es = 'Personal' WHERE en = 'Staff';
UPDATE translations SET es = 'Informes' WHERE en = 'Reports';
UPDATE translations SET es = 'Configuración' WHERE en = 'Settings';
UPDATE translations SET es = 'Administración' WHERE en = 'Administration';
UPDATE translations SET es = 'Demo' WHERE en = 'Demo';
UPDATE translations SET es = '-Seleccione-' WHERE en = '--Select--';
UPDATE translations SET es = 'Día' WHERE en = 'Day';
UPDATE translations SET es = 'Mes' WHERE en = 'Month';
UPDATE translations SET es = 'Año' WHERE en = 'Year';
UPDATE translations SET es = 'Masculino' WHERE en = 'Male';
UPDATE translations SET es = 'Femenino' WHERE en = 'Female';
UPDATE translations SET es = 'Unisex' WHERE en = 'Unisex';
UPDATE translations SET es = 'Seleccionar archivo' WHERE en = 'Select File';
UPDATE translations SET es = 'Editar' WHERE en = 'Edit';
UPDATE translations SET es = 'Ver' WHERE en = 'View';
UPDATE translations SET es = 'Eliminar' WHERE en = 'Delete';
UPDATE translations SET es = 'Historial' WHERE en = 'History';
UPDATE translations SET es = 'Guardar' WHERE en = 'Save';
UPDATE translations SET es = 'Cancelar' WHERE en = 'Cancel';
UPDATE translations SET es = 'Actualizar' WHERE en = 'Update';
UPDATE translations SET es = 'Anterior' WHERE en = 'Previous';
UPDATE translations SET es = 'Siguiente' WHERE en = 'Next';
UPDATE translations SET es = 'Estadísticas' WHERE en = 'Statistics';
UPDATE translations SET es = 'Sede de Instituciones' WHERE en = 'Institutions Sites';
UPDATE translations SET es = 'Actividades' WHERE en = 'Activities';
UPDATE translations SET es = 'Estudiante' WHERE en = 'Student';
UPDATE translations SET es = 'había sido editado' WHERE en = 'has been edited';
UPDATE translations SET es = 'había sido eliminado' WHERE en = 'has been deleted';
UPDATE translations SET es = 'ha sido añadido a la Lista de' WHERE en = 'has been added to the List of';
UPDATE translations SET es = 'Por' WHERE en = 'By';
UPDATE translations SET es = 'Aviso' WHERE en = 'Notice';
UPDATE translations SET es = 'Nota: Tamaño máximo 2MB.' WHERE en = 'Note: Max upload file size is 2MB.';
UPDATE translations SET es = 'Imagen' WHERE en = 'Image';
UPDATE translations SET es = 'Documento' WHERE en = 'Document';
UPDATE translations SET es = 'Excel' WHERE en = 'Excel';
UPDATE translations SET es = 'Powerpoint' WHERE en = 'Powerpoint';
UPDATE translations SET es = 'Archivo eliminado exitosamente.' WHERE en = 'File is deleted successfully.';
UPDATE translations SET es = 'El archivo fue eliminado exitosamente.' WHERE en = 'File was deleted successfully.';
UPDATE translations SET es = 'Ocurrió un error mientras se estaba eliminando el archivo.' WHERE en = 'Error occurred while deleting file.';
UPDATE translations SET es = 'Archivos guardados exitosamente.' WHERE en = 'Files have been saved successfully.';
UPDATE translations SET es = 'Errores encontrados mientras se guardaban los archivos.' WHERE en = 'Some errors have been encountered while saving files.';
UPDATE translations SET es = 'Registros añadidos / modificados exitosamente.' WHERE en = 'Records have been added/updated successfully.';
UPDATE translations SET es = 'Registros eliminados exitosamente.' WHERE en = 'Records have been deleted successfully.';
UPDATE translations SET es = 'Ocurrió un error mientras se estaban eliminando los registros.' WHERE en = 'Error occurred while deleting record.';
UPDATE translations SET es = 'Eliminado exitosamente ' WHERE en = ' have been deleted successfully.';
UPDATE translations SET es = 'Buscando...' WHERE en = 'Searching...';
UPDATE translations SET es = '%s eliminado exitosamente.' WHERE en = '%s have been deleted successfully.';
UPDATE translations SET es = 'Por favor, introduzca Número de identificación único.' WHERE en = 'Please enter a unique Identification No';
UPDATE translations SET es = 'Por favor, introduzca Código único' WHERE en = 'Please enter a unique Code';
UPDATE translations SET es = 'Lista de Estudiantes' WHERE en = 'List of Students';
UPDATE translations SET es = 'Añadir nuevo Estudiante' WHERE en = 'Add new Student';
UPDATE translations SET es = 'Detalles' WHERE en = 'Details';
UPDATE translations SET es = 'Archivos adjuntos' WHERE en = 'Attachments';
UPDATE translations SET es = 'Vista preliminar' WHERE en = 'Overview';
UPDATE translations SET es = 'Más' WHERE en = 'More';
UPDATE translations SET es = 'Información Adicional' WHERE en = 'Additional Info';
UPDATE translations SET es = 'Información del Estudiante' WHERE en = 'Student Information';
UPDATE translations SET es = 'Campos estáticos' WHERE en = 'Static fields';
UPDATE translations SET es = 'Opciones de Campo' WHERE en = 'Field Options';
UPDATE translations SET es = 'Evaluaciones Nacionales' WHERE en = 'National Assessments';
UPDATE translations SET es = 'Evaluaciones' WHERE en = 'Assessments';
UPDATE translations SET es = 'Resultados de la Evaluación' WHERE en = 'Assessment Results';
UPDATE translations SET es = 'Detalles del Estudiante' WHERE en = 'Student Details';
UPDATE translations SET es = 'Historial del Estudiante' WHERE en = 'Student History';
UPDATE translations SET es = 'Añadir' WHERE en = 'Add';
UPDATE translations SET es = 'Archivo adjunto' WHERE en = 'Attachment';
UPDATE translations SET es = 'No. Identificación del Estudiante' WHERE en = 'Student Identification No';
UPDATE translations SET es = 'Identificación del Estudiante' WHERE en = 'Student Identification';
UPDATE translations SET es = 'Identificación del Docente' WHERE en = 'Teacher Identification';
UPDATE translations SET es = 'Identificación del Personal' WHERE en = 'Staff Identification';
UPDATE translations SET es = 'No. de identificación' WHERE en = 'Identification No.';
UPDATE translations SET es = 'No. de identificación' WHERE en = 'Identification No';
UPDATE translations SET es = 'Nombre' WHERE en = 'First Name';
UPDATE translations SET es = 'Apellido' WHERE en = 'Last Name';
UPDATE translations SET es = 'Género' WHERE en = 'Gender';
UPDATE translations SET es = 'Fecha de Nacimiento' WHERE en = 'Date Of Birth';
UPDATE translations SET es = 'Archivo' WHERE en = 'File';
UPDATE translations SET es = 'Descripción' WHERE en = 'Description';
UPDATE translations SET es = 'Tipo de Archivo' WHERE en = 'File Type';
UPDATE translations SET es = 'Subir' WHERE en = 'Uploaded On';
UPDATE translations SET es = 'Institución' WHERE en = 'Institution';
UPDATE translations SET es = 'Fecha de Inicio' WHERE en = 'Start Date';
UPDATE translations SET es = 'Fecha de finalización' WHERE en = 'End Date';
UPDATE translations SET es = 'Dirección' WHERE en = 'Address';
UPDATE translations SET es = 'Dirección de Área' WHERE en = 'Address Area';
UPDATE translations SET es = 'Área de Nacimiento' WHERE en = 'Birth Place Area';
UPDATE translations SET es = 'Código Postal' WHERE en = 'Postal Code';
UPDATE translations SET es = 'Teléfono' WHERE en = 'Telephone';
UPDATE translations SET es = 'Correo electrónico' WHERE en = 'Email';
UPDATE translations SET es = 'Correo electrónico' WHERE en = 'E-mail';
UPDATE translations SET es = 'General' WHERE en = 'General';
UPDATE translations SET es = 'Contacto' WHERE en = 'Contact';
UPDATE translations SET es = 'No se encontró Estudiante' WHERE en = 'No Student found';
UPDATE translations SET es = 'No se encontró un historial' WHERE en = 'No history found.';
UPDATE translations SET es = 'Lista del Personal' WHERE en = 'List of Staff';
UPDATE translations SET es = 'Añadir nuevo Personal' WHERE en = 'Add new Staff';
UPDATE translations SET es = 'Información del Personal' WHERE en = 'Staff Information';
UPDATE translations SET es = 'Detalles del Personal' WHERE en = 'Staff Details';
UPDATE translations SET es = 'No se encontró Personal' WHERE en = 'No Staff found.';
UPDATE translations SET es = 'Historial del Personal' WHERE en = 'Staff History';
UPDATE translations SET es = 'Lista de Docentes' WHERE en = 'List of Teachers';
UPDATE translations SET es = 'Añadir un nuevo Docente' WHERE en = 'Add new Teacher';
UPDATE translations SET es = 'Calificaciones' WHERE en = 'Qualifications';
UPDATE translations SET es = 'Capacitación' WHERE en = 'Training';
UPDATE translations SET es = 'Información del Docente' WHERE en = 'Teacher Information';
UPDATE translations SET es = 'Otra Información' WHERE en = 'Other Information';
UPDATE translations SET es = 'Detalles del Docente' WHERE en = 'Teacher Details';
UPDATE translations SET es = 'Historial del Docente' WHERE en = 'Teacher History';
UPDATE translations SET es = 'Fecha de emisión' WHERE en = 'Date of Issue';
UPDATE translations SET es = 'Certificado' WHERE en = 'Certificate';
UPDATE translations SET es = 'No. de Certificado' WHERE en = 'Certificate No.';
UPDATE translations SET es = 'Emitido por' WHERE en = 'Issued By';
UPDATE translations SET es = 'Fecha de terminación' WHERE en = 'Completed Date';
UPDATE translations SET es = 'Categoría' WHERE en = 'Category';
UPDATE translations SET es = 'Certificaciones' WHERE en = 'Certifications';
UPDATE translations SET es = 'No se encontró Docente' WHERE en = 'No Teacher found.';
UPDATE translations SET es = 'Docente' WHERE en = 'Teacher';
UPDATE translations SET es = 'Ingrese un Nombre válido, por favor.' WHERE en = 'Please enter a valid First Name';
UPDATE translations SET es = 'Ingrese un Apellido válido, por favor.' WHERE en = 'Please enter a valid Last Name';
UPDATE translations SET es = 'Ingrese un No. de identificación válido, por favor.' WHERE en = 'Please enter a valid Identification No';
UPDATE translations SET es = 'Seleccione un Género' WHERE en = 'Please select a Gender';
UPDATE translations SET es = 'Ingrese una Dirección válida, por favor.' WHERE en = 'Please enter a valid Address';
UPDATE translations SET es = 'Ingrese un Código Postal válido, por favor.' WHERE en = 'Please enter a valid Postal Code';
UPDATE translations SET es = 'Seleccione una Fecha de Nacimiento, por favor.' WHERE en = 'Please select a Date of Birth';
UPDATE translations SET es = 'Ingrese un Correo electrónico válido, por favor.' WHERE en = 'Please enter a valid Email';
UPDATE translations SET es = 'Ingrese un Nombre de Usuario válido, por favor.' WHERE en = 'Please enter a valid username';
UPDATE translations SET es = 'Este Nombre de Usuario ya está en uso.' WHERE en = 'This username is already in use.';
UPDATE translations SET es = 'La Contraseña debe tener al menos 6 caracteres.' WHERE en = 'Password must be at least 6 characters';
UPDATE translations SET es = 'Ingrese una Contraseña válida, por favor.' WHERE en = 'Please enter a valid password';
UPDATE translations SET es = 'Usted necesita asignar un rol al Usuario' WHERE en = 'You need to assign a role to the user';
DELETE FROM translations WHERE en = 'No Institution Sites';
UPDATE translations SET es = 'No hay Áreas' WHERE en = 'No Areas';
UPDATE translations SET es = 'Permisos' WHERE en = 'Permissions';
UPDATE translations SET es = 'Editar Permisos' WHERE en = 'Edit Permissions';
UPDATE translations SET es = 'Editar Rol - Zona Restringida' WHERE en = 'Edit Role - Area Restricted';
UPDATE translations SET es = 'Rol - Zona Restringida' WHERE en = 'Role - Area Restricted';
UPDATE translations SET es = 'Editar Asignación de Rol' WHERE en = 'Edit Role Assignment';
UPDATE translations SET es = 'Asignación de Rol' WHERE en = 'Role Assignment';
UPDATE translations SET es = 'Editar Roles' WHERE en = 'Edit Roles';
UPDATE translations SET es = 'Usuarios' WHERE en = 'Users';
UPDATE translations SET es = 'Añadir Usuario' WHERE en = 'Add User';
UPDATE translations SET es = 'Roles' WHERE en = 'Roles';
UPDATE translations SET es = 'Editar Detalles' WHERE en = 'Edit Details';
UPDATE translations SET es = 'Editar Información Adicional' WHERE en = 'Edit Additional Info';
UPDATE translations SET es = 'Editar Programas' WHERE en = 'Edit Programmes';
UPDATE translations SET es = 'Añadir Institución' WHERE en = 'Add Institution';
UPDATE translations SET es = 'Nombre' WHERE en = 'Name';
UPDATE translations SET es = 'Área' WHERE en = 'Area';
UPDATE translations SET es = 'Tipo de Sede' WHERE en = 'Site Type';
UPDATE translations SET es = 'Codigo de Sede' WHERE en = 'Site Code';
UPDATE translations SET es = 'Código' WHERE en = 'Code';
UPDATE translations SET es = 'Nombre de la Institución' WHERE en = 'Institution Name';
UPDATE translations SET es = 'Sector' WHERE en = 'Sector';
UPDATE translations SET es = 'Proveedor' WHERE en = 'Provider';
UPDATE translations SET es = 'Nombre de la Sede' WHERE en = 'Site Name';
UPDATE translations SET es = 'Tipo' WHERE en = 'Type';
UPDATE translations SET es = 'Propiedad' WHERE en = 'Ownership';
UPDATE translations SET es = 'País' WHERE en = 'Country';
UPDATE translations SET es = 'Provincia' WHERE en = 'Province';
UPDATE translations SET es = 'Distrito' WHERE en = 'District';
UPDATE translations SET es = 'LLG' WHERE en = 'LLG';
UPDATE translations SET es = 'Guarda' WHERE en = 'Ward';
UPDATE translations SET es = 'Calle' WHERE en = 'Street';
UPDATE translations SET es = 'Bloquear' WHERE en = 'Block';
UPDATE translations SET es = 'Eje' WHERE en = 'Axis';
UPDATE translations SET es = 'Estado' WHERE en = 'State';
UPDATE translations SET es = 'Localidad' WHERE en = 'Locality';
UPDATE translations SET es = 'Latitud' WHERE en = 'Latitude';
UPDATE translations SET es = 'Longitud' WHERE en = 'Longitude';
DELETE FROM translations WHERE en = 'No Institution Sites.';
DELETE FROM translations WHERE en = 'Institution Site Details';
DELETE FROM translations WHERE en = 'Institution Site Code';
UPDATE translations SET es = 'Ingrese un Nombre válido, por favor.' WHERE en = 'Please enter a valid Name';
UPDATE translations SET es = 'Ingrese un Código válido, por favor.' WHERE en = 'Please enter a valid Code';
UPDATE translations SET es = 'Seleccione un Proveedor, por favor.' WHERE en = 'Please select a Provider';
UPDATE translations SET es = 'Seleccione un Estatus, por favor.' WHERE en = 'Please select a Status';
UPDATE translations SET es = 'Seleccione la Fecha de apertura, por favor.' WHERE en = 'Please select the Date Opened';
DELETE FROM translations WHERE en = 'Please select a Site Type';
UPDATE translations SET es = 'Seleccione una Propiedad, por favor.' WHERE en = 'Please select an Ownership';
UPDATE translations SET es = 'Seleccione un Área, por favor.' WHERE en = 'Please select an Area';
UPDATE translations SET es = 'Código de Institución ' WHERE en = 'Institution Code';
UPDATE translations SET es = 'Nombre o Código de la Institución ' WHERE en = 'Institution Name or Code';
UPDATE translations SET es = 'Estatus' WHERE en = 'Status';
UPDATE translations SET es = 'Fecha de Apertura' WHERE en = 'Date Opened';
UPDATE translations SET es = 'Fecha de Clausura' WHERE en = 'Date Closed';
UPDATE translations SET es = 'Localización' WHERE en = 'Location';
UPDATE translations SET es = 'Persona de contacto' WHERE en = 'Contact Person';
UPDATE translations SET es = 'Fax' WHERE en = 'Fax';
UPDATE translations SET es = 'Sitio Web' WHERE en = 'Website';
UPDATE translations SET es = 'Agregar nuevo' WHERE en = 'Add New';
UPDATE translations SET es = 'Activo' WHERE en = 'Active';
UPDATE translations SET es = 'Nombre de la Cuenta' WHERE en = 'Account Name';
UPDATE translations SET es = 'Número de la Cuenta' WHERE en = 'Account Number';
UPDATE translations SET es = 'Banco' WHERE en = 'Bank';
UPDATE translations SET es = 'Sucursal' WHERE en = 'Branch';
UPDATE translations SET es = 'Detalles del Banco' WHERE en = 'Bank Details';
UPDATE translations SET es = 'Sistema Nacional de Educación' WHERE en = 'National Education System';
UPDATE translations SET es = 'Grado' WHERE en = 'Grade';
UPDATE translations SET es = 'Asientos' WHERE en = 'Seats';
UPDATE translations SET es = 'Asistencia' WHERE en = 'Attendance';
UPDATE translations SET es = 'Comportamiento ' WHERE en = 'Behaviour';
UPDATE translations SET es = 'Resultados' WHERE en = 'Results';
UPDATE translations SET es = 'Totales' WHERE en = 'Totals';
UPDATE translations SET es = 'Total' WHERE en = 'Total';
UPDATE translations SET es = 'Fuente' WHERE en = 'Source';
UPDATE translations SET es = 'Naturaleza' WHERE en = 'Nature';
UPDATE translations SET es = 'Monto' WHERE en = 'Amount';
UPDATE translations SET es = 'Editar Docentes' WHERE en = 'Edit Teachers';
UPDATE translations SET es = 'Editar Capacitación' WHERE en = 'Edit Training';
UPDATE translations SET es = 'Editar Cuentas bancarias' WHERE en = 'Edit Bank Accounts';
UPDATE translations SET es = 'Editar Clases' WHERE en = 'Edit Classes';
UPDATE translations SET es = 'Editar Finanzas' WHERE en = 'Edit Finances';
UPDATE translations SET es = 'Editar Otros Formularios' WHERE en = 'Edit Other Forms';
UPDATE translations SET es = 'Editar Infraestructura' WHERE en = 'Edit Infrastructure';
UPDATE translations SET es = 'Docentes capacitados' WHERE en = 'Trained Teachers';
UPDATE translations SET es = 'No hay Programas disponibles' WHERE en = 'No Available Programmes';
UPDATE translations SET es = 'Historial de la Institución' WHERE en = 'Institution History';
DELETE FROM translations WHERE en = 'Institution Site History';
UPDATE translations SET es = 'Mis detalles' WHERE en = 'My Details';
UPDATE translations SET es = 'Actualizado exitosamente.' WHERE en = 'has been updated successfully.';
UPDATE translations SET es = 'Cambiar Contraseña' WHERE en = 'Change Password';
UPDATE translations SET es = 'Actualizado exitosamente.' WHERE en = 'successfully updated.';
UPDATE translations SET es = 'Inténtelo nuevamente más tarde, por favor.' WHERE en = 'Please try again later.';
UPDATE translations SET es = 'Ingrese su Contraseña actual, por favor.' WHERE en = 'Please enter your current password';
UPDATE translations SET es = 'La Contraseña actual no coincide.' WHERE en = 'Current password does not match.';
UPDATE translations SET es = 'Nueva Contraseña es requerida' WHERE en = 'New password required.';
UPDATE translations SET es = 'Ingrese un mínimo de 6 caracteres alfanuméricos, por favor.' WHERE en = 'Please enter a min of 6 alpha numeric characters.';
UPDATE translations SET es = 'Ingrese caracteres alfanuméricos, por favor.' WHERE en = 'Please enter alpha numeric characters.';
UPDATE translations SET es = 'Las Contraseñas no coinciden' WHERE en = 'Passwords do not match.';
UPDATE translations SET es = 'Información del Sistema' WHERE en = 'System Information';
UPDATE translations SET es = 'Soporte' WHERE en = 'Support';
UPDATE translations SET es = 'Licencia' WHERE en = 'License';
UPDATE translations SET es = 'Modificar mis detalles' WHERE en = 'Edit My Details';
UPDATE translations SET es = 'Finanzas' WHERE en = 'Finance';
DELETE FROM translations WHERE en = 'Total Public Expenditure';
DELETE FROM translations WHERE en = 'National Denominators';
UPDATE translations SET es = 'Seleccionar' WHERE en = 'Select';
DELETE FROM translations WHERE en = 'Total Public Expenditure Per Education Level';
UPDATE translations SET es = 'Sistema' WHERE en = 'System';
UPDATE translations SET es = 'Nivel' WHERE en = 'Level';
UPDATE translations SET es = 'Ciclo' WHERE en = 'Cycle';
UPDATE translations SET es = 'Programa' WHERE en = 'Programme';
UPDATE translations SET es = 'Orientación' WHERE en = 'Orientation';
UPDATE translations SET es = 'Campo de estudio' WHERE en = 'Field Of Study';
UPDATE translations SET es = 'Certificación' WHERE en = 'Certification';
UPDATE translations SET es = 'Asunto' WHERE en = 'Subject';
UPDATE translations SET es = 'Enero' WHERE en = 'January';
UPDATE translations SET es = 'Febrero' WHERE en = 'February';
UPDATE translations SET es = 'Marzo' WHERE en = 'March';
UPDATE translations SET es = 'Abril' WHERE en = 'April';
UPDATE translations SET es = 'Mayo' WHERE en = 'May';
UPDATE translations SET es = 'Junio' WHERE en = 'June';
UPDATE translations SET es = 'Julio' WHERE en = 'July';
UPDATE translations SET es = 'Agosto' WHERE en = 'August';
UPDATE translations SET es = 'Septiembre' WHERE en = 'September';
UPDATE translations SET es = 'Octubre' WHERE en = 'October';
UPDATE translations SET es = 'Noviembre' WHERE en = 'November';
UPDATE translations SET es = 'Diciembre' WHERE en = 'December';
UPDATE translations SET es = 'Su sesión ha caducado. Por favor, ingrese de nuevo.' WHERE en = 'Your session is timed out. Please login again.';
UPDATE translations SET es = 'Usted no es un usuario autorizado.' WHERE en = 'You are not an authorized user.';
UPDATE translations SET es = 'Se ha producido un error inesperado. Por favor contactar al Administrador del Sistema para obtener ayuda.' WHERE en = 'You have encountered an unexpected error. Please contact the system administrator for assistance.';
UPDATE translations SET es = 'Destino inalcanzable ' WHERE en = 'Host Unreachable';
UPDATE translations SET es = 'Destino inalcanzable, por favor, compruebe su conexión a Internet.' WHERE en = 'Host is unreachable';
UPDATE translations SET es = 'Sesión terminada' WHERE en = 'Session Timed Out';
UPDATE translations SET es = 'Página no encontrada' WHERE en = 'Page not found';
UPDATE translations SET es = 'La página solicitada no fue encontrada.' WHERE en = 'The requested page cannot be found.';
UPDATE translations SET es = 'Por favor, contactar al Administrador para obtener ayuda.' WHERE en = 'Please contact the administrator for assistance.';
UPDATE translations SET es = 'Un error inesperado ha ocurrido.' WHERE en = 'An unexpected error has occurred.';
UPDATE translations SET es = 'Falla en parse JSON ' WHERE en = 'JSON parse failed';
UPDATE translations SET es = 'Datos JSON Inválidos' WHERE en = 'Invalid JSON data.';
UPDATE translations SET es = 'Petición ha caducado' WHERE en = 'Request Timeout';
UPDATE translations SET es = 'Petición Anulada' WHERE en = 'Request Aborted';
UPDATE translations SET es = 'Su petición ha sido anulada' WHERE en = 'Your request has been aborted.';
UPDATE translations SET es = 'Error inesperado' WHERE en = 'Unexpected Error';
UPDATE translations SET es = 'Editar Configuraciones del Sistema' WHERE en = 'Edit System Configurations';
UPDATE translations SET es = 'Archivo actualizado exitosamente.' WHERE en = 'File is updated successfully.';
UPDATE translations SET es = 'Ocurrió un error mientras se estaba actualizando el archivo.' WHERE en = 'Error occurred while updating file.';
UPDATE translations SET es = 'Archivo actualizado exitosamente.' WHERE en = 'File have been updated successfully.';
UPDATE translations SET es = 'Archivo no se ha actualizado correctamente.' WHERE en = 'File has not been updated successfully.';
UPDATE translations SET es = 'Formato de Archivo no admitido' WHERE en = 'File format not supported.';
UPDATE translations SET es = 'La imagen ha superado el tamaño permitido' WHERE en = 'Image has exceeded the allow file size of';
UPDATE translations SET es = 'Por favor, reducir el tamaño del archivo.' WHERE en = 'Please reduce file size.';
UPDATE translations SET es = 'Resolución de la Imagen es muy pequeña.' WHERE en = 'Image resolution is too small.';
UPDATE translations SET es = 'Error' WHERE en = 'Error';
UPDATE translations SET es = 'No existen registros.' WHERE en = 'log does not exists';
UPDATE translations SET es = 'Añadir nueva Institución' WHERE en = 'Add new Institution';
UPDATE translations SET es = 'Detalles de la Institución' WHERE en = 'Institution Details';
DELETE FROM translations WHERE en = 'SITE INFORMATION';
DELETE FROM translations WHERE en = 'Add New Institution Site';
UPDATE translations SET es = 'Cuentas bancarias' WHERE en = 'Bank Accounts';
UPDATE translations SET es = 'Cuenta bancaria' WHERE en = 'Bank Account';
UPDATE translations SET es = 'Censo' WHERE en = 'Census';
UPDATE translations SET es = 'Inscripción' WHERE en = 'Enrolment';
UPDATE translations SET es = 'Programas' WHERE en = 'Programmes';
UPDATE translations SET es = 'Clases' WHERE en = 'Classes';
UPDATE translations SET es = 'Libros' WHERE en = 'Textbooks';
UPDATE translations SET es = 'Infraestructura' WHERE en = 'Infrastructure';
UPDATE translations SET es = 'Finanzas' WHERE en = 'Finances';
UPDATE translations SET es = 'Otros Formularios' WHERE en = 'Other Forms';
UPDATE translations SET es = 'Configuración del Sistema' WHERE en = 'System Setup';
UPDATE translations SET es = 'Limites Administrativos' WHERE en = 'Administrative Boundaries';
UPDATE translations SET es = 'Estructura Educativa' WHERE en = 'Education Structure';
DELETE FROM translations WHERE en = 'Setup Variables';
DELETE FROM translations WHERE en = 'Edit Setup Variables';
UPDATE translations SET es = 'Personalización de Campos' WHERE en = 'Custom Fields';
UPDATE translations SET es = 'Editar Personalización de Campos' WHERE en = 'Edit Custom Fields';
UPDATE translations SET es = 'Personalización de Tablas' WHERE en = 'Custom Table';
UPDATE translations SET es = 'Editar Personalización de Tablas' WHERE en = 'Edit Custom Table';
UPDATE translations SET es = 'Configuraciones del Sistema' WHERE en = 'System Configurations';
UPDATE translations SET es = 'Cuentas y Seguridad' WHERE en = 'Accounts and Security';
DELETE FROM translations WHERE en = 'ACCOUNTS &amp; SECURITY';
UPDATE translations SET es = 'Población' WHERE en = 'Population';
UPDATE translations SET es = 'Procesamiento de datos' WHERE en = 'Data Processing';
UPDATE translations SET es = 'Generar Informes' WHERE en = 'Generate Reports';
DELETE FROM translations WHERE en = 'Export Indicators';
UPDATE translations SET es = 'Procesos' WHERE en = 'Processes';
UPDATE translations SET es = 'Base de datos' WHERE en = 'Database';
UPDATE translations SET es = 'Backup' WHERE en = 'Backup';
UPDATE translations SET es = 'Restaurar' WHERE en = 'Restore';
UPDATE translations SET es = 'Configuración' WHERE en = 'Setup';
DELETE FROM translations WHERE en = 'The census data has been updated successfully.';
UPDATE translations SET es = 'No se requieren graduados.' WHERE en = 'Graduates not required.';
DELETE FROM translations WHERE en = 'No Custom Census Table.';
UPDATE translations SET es = 'Mensaje no encontrado.' WHERE en = 'Message Not Found.';
UPDATE translations SET es = 'Bueno' WHERE en = 'Good';
UPDATE translations SET es = 'Normal' WHERE en = 'Fair';
UPDATE translations SET es = 'Pobre' WHERE en = 'Poor';
UPDATE translations SET es = 'Contraseña actual' WHERE en = 'Current Password';
UPDATE translations SET es = 'Nueva Contraseña' WHERE en = 'New Password';
UPDATE translations SET es = 'Vuelva a introducir la Nueva Contraseña' WHERE en = 'Retype New Password';
UPDATE translations SET es = 'Nombre de usuario' WHERE en = 'Username';
UPDATE translations SET es = 'Ultimo Ingreso' WHERE en = 'Last Login';
UPDATE translations SET es = 'Ingresar su contraseña actual, por favor.' WHERE en = 'Please enter your current password.';
UPDATE translations SET es = 'Áreas' WHERE en = 'Areas';
UPDATE translations SET es = 'Visible' WHERE en = 'Visible';
UPDATE translations SET es = 'Nivel de Área ' WHERE en = 'Area Level';
UPDATE translations SET es = 'Niveles de Área' WHERE en = 'Area Levels';
UPDATE translations SET es = 'Editar Niveles de Área' WHERE en = 'Edit Area Levels';
UPDATE translations SET es = 'Estructura' WHERE en = 'Structure';
UPDATE translations SET es = 'Educación' WHERE en = 'Education';
UPDATE translations SET es = 'Nivel Educativo' WHERE en = 'Education Level';
UPDATE translations SET es = 'Sistema Educativo' WHERE en = 'Education System';
UPDATE translations SET es = 'Edad de Ingreso' WHERE en = 'Admission Age';
UPDATE translations SET es = 'Programa de Orientación' WHERE en = 'Programme Orientation';
UPDATE translations SET es = 'Orden' WHERE en = 'Order';
UPDATE translations SET es = 'Nivel ISCED' WHERE en = 'ISCED Level';
UPDATE translations SET es = 'Temas' WHERE en = 'Subjects';
UPDATE translations SET es = 'Duración' WHERE en = 'Duration';
UPDATE translations SET es = 'Grados' WHERE en = 'Grades';
UPDATE translations SET es = 'Tema del Grado' WHERE en = 'Grade Subject';
UPDATE translations SET es = 'Volver a los Programas ' WHERE en = 'Back to Programmes';
UPDATE translations SET es = 'Volver a los Grados' WHERE en = 'Back to Grades';
UPDATE translations SET es = 'Graduados' WHERE en = 'Graduates';
UPDATE translations SET es = 'Por favor selecciona un Programa primero' WHERE en = 'Please select a programme first.';
UPDATE translations SET es = 'Opción' WHERE en = 'Option';
UPDATE translations SET es = 'Una Opción' WHERE en = 'an option';
UPDATE translations SET es = 'Año Escolar' WHERE en = 'School Year';
UPDATE translations SET es = 'Actual' WHERE en = 'Current';
UPDATE translations SET es = 'Disponible' WHERE en = 'Available';
UPDATE translations SET es = 'Categorías' WHERE en = 'Categories';
UPDATE translations SET es = 'Construcciones' WHERE en = 'Buildings';
UPDATE translations SET es = 'Materiales' WHERE en = 'Materials';
UPDATE translations SET es = 'Recursos' WHERE en = 'Resources';
UPDATE translations SET es = 'Mobiliario' WHERE en = 'Furniture';
UPDATE translations SET es = 'Energía' WHERE en = 'Energy';
UPDATE translations SET es = 'Aulas' WHERE en = 'Rooms';
UPDATE translations SET es = 'Saneamiento' WHERE en = 'Sanitation';
UPDATE translations SET es = 'Agua' WHERE en = 'Water';
UPDATE translations SET es = 'Bancos' WHERE en = 'Banks';
UPDATE translations SET es = 'Ingresos de Capital ' WHERE en = 'Capital Income';
UPDATE translations SET es = 'Gastos de Capital' WHERE en = 'Capital Expenditure';
UPDATE translations SET es = 'Ingresos recurrentes' WHERE en = 'Recurrent Income';
UPDATE translations SET es = 'Gastos recurrentes' WHERE en = 'Recurrent Expenditure';
UPDATE translations SET es = 'Otros' WHERE en = 'Other';
UPDATE translations SET es = 'Instructivo' WHERE en = 'Instructional';
UPDATE translations SET es = 'Servicios de apoyo' WHERE en = 'Support Services';
UPDATE translations SET es = 'Instalaciones' WHERE en = 'Facilities';
UPDATE translations SET es = 'Certificados de Calificación' WHERE en = 'Qualification Certificates';
UPDATE translations SET es = 'Categoría de Calificación' WHERE en = 'Qualification Categories';
UPDATE translations SET es = 'Calificación de Instituciones ' WHERE en = 'Qualification Institutions';
UPDATE translations SET es = 'Categorías de Capacitaciones' WHERE en = 'Training Categories';
UPDATE translations SET es = 'Fuentes' WHERE en = 'Sources';
UPDATE translations SET es = 'Sucursales' WHERE en = 'Branches';
UPDATE translations SET es = 'Saneamientos' WHERE en = 'Sanitations';
UPDATE translations SET es = 'Salto de sección' WHERE en = 'Section Break';
UPDATE translations SET es = 'TEXT una línea' WHERE en = 'Single Line Text';
UPDATE translations SET es = 'TEXT multi líneas' WHERE en = 'Multi Line Text';
UPDATE translations SET es = 'Lista desplegable' WHERE en = 'Dropdown List';
UPDATE translations SET es = 'Checkboxes' WHERE en = 'Checkboxes';
UPDATE translations SET es = 'Personalización de Campos para Institución ' WHERE en = 'Institution Custom Fields';
DELETE FROM translations WHERE en = 'Institution Site Custom Fields';
DELETE FROM translations WHERE en = 'Census Custom Fields';
UPDATE translations SET es = 'Personalización de Campos para Estudiantes' WHERE en = 'Student Custom Fields';
UPDATE translations SET es = 'Personalización de Campos para Docentes' WHERE en = 'Teacher Custom Fields';
UPDATE translations SET es = 'Personalización de Campos para Personal' WHERE en = 'Staff Custom Fields';
DELETE FROM translations WHERE en = 'Census Custom Tables';
UPDATE translations SET es = 'Etiqueta del Campo' WHERE en = 'Field Label';
DELETE FROM translations WHERE en = 'List of Custom Census Table';
UPDATE translations SET es = 'Filtrar por' WHERE en = 'Filter by';
UPDATE translations SET es = 'Categoría X' WHERE en = 'X Category';
UPDATE translations SET es = 'Categoría Y' WHERE en = 'Y Category';
UPDATE translations SET es = 'Lista' WHERE en = 'List';
UPDATE translations SET es = 'Imagen del Dashboard' WHERE en = 'Dashboard Image';
UPDATE translations SET es = 'Volver a Configuración' WHERE en = 'Back to Config';
UPDATE translations SET es = 'Título' WHERE en = 'Title';
UPDATE translations SET es = 'Formato de la Fecha' WHERE en = 'Date Format';
UPDATE translations SET es = 'Moneda' WHERE en = 'Currency';
UPDATE translations SET es = 'Idioma' WHERE en = 'Language';
UPDATE translations SET es = 'Volver a la Lista' WHERE en = 'Back to List';
UPDATE translations SET es = 'Reconectando...' WHERE en = 'Reconnecting...';
UPDATE translations SET es = 'Contraseña' WHERE en = 'Password';
UPDATE translations SET es = 'Login' WHERE en = 'Login';
UPDATE translations SET es = 'Sistema de Información para la Gestión del Sistema Educativo' WHERE en = 'Education Management Information System';
UPDATE translations SET es = 'Función' WHERE en = 'Function';
DELETE FROM translations WHERE en = 'Institution Site';
DELETE FROM translations WHERE en = 'Export Indicator';
UPDATE translations SET es = 'Tablas personalizadas' WHERE en = 'Custom Tables';
UPDATE translations SET es = 'Configuraciones' WHERE en = 'Configurations';
UPDATE translations SET es = 'Roles' WHERE en = 'Role';
UPDATE translations SET es = 'Módulos' WHERE en = 'Modules';
UPDATE translations SET es = 'Añadir nuevo Usuario' WHERE en = 'Add New User';
UPDATE translations SET es = 'Inactivo' WHERE en = 'Inactive';
UPDATE translations SET es = 'Repita la Contraseña' WHERE en = 'Retype Password';
UPDATE translations SET es = 'Detalles del Usuario' WHERE en = 'User Details';
UPDATE translations SET es = 'Acceso completo a todos los módulos' WHERE en = 'Full access on all modules';
UPDATE translations SET es = 'Volver a Roles' WHERE en = 'Back to Roles';
UPDATE translations SET es = 'Version PHP ' WHERE en = 'PHP Version';
UPDATE translations SET es = 'Servidor Web ' WHERE en = 'Web Server';
UPDATE translations SET es = 'Sistema Operativo' WHERE en = 'Operating System';
UPDATE translations SET es = 'Datos no disponibles' WHERE en = 'Data not available.';
UPDATE translations SET es = 'El Informe seleccionado actualmente se está procesando.' WHERE en = 'The selected report is currently being processed.';
UPDATE translations SET es = 'Informes de Institución ' WHERE en = 'Institution Reports';
UPDATE translations SET es = 'Informes de Estudiante' WHERE en = 'Student Reports';
UPDATE translations SET es = 'Informes de Docente' WHERE en = 'Teacher Reports';
UPDATE translations SET es = 'Informes de Personal' WHERE en = 'Staff Reports';
UPDATE translations SET es = 'Informes Consolidados' WHERE en = 'Consolidated Reports';
UPDATE translations SET es = 'Informes de Indicador' WHERE en = 'Indicator Reports';
UPDATE translations SET es = 'Informes de Calidad de datos' WHERE en = 'Data Quality Reports';
UPDATE translations SET es = 'Informes de Calidad de datos' WHERE en = 'DataQuality Reports';
UPDATE translations SET es = 'Informes Personalizados' WHERE en = 'Custom Reports';
UPDATE translations SET es = 'Por favor contactar a' WHERE en = 'Please contact';
UPDATE translations SET es = 'para obtener más información sobre Informes Personalizados' WHERE en = 'for more information on Custom Reports.';
UPDATE translations SET es = 'Última ejecución' WHERE en = 'Last Run';
UPDATE translations SET es = 'Tipos' WHERE en = 'Types';
UPDATE translations SET es = 'Informes de Institución ' WHERE en = 'Institution Report';
UPDATE translations SET es = 'Lista de Instituciones' WHERE en = 'List of Institutions';
DELETE FROM translations WHERE en = 'Institution Additional Info Report';
DELETE FROM translations WHERE en = 'List of Institutions with additional info';
DELETE FROM translations WHERE en = 'Institution Site Report';
DELETE FROM translations WHERE en = 'List of Institution Sites';
DELETE FROM translations WHERE en = 'Institution Site Additional Info Report';
DELETE FROM translations WHERE en = 'List of Institution Sites with additional info';
UPDATE translations SET es = 'Informe de los Programas de la Institución' WHERE en = 'Institution Programme Report';
UPDATE translations SET es = 'Lista de Instituciones con Programas' WHERE en = 'List of Institutions with programmes';
UPDATE translations SET es = 'Informe de Cuentas bancarias de la Institución' WHERE en = 'Institution Bank Account Report';
UPDATE translations SET es = 'Lista de las Instituciones con cuentas bancarias' WHERE en = 'List of Institutions with bank accounts';
UPDATE translations SET es = 'Informe de Matrícula' WHERE en = 'Enrolment Report';
DELETE FROM translations WHERE en = 'Summary of enrolment from census';
UPDATE translations SET es = 'Informe de Clases' WHERE en = 'Class Report';
DELETE FROM translations WHERE en = 'Summary of classes from census';
UPDATE translations SET es = 'Informe de Libros de texto' WHERE en = 'Textbook Report';
DELETE FROM translations WHERE en = 'Summary of textbooks from census';
UPDATE translations SET es = 'Informe Docente' WHERE en = 'Teacher Report';
DELETE FROM translations WHERE en = 'Summary of teachers from census';
UPDATE translations SET es = 'Informe de Capacitaciones' WHERE en = 'Training Report';
DELETE FROM translations WHERE en = 'Summary of teacher training from census';
UPDATE translations SET es = 'Informe del Personal' WHERE en = 'Staff Report';
DELETE FROM translations WHERE en = 'Summary of staff from census';
DELETE FROM translations WHERE en = 'Census Additional Info Report';
DELETE FROM translations WHERE en = 'Summary of additional info from census';
DELETE FROM translations WHERE en = 'Census Tables';
DELETE FROM translations WHERE en = 'Summary of additional info tables from census';
UPDATE translations SET es = 'Informe de Ingreso' WHERE en = 'Income Report';
DELETE FROM translations WHERE en = 'Summary of income from census';
UPDATE translations SET es = 'Informe de Gastos' WHERE en = 'Expenditure Report';
DELETE FROM translations WHERE en = 'Summary of expenditure from census';
UPDATE translations SET es = 'Informe del Edificio' WHERE en = 'Building Report';
DELETE FROM translations WHERE en = 'Summary of buildings and condition from census';
UPDATE translations SET es = 'Informe de las Aulas' WHERE en = 'Rooms Report';
DELETE FROM translations WHERE en = 'Summary of rooms and condition from census';
UPDATE translations SET es = 'Informe de Saneamiento ' WHERE en = 'Sanitation Report';
DELETE FROM translations WHERE en = 'Summary of sanitation';
UPDATE translations SET es = 'Informe de Inmuebles' WHERE en = 'Furniture Report';
DELETE FROM translations WHERE en = 'Summary of furniture and condition from census';
UPDATE translations SET es = 'Informe de Recursos' WHERE en = 'Resource Report';
DELETE FROM translations WHERE en = 'Summary of resources and conditions from census';
UPDATE translations SET es = 'Informe de Energía' WHERE en = 'Energy Report';
DELETE FROM translations WHERE en = 'Summary of power and condition from census';
UPDATE translations SET es = 'Reporte del Agua' WHERE en = 'Water Report';
DELETE FROM translations WHERE en = 'Summary of water and condition from census';
UPDATE translations SET es = 'Informe del Estudiante' WHERE en = 'Student Report';
UPDATE translations SET es = 'Informe sobre los Estudiantes' WHERE en = 'Report on student';
DELETE FROM translations WHERE en = 'Students Additional Info Report';
DELETE FROM translations WHERE en = 'List of Students with additional info';
UPDATE translations SET es = 'Lista de Docentes' WHERE en = 'Teacher List';
UPDATE translations SET es = 'Informe sobre los Docentes' WHERE en = 'Report on Teachers';
DELETE FROM translations WHERE en = 'Teachers Additional Info Report';
DELETE FROM translations WHERE en = 'List of Teachers with additional info';
UPDATE translations SET es = 'Lista del Personal' WHERE en = 'Staff List';
UPDATE translations SET es = 'Informe sobre el Personal' WHERE en = 'Report on Staff';
DELETE FROM translations WHERE en = 'Staff Additional Info Report';
DELETE FROM translations WHERE en = 'List of Staff with additional info';
UPDATE translations SET es = '¿Dónde está el Informe de mi Escuela?' WHERE en = 'Wheres My School Report';
UPDATE translations SET es = 'Archivo de Google Earth (KML) que contiene la ubicación de todas las Sedes de las Instituciones' WHERE en = 'A Google Earth (KML) file containing all the location of all Institutions';
UPDATE translations SET es = 'Informe del Anuario' WHERE en = 'Year Book Report';
DELETE FROM translations WHERE en = 'Formatted summary of census data for a given year';
UPDATE translations SET es = 'Tasa de Rendimiento' WHERE en = 'Return Rate';
UPDATE translations SET es = 'Discrepancia de los datos del Censo' WHERE en = 'Census Discrepancy';
UPDATE translations SET es = 'Backup encontrado' WHERE en = 'Backup files found.';
UPDATE translations SET es = 'Personalizado' WHERE en = 'Custom';
UPDATE translations SET es = 'Consolidado' WHERE en = 'Consolidated';
UPDATE translations SET es = 'Calidad de los datos' WHERE en = 'Data Quality';
UPDATE translations SET es = 'Resumen de la Escuela' WHERE en = 'Summary of School';
UPDATE translations SET es = 'Informe de Escuelas sin responder.' WHERE en = 'Non-Responsive Schools Report';
DELETE FROM translations WHERE en = 'List of Institutions that do not contain census data for a given year';
UPDATE translations SET es = 'Informe de la Discrepancia de datos' WHERE en = 'Data Discrepancy Report';
DELETE FROM translations WHERE en = 'List of Institutions with questionable census data compared to the previous year';
DELETE FROM translations WHERE en = 'Number of students (enrollment) by sex';
DELETE FROM translations WHERE en = 'Number of teachers by sex';
DELETE FROM translations WHERE en = 'Number of staff by sex and locality';
UPDATE translations SET es = 'Informe de Instituciones ' WHERE en = 'Institutions Report';
DELETE FROM translations WHERE en = 'Number of institutions by provider and sector';
DELETE FROM translations WHERE en = 'Institution Sites Report';
DELETE FROM translations WHERE en = 'Number of institution sites by locality and type';
DELETE FROM translations WHERE en = 'Population Report';
DELETE FROM translations WHERE en = 'Number of people by sex and age';
DELETE FROM translations WHERE en = 'Net Enrolment Rate Report';
DELETE FROM translations WHERE en = 'Divide the number of pupils (or students) enrolled who are of the official age group for a given level of education by the population for the same age group and multiply the result by 100. This indicator has dimension values of sex';
DELETE FROM translations WHERE en = 'Gross Enrolment Rate Report';
DELETE FROM translations WHERE en = 'Net Intake Rate Report';
DELETE FROM translations WHERE en = 'Divide the number of children of official primary school-entrance age who enter the first grade of primary education for the first time by the population of the same age';
DELETE FROM translations WHERE en = 'Gross Intake Rate Report';
DELETE FROM translations WHERE en = 'Divide the number of pupils (or students) enrolled in a given level of education regardless of age by the population of the age group which officially corresponds to the given level of education';
DELETE FROM translations WHERE en = 'Divide the number of new entrants in grade 1';
DELETE FROM translations WHERE en = 'Repetition Rate Report';
DELETE FROM translations WHERE en = 'Divide the number of repeaters in a given grade in school year t+1 by the number of pupils from the same cohort enrolled in the same grade in the previous school year t . This indicator has dimension values of sex';
DELETE FROM translations WHERE en = 'Completion Rate Report';
DELETE FROM translations WHERE en = 'Divide the number of primary graduates';
DELETE FROM translations WHERE en = 'Survival Rate Report';
DELETE FROM translations WHERE en = 'Divide the total number of pupils belonging to a school-cohort who reached each successive grade of the specified level of education by the number of pupils in the school-cohort i.e. those originally enrolled in the first grade of primary education';
DELETE FROM translations WHERE en = 'Transition Rate Report';
DELETE FROM translations WHERE en = 'Divide the number of new entrants in the first grade of the specified higher cycle or level of education by the number of pupils who were enrolled in the final grade of the preceding cycle or level of education in the previous school year';
DELETE FROM translations WHERE en = 'Pupil Teacher Ratio Report';
DELETE FROM translations WHERE en = 'Divide the total number of pupils enrolled at the specified level of education by the number of teachers at the same level. This indicator has dimension values of locality';
DELETE FROM translations WHERE en = 'Pupil Class Ratio Report';
DELETE FROM translations WHERE en = 'Divide the total number of pupils enrolled at the specified level of education by the number of classes at the same level. This indicator has dimension values of locality and sector.';
DELETE FROM translations WHERE en = 'Trained Teachers Report';
DELETE FROM translations WHERE en = 'Divide the number of teachers of the specified level of education who have received the minimum required teacher training by the total number of teachers at the same level of education';
DELETE FROM translations WHERE en = 'Gender Parity Index (Students) Report';
DELETE FROM translations WHERE en = 'Divide the number of female students by the number of male students. This indicator has dimension values of sex';
DELETE FROM translations WHERE en = 'Female Students Report';
DELETE FROM translations WHERE en = 'Divide the number of female tertiary students enrolled in a specified ISCED level by the total number of students (male plus female) in that level in a given academic-year';
DELETE FROM translations WHERE en = 'Female Teachers Report';
DELETE FROM translations WHERE en = 'Divide the total number of female teachers at a given level of education by the total number of teachers (male and female) at the same level in a given school year';
DELETE FROM translations WHERE en = 'Private Enrolment Report';
DELETE FROM translations WHERE en = 'Divide the number of pupils (or students) enrolled in private educational institutions in a given level of education by total enrolment (public and private) at the same level of education';
DELETE FROM translations WHERE en = 'Pupil Textbook Ratio Report';
DELETE FROM translations WHERE en = 'Divide the total number of pupils enrolled at the specified level of education by the number of textbooks at the same level. This indicator has dimension values of locality';
DELETE FROM translations WHERE en = 'Water Sources Report';
DELETE FROM translations WHERE en = 'Number of water sources by type. This indicator has dimension values of locality';
DELETE FROM translations WHERE en = 'Sanitation Facilities Report';
DELETE FROM translations WHERE en = 'Number of sanitation facilities by type. This indicator has dimension values of locality';
DELETE FROM translations WHERE en = 'Public Expenditure per Education Level Report';
DELETE FROM translations WHERE en = 'Divide total public expenditure for each level of education in a given financial year by the total public expenditure on education for the same financial year and multiply the result by 100. ';
DELETE FROM translations WHERE en = 'Public Expenditure on Education Report';
DELETE FROM translations WHERE en = 'Divide total public expenditure on education incurred by all government agencies/departments in a given financial year by the total government expenditure for the same financial year and multiply by 100.';
DELETE FROM translations WHERE en = 'Public Expenditure on Education by GNP Report';
DELETE FROM translations WHERE en = 'Divide total public expenditure on education in a given financial year by the Gross National Product (GNP) or Gross National Income (GNI) of the country for the corresponding year and multiply by 100.';
DELETE FROM translations WHERE en = 'Public Expenditure per Pupil Report';
DELETE FROM translations WHERE en = 'Divide per pupil public current expenditure on each level of education in a given year by the GNI per capita for the same year and multiply by 100. This indicator has dimension values of level of education.';
UPDATE translations SET es = 'Procesos' WHERE en = 'Process';
UPDATE translations SET es = 'Cancelar todo' WHERE en = 'Abort All';
UPDATE translations SET es = 'Borrar todo' WHERE en = 'Clear All';
UPDATE translations SET es = 'Iniciando por' WHERE en = 'Started By';
UPDATE translations SET es = 'Fecha de Inicio' WHERE en = 'Started Date';
UPDATE translations SET es = 'Fecha Final' WHERE en = 'Finished Date';
UPDATE translations SET es = 'Registro' WHERE en = 'Log';
UPDATE translations SET es = 'Procesando' WHERE en = 'Processing';
UPDATE translations SET es = 'Pendiente' WHERE en = 'Pending';
UPDATE translations SET es = 'Cancelado' WHERE en = 'Aborted';
UPDATE translations SET es = 'Completado' WHERE en = 'Completed';
UPDATE translations SET es = 'Exportar a' WHERE en = 'Export To';
UPDATE translations SET es = 'Exportar' WHERE en = 'Export';
UPDATE translations SET es = 'No hay datos' WHERE en = 'No Data';
UPDATE translations SET es = 'PNB' WHERE en = 'GNP';
DELETE FROM translations WHERE en = 'Total Public Expenditure For Education';
UPDATE translations SET es = 'No hay registros financieros disponibles' WHERE en = 'No Available Finance Records';
DELETE FROM translations WHERE en = 'Gender Parity Index';
DELETE FROM translations WHERE en = 'Click the Generate button to create a restore point.';
DELETE FROM translations WHERE en = 'All data will be backed up except for following tables:';
UPDATE translations SET es = 'Seguridad de Usuarios' WHERE en = 'Security Users';
UPDATE translations SET es = 'Valores de Configuración del Sistema' WHERE en = 'System Configuration Values';
UPDATE translations SET es = 'Seguridad' WHERE en = 'Security';
DELETE FROM translations WHERE en = 'Please note that it may take sometime for the backup process to finish.';
UPDATE translations SET es = 'Ejecutar Informes ' WHERE en = 'Run Reports';
UPDATE translations SET es = 'Seleccionar todo' WHERE en = 'Select All';
UPDATE translations SET es = 'Cancelar toda la selección ' WHERE en = 'De-Select All';
UPDATE translations SET es = 'Generar' WHERE en = 'Generate';
UPDATE translations SET es = 'Archivos generados' WHERE en = 'Generated Files';
DELETE FROM translations WHERE en = 'The system will %s the current data and restore to your previous data based on the restore point you selected.';
DELETE FROM translations WHERE en = 'Please note that the system will not restore the following tables:';
DELETE FROM translations WHERE en = 'Below are the list of available backup dates';
UPDATE translations SET es = 'Archivos' WHERE en = 'Files';
UPDATE translations SET es = 'Formato no compatible' WHERE en = 'Format not support.';
UPDATE translations SET es = 'Archivo de Imagen es demasiado grande ' WHERE en = 'Image filesize too large.';
UPDATE translations SET es = 'Resolución demasiado grande.' WHERE en = 'Resolution too large.';
UPDATE translations SET es = 'Archivo subido con éxito.' WHERE en = 'File uploaded with success.';
UPDATE translations SET es = 'La imagen excede el tamaño máximo permitido en el sistema.' WHERE en = 'Image exceeds system max filesize.';
UPDATE translations SET es = 'La imagen excede el tamaño máximo permitido en el formulario HTML.' WHERE en = 'Image exceeds max file size in the HTML form.';
UPDATE translations SET es = 'La imagen fue parcialmente cargada.' WHERE en = 'Image was only partially uploaded.';
UPDATE translations SET es = 'No se ha cargado la imagen.' WHERE en = 'No image was uploaded.';
UPDATE translations SET es = 'Falta una carpeta temporal.' WHERE en = 'Missing a temporary folder.';
UPDATE translations SET es = 'No se pudo escribir el archivo en el disco.' WHERE en = 'Failed to write file to disk.';
UPDATE translations SET es = 'Una extensión PHP detuvo la carga de archivos.' WHERE en = 'A PHP extension stopped the file upload.';
UPDATE translations SET es = 'Resolución máxima:' WHERE en = 'Max Resolution:';
UPDATE translations SET es = 'Tamaño máximo permitido:' WHERE en = 'Max File Size:';
UPDATE translations SET es = 'Formato admitido:' WHERE en = 'Format Supported:';
UPDATE translations SET es = 'Imagen de Perfil ' WHERE en = 'Profile Image';
UPDATE translations SET es = 'Por favor, introduzca el código de Área.' WHERE en = 'Please enter the code for the Area.';
UPDATE translations SET es = 'Hay códigos de Área duplicados.' WHERE en = 'There are duplicate area code.';
UPDATE translations SET es = 'Introduzca el nombre del Área' WHERE en = 'Please enter the name for the Area.';
UPDATE translations SET es = 'Por favor proporcione una imagen válida.' WHERE en = 'Please supply a valid image.';
UPDATE translations SET es = 'Por favor ingrese un nombre para el Campo de Estudio.' WHERE en = 'Please enter a name for the Field of Study.';
UPDATE translations SET es = 'Este Campo de Estudio ya existe en el Sistema.' WHERE en = 'This Field of Study already exists in the system.';
UPDATE translations SET es = 'Por favor, seleccione el Programa de Orientación.' WHERE en = 'Please select the programme orientation.';
UPDATE translations SET es = 'Por favor, introduzca un nombre de la Asignatura' WHERE en = 'Please enter a name for the Subject.';
UPDATE translations SET es = 'Esta Asignatura ya existe en el Sistema.' WHERE en = 'This subject already exists in the system.';
UPDATE translations SET es = 'Ha introducido un nombre de usuario o contraseña inválido' WHERE en = 'You have entered an invalid username or password.';
UPDATE translations SET es = 'Por favor introduzca una duración.' WHERE en = 'Please enter a duration.';
UPDATE translations SET es = 'Agregar Programa' WHERE en = 'Add Programme';
DELETE FROM translations WHERE en = 'You are able to delete this record in the database. <br><br>All related information of this record will also be deleted.<br><br>Are you sure you want to do this?';
UPDATE translations SET es = 'Eliminando el archivo adjunto ...' WHERE en = 'Deleting attachment...';
UPDATE translations SET es = 'Eliminar archivo adjunto' WHERE en = 'Delete Attachment';
UPDATE translations SET es = '¿Desea eliminar este registro?' WHERE en = 'Do you wish to delete this record?';
UPDATE translations SET es = 'Actualizando archivo adjunto ...' WHERE en = 'Updating attachment...';
UPDATE translations SET es = '!La Sucursal del Banco es necesaria!' WHERE en = 'Bank Branch is required!';
UPDATE translations SET es = 'Valor' WHERE en = 'Value';
UPDATE translations SET es = 'Datos sin guardar' WHERE en = 'Unsaved Data';
DELETE FROM translations WHERE en = 'Please save your data before proceed. <br><br>Do you want to save now?';
UPDATE translations SET es = '¿Está seguro que quiere salir?' WHERE en = 'Are you sure you want to leave?';
DELETE FROM translations WHERE en = 'GNP value is required.';
UPDATE translations SET es = '!Categoría es requerida!' WHERE en = 'Category is required!';
UPDATE translations SET es = '!Certificado es requerido!' WHERE en = 'Certificate is required!';
DELETE FROM translations WHERE en = 'Institute is required!';
UPDATE translations SET es = 'Seleccione un país antes de añadir nuevos registros' WHERE en = 'Please select a country before adding new records.';
UPDATE translations SET es = 'Ha ocurrido un Error.' WHERE en = 'Error has occurred.';
UPDATE translations SET es = 'La edad no puede estar vacía' WHERE en = 'Age cannot be empty.';
UPDATE translations SET es = 'La edad debe ser más que 0.' WHERE en = 'Age must be more then 0.';
UPDATE translations SET es = 'No' WHERE en = 'No';
UPDATE translations SET es = 'Sí' WHERE en = 'Yes';
UPDATE translations SET es = 'Diálogo' WHERE en = 'Dialog';
UPDATE translations SET es = 'Campo requerido' WHERE en = 'Required Field';
UPDATE translations SET es = 'Recuperando ...' WHERE en = 'Retrieving...';
UPDATE translations SET es = 'Añadiendo fila ...' WHERE en = 'Adding row...';
UPDATE translations SET es = 'Añadiendo opción ...' WHERE en = 'Adding option...';
UPDATE translations SET es = 'Cargando ...' WHERE en = 'Loading...';
UPDATE translations SET es = 'Cargar lista ...' WHERE en = 'Loading list...';
UPDATE translations SET es = '!Archivo requerido!' WHERE en = 'File is required!';
UPDATE translations SET es = '!Estatus es requerido!' WHERE en = 'Status is required!';
UPDATE translations SET es = 'Mover hacia arriba' WHERE en = 'Move Up';
UPDATE translations SET es = 'Mover hacia abajo' WHERE en = 'Move Down';
UPDATE translations SET es = 'Alternar este campo activo / inactivo' WHERE en = 'Toggle this field active/inactive';
UPDATE translations SET es = 'Confirmar eliminación' WHERE en = 'Delete Confirmation';
UPDATE translations SET es = 'Haga clic para ignorar' WHERE en = 'Click to dismiss';
UPDATE translations SET es = 'No se puede añadir Áreas. <br /> por favor, crear Nivel de Área antes de añadir Áreas' WHERE en = 'Unable to add Areas.<br/>Please create Area Level before adding Areas.';
UPDATE translations SET es = 'Guardando por favor espere ...' WHERE en = 'Saving please wait...';
UPDATE translations SET es = 'Cargando Áreas ' WHERE en = 'Loading Areas';
DELETE FROM translations WHERE en = 'Batch executed successfully.';
DELETE FROM translations WHERE en = 'Running batch...';
UPDATE translations SET es = 'Agregando Campo ...' WHERE en = 'Adding Field...';
UPDATE translations SET es = 'Por favor, seleccione una Fecha de Inicio válida.' WHERE en = 'Please select a valid Start Date';
UPDATE translations SET es = 'Por favor, seleccione una Fecha de Finalización válida.' WHERE en = 'Please select a valid End Date';
UPDATE translations SET es = 'Por favor agregar un Programa a esta Sede de Institución.' WHERE en = 'Please add a programme to this institution site.';
UPDATE translations SET es = 'Informe de coordenadas que faltan.' WHERE en = 'Missing Coordinates Report';
UPDATE translations SET es = 'Lista de Instituciones con valores de latitud y longitud de 0 o nulo.' WHERE en = 'List of Institutions with latitude and/or longitude values of 0 or null';
DELETE FROM translations WHERE en = 'OLAP Report';
DELETE FROM translations WHERE en = 'OLAP Reports';
UPDATE translations SET es = 'Generado' WHERE en = 'Generated';
DELETE FROM translations WHERE en = 'Generating OLAP Report...';
DELETE FROM translations WHERE en = 'Indicator';
DELETE FROM translations WHERE en = 'Sub Groups';
DELETE FROM translations WHERE en = 'Time Period';
DELETE FROM translations WHERE en = 'Data Value';
UPDATE translations SET es = 'Clasificación' WHERE en = 'Classification';
UPDATE translations SET es = 'Información' WHERE en = 'Information';
UPDATE translations SET es = 'Nombre Área' WHERE en = 'Area Name';
UPDATE translations SET es = 'Nombre del Programa de Educación' WHERE en = 'Education Programme Name';
UPDATE translations SET es = 'Nombre de la cuenta bancaria' WHERE en = 'Bank Account Name';
UPDATE translations SET es = 'Número de cuenta bancaria' WHERE en = 'Bank Account Number';
UPDATE translations SET es = 'Cuenta bancaria activa' WHERE en = 'Bank Account Active';
UPDATE translations SET es = 'Nombre de Sucursal del Banco ' WHERE en = 'Bank Branch Name';
UPDATE translations SET es = 'Año escolar' WHERE en = 'Academic Year';
UPDATE translations SET es = 'Nombre  del grado educativo ' WHERE en = 'Education Grade Name';
UPDATE translations SET es = 'Clase' WHERE en = 'Class';
UPDATE translations SET es = 'Nombre de la Asignatura' WHERE en = 'Education Subject Name';
UPDATE translations SET es = 'Número de libros de texto' WHERE en = 'No Of Textbooks';
UPDATE translations SET es = 'Categoría de cuadrícula X' WHERE en = 'Grid X Category';
UPDATE translations SET es = 'Categoría de cuadrícula Y' WHERE en = 'Grid Y Category';
UPDATE translations SET es = 'Edificio' WHERE en = 'Building';
UPDATE translations SET es = 'Material' WHERE en = 'Material';
UPDATE translations SET es = 'Aula' WHERE en = 'Room';
UPDATE translations SET es = 'Recurso' WHERE en = 'Resource';
UPDATE translations SET es = 'Nombre del Estudiante' WHERE en = 'Student Name';
UPDATE translations SET es = 'Nombre del Docente' WHERE en = 'Teacher Name';
UPDATE translations SET es = 'Nombre del Personal' WHERE en = 'Staff Name';
DELETE FROM translations WHERE en = 'Available Variables';
DELETE FROM translations WHERE en = 'Selected Variables';
UPDATE translations SET es = 'Institución de Proveedores' WHERE en = 'Institution Provider';
UPDATE translations SET es = 'Estatus de la Institución' WHERE en = 'Institution Status (حالة المدرسة)';
DELETE FROM translations WHERE en = 'Institution Site Locality';
DELETE FROM translations WHERE en = 'Institution Site Type';
DELETE FROM translations WHERE en = 'Institution Site Ownership';
DELETE FROM translations WHERE en = 'Institution Site Status';
UPDATE translations SET es = 'Programa educativo' WHERE en = 'Education Programme';
UPDATE translations SET es = 'Grado educativo ' WHERE en = 'Education Grade';
UPDATE translations SET es = 'Categoría de estudiantes' WHERE en = 'Student Category';
DELETE FROM translations WHERE en = 'No backup files found.';
UPDATE translations SET es = 'Anuario' WHERE en = 'Yearbook';
UPDATE translations SET es = 'Nombre de la Organización' WHERE en = 'Organization Name';
UPDATE translations SET es = 'Año de la Publicación' WHERE en = 'Publication Date';
UPDATE translations SET es = 'Logo' WHERE en = 'Logo';
UPDATE translations SET es = 'Datos atípicos ' WHERE en = 'Data Outliers';
DELETE FROM translations WHERE en = 'Census Numbers that have age or total of male and female numbers beyond the specified range in settings.';
UPDATE translations SET es = 'Orientación de página' WHERE en = 'Page Orientation';
UPDATE translations SET es = 'Logo del Anuario' WHERE en = 'Yearbook Logo';
UPDATE translations SET es = 'Edad máxima del estudiante' WHERE en = 'Maximum Student Age';
UPDATE translations SET es = 'Edad mínima del estudiante' WHERE en = 'Minimum Student Age';
UPDATE translations SET es = 'Número máximo de estudiantes' WHERE en = 'Maximum Student Number';
UPDATE translations SET es = 'Número mínimo de estudiantes' WHERE en = 'Minimum Student Number';
UPDATE translations SET es = 'Discrepancia de datos' WHERE en = 'Data Discrepancy';
UPDATE translations SET es = 'Variación de la Discrepancia de datos (%)' WHERE en = 'Data Discrepancy Variation(%)';
UPDATE translations SET es = 'Año Anterior' WHERE en = 'Previous Year';
UPDATE translations SET es = 'Masculinos del año anterior' WHERE en = 'Previous Year Male';
UPDATE translations SET es = 'Femeninos del año anterior' WHERE en = 'Previous Year Female';
UPDATE translations SET es = 'Informes de Discrepancia de datos' WHERE en = 'Data Discrepancy Reports';
UPDATE translations SET es = 'Matrices de valoración' WHERE en = 'Rubrics';
UPDATE translations SET es = 'Calidad - Matrices de valoración' WHERE en = 'Quality - Rubrics';
DELETE FROM translations WHERE en = 'Weighthings';
UPDATE translations SET es = 'Abrobado' WHERE en = 'Pass Mark';
UPDATE translations SET es = 'Atrás' WHERE en = 'Back';
UPDATE translations SET es = 'Modificado por' WHERE en = 'Modified By';
UPDATE translations SET es = 'Modificado el' WHERE en = 'Modified On';
UPDATE translations SET es = 'Creado por' WHERE en = 'Created By';
UPDATE translations SET es = 'Creado en' WHERE en = 'Created On';
UPDATE translations SET es = 'Ver Matrices de valoración' WHERE en = 'View Rubric';
UPDATE translations SET es = 'Descripción' WHERE en = 'Descriptions';
UPDATE translations SET es = 'Calidad' WHERE en = 'Quality';
UPDATE translations SET es = 'Detalles de las Matrices de Valoración' WHERE en = 'Rubric Details';
UPDATE translations SET es = 'Calidad - Detalles de las Matrices de valoración' WHERE en = 'Quality - Rubric Details';
UPDATE translations SET es = 'Calidad - Editar Detalles de las Matrices de valoración.' WHERE en = 'Quality - Edit Rubric Details';
UPDATE translations SET es = 'Agregar Título' WHERE en = 'Add Heading';
UPDATE translations SET es = 'Agregar criterio de fila' WHERE en = 'Add Criteria Row';
UPDATE translations SET es = 'Agregar nivel de columna' WHERE en = 'Add Level Column';
UPDATE translations SET es = 'Editar detalles de las Matrices de valoración.' WHERE en = 'Edit Rubric Details';
UPDATE translations SET es = 'Visitas' WHERE en = 'Visits';
UPDATE translations SET es = 'Calidad - Matrices de valoración.' WHERE en = 'Quality - Rubric';
UPDATE translations SET es = 'Supervisor' WHERE en = 'Supervisor';
UPDATE translations SET es = 'Calidad - Detalles de las Matrices de valoración.' WHERE en = 'Quality - Rubric Detail';
UPDATE translations SET es = 'Color' WHERE en = 'Color';
UPDATE translations SET es = 'Calidad - Visitas' WHERE en = 'Quality - Visit';
UPDATE translations SET es = 'Fecha' WHERE en = 'Date';
UPDATE translations SET es = 'Comentarios' WHERE en = 'Comment';
UPDATE translations SET es = 'Tipo de visita' WHERE en = 'Visit Type';
UPDATE translations SET es = 'Código nacional' WHERE en = 'National Code';
UPDATE translations SET es = 'Código internacional' WHERE en = 'International Code';
UPDATE translations SET es = 'Calidad - Agregar Matrices de valoración.' WHERE en = 'Quality - Add Rubric';
UPDATE translations SET es = 'Agregar Matrices de valoración.' WHERE en = 'Add Rubric';
UPDATE translations SET es = 'Calidad - Información sobre las Matrices de valoración.' WHERE en = 'Quality - Rubric Infomations';
UPDATE translations SET es = 'Información sobre las Matrices de valoración.' WHERE en = 'Rubric Infomations';
UPDATE translations SET es = 'Calidad - Editar Matrices de valoración.' WHERE en = 'Quality - Edit Rubric';
UPDATE translations SET es = 'Editar Matrices de valoración.' WHERE en = 'Edit Rubric';
UPDATE translations SET es = 'Calidad - Criterios de instalación para las Matrices de valoración' WHERE en = 'Quality - Setup Rubric Criteria';
UPDATE translations SET es = 'Criterios de instalación para las Matrices de valoración' WHERE en = 'Setup Rubric Criteria';
UPDATE translations SET es = 'Calidad - Agregar Criterios para las Matrices de valoración' WHERE en = 'Quality - Add Rubric Criteria';
UPDATE translations SET es = 'Agregar Criterios para las Matrices de valoración' WHERE en = 'Add Rubric Criteria';
UPDATE translations SET es = 'Calidad - Editar Criterios de las Matrices de valoración' WHERE en = 'Quality - Edit Rubric Criteria';
UPDATE translations SET es = 'Editar Criterios de las Matrices de valoración' WHERE en = 'Edit Rubric Criteria';
UPDATE translations SET es = 'Calidad - Criterios de las Matrices de valoración' WHERE en = 'Quality - Rubric Criteria';
UPDATE translations SET es = 'Criterios de las Matrices de valoración' WHERE en = 'Rubric Criteria';
UPDATE translations SET es = 'Calidad - Estatus' WHERE en = 'Quality - Status';
UPDATE translations SET es = 'Calidad - Agregar Estatus' WHERE en = 'Quality - Add Status';
UPDATE translations SET es = 'Calidad - Editar Estatus' WHERE en = 'Quality - Edit Status';
UPDATE translations SET es = 'Calidad - Agregar Matrices de valoración.' WHERE en = 'Quality - Add Rubrics';
UPDATE translations SET es = 'Calidad - Editar Matrices de valoración' WHERE en = 'Quality - Edit Rubrics';
UPDATE translations SET es = 'Calidad - Agregar visitas' WHERE en = 'Quality - Add Visit';
UPDATE translations SET es = 'Calidad - Editar visitas' WHERE en = 'Quality - Edit Visit';
UPDATE translations SET es = 'identidades' WHERE en = 'Identities';
UPDATE translations SET es = 'Detalles de identidad' WHERE en = 'Identity Details';
UPDATE translations SET es = 'Editar Detalles de identidad' WHERE en = 'Edit Identity Details';
UPDATE translations SET es = 'Número' WHERE en = 'Number';
UPDATE translations SET es = 'Emitido' WHERE en = 'Issued';
UPDATE translations SET es = 'Expiración' WHERE en = 'Expiry';
UPDATE translations SET es = 'Fecha de emisión' WHERE en = 'Issue Date';
UPDATE translations SET es = 'Fecha de expiración' WHERE en = 'Expiry Date';
UPDATE translations SET es = 'Lugar de emisión' WHERE en = 'Issue Location';
UPDATE translations SET es = 'Cursos' WHERE en = 'Courses';
UPDATE translations SET es = 'Sesiones' WHERE en = 'Sessions';
UPDATE translations SET es = 'Todo' WHERE en = 'All';
UPDATE translations SET es = 'Nuevo' WHERE en = 'New';
UPDATE translations SET es = 'Pendiente de aprobación' WHERE en = 'Pending for Approval';
UPDATE translations SET es = 'Código del Curso' WHERE en = 'Course Code';
UPDATE translations SET es = 'Título del Curso' WHERE en = 'Course Title';
UPDATE translations SET es = 'Descripción del Curso' WHERE en = 'Course Description';
UPDATE translations SET es = 'Meta / Objetivos' WHERE en = 'Goal / Objectives';
UPDATE translations SET es = 'Categoría / Campo de estudio' WHERE en = 'Category / Field of Study';
UPDATE translations SET es = 'Población objetivo' WHERE en = 'Target Population';
UPDATE translations SET es = 'Agregar Población objetivo' WHERE en = 'Add Target Population';
UPDATE translations SET es = 'Créditos por hora' WHERE en = 'Credit Hours';
UPDATE translations SET es = 'Modo de entrega' WHERE en = 'Mode of Delivery';
UPDATE translations SET es = 'Proovedor de Capacitación' WHERE en = 'Training Provider';
UPDATE translations SET es = 'Requisitos de Capacitación' WHERE en = 'Training Requirement';
UPDATE translations SET es = 'Nivel de Capacitación' WHERE en = 'Training Level';
UPDATE translations SET es = 'Prerequisitos' WHERE en = 'Prerequisite';
UPDATE translations SET es = 'Agregar Prerequisito' WHERE en = 'Add Prerequisite';
UPDATE translations SET es = 'Resultado para aprobar' WHERE en = 'Pass Result';
UPDATE translations SET es = 'Enviar para su aprobación' WHERE en = 'Submit for Approval';
UPDATE translations SET es = 'Detalles de los Cursos' WHERE en = 'Courses Details';
UPDATE translations SET es = 'Detalles de las Sesiones' WHERE en = 'Sessions Details';
UPDATE translations SET es = 'Inactivo' WHERE en = 'Inactivate';
UPDATE translations SET es = 'Activo' WHERE en = 'Activate';
UPDATE translations SET es = 'Instructor' WHERE en = 'Trainer';
UPDATE translations SET es = 'Instructores' WHERE en = 'Trainees';
UPDATE translations SET es = 'Curso' WHERE en = 'Course';
UPDATE translations SET es = 'Detalles de los Resultados' WHERE en = 'Results Details';
UPDATE translations SET es = 'Necesidades' WHERE en = 'Needs';
UPDATE translations SET es = 'Autodidáctico' WHERE en = 'Self-Study';
UPDATE translations SET es = 'Crédito' WHERE en = 'Credit';
UPDATE translations SET es = 'Necesidades de la Capacitación' WHERE en = 'Training Needs';
UPDATE translations SET es = 'Detalles de las Necesidades de la Capacitación' WHERE en = 'Training Needs Details';
UPDATE translations SET es = 'Prioridad' WHERE en = 'Priority';
UPDATE translations SET es = 'Resultados de la Capacitación' WHERE en = 'Training Results';
UPDATE translations SET es = 'Resultados detallados de la Capacitación' WHERE en = 'Training Results Details';
UPDATE translations SET es = 'Capacitación Autodidacta' WHERE en = 'Training Self Study';
UPDATE translations SET es = 'Detalles de la capacitación autodidacta' WHERE en = 'Training Self Study Details';
UPDATE translations SET es = 'Resultado' WHERE en = 'Result';
UPDATE translations SET es = 'Créditos' WHERE en = 'Credits';
UPDATE translations SET es = 'Comentarios' WHERE en = 'Comments';
UPDATE translations SET es = 'Sus datos han sido guardados correctamente.' WHERE en = 'Your data has been saved successfully.';
UPDATE translations SET es = 'Seleccione un Tipo, por favor.' WHERE en = 'Please select a Type';
UPDATE translations SET es = 'Introduzca un Mensaje válido, por favor.' WHERE en = 'Please enter a valid Message';
UPDATE translations SET es = 'Introduzca un Número válido, por favor.' WHERE en = 'Please enter a valid Number';
UPDATE translations SET es = 'Introduzca una Localización de emisión válida, por favor.' WHERE en = 'Please enter a valid Issue Location';
UPDATE translations SET es = 'La Fecha de expiración debe ser mayor que la Fecha de Emisión' WHERE en = 'Expiry Date must be greater than Issue Date';
UPDATE translations SET es = 'Nacionalidades' WHERE en = 'Nationalities';
UPDATE translations SET es = 'Detalles de Nacionalidad' WHERE en = 'Nationality Details';
UPDATE translations SET es = 'Editar Detalles de Nacionalidad' WHERE en = 'Edit Nationality Details';
UPDATE translations SET es = 'Seleccione un País, por favor.' WHERE en = 'Please select a Country';
UPDATE translations SET es = 'SMS' WHERE en = 'SMS';
UPDATE translations SET es = 'Mensajes' WHERE en = 'Messages';
UPDATE translations SET es = 'Respuestas' WHERE en = 'Responses';
UPDATE translations SET es = 'Registros' WHERE en = 'Logs';
UPDATE translations SET es = 'Habilitado' WHERE en = 'Enabled';
UPDATE translations SET es = 'Mensaje' WHERE en = 'Message';
UPDATE translations SET es = 'Agregar Mensaje' WHERE en = 'Add Message';
UPDATE translations SET es = 'Detalles del Mensaje' WHERE en = 'Message Details';
UPDATE translations SET es = 'Editar Detalles del Mensaje' WHERE en = 'Edit Message Details';
UPDATE translations SET es = 'Todos los registros se han eliminado con éxito.' WHERE en = 'All logs have been deleted successfully.';
UPDATE translations SET es = 'Todas las respuestas se han eliminado con éxito.' WHERE en = 'All responses have been deleted successfully.';
UPDATE translations SET es = 'Descargar' WHERE en = 'Download';
UPDATE translations SET es = 'Enviado' WHERE en = 'Sent';
UPDATE translations SET es = 'Recibido' WHERE en = 'Received';
UPDATE translations SET es = 'Advertencia' WHERE en = 'Warning';
UPDATE translations SET es = 'Continuar' WHERE en = 'Continue';
UPDATE translations SET es = 'Nota: por favor limpiar la pagina de respuestas ya que las respuestas no coinciden con los mensajes ' WHERE en = 'Note: Please clear the Responses page as existing responses may no longer match the updated Messages.';
UPDATE translations SET es = '¿Desea usted borrar todos los registros?' WHERE en = 'Do you wish to clear all records?';
UPDATE translations SET es = 'Fecha / Hora' WHERE en = 'Date/Time';
UPDATE translations SET es = 'Confirmación' WHERE en = 'Confirmation';
UPDATE translations SET es = 'Confirmar' WHERE en = 'Confirm';
UPDATE translations SET es = '¿Desea usted inactivar este registro?' WHERE en = 'Do you wish to inactivate this record?';
UPDATE translations SET es = '¿Desea usted activar este registro?' WHERE en = 'Do you wish to activate this record?';
UPDATE translations SET es = 'Campo de estudios' WHERE en = 'Field of Studies';
UPDATE translations SET es = 'Niveles' WHERE en = 'Levels';
UPDATE translations SET es = 'Modo de entregas' WHERE en = 'Mode of Deliveries';
UPDATE translations SET es = 'Prioridades' WHERE en = 'Priorities';
UPDATE translations SET es = 'Proveedores' WHERE en = 'Providers';
UPDATE translations SET es = 'Requisitos' WHERE en = 'Requirements';
UPDATE translations SET es = 'Estatus' WHERE en = 'Statuses';
UPDATE translations SET es = 'Editar Opciones de Campo' WHERE en = 'Edit Field Options';
UPDATE translations SET es = 'Agregar aprendiz' WHERE en = 'Add Trainee';
UPDATE translations SET es = 'Número de identificación' WHERE en = 'Identification No';
UPDATE translations SET es = 'Aprobar' WHERE en = 'Pass';
UPDATE translations SET es = 'Tipo de Curso' WHERE en = 'Course Type';
UPDATE translations SET es = 'Tipos de Cursos' WHERE en = 'Course Types';
UPDATE translations SET es = 'Agregar un Nivel de Área en esta Área, por favor.' WHERE en = 'Please add an area level to this area';
UPDATE translations SET es = 'No hay evaluaciones' WHERE en = 'There are no assessments';
UPDATE translations SET es = 'Grupos' WHERE en = 'Groups';
DELETE FROM translations WHERE en = 'Custom Indicators';
UPDATE translations SET es = 'Construir' WHERE en = 'Build';
UPDATE translations SET es = 'Acción' WHERE en = 'Action';
DELETE FROM translations WHERE en = 'For more information on building custom indicators';
DELETE FROM translations WHERE en = 'Institution Sites';
DELETE FROM translations WHERE en = 'Institution Sites Total';
DELETE FROM translations WHERE en = 'There are no available files found for this report';
UPDATE translations SET es = 'Informe de la Calidad de la Garantía' WHERE en = 'Quality Assurance Report';
UPDATE translations SET es = 'Formato' WHERE en = 'Format';
UPDATE translations SET es = 'Períodos de tiempo' WHERE en = 'Time Periods';
UPDATE translations SET es = 'Busqueda avanzada' WHERE en = 'Advance Search';
UPDATE translations SET es = 'OpenEMIS ID' WHERE en = 'OpenEMIS ID';
UPDATE translations SET es = 'Segundo Nombre' WHERE en = 'Middle Name';
UPDATE translations SET es = 'Nombre preferido' WHERE en = 'Preferred Name';
UPDATE translations SET es = 'Fecha de defunción' WHERE en = 'Date of Death';
UPDATE translations SET es = 'Ningún archivo elegido' WHERE en = 'No File Chosen';
UPDATE translations SET es = 'Máxima resolución' WHERE en = 'Max Resolution';
UPDATE translations SET es = 'Su solicitud ha caducado. Por favor, inténtelo de nuevo.' WHERE en = 'Your request has been timed out. Please try again.';
UPDATE translations SET es = 'SOBREESCRIBIR TODO' WHERE en = 'OVERWRITE ALL';
UPDATE translations SET es = 'Resolución máxima: %s pixels' WHERE en = 'Max Resolution: %s pixels';
UPDATE translations SET es = 'Escoja un Archivo' WHERE en = 'Choose File';
UPDATE translations SET es = 'El archivo no se ha actualizado correctamente.' WHERE en = 'File have not been updated successfully.';
DELETE FROM translations WHERE en = 'Completion Rate / Gross Primary Graduation Ratio.';
DELETE FROM translations WHERE en = 'Divide the number of primary graduates.';
DELETE FROM translations WHERE en = 'Percentage of Trained Teachers.';
DELETE FROM translations WHERE en = 'Divide the female value of a given indicator by that of the male.';
DELETE FROM translations WHERE en = 'Percent of Female Students.';
DELETE FROM translations WHERE en = 'Percent of Female Teachers.';
DELETE FROM translations WHERE en = 'Percentage of Private Enrolment.';
DELETE FROM translations WHERE en = 'Percentage of schools with improved drinking water sources.';
DELETE FROM translations WHERE en = 'Percentage of schools with adequate sanitation facilities.';
DELETE FROM translations WHERE en = 'Public Expenditure as percentage of Total Public Expenditure on Education';
DELETE FROM translations WHERE en = 'Divide public current expenditure devoted to each level of education by the total public current expenditure on education';
DELETE FROM translations WHERE en = 'Public Expenditure as percentage of Total Government Expenditure';
DELETE FROM translations WHERE en = 'Public Expenditure as percentage of Gross National Product (GNP)';
DELETE FROM translations WHERE en = 'Number of teachers';
DELETE FROM translations WHERE en = 'Net Admission Rate / Net Intake Rate';
DELETE FROM translations WHERE en = 'Gross Admission Rate / Gross Intake Rate';
DELETE FROM translations WHERE en = 'Summary report on Census Additional Information about enrolment';
DELETE FROM translations WHERE en = 'Summary report on Buildings';
DELETE FROM translations WHERE en = 'Report on Rooms';
DELETE FROM translations WHERE en = 'Report on number of Sanitations and Conditions';
DELETE FROM translations WHERE en = 'Report on number of Furnitures and its conditions';
DELETE FROM translations WHERE en = 'Report on number and kinds of Resources as well as Condition status';
DELETE FROM translations WHERE en = 'Report on Power sources';
DELETE FROM translations WHERE en = 'Report on number of Powers types and Conditions';
DELETE FROM translations WHERE en = 'Report on Water';
DELETE FROM translations WHERE en = 'Report on conditions';
UPDATE translations SET es = 'Lista de estudiantes.' WHERE en = 'Student List';
DELETE FROM translations WHERE en = 'Report on student additional info';
DELETE FROM translations WHERE en = 'Report on Teachers Additional Info';
DELETE FROM translations WHERE en = 'Report on Staff Additional Info';
UPDATE translations SET es = 'Listado de instituciones.' WHERE en = 'List of institution';
DELETE FROM translations WHERE en = 'Enrolment Summary';
UPDATE translations SET es = 'Listado de Clases' WHERE en = 'Class List';
UPDATE translations SET es = 'Informe sobre las Clases' WHERE en = 'Report on classes';
DELETE FROM translations WHERE en = 'Summary report on Textbooks';
DELETE FROM translations WHERE en = 'Summary report on Teachers';
UPDATE translations SET es = 'Capacitaciones' WHERE en = 'Trainings';
DELETE FROM translations WHERE en = 'Staffs';
DELETE FROM translations WHERE en = 'Summary report on Institution Staffs';
DELETE FROM translations WHERE en = 'Summary report on Census Table Related Information';
DELETE FROM translations WHERE en = 'Summary report on income';
DELETE FROM translations WHERE en = 'Report on expenditure';
UPDATE translations SET es = 'Listado de instituciones.' WHERE en = 'Institution List';
UPDATE translations SET es = 'Informe sobre las instituciones.' WHERE en = 'Report on institutions';
DELETE FROM translations WHERE en = 'Institution Site List';
DELETE FROM translations WHERE en = 'Report on institution sites';
DELETE FROM translations WHERE en = 'Please contact support for more information on Custom Report.';
DELETE FROM translations WHERE en = 'This is a report on classes';
DELETE FROM translations WHERE en = 'This is a report on student';
DELETE FROM translations WHERE en = 'This is a report on Teachers';
DELETE FROM translations WHERE en = 'This is a report on Staff';
UPDATE translations SET es = 'Asistente' WHERE en = 'Wizard';
UPDATE translations SET es = 'Omitir' WHERE en = 'Skip';
UPDATE translations SET es = 'ha sido eliminado correctamente.' WHERE en = 'have been deleted successfully.';
UPDATE translations SET es = 'Editar adjuntos' WHERE en = 'Edit Attachments';
DELETE FROM translations WHERE en = 'Institution Site Name';
UPDATE translations SET es = 'Socios' WHERE en = 'Partners';
UPDATE translations SET es = 'Verificaciones' WHERE en = 'Verifications';
UPDATE translations SET es = 'Turnos' WHERE en = 'Shifts';
UPDATE translations SET es = 'Informe de capacitaciones' WHERE en = 'Training Reports';
DELETE FROM translations WHERE en = 'Summary of buildings and conditions from census';
DELETE FROM translations WHERE en = 'Room Report';
DELETE FROM translations WHERE en = 'Summary of sanitation';
DELETE FROM translations WHERE en = 'Summary of water and conditions from census';
UPDATE translations SET es = 'Limpiar' WHERE en = 'Clear';
UPDATE translations SET es = 'Agregar proveedor' WHERE en = 'Add Provider';
UPDATE translations SET es = 'Horas' WHERE en = 'Hours';
UPDATE translations SET es = 'Prerequisitos del Curso.' WHERE en = 'Course Prerequisite';
UPDATE translations SET es = 'Agregar Prerequisitos del Curso.' WHERE en = 'Add Course Prerequisite';
UPDATE translations SET es = 'No hay evaluacines.' WHERE en = 'There are no assessments.';
UPDATE translations SET es = 'Buscar' WHERE en = 'Search';
UPDATE translations SET es = 'Busqueda avanzada' WHERE en = 'Advanced Search';
UPDATE translations SET es = 'Ciclo de educación' WHERE en = 'Education Cycle';
UPDATE translations SET es = 'Guardería' WHERE en = 'Nursery';
UPDATE translations SET es = 'Jardin de Infancia' WHERE en = 'Kindergarten';
UPDATE translations SET es = 'Cantidad de turnos' WHERE en = 'Number of Shifts';
UPDATE translations SET es = 'ID OpenEMIS auto generado.' WHERE en = 'Auto Generated OpenEMIS ID';
UPDATE translations SET es = 'Prefijo Estudiante' WHERE en = 'Student Prefix';
UPDATE translations SET es = 'Prefijo Docente' WHERE en = 'Teacher Prefix';
UPDATE translations SET es = 'Prefijo Personal' WHERE en = 'Staff Prefix';
UPDATE translations SET es = 'Validación personalizada' WHERE en = 'Custom Validation';
UPDATE translations SET es = 'Telofono de la institución' WHERE en = 'Institution Telephone';
UPDATE translations SET es = 'Fax de la Institución' WHERE en = 'Institution Fax';
UPDATE translations SET es = 'Código postal de la Institución' WHERE en = 'Institution Postal Code';
DELETE FROM translations WHERE en = 'Institution Site Telephone';
DELETE FROM translations WHERE en = 'Institution Site Fax';
DELETE FROM translations WHERE en = 'Institution Site Postal Code';
UPDATE translations SET es = 'Teléfono del Estudiante' WHERE en = 'Student Telephone';
UPDATE translations SET es = 'Código postal del estudiante' WHERE en = 'Student Postal Code';
UPDATE translations SET es = 'Teléfono del Docente.' WHERE en = 'Teacher Telephone';
UPDATE translations SET es = 'Código postal del Docente.' WHERE en = 'Teacher Postal Code';
UPDATE translations SET es = 'Teléfono del Personal' WHERE en = 'Staff Telephone';
UPDATE translations SET es = 'Código postal del Personal' WHERE en = 'Staff Postal Code';
UPDATE translations SET es = 'Configuración del LDAP' WHERE en = 'LDAP Configuration';
UPDATE translations SET es = 'Servidor del LDAP' WHERE en = 'LDAP Server';
UPDATE translations SET es = 'Versión' WHERE en = 'Version';
UPDATE translations SET es = 'Base DN' WHERE en = 'Base DN';
UPDATE translations SET es = 'Autenticación' WHERE en = 'Authentication';
UPDATE translations SET es = 'Dónde' WHERE en = 'Where';
UPDATE translations SET es = '¿Dónde esta la URL de mi escuela?' WHERE en = 'Where is my School URL';
UPDATE translations SET es = 'Longitud de partida' WHERE en = 'Starting Longitude';
UPDATE translations SET es = 'Latitud de partida' WHERE en = 'Starting Latitude';
UPDATE translations SET es = 'Rango de partida' WHERE en = 'Starting Range';
UPDATE translations SET es = 'URL del Proveedor de SMS' WHERE en = 'SMS Provider URL';
UPDATE translations SET es = 'Número de SMS' WHERE en = 'SMS Number';
UPDATE translations SET es = 'Contenido de SMS' WHERE en = 'SMS Content';
UPDATE translations SET es = 'Reintentos de SMS' WHERE en = 'SMS Retry Times';
UPDATE translations SET es = 'Tiempo de espera entre reintentos SMS' WHERE en = 'SMS Retry Delay';
UPDATE translations SET es = 'Créditos de Horas' WHERE en = 'Credit Hour';
UPDATE translations SET es = 'Nacionalidad' WHERE en = 'Nationality';
UPDATE translations SET es = 'País predeterminado.' WHERE en = 'Default Country';
UPDATE translations SET es = 'Roles definidos por el Sistema.' WHERE en = 'System Defined Roles';
UPDATE translations SET es = 'Roles definidos por el Usuario.' WHERE en = 'User Defined Roles';
DELETE FROM translations WHERE en = 'List of Institutions with custom fields';
DELETE FROM translations WHERE en = 'Institution Custom Field Report';
DELETE FROM translations WHERE en = 'Report on all available courses by status';
DELETE FROM translations WHERE en = 'Training Course Report';
DELETE FROM translations WHERE en = 'Report on all completed courses by staff';
DELETE FROM translations WHERE en = 'Training Course Completed Report';
DELETE FROM translations WHERE en = 'Report on Staff training needs by course';
DELETE FROM translations WHERE en = 'Staff Training Need Report';
DELETE FROM translations WHERE en = 'Report on Teacher training needs by course';
DELETE FROM translations WHERE en = 'Teacher Training Need Report';
DELETE FROM translations WHERE en = 'Report on who has not completed a course by location';
DELETE FROM translations WHERE en = 'Training Course Uncompleted Report';
DELETE FROM translations WHERE en = 'Report of trainers by name';
DELETE FROM translations WHERE en = 'Training Trainer Report';
DELETE FROM translations WHERE en = 'Report of Exceptions to see if a staff is enrolled in two courses at the same time or has already completed the course';
DELETE FROM translations WHERE en = 'Training Exception Report';
DELETE FROM translations WHERE en = 'Report on the number of staff actually trained verses target groups for each program';
DELETE FROM translations WHERE en = 'Training Staff Statistics Report';
DELETE FROM translations WHERE en = 'Report on the number of teachers actually trained verses target groups for each program';
DELETE FROM translations WHERE en = 'Training Teacher Statistic Report';
DELETE FROM translations WHERE en = 'List of Institution Sites with custom fields';
DELETE FROM translations WHERE en = 'Institution Site Custom Field Report';
DELETE FROM translations WHERE en = 'Institution Totals';
DELETE FROM translations WHERE en = 'Summary of verifications from census';
DELETE FROM translations WHERE en = 'Verification Report';
DELETE FROM translations WHERE en = 'Summary of student enrolment from census';
DELETE FROM translations WHERE en = 'Summary of graduates from census';
DELETE FROM translations WHERE en = 'Graduate Report';
DELETE FROM translations WHERE en = 'Summary of attendances from census';
UPDATE translations SET es = 'Informe de asistencia.' WHERE en = 'Attendance Report';
DELETE FROM translations WHERE en = 'Summary of assessment from census';
UPDATE translations SET es = 'Informe de evaluación.' WHERE en = 'Assessment Report';
DELETE FROM translations WHERE en = 'Summary of behaviours from census';
UPDATE translations SET es = 'Informe de conductas.' WHERE en = 'Behaviour Report';
DELETE FROM translations WHERE en = 'Summary of custom fields from census';
DELETE FROM translations WHERE en = 'Custom Field Report';
DELETE FROM translations WHERE en = 'Summary of custom tables from census';
DELETE FROM translations WHERE en = 'Custom Table Report';
DELETE FROM translations WHERE en = 'Summary of rooms and conditions from census';
DELETE FROM translations WHERE en = 'Summary of furniture and conditions from census';
DELETE FROM translations WHERE en = 'Summary of power and conditions from census';
DELETE FROM translations WHERE en = 'List of Students with custom fields';
DELETE FROM translations WHERE en = 'Student Custom Field Report';
DELETE FROM translations WHERE en = 'Summary of assessment results from Students';
UPDATE translations SET es = 'Informe de evaluación de estudiantes.' WHERE en = 'Student Assessment Report';
DELETE FROM translations WHERE en = 'List of out of school Students';
DELETE FROM translations WHERE en = 'Student Out of School Report';
DELETE FROM translations WHERE en = 'List of Teachers with custom fields';
DELETE FROM translations WHERE en = 'Teacher Custom Field Report';
DELETE FROM translations WHERE en = 'List of Staff with custom fields';
DELETE FROM translations WHERE en = 'Staff Custom Field Report';
UPDATE translations SET es = 'Estándar' WHERE en = 'Standard';
UPDATE translations SET es = 'Encuesta' WHERE en = 'Survey';
UPDATE translations SET es = 'Nueva encuesta' WHERE en = 'New Surveys';
UPDATE translations SET es = 'Encuesta completada' WHERE en = 'Completed Surveys';
UPDATE translations SET es = 'Sincronizado' WHERE en = 'Synchronized';
UPDATE translations SET es = 'Sincronizar' WHERE en = 'Sync';
DELETE FROM translations WHERE en = 'Training Staff Statistic Report';
DELETE FROM translations WHERE en = 'Report on the number of teacher actually trained verses target groups for each program';
UPDATE translations SET es = 'Nuevo Personal' WHERE en = 'New Staff';
UPDATE translations SET es = 'Contactos' WHERE en = 'Contacts';
UPDATE translations SET es = 'Idiomas' WHERE en = 'Languages';
UPDATE translations SET es = 'Necesidades especiales' WHERE en = 'Special Needs';
UPDATE translations SET es = 'Premios' WHERE en = 'Awards';
UPDATE translations SET es = 'Membresías' WHERE en = 'Memberships';
UPDATE translations SET es = 'Licencias' WHERE en = 'Licenses';
UPDATE translations SET es = 'Posición' WHERE en = 'Positions';
UPDATE translations SET es = 'Licencia laboral' WHERE en = 'Leave';
UPDATE translations SET es = 'Extracurricular' WHERE en = 'Extracurricular';
UPDATE translations SET es = 'Puesto' WHERE en = 'Employment';
UPDATE translations SET es = 'Salario' WHERE en = 'Salary';
UPDATE translations SET es = 'Salud' WHERE en = 'Health';
UPDATE translations SET es = 'Familia' WHERE en = 'Family';
UPDATE translations SET es = 'Vacunas' WHERE en = 'Immunizations';
UPDATE translations SET es = 'Medicamentos' WHERE en = 'Medications';
UPDATE translations SET es = 'Alergias' WHERE en = 'Allergies';
UPDATE translations SET es = 'Pruebas' WHERE en = 'Tests';
UPDATE translations SET es = 'Consultas' WHERE en = 'Consultations';
UPDATE translations SET es = 'Logros' WHERE en = 'Achievements';
UPDATE translations SET es = 'INFORME' WHERE en = 'REPORT';
UPDATE translations SET es = 'Móvil' WHERE en = 'Mobile';
UPDATE translations SET es = 'Teléfono' WHERE en = 'Phone';
UPDATE translations SET es = 'Preferencias' WHERE en = 'Preferred';
UPDATE translations SET es = 'Escribiendo' WHERE en = 'Writing';
UPDATE translations SET es = 'Leyendo' WHERE en = 'Reading';
UPDATE translations SET es = 'Hablando' WHERE en = 'Speaking';
UPDATE translations SET es = 'Escuchando' WHERE en = 'Listening';
UPDATE translations SET es = 'Editor' WHERE en = 'Issuer';
UPDATE translations SET es = 'Etiqueta del personal' WHERE en = 'Staff Label';
UPDATE translations SET es = 'Casilla de verificación' WHERE en = 'Check Box';
UPDATE translations SET es = 'Estatus del empleo' WHERE en = 'Employment Status';
UPDATE translations SET es = 'Horas por semana' WHERE en = 'Hours per Week';
UPDATE translations SET es = 'Fecha de inicio' WHERE en = 'Commencement Date';
UPDATE translations SET es = 'No. Documento' WHERE en = 'Document No.';
UPDATE translations SET es = 'Título de Cualificación' WHERE en = 'Qualification Title';
UPDATE translations SET es = 'Año de graduación' WHERE en = 'Graduate Year';
UPDATE translations SET es = 'No se han encontrado posición' WHERE en = 'No position found';
UPDATE translations SET es = 'Total de días ausente' WHERE en = 'Total days absent';
UPDATE translations SET es = 'Total de días que asistieron' WHERE en = 'Total days attended';
UPDATE translations SET es = 'Número de días' WHERE en = 'Number Of Days';
UPDATE translations SET es = 'Último día' WHERE en = 'Last day ';
UPDATE translations SET es = 'Primer día' WHERE en = 'First day';
UPDATE translations SET es = 'Total de días' WHERE en = 'Total Days';
UPDATE translations SET es = 'Lista de conducta' WHERE en = 'List of Behaviour';
UPDATE translations SET es = 'No hay registro disponible' WHERE en = 'No record available';
UPDATE translations SET es = 'Red' WHERE en = 'Net';
UPDATE translations SET es = 'Deducciones' WHERE en = 'Deductions';
UPDATE translations SET es = 'Incorporaciones' WHERE en = 'Additions';
UPDATE translations SET es = 'Bruto' WHERE en = 'Gross';
UPDATE translations SET es = 'Condiciones' WHERE en = 'Conditions';
UPDATE translations SET es = 'Parentesco' WHERE en = 'Relationship';
UPDATE translations SET es = 'Dosis' WHERE en = 'Dosage';
UPDATE translations SET es = 'Finalizó' WHERE en = 'Ended';
UPDATE translations SET es = 'Inició' WHERE en = 'Commenced';
UPDATE translations SET es = 'Severo' WHERE en = 'Severe';
UPDATE translations SET es = 'Tratamiento' WHERE en = 'Treatment';
UPDATE translations SET es = 'Logros de la Capacitación' WHERE en = 'Training Achievements';
UPDATE translations SET es = 'Salud - Historia' WHERE en = 'Health - History';
UPDATE translations SET es = 'Salud - Familia' WHERE en = 'Health - Family';
UPDATE translations SET es = 'Salud - Inmunizaciones' WHERE en = 'Health - Immunizations';
UPDATE translations SET es = 'Salud - Medicamentos' WHERE en = 'Health - Medications';
UPDATE translations SET es = 'Salud - Medicación' WHERE en = 'Health - Medication';
UPDATE translations SET es = 'Salud - Alergias' WHERE en = 'Health - Allergies';
UPDATE translations SET es = 'Salud - Pruebas' WHERE en = 'Health - Tests';
UPDATE translations SET es = 'Salud - Consultas' WHERE en = 'Health - Consultations';
UPDATE translations SET es = 'Tamaño del archivo' WHERE en = 'File size';
UPDATE translations SET es = 'Por favor, introduzca el ID válido de OpenEMIS' WHERE en = 'Please enter a valid OpenEMIS ID';
UPDATE translations SET es = 'Dashboard' WHERE en = 'Dashboards';
DELETE FROM translations WHERE en = 'Dashboard Reports';
DELETE FROM translations WHERE en = 'ECE QA Dashboard';
DELETE FROM translations WHERE en = 'Early Childhood Education Quality Assurance Data';
DELETE FROM translations WHERE en = 'Early Childhood Education Quality Assurance';
UPDATE translations SET es = 'Nivel Geografico' WHERE en = 'Geographical Level';
DELETE FROM translations WHERE en = 'Administrative and Technical Aspects';
DELETE FROM translations WHERE en = 'Trends';
DELETE FROM translations WHERE en = 'Administrative Domains';
DELETE FROM translations WHERE en = 'Technical Domains';
DELETE FROM translations WHERE en = 'Distribution of Both Aspects';
DELETE FROM translations WHERE en = 'Scatterplot of Administrative and Technical and Aspects';
UPDATE translations SET es = 'Nombramiento' WHERE en = 'Appointment';
DELETE FROM translations WHERE en = 'Report - Dashboard';
UPDATE translations SET es = 'Propio' WHERE en = 'Owned';
UPDATE translations SET es = 'Alquilado' WHERE en = 'Rented';
UPDATE translations SET es = 'Ambos' WHERE en = 'Both';
UPDATE translations SET es = 'Permanente' WHERE en = 'Permanent';
UPDATE translations SET es = 'Contrato' WHERE en = 'Contract';
UPDATE translations SET es = 'Urbano' WHERE en = 'Urban';
UPDATE translations SET es = 'Rural' WHERE en = 'Rural';
UPDATE translations SET es = 'Administrativo' WHERE en = 'Administrative';
UPDATE translations SET es = 'Técnico' WHERE en = 'Technical';
UPDATE translations SET es = 'Descargar CSV' WHERE en = 'Download CSV';
UPDATE translations SET es = 'Ponderaciones' WHERE en = 'Weightings';
UPDATE translations SET es = 'Ponderación' WHERE en = 'Weighting';
UPDATE translations SET es = 'Agregar encabezado' WHERE en = 'Add Header';
UPDATE translations SET es = 'Evaluador' WHERE en = 'Evaluator';
UPDATE translations SET es = 'Agregar criterio' WHERE en = 'Add Criteria';
UPDATE translations SET es = 'Editar criterio' WHERE en = 'Edit Criteria';
UPDATE translations SET es = 'Detalles del criterio' WHERE en = 'Criteria Details';
UPDATE translations SET es = 'Informe de Control de Calidad' WHERE en = 'Quality Assurance Reports';
UPDATE translations SET es = 'Garantía de Calidad' WHERE en = 'Quality Assurance';
UPDATE translations SET es = 'Informe generado en la escuela' WHERE en = 'Report generated at the school';
UPDATE translations SET es = 'Informe de Control de Calidad en la escuela' WHERE en = 'QA Schools Report';
UPDATE translations SET es = 'Informe generado por tipo' WHERE en = 'Report generated by type';
UPDATE translations SET es = 'Informe de resultados del Control de Calidad' WHERE en = 'QA Results Report';
UPDATE translations SET es = 'Generación de informes para los que no tiene' WHERE en = 'Report generation for those who hasn';
UPDATE translations SET es = 'Informe sin completar del Control de calidad en Matrices de valoración.' WHERE en = 'QA Rubric Not Completed Report';
UPDATE translations SET es = 'Un máximo de 150 palabras por comentario' WHERE en = 'Maximum 150 words per comment';
UPDATE translations SET es = 'Fecha habilitada' WHERE en = 'Date Enabled';
UPDATE translations SET es = 'Fecha deshabilitada' WHERE en = 'Date Disabled';
UPDATE translations SET es = 'Rol de Seguridad' WHERE en = 'Security Role';
UPDATE translations SET es = 'Configuración del criterio de columna' WHERE en = 'Setup Criteria Column';
UPDATE translations SET es = 'Encabezado' WHERE en = 'Header';
UPDATE translations SET es = 'Criterio' WHERE en = 'Criteria';
UPDATE translations SET es = 'Descriptores' WHERE en = 'Descriptors';
UPDATE translations SET es = 'Una nueva fila se ha añadido en la parte inferior de la tabla de Matrices de valoración.' WHERE en = 'New row has been added at the bottom of the rubric table.';
UPDATE translations SET es = 'Encabezado / Sub-Encabezado / Título' WHERE en = 'Header / Sub-Header / Title';
UPDATE translations SET es = 'Sección de cabecera' WHERE en = 'Section Header';
UPDATE translations SET es = 'Agregar Sección de Cabecera' WHERE en = 'Add Section Header';
UPDATE translations SET es = 'Reordenar' WHERE en = 'Reorder';
UPDATE translations SET es = 'Agregar grado' WHERE en = 'Add Grade';
UPDATE translations SET es = 'Agregar visita' WHERE en = 'Add Visit';
UPDATE translations SET es = 'Visita' WHERE en = 'Visit';
UPDATE translations SET es = 'Editar encabecados de las Matrices de valoración.' WHERE en = 'Edit Rubric Headers';
UPDATE translations SET es = 'Editar encabezados' WHERE en = 'Edit Headers';
UPDATE translations SET es = 'No hay registros.' WHERE en = 'There are no records.';
UPDATE translations SET es = 'Crear Tabla de Matrices de valoración.' WHERE en = 'Create Rubric Table';
UPDATE translations SET es = 'Reordenar criterios' WHERE en = 'Reorder Criteria';
UPDATE translations SET es = 'Opciones.' WHERE en = 'Options';
UPDATE translations SET es = 'Editar estatus' WHERE en = 'Edit Status';
UPDATE translations SET es = 'Agregar estatus' WHERE en = 'Add Status';
UPDATE translations SET es = 'Descripción del nivel de criterios' WHERE en = 'Criteria Level Description';
UPDATE translations SET es = 'Ver detalles' WHERE en = 'View Details';
UPDATE translations SET es = 'Coecifiente de ponderación' WHERE en = 'Weightage';
UPDATE translations SET es = 'Grado(s) Seleccionado(s)' WHERE en = 'Selected Grade(s)';
UPDATE translations SET es = 'Detalles de estatus' WHERE en = 'Status Details';
UPDATE translations SET es = 'Nombre de la Matriz de valoración' WHERE en = 'Rubric Name';
UPDATE translations SET es = 'Coecifiente de ponderación total (%)' WHERE en = 'Total Weighting(%)';
UPDATE translations SET es = 'Aprobar / Fracasar' WHERE en = 'Pass/Fail';
UPDATE translations SET es = 'Gran tortal del Coeficiente de ponderación (%)' WHERE en = 'Grand Total Weighting(%)';
UPDATE translations SET es = 'Informe del Control de Calidad' WHERE en = 'QA Report';
UPDATE translations SET es = 'Informe de visitas' WHERE en = 'Visit Report';
UPDATE translations SET es = 'Informe - Calidad' WHERE en = 'Reports - Quality';
UPDATE translations SET es = 'Informe Generado' WHERE en = 'Report Generated';
UPDATE translations SET es = 'Tipo de Personal' WHERE en = 'Staff Type';
UPDATE translations SET es = 'Fracaso' WHERE en = 'Fail';
UPDATE translations SET es = 'Tipo de Calidad' WHERE en = 'Quality Type';
UPDATE translations SET es = 'Fecha de visita' WHERE en = 'Visit Date';
UPDATE translations SET es = 'Nombre del evaluador' WHERE en = 'Evaluator Name';
UPDATE translations SET es = 'Total de clases' WHERE en = 'Total Classes';
UPDATE translations SET es = 'Máximo' WHERE en = 'Maximum';
UPDATE translations SET es = 'Mínimo' WHERE en = 'Minimum';
UPDATE translations SET es = 'Promedio' WHERE en = 'Average';
UPDATE translations SET es = 'Aprobar / Fracasar' WHERE en = 'Pass/ Fail';
UPDATE translations SET es = 'Total de preguntas' WHERE en = 'Total Questions';
UPDATE translations SET es = 'Total de respuestas' WHERE en = 'Total Answered';
DELETE FROM translations WHERE en = 'There are no available files found for this report.';
UPDATE translations SET es = 'Meta objetivo' WHERE en = 'Goal Objective';
UPDATE translations SET es = 'Requisito' WHERE en = 'Requirement';
UPDATE translations SET es = 'Tipo de instructor' WHERE en = 'Trainer Type';
UPDATE translations SET es = 'Posición' WHERE en = 'Position';
UPDATE translations SET es = 'Grupo destinatario' WHERE en = 'Target Group';
UPDATE translations SET es = 'Total de Grupo destinatario' WHERE en = 'Total Target Group';
UPDATE translations SET es = 'Total de Capacitados' WHERE en = 'Total Trained';
UPDATE translations SET es = 'Porcentaje de Grupo destinatario' WHERE en = 'Target Group Percentage';
UPDATE translations SET es = 'Última actualización realizada por' WHERE en = 'Last Updated By';
UPDATE translations SET es = 'Comportamiento - Personal' WHERE en = 'Behaviour - Staff';
UPDATE translations SET es = 'Comportamiento - Estudiantes' WHERE en = 'Behaviour - Students';
UPDATE translations SET es = '* El Tamaño del archivo no debe ser mayor de 2 MB.' WHERE en = '*File size should not be larger than 2MB.';
UPDATE translations SET es = '* Se permite un máximo de cinco archivos por carga. Cada archivo no debe ser mayor de 2MB.' WHERE en = '*Maximum 5 files are permitted on single upload. Each file size should not be larger than 2MB.';
UPDATE translations SET es = 'Tipo de resultado' WHERE en = 'Result Type';
UPDATE translations SET es = 'Usuario' WHERE en = 'User';
UPDATE translations SET es = 'Agregar tipo de resultado' WHERE en = 'Add Result Type';
UPDATE translations SET es = 'Meta / Objetivo' WHERE en = 'Goal / objective';
UPDATE translations SET es = 'Interno' WHERE en = 'Internal';
UPDATE translations SET es = 'Externo' WHERE en = 'External';
UPDATE translations SET es = 'Agregar Instructor' WHERE en = 'Add Trainer';
UPDATE translations SET es = 'Resultado General' WHERE en = 'Overall Result';
UPDATE translations SET es = 'Instructores' WHERE en = 'Trainers';
UPDATE translations SET es = 'Editar detalles de resultado' WHERE en = 'Edit Results Details';
UPDATE translations SET es = 'Descargar Plantilla' WHERE en = 'Download Template';
UPDATE translations SET es = 'Subir resultado' WHERE en = 'Upload Results';
UPDATE translations SET es = 'Aprobado' WHERE en = 'Passed';
UPDATE translations SET es = 'Fracasado' WHERE en = 'Failed';
UPDATE translations SET es = 'Subir archivo' WHERE en = 'Upload File';
UPDATE translations SET es = 'Subir' WHERE en = 'Upload';
UPDATE translations SET es = 'Formato de archivo inválido.' WHERE en = 'Invalid File Format';
UPDATE translations SET es = 'Columnas / datos no coinciden.' WHERE en = 'Columns/Data do not match.';
UPDATE translations SET es = 'Ha surgido un error' WHERE en = 'Error encountered';
UPDATE translations SET es = '%s Registos han sido actualizados' WHERE en = '%s Record(s) have been updated';
UPDATE translations SET es = 'La Columna %s sólo acepta 0 ó 1 como entrada.' WHERE en = 'Column %s only accepts 0 or 1 as input.';
UPDATE translations SET es = 'Fila %s: %s' WHERE en = 'Row %s: %s';
UPDATE translations SET es = 'Resultado de la Capacitación' WHERE en = 'TrainingResult';
UPDATE translations SET es = 'OpenEMIS ID' WHERE en = 'OpenEMIS ID';
UPDATE translations SET es = '(1=Aprobado / 0=Fracaso)' WHERE en = '(1=Pass/0=Fail)';
UPDATE translations SET es = 'Tipo de necesidad' WHERE en = 'Need Type';
UPDATE translations SET es = 'Prioridad de Capacitación' WHERE en = 'Training Priority';
UPDATE translations SET es = 'Tipo de Logro' WHERE en = 'Achievement Type';
UPDATE translations SET es = 'Metas del Curso / Objetivos' WHERE en = 'Course Goal / Objectives';
UPDATE translations SET es = 'Agregar necesidades de capacitación' WHERE en = 'Add Training Needs';
UPDATE translations SET es = 'Catálogo de Cursos' WHERE en = 'Course Catalogue';
UPDATE translations SET es = 'Categoría necesaria' WHERE en = 'Need Category';
UPDATE translations SET es = 'Agregar Logros' WHERE en = 'Add Achievements';
UPDATE translations SET es = 'Detalles de Logros' WHERE en = 'Achievements Details';
UPDATE translations SET es = 'Editar Logros' WHERE en = 'Edit Achievements';
UPDATE translations SET es = 'Sesión de Capacitación de aprendiz' WHERE en = 'TrainingSessionTrainee';
UPDATE translations SET es = 'Visto bueno' WHERE en = 'Approval';
UPDATE translations SET es = 'Rechazar' WHERE en = 'Reject';
UPDATE translations SET es = 'Experiencia' WHERE en = 'Experience';
UPDATE translations SET es = 'Especialización' WHERE en = 'Specialisation';
UPDATE translations SET es = 'Años' WHERE en = 'Years';
UPDATE translations SET es = 'Meses' WHERE en = 'Months';
UPDATE translations SET es = 'Agregar Experiencia' WHERE en = 'Add Experience';
UPDATE translations SET es = 'Agregar Especialización' WHERE en = 'Add Specialisation';
UPDATE translations SET es = 'Cargar aprendiz' WHERE en = 'Upload Trainee';
UPDATE translations SET es = 'Personal con OpenEMIS ID %s no existe' WHERE en = 'Staff with OpenEmis ID %s does not exist.';
UPDATE translations SET es = 'El Personal con OpenEMIS ID %s no cumple con el requisito del curso.' WHERE en = 'Staff with OpenEmis ID %s does not meet the Course requirement.';
UPDATE translations SET es = 'Editar detalles de sesión' WHERE en = 'Edit Sessions Details';
UPDATE translations SET es = 'Herramientas de evaluación' WHERE en = 'Evaluation Tools';
DELETE FROM translations WHERE en = 'Compile';
DELETE FROM translations WHERE en = 'The translation file has been compiled successfully.';
UPDATE translations SET es = 'Traducciones' WHERE en = 'Translations';
UPDATE translations SET es = 'Lista de traducciones' WHERE en = 'List of Translations';
UPDATE translations SET es = 'Agregar Traducción' WHERE en = 'Add Translation';
UPDATE translations SET es = 'Editar Traducción' WHERE en = 'Edit Translation';
UPDATE translations SET es = 'Detalles de Traducción' WHERE en = 'Translation Details';
UPDATE translations SET es = 'Por favor, asegurese que la traducción en Inglés se teclea.' WHERE en = 'Please ensure the english translation is keyed in.';
UPDATE translations SET es = 'Descarga para aprendiz' WHERE en = 'Download Trainees';
UPDATE translations SET es = 'Resultado de descarga para aprendiz' WHERE en = 'Download Trainee Results';
UPDATE translations SET es = 'Sesión duplicada' WHERE en = 'Duplicate Session';
UPDATE translations SET es = 'Informes compartidos' WHERE en = 'Shared Reports';
UPDATE translations SET es = 'Mis Informes' WHERE en = 'My Reports';
UPDATE translations SET es = 'Ausencia' WHERE en = 'Absence';
UPDATE translations SET es = 'de' WHERE en = 'From';
UPDATE translations SET es = 'para' WHERE en = 'To';
UPDATE translations SET es = 'Razón' WHERE en = 'Reason';
UPDATE translations SET es = 'Hora' WHERE en = 'Time';
UPDATE translations SET es = 'Seleccionar imagen' WHERE en = 'Select Image';
UPDATE translations SET es = 'Remover' WHERE en = 'Remove';
UPDATE translations SET es = 'Cambiar' WHERE en = 'Change';
UPDATE translations SET es = 'No hay datos de visualización.' WHERE en = 'There is no data to be displayed.';
DELETE FROM translations WHERE en = 'Site Type:';
UPDATE translations SET es = 'Periodo' WHERE en = 'Period';
UPDATE translations SET es = 'Turno' WHERE en = 'Shift';
UPDATE translations SET es = 'Nombre del turno' WHERE en = 'Shift Name';
UPDATE translations SET es = 'Hora de inicio' WHERE en = 'Start Time';
UPDATE translations SET es = 'Hora de finalización' WHERE en = 'End Time';
UPDATE translations SET es = 'Detalles del turno' WHERE en = 'Shift Details';
UPDATE translations SET es = 'Editar turno' WHERE en = 'Edit Shift';
UPDATE translations SET es = 'Agregar turno' WHERE en = 'Add Shift';
UPDATE translations SET es = 'Agregar adjunto' WHERE en = 'Add Attachment';
UPDATE translations SET es = 'Por favor introduzca un nombre de archivo' WHERE en = 'Please enter a File name';
UPDATE translations SET es = 'Ningún archivo fue subido' WHERE en = 'No file was uploaded';
UPDATE translations SET es = 'Los archivos se han subido' WHERE en = 'The files has been uploaded';
UPDATE translations SET es = 'Detalles de adjuntos' WHERE en = 'Attachment Details';
UPDATE translations SET es = 'Enseñanza' WHERE en = 'Teaching';
UPDATE translations SET es = 'Detalles de posición' WHERE en = 'Position Details';
UPDATE translations SET es = 'Editar posición' WHERE en = 'Edit Position';
UPDATE translations SET es = 'Todos los años' WHERE en = 'All Years';
UPDATE translations SET es = 'Número:' WHERE en = 'Number:';
UPDATE translations SET es = 'Reiniciar' WHERE en = 'Reset';
DELETE FROM translations WHERE en = 'Visualizer - Indicator';
UPDATE translations SET es = 'Unidad' WHERE en = 'Unit';
UPDATE translations SET es = 'Paso 1' WHERE en = 'Step 1';
UPDATE translations SET es = 'Paso 2' WHERE en = 'Step 2';
UPDATE translations SET es = 'Paso 3' WHERE en = 'Step 3';
UPDATE translations SET es = 'Paso 4' WHERE en = 'Step 4';
UPDATE translations SET es = 'Paso 5' WHERE en = 'Step 5';
UPDATE translations SET es = 'Paso 6' WHERE en = 'Step 6';
UPDATE translations SET es = 'Paso 7' WHERE en = 'Step 7';
UPDATE translations SET es = 'Paso 8' WHERE en = 'Step 8';
DELETE FROM translations WHERE en = 'Step 9';
UPDATE translations SET es = 'Dimensión' WHERE en = 'Dimension';
UPDATE translations SET es = 'Revisión' WHERE en = 'Review';
DELETE FROM translations WHERE en = 'Visualize';
DELETE FROM translations WHERE en = 'Indicators';
UPDATE translations SET es = 'ID de Área' WHERE en = 'Area ID';
UPDATE translations SET es = 'Tabla' WHERE en = 'Table';
UPDATE translations SET es = 'Columna' WHERE en = 'Column';
DELETE FROM translations WHERE en = 'Stacked Column';
UPDATE translations SET es = 'Barra' WHERE en = 'Bar';
DELETE FROM translations WHERE en = 'Stacked Bar';
UPDATE translations SET es = 'Linea' WHERE en = 'Line';
DELETE FROM translations WHERE en = 'Scatter';
DELETE FROM translations WHERE en = 'Pie';
UPDATE translations SET es = 'Visualización' WHERE en = 'Visualization';
DELETE FROM translations WHERE en = 'Visualizer';
UPDATE translations SET es = 'Lista de clases' WHERE en = 'List of Classes';
UPDATE translations SET es = 'Verificar' WHERE en = 'Verify';
UPDATE translations SET es = 'Entrada de datos' WHERE en = 'Data Entry';
UPDATE translations SET es = 'Estimar' WHERE en = 'Estimate';
UPDATE translations SET es = 'Equivalente a Docentes de tiempo completo' WHERE en = 'Full Time Equivalent Teachers';
DELETE FROM translations WHERE en = 'Single Grade Teachers Only';
DELETE FROM translations WHERE en = 'Single Grade Classes Only';
UPDATE translations SET es = 'Clases de multi grados' WHERE en = 'Multi Grade Classes';
DELETE FROM translations WHERE en = 'There are no classes associated with this institution site for the selected year.';
UPDATE translations SET es = 'No hay materias configuradas en el sistema.' WHERE en = 'There are no subjects configured in the system.';
DELETE FROM translations WHERE en = 'Overview and More';
UPDATE translations SET es = 'Tutores' WHERE en = 'Guardians';
UPDATE translations SET es = 'Mapas' WHERE en = 'Maps';
UPDATE translations SET es = 'Honorarios' WHERE en = 'Fees';
UPDATE translations SET es = 'Tipos de honorario' WHERE en = 'Fee Types';
UPDATE translations SET es = 'Agregar estudiantes existente' WHERE en = 'Add existing Student';
UPDATE translations SET es = 'Agregar personal existente' WHERE en = 'Add existing Staff';
UPDATE translations SET es = 'Agregar estudiante' WHERE en = 'Add Student';
UPDATE translations SET es = 'Agregar personal' WHERE en = 'Add Staff';
UPDATE translations SET es = 'Personalizar Campo' WHERE en = 'Custom Field';
UPDATE translations SET es = 'Promovido' WHERE en = 'Promoted';
UPDATE translations SET es = 'Sección' WHERE en = 'Sections';
DELETE FROM translations WHERE en = 'Non- Teaching';
UPDATE translations SET es = 'Repetido' WHERE en = 'Repeated';
UPDATE translations SET es = 'Elevador' WHERE en = 'مرفع';
UPDATE translations SET es = 'Frecuencia' WHERE en = 'متكرر';
UPDATE translations SET es = 'Periodos académicos' WHERE en = 'Academic Periods';
UPDATE translations SET es = 'Encuestas' WHERE en = 'Surveys';
UPDATE translations SET es = 'Comunicación' WHERE en = 'Communications';
UPDATE translations SET es = 'Dashboard' WHERE en = 'Dashboard';
UPDATE translations SET es = 'Infraestructuras' WHERE en = 'Infrastructures';
UPDATE translations SET es = 'Notificaciones' WHERE en = 'Notices';
UPDATE translations SET es = 'Clases' WHERE en = 'Classes';
UPDATE translations SET es = 'Estatus de transferencia' WHERE en = 'Transfer Status';
UPDATE translations SET es = 'Razón de transferencia de estudiante' WHERE en = 'Student Transfer Reason';
UPDATE translations SET es = 'Académico' WHERE en = 'Academic';
UPDATE translations SET es = 'Total de instituciones' WHERE en = 'Total Institutions';
UPDATE translations SET es = 'Total de estudiantes' WHERE en = 'Total Students';
UPDATE translations SET es = 'Total de personal' WHERE en = 'Total Staff';
UPDATE translations SET es = 'Acerca de' WHERE en = 'About';
UPDATE translations SET es = 'Preferencias' WHERE en = 'Preferences';
UPDATE translations SET es = 'Formularios' WHERE en = 'Forms';
UPDATE translations SET es = 'Tercer nombre' WHERE en = 'Third Name';
UPDATE translations SET es = 'Área Natal' WHERE en = 'Birthplace Area';
UPDATE translations SET es = 'Usuario modificado' WHERE en = 'Modified User';
UPDATE translations SET es = 'Modificado' WHERE en = 'Modified';
UPDATE translations SET es = 'Usuario creado' WHERE en = 'Created User';
UPDATE translations SET es = 'Creado' WHERE en = 'Created';
UPDATE translations SET es = 'Ausencias' WHERE en = 'Absences';
UPDATE translations SET es = 'Conductas' WHERE en = 'Behaviours';
UPDATE translations SET es = 'Extracurriculares' WHERE en = 'Extracurriculars';
UPDATE translations SET es = 'Nombre alternativo' WHERE en = 'Alternative Name';
UPDATE translations SET es = 'Área Administrativa' WHERE en = 'Area Administrative';
UPDATE translations SET es = '(Área (Educación' WHERE en = '(Area (Education';
UPDATE translations SET es = 'Carrera' WHERE en = 'Career';
UPDATE translations SET es = 'Desarrollo profesional' WHERE en = 'Professional Development';
UPDATE translations SET es = 'Salarios' WHERE en = 'Salaries';
UPDATE translations SET es = 'Flujo de trabajo' WHERE en = 'Workflow';
UPDATE translations SET es = 'Solicitante' WHERE en = 'Requester';
UPDATE translations SET es = 'Mesa de trabajo' WHERE en = 'Workbench';
UPDATE translations SET es = 'Fecha de vencimiento' WHERE en = 'Due Date';
UPDATE translations SET es = 'Fecha de recepción' WHERE en = 'Received Date';
UPDATE translations SET es = 'Título de solicitud' WHERE en = 'Request Title';
UPDATE translations SET es = 'Transferencia de estudiante' WHERE en = 'Student Transfer';
UPDATE translations SET es = 'Transferencia de estudiante' WHERE en = 'Transfer of student';
UPDATE translations SET es = 'Transferido' WHERE en = 'Transferred';
UPDATE translations SET es = 'Transferencia pendiente' WHERE en = 'Pending Transfer';
UPDATE translations SET es = 'Número Nacional' WHERE en = 'National Number';
UPDATE translations SET es = 'Expulsado' WHERE en = 'Expelled';
UPDATE translations SET es = 'Graduado' WHERE en = 'Graduated';
UPDATE translations SET es = 'Abandono' WHERE en = 'Dropout';
UPDATE translations SET es = 'Todos los estatus' WHERE en = 'All Statuses';
UPDATE translations SET es = 'Estatus del estudiante' WHERE en = 'Student Status';
UPDATE translations SET es = 'Foto' WHERE en = 'Photo';
UPDATE translations SET es = 'Conducta' WHERE en = 'Actions';
UPDATE translations SET es = 'Todos los grados' WHERE en = 'All Grades';
UPDATE translations SET es = 'Mostrar' WHERE en = 'Display';
UPDATE translations SET es = 'registros' WHERE en = 'records';
UPDATE translations SET es = 'Mostrando %s para %s de %s registros' WHERE en = 'Showing %s to %s of %s records';
UPDATE translations SET es = 'Periodo académico actual' WHERE en = 'Current Academic Period';
UPDATE translations SET es = 'Próximo periodo académico' WHERE en = 'Next Academic Period';
UPDATE translations SET es = 'No EpenEMIS' WHERE en = 'Openemis No';
UPDATE translations SET es = 'Periodo Académico' WHERE en = 'Academic Period';
UPDATE translations SET es = 'Solicitudes de transferencia' WHERE en = 'Transfer Requests';
UPDATE translations SET es = 'Transferir' WHERE en = 'Transfer';
UPDATE translations SET es = 'Promoción / Graduación' WHERE en = 'Promotion / Graduation';
UPDATE translations SET es = 'Preguntas' WHERE en = 'Questions';
UPDATE translations SET es = 'Borrador' WHERE en = 'Draft';
UPDATE translations SET es = 'Marcar' WHERE en = 'Mark';
UPDATE translations SET es = 'Inscrito' WHERE en = 'Enrolled';
UPDATE translations SET es = 'Área (Educación)' WHERE en = 'Area (Education)';
UPDATE translations SET es = 'Tipo de posición' WHERE en = 'Position Type';
UPDATE translations SET es = 'Tiempo completo' WHERE en = 'Full-Time';
UPDATE translations SET es = 'Medio tiempo' WHERE en = 'Part-Time';
UPDATE translations SET es = 'Estatus del personal' WHERE en = 'Staff Status';
UPDATE translations SET es = 'No hay opciones configuradas' WHERE en = 'No configured options';
UPDATE translations SET es = 'Seleccionar el Rol' WHERE en = 'Select Role';
UPDATE translations SET es = 'Páginas' WHERE en = 'Pages';
UPDATE translations SET es = 'Campos' WHERE en = 'Fields';
UPDATE translations SET es = 'Nivel de infraestructura' WHERE en = 'Infrastructure Level';
UPDATE translations SET es = 'Tipo de infraestructura' WHERE en = 'Infrastructure Type';
UPDATE translations SET es = 'Tamaño' WHERE en = 'Size';
UPDATE translations SET es = 'Propietario de la infraestructura' WHERE en = 'Infrastructure Ownership';
UPDATE translations SET es = 'Año de adquisición' WHERE en = 'Year Acquired';
UPDATE translations SET es = 'Año disponible' WHERE en = 'Year Disposed';
UPDATE translations SET es = 'Condición de la infraestructura' WHERE en = 'Infrastructure Condition';
UPDATE translations SET es = 'País -' WHERE en = 'Country -';
UPDATE translations SET es = 'Áreas Administrativas' WHERE en = 'Area Administratives';
UPDATE translations SET es = 'Número de estudiantes por año' WHERE en = 'Number Of Students By Year';
UPDATE translations SET es = 'Número de estudiantes por grado' WHERE en = 'Number Of Students By Grade';
UPDATE translations SET es = 'Número de personal' WHERE en = 'Number Of Staff';
UPDATE translations SET es = 'Fecha del archivo' WHERE en = 'Date On File';
UPDATE translations SET es = 'Nuevo valor' WHERE en = 'New Value';
UPDATE translations SET es = 'Antiguo valor' WHERE en = 'Old Value';
UPDATE translations SET es = 'Campo' WHERE en = 'Field';
UPDATE translations SET es = 'Módulo' WHERE en = 'Module';
UPDATE translations SET es = 'Estudiantes femeninas' WHERE en = 'Female Students';
UPDATE translations SET es = 'Estudiantes masculinos' WHERE en = 'Male Students';
UPDATE translations SET es = 'Docente del Aula' WHERE en = 'Home Room Teacher';
UPDATE translations SET es = 'Nombre de la clase' WHERE en = 'Class Name';
UPDATE translations SET es = 'Nombre de la Materia' WHERE en = 'Subject Name';
UPDATE translations SET es = 'No hay Programa establecido para esta institución' WHERE en = 'There is no programme set for this institution';
UPDATE translations SET es = 'OpenEMIS ID o Nombre' WHERE en = 'OpenEMIS ID or Name';
UPDATE translations SET es = 'Contenido de la foto' WHERE en = 'Photo Content';
UPDATE translations SET es = 'Tipo de contacto' WHERE en = 'Contact Type';
UPDATE translations SET es = 'Valor de Contacto' WHERE en = 'Contact Value';
UPDATE translations SET es = 'Tipo de identificación' WHERE en = 'Identity Type';
UPDATE translations SET es = 'Número de identificación' WHERE en = 'Identity Number';
UPDATE translations SET es = 'Necesidad especial' WHERE en = 'Special Need';
UPDATE translations SET es = 'Comentario de la necesidad especial' WHERE en = 'Special Need Comment';
UPDATE translations SET es = 'Categoría de la Conducta del estudiante' WHERE en = 'Student Behaviour Category';
UPDATE translations SET es = 'Fecha de Conducta' WHERE en = 'Date Of Behaviour';
UPDATE translations SET es = 'No ha clases disponibles' WHERE en = 'No Available Classes';
UPDATE translations SET es = 'Ausencia - Justificada' WHERE en = 'Absent - Excused';
UPDATE translations SET es = 'Ausencia - Injustificada' WHERE en = 'Absent - Unexcused';
UPDATE translations SET es = 'Presente' WHERE en = 'Present';
UPDATE translations SET es = 'Sin clases' WHERE en = 'No Classes';
UPDATE translations SET es = 'Evaluación' WHERE en = 'Assessment';
UPDATE translations SET es = 'Debe ser completado por' WHERE en = 'To Be Completed By';
UPDATE translations SET es = 'ültima modificación' WHERE en = 'Last Modified';
UPDATE translations SET es = 'Completado en' WHERE en = 'Completed On';
UPDATE translations SET es = 'Todas las posiciones' WHERE en = 'All Positions';
UPDATE translations SET es = 'FTE' WHERE en = 'FTE';
UPDATE translations SET es = 'Justificado' WHERE en = 'Excused';
UPDATE translations SET es = 'Injustificado' WHERE en = 'Unexcused';
UPDATE translations SET es = 'Género' WHERE en = 'Genders';
UPDATE translations SET es = 'Áreas Natales' WHERE en = 'Birthplace Areas';
UPDATE translations SET es = 'Dirección de Área' WHERE en = 'Address Areas';
UPDATE translations SET es = 'Expira en' WHERE en = 'Expires On';
UPDATE translations SET es = 'Comenzó en' WHERE en = 'Started On';
UPDATE translations SET es = 'Fecha de evaluación' WHERE en = 'Evaluation Date';
UPDATE translations SET es = 'Empleos' WHERE en = 'Employments';
UPDATE translations SET es = 'Tipo de empleo' WHERE en = 'Employment Type';
UPDATE translations SET es = 'Fecha de empleo' WHERE en = 'Employment Date';
UPDATE translations SET es = 'Tema educativo' WHERE en = 'EducationSubject';
UPDATE translations SET es = 'Razón de ausencia del personal' WHERE en = 'Staff Absence Reason';
UPDATE translations SET es = 'Días' WHERE en = 'Days';
UPDATE translations SET es = 'Fecha para' WHERE en = 'Date To';
UPDATE translations SET es = 'Fecha de' WHERE en = 'Date From';
UPDATE translations SET es = 'Estatus de vacaciones' WHERE en = 'Leave Status';
UPDATE translations SET es = 'Tipo de vacaciones del personal' WHERE en = 'Staff Leave Type';
UPDATE translations SET es = 'Vacaciones' WHERE en = 'Leaves';
UPDATE translations SET es = 'Calificación de la Institución' WHERE en = 'Qualification Institution';
UPDATE translations SET es = 'No de documento' WHERE en = 'Document No';
UPDATE translations SET es = 'Nivel de calificación' WHERE en = 'Qualification Level';
UPDATE translations SET es = 'Tipo de extracurricular' WHERE en = 'Extracurricular Type';
UPDATE translations SET es = 'Puntos' WHERE en = 'Points';
UPDATE translations SET es = 'País de la Institución' WHERE en = 'Institution Country';
UPDATE translations SET es = 'Clasificación de la especialización' WHERE en = 'Qualification Specialisation';
UPDATE translations SET es = 'Grado / Calificación' WHERE en = 'Grade/Score';
UPDATE translations SET es = 'Afiliación' WHERE en = 'Membership';
UPDATE translations SET es = 'Número de licencia' WHERE en = 'License Number';
UPDATE translations SET es = 'Tipo de licencia' WHERE en = 'License Type';
UPDATE translations SET es = 'Categoría de la Capacitación del personal' WHERE en = 'Staff Training Category';
UPDATE translations SET es = 'Observaciones' WHERE en = 'Remarks';
UPDATE translations SET es = 'Sucursal bancaria' WHERE en = 'Bank Branch';
UPDATE translations SET es = 'Nombre del Banco' WHERE en = 'Bank Name';
UPDATE translations SET es = 'Salario neto' WHERE en = 'Net Salary';
UPDATE translations SET es = 'Salario bruto' WHERE en = 'Gross Salary';
UPDATE translations SET es = 'Fecha de pago de salarios' WHERE en = 'Salary Date';
UPDATE translations SET es = 'País' WHERE en = 'Country';
UPDATE translations SET es = 'Característica' WHERE en = 'Feature';
UPDATE translations SET es = 'Cantidad de clases' WHERE en = 'Number Of Classes';
UPDATE translations SET es = 'Enviar' WHERE en = 'Submit';
UPDATE translations SET es = 'Guardar como borrador' WHERE en = 'Save As Draft';
UPDATE translations SET es = 'Tiempo de Conducta' WHERE en = 'Time Of Behaviour';
UPDATE translations SET es = 'Identidad' WHERE en = 'Identity';
UPDATE translations SET es = 'Grados de educación' WHERE en = 'Education Grades';
UPDATE translations SET es = 'Código del asunto' WHERE en = 'Subject Code';
UPDATE translations SET es = 'Crear Nuevo' WHERE en = 'Create New';
UPDATE translations SET es = 'Asistencias del personal' WHERE en = 'Staff Attendances';
UPDATE translations SET es = 'Estudiantes no encontrados' WHERE en = 'No Students Found';
UPDATE translations SET es = 'Admisión pendiente' WHERE en = 'Pending Admission';
UPDATE translations SET es = 'Rechazado' WHERE en = 'Rejected';
UPDATE translations SET es = 'Página de inicio' WHERE en = 'Home Page';
UPDATE translations SET es = 'Deserción pendiente' WHERE en = 'Pending Dropout';
UPDATE translations SET es = 'Ejecutar' WHERE en = 'Execute';
UPDATE translations SET es = 'Director de escuela' WHERE en = 'School Principal';
UPDATE translations SET es = 'País - Región administrativa' WHERE en = 'المنطقة الإدارية - Country';
UPDATE translations SET es = 'Contenido del archivo' WHERE en = 'File Content';
UPDATE translations SET es = 'No hay docente asignado' WHERE en = 'No Teacher Assigned';
UPDATE translations SET es = 'Turno predeterminado' WHERE en = 'Default Shift';
UPDATE translations SET es = 'Turno predeterminado 2014/2015' WHERE en = 'Default Shift 2014/2015';
UPDATE translations SET es = 'Seleccionar estudiante' WHERE en = 'Select Student';
UPDATE translations SET es = 'Todas las clases' WHERE en = 'All Classes';
UPDATE translations SET es = 'Conducta del estudiante' WHERE en = 'Student Behaviours';
UPDATE translations SET es = 'Asistencia del estudiante' WHERE en = 'Student Attendance';
UPDATE translations SET es = 'Asistencias de los estudiantes' WHERE en = 'Student Attendances';
UPDATE translations SET es = 'Honorario total' WHERE en = 'Total Fee';
UPDATE translations SET es = 'Cantidad pagada' WHERE en = 'Amount Paid';
UPDATE translations SET es = 'Honorario Destacado' WHERE en = 'Outstanding Fee';
UPDATE translations SET es = 'No OpenEMIS' WHERE en = 'Openemisno';
UPDATE translations SET es = 'Conducta del personal' WHERE en = 'Staff Behaviours';
UPDATE translations SET es = 'Seguridad del Usuario' WHERE en = 'Security User';
UPDATE translations SET es = 'Cantidad (JD)' WHERE en = 'Amount (JD)';
UPDATE translations SET es = 'Formulario de encuesta' WHERE en = 'Survey Form';
UPDATE translations SET es = 'Plantilla para Matriz de valoración' WHERE en = 'Rubric Template';
UPDATE translations SET es = 'Nivel del periodo académico' WHERE en = 'Academic Period Level';
DELETE FROM translations WHERE en = 'Institution Site Section';
DELETE FROM translations WHERE en = 'Institution Site Class';
UPDATE translations SET es = 'Tipo de calidad de visita' WHERE en = 'Quality Visit Type';
UPDATE translations SET es = 'Etiquetas' WHERE en = 'Labels';
UPDATE translations SET es = 'Tutor' WHERE en = 'Guardian';
UPDATE translations SET es = 'Relación con el Tutor' WHERE en = 'Guardian Relation';
UPDATE translations SET es = 'Tarifas del estudiante' WHERE en = 'Student Fees';
UPDATE translations SET es = 'Agregar adición' WHERE en = 'Add Addition';
UPDATE translations SET es = 'Agregar deducción' WHERE en = 'Add Deduction';
UPDATE translations SET es = 'Seleccionar archivo' WHERE en = 'Select File';
UPDATE translations SET es = 'Motivo ausencia del estudiante' WHERE en = 'Student Absence Reason';
UPDATE translations SET es = 'Tipo ausencia' WHERE en = 'Absence Type';
UPDATE translations SET es = 'Copyright © 2015 OpenEMIS. Todos los derechos reservados.' WHERE en = 'Copyright © 2015 OpenEMIS. All rights reserved.';
UPDATE translations SET es = 'Edad' WHERE en = 'Age';
UPDATE translations SET es = 'Copyright' WHERE en = 'Copyright';
UPDATE translations SET es = 'Todos los derechos reservados.' WHERE en = 'All rights reserved.';
UPDATE translations SET es = 'Directorio' WHERE en = 'Directory';
UPDATE translations SET es = 'Auditoría' WHERE en = 'Audit';
UPDATE translations SET es = 'Importar estudiantes' WHERE en = 'Import Students';
UPDATE translations SET es = 'Seleccionar archivo para importar' WHERE en = 'Select File To Import';
UPDATE translations SET es = 'Importar' WHERE en = 'Import';
UPDATE translations SET es = 'Número de fila' WHERE en = 'Row Number';
UPDATE translations SET es = 'El registro no se añade debido a errores encontrados' WHERE en = 'The record is not added due to errors encountered';
UPDATE translations SET es = 'Conectividad de red' WHERE en = 'Network Connectivity';
UPDATE translations SET es = 'Esta escuela' WHERE en = 'This School';
UPDATE translations SET es = 'Otra escuela' WHERE en = 'Other School';
UPDATE translations SET es = 'Abierto' WHERE en = 'Open';
UPDATE translations SET es = 'Tipo de sangre' WHERE en = 'Blood Type';
UPDATE translations SET es = 'Nombre del Médico' WHERE en = 'Doctor Name';
UPDATE translations SET es = 'Contacto del Médico' WHERE en = 'Doctor Contact';
UPDATE translations SET es = 'Salud' WHERE en = 'Healths';
UPDATE translations SET es = 'Seguro médico' WHERE en = 'Health Insurance';
UPDATE translations SET es = 'Centro Médico' WHERE en = 'Medical Facility';
UPDATE translations SET es = 'Salud - Alergías' WHERE en = 'Health Allergies';
UPDATE translations SET es = 'Salud - Tipo de Alergías' WHERE en = 'Health Allergy Type';
UPDATE translations SET es = 'Salud - Consultas' WHERE en = 'Health Consultations';
UPDATE translations SET es = 'Salud - Tipo de Consultas' WHERE en = 'Health Consultation Type';
UPDATE translations SET es = 'Salud - Familias' WHERE en = 'Health Families';
UPDATE translations SET es = 'Familias' WHERE en = 'Families';
UPDATE translations SET es = 'Salud - Relaciones' WHERE en = 'Health Relationship';
UPDATE translations SET es = 'Salud - Condición' WHERE en = 'Health Condition';
UPDATE translations SET es = 'Salud - Historial' WHERE en = 'Health Histories';
UPDATE translations SET es = 'Historial' WHERE en = 'Histories';
UPDATE translations SET es = 'Salud - Tipo de inmunización' WHERE en = 'Health Immunization Type';
UPDATE translations SET es = 'Salud - Pruebas' WHERE en = 'Health Tests';
UPDATE translations SET es = 'Salud - Tipo de pruebas' WHERE en = 'Health Test Type';
UPDATE translations SET es = 'Mapa' WHERE en = 'Map';
UPDATE translations SET es = 'Salud - Inmunizaciones' WHERE en = 'Health Immunizations';
UPDATE translations SET es = 'Salud - Medicamentos' WHERE en = 'Health Medications';
UPDATE translations SET es = 'Área (Administrativa) - País' WHERE en = 'Area (Administrative) - Country';
UPDATE translations SET es = 'Área (Administrativa)' WHERE en = 'Area (Administrative)';
UPDATE translations SET es = 'Terreno' WHERE en = 'land';
UPDATE translations SET es = 'Estatus del edificio' WHERE en = 'Building Status';
UPDATE translations SET es = 'Año del edificio' WHERE en = 'Building Inst Year';
UPDATE translations SET es = 'Futura expansión del edificio' WHERE en = 'Building Future Expansion';
UPDATE translations SET es = 'Propietario del edificio' WHERE en = 'Building Ownership';
UPDATE translations SET es = 'Costo anual del alquiler' WHERE en = 'Yearly Rent Cost';
UPDATE translations SET es = 'Estatus WC del edificio' WHERE en = 'Building WCstatus';
UPDATE translations SET es = 'Tipo de edificio' WHERE en = 'Building Type';
UPDATE translations SET es = 'Superficie del terreno' WHERE en = 'Land Area';
UPDATE translations SET es = 'Número del terreno del edificio' WHERE en = 'Building Land Number';
UPDATE translations SET es = 'Disponibilidad de agua en el edificio' WHERE en = 'Building Water Availability';
UPDATE translations SET es = 'Tipo de deflación del edificio' WHERE en = 'Building Deflation Type';
UPDATE translations SET es = 'Modelo del edificio' WHERE en = 'Building Model';
UPDATE translations SET es = 'Disponibilidad de electricidad en el edificio' WHERE en = 'Building Electricity Availability';
UPDATE translations SET es = 'Seq del edificio' WHERE en = 'Building seq';
UPDATE translations SET es = 'Numero de cama del edificio ' WHERE en = 'Building Bed Number';
UPDATE translations SET es = 'Grado individual' WHERE en = 'Single Grade';
UPDATE translations SET es = 'Grado multiple' WHERE en = 'Multi Grade';
UPDATE translations SET es = 'Clases con grados individuales' WHERE en = 'Single Grade Classes';
UPDATE translations SET es = 'Seleccione un Docente o dejar en blanco' WHERE en = 'Select Teacher or Leave Blank';
UPDATE translations SET es = 'Cuenta de Personal' WHERE en = 'Staff Account';
UPDATE translations SET es = 'Usuario de Personal' WHERE en = 'Staff User';
UPDATE translations SET es = '. ¿Seguro que quieres eliminar este registro?' WHERE en = '. Are you sure you want to delete this record';
UPDATE translations SET es = 'Eliminar no está permitido ya que los estudiantes aun existe en la clase.' WHERE en = 'Delete is not allowed as students still exists in class';
UPDATE translations SET es = 'Secciones de Institución' WHERE en = 'InstitutionSections';
UPDATE translations SET es = 'Por favor, revise la información antes de proceder con la operación.' WHERE en = 'Please review the information before proceeding with the operation';
UPDATE translations SET es = 'De Período Académico' WHERE en = 'From Academic Period';
UPDATE translations SET es = 'Para Período Académico' WHERE en = 'To Academic Period';
UPDATE translations SET es = 'De Grado' WHERE en = 'From Grade';
UPDATE translations SET es = 'Para Grado' WHERE en = 'To Grade';
UPDATE translations SET es = 'OpenEmis ID' WHERE en = 'OpenEmis ID';
UPDATE translations SET es = 'El siguiente Grado en la estructura de la educación no está disponible en esta Institución o no ha sido definido en ningún período académico próximo.' WHERE en = 'Next grade in the Education Structure is not available in this Institution or no Next Academic Period defined';
UPDATE translations SET es = 'Grado actual' WHERE en = 'Current Grade';
UPDATE translations SET es = 'Actual Grado educativo' WHERE en = 'Current Education Grade';
UPDATE translations SET es = 'Próximo Grado educativo' WHERE en = 'Next Education Grade';
UPDATE translations SET es = 'Los estudiantes han sido transferidos.' WHERE en = 'Students have been transferred.';
UPDATE translations SET es = '¿Seguro que quieres eliminar este registro?' WHERE en = 'Are you sure you want to delete this record.';
UPDATE translations SET es = 'Nombre de usuario' WHERE en = 'User Name';
UPDATE translations SET es = 'Ausencias del estudiante' WHERE en = 'Student Absences';
UPDATE translations SET es = 'Día completo' WHERE en = 'Full Day';
UPDATE translations SET es = 'Ausencias del personal' WHERE en = 'Staff Absences';
UPDATE translations SET es = 'Usuario estudiante' WHERE en = 'Student User';
UPDATE translations SET es = 'Encuestas a estudiante' WHERE en = 'Student Surveys';
UPDATE translations SET es = 'Encuestas a estudiante' WHERE en = 'student surveys';
UPDATE translations SET es = 'Encuestas a estudiantes' WHERE en = 'students surveys';
UPDATE translations SET es = 'Por Año %s' WHERE en = 'For Year %s';
UPDATE translations SET es = 'Área (Administrativa)' WHERE en = 'Area (Administrative)';
UPDATE translations SET es = 'No. de otro estudiante disponible' WHERE en = 'No Other Student Available';
UPDATE translations SET es = 'Seleccionar periodo' WHERE en = 'Select Period';
UPDATE translations SET es = 'Seleccionar clase' WHERE en = 'Select Class';
UPDATE translations SET es = 'Domingo' WHERE en = 'Sunday';
UPDATE translations SET es = 'Lunes' WHERE en = 'Monday';
UPDATE translations SET es = 'Martes' WHERE en = 'Tuesday';
UPDATE translations SET es = 'Miércoles' WHERE en = 'Wednesday';
UPDATE translations SET es = 'Jueves' WHERE en = 'Thursday';
UPDATE translations SET es = 'Viernes' WHERE en = 'Friday';
UPDATE translations SET es = 'Sábado' WHERE en = 'Saturday';
UPDATE translations SET es = 'No. Tarifas Programa de Grado' WHERE en = 'No Programme Grade Fees';
UPDATE translations SET es = 'No. Asignaturas Disponibles' WHERE en = 'No Available Subjects';
UPDATE translations SET es = 'Próximo Grado en la estructura de Educación no está disponible en esta Institución.' WHERE en = 'Next grade in the Education Structure is not available in this Institution.';
UPDATE translations SET es = 'No hay docentes' WHERE en = 'Non-Teaching';
UPDATE translations SET es = 'No. de Notificación' WHERE en = 'No Notices';
UPDATE translations SET es = 'Pariente' WHERE en = 'Parent';
UPDATE translations SET es = 'Salud - Tipo de Consulta' WHERE en = 'Health Consultation Type';
UPDATE translations SET es = 'Empleos del personal' WHERE en = 'Staff Employments';
UPDATE translations SET es = 'Nuestros turnos' WHERE en = 'Our Shifts';
UPDATE translations SET es = 'Cambios externos' WHERE en = 'External Shifts';
UPDATE translations SET es = 'Esta institución' WHERE en = 'This Institution';
UPDATE translations SET es = 'Otra institución' WHERE en = 'Other Institution';
UPDATE translations SET es = 'Agregar a todos los estudiantes' WHERE en = 'Add All Students';
UPDATE translations SET es = 'Promoción' WHERE en = 'Promotion';
UPDATE translations SET es = 'Los estudiantes han sido promovidos' WHERE en = 'Students have been promoted ';
UPDATE translations SET es = 'Deshacer' WHERE en = 'Undo';
UPDATE translations SET es = 'Aprobación de transferencia' WHERE en = 'Transfer Approvals';
UPDATE translations SET es = 'Aprobado' WHERE en = 'Approve';
UPDATE translations SET es = 'Estatus de la aplicación' WHERE en = 'Application Status';
UPDATE translations SET es = 'Fecha vigente' WHERE en = 'Effective Date';
UPDATE translations SET es = 'Motivo de deserción del estudiante' WHERE en = 'Student Dropout Reason';
UPDATE translations SET es = 'Ausencia - Justificada' WHERE en = 'Absence - Excused';
UPDATE translations SET es = 'Ausencia - Injustificada' WHERE en = 'Absence - Unexcused';
UPDATE translations SET es = 'Tarde' WHERE en = 'Late';
UPDATE translations SET es = 'No. de  grados disponibles en esta institución' WHERE en = 'No Available Grades in this Institution';
UPDATE translations SET es = 'No. de períodos académicos disponibles' WHERE en = 'No Available Academic Periods';
UPDATE translations SET es = 'Por favor Definir el Tipo de Identificación predeterminada' WHERE en = 'Please Define Default Identity Type';
UPDATE translations SET es = 'Identificación del personal es obligatoria' WHERE en = 'Staff identity is mandatory';
UPDATE translations SET es = 'Identificación del estudiante es obligatoria' WHERE en = 'Student identity is mandatory';


-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3017', NOW());

-- infrastructure_levels
RENAME TABLE `infrastructure_levels` TO `z_3017_infrastructure_levels`;

DROP TABLE IF EXISTS `infrastructure_levels`;
CREATE TABLE IF NOT EXISTS `infrastructure_levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `name` varchar(250) NOT NULL,
  `description` text,
  `editable` int(11) NOT NULL DEFAULT '1',
  `parent_id` int(11) DEFAULT NULL,
  `lft` int(11) DEFAULT NULL,
  `rght` int(11) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `infrastructure_levels` (`id`, `code`, `name`, `description`, `editable`, `parent_id`, `lft`, `rght`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1, 'LAND', 'Land', '', 0, NULL, 1, 8, NULL, NULL, 2, '0000-00-00 00:00:00'),
(2, 'BUILDING', 'Building', '', 0, 1, 2, 7, NULL, NULL, 2, '0000-00-00 00:00:00'),
(3, 'FLOOR', 'Floor', '', 0, 2, 3, 6, NULL, NULL, 2, '0000-00-00 00:00:00'),
(4, 'ROOM', 'Room', '', 0, 3, 4, 5, NULL, NULL, 2, '0000-00-00 00:00:00');

-- infrastructure_types
RENAME TABLE `infrastructure_types` TO `z_3017_infrastructure_types`;

DROP TABLE IF EXISTS `infrastructure_types`;
CREATE TABLE IF NOT EXISTS `infrastructure_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `infrastructure_level_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `room_statuses`;
CREATE TABLE IF NOT EXISTS `room_statuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `name` varchar(250) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `room_statuses` (`id`, `code`, `name`) VALUES
(1, 'IN_USE', 'In Use'),
(2, 'END_OF_USAGE', 'End of Usage'),
(3, 'CHANGE_IN_ROOM_TYPE', 'Change in Room Type');

-- room_types
DROP TABLE IF EXISTS `room_types`;
CREATE TABLE IF NOT EXISTS `room_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- institution_rooms
DROP TABLE IF EXISTS `institution_rooms`;
CREATE TABLE IF NOT EXISTS `institution_rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL,
  `name` varchar(250) NOT NULL,
  `start_date` date NOT NULL,
  `start_year` int(4) NOT NULL,
  `end_date` date NOT NULL,
  `end_year` int(4) NOT NULL,
  `room_status_id` int(11) NOT NULL,
  `institution_infrastructure_id` int(11) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `room_type_id` int(11) NOT NULL,
  `infrastructure_condition_id` int(11) NOT NULL,
  `previous_room_id` int(11) NOT NULL COMMENT 'links to institution_rooms.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `institution_infrastructure_id` (`institution_infrastructure_id`),
  KEY `institution_id` (`institution_id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `room_type_id` (`room_type_id`),
  KEY `infrastructure_condition_id` (`infrastructure_condition_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- custom field
CREATE TABLE `z_3017_infrastructure_custom_forms` LIKE  `infrastructure_custom_forms`;
INSERT INTO `z_3017_infrastructure_custom_forms` SELECT * FROM `infrastructure_custom_forms` WHERE 1;

CREATE TABLE `z_3017_infrastructure_custom_forms_filters` LIKE  `infrastructure_custom_forms_filters`;
INSERT INTO `z_3017_infrastructure_custom_forms_filters` SELECT * FROM `infrastructure_custom_forms_filters` WHERE 1;

RENAME TABLE `institution_infrastructures` TO `z_3017_institution_infrastructures`;
CREATE TABLE `institution_infrastructures` LIKE  `z_3017_institution_infrastructures`;

RENAME TABLE `infrastructure_custom_field_values` TO `z_3017_infrastructure_custom_field_values`;
CREATE TABLE `infrastructure_custom_field_values` LIKE  `z_3017_infrastructure_custom_field_values`;

RENAME TABLE `infrastructure_custom_table_columns` TO `z_3017_infrastructure_custom_table_columns`;
RENAME TABLE `infrastructure_custom_table_rows` TO `z_3017_infrastructure_custom_table_rows`;
RENAME TABLE `infrastructure_custom_table_cells` TO `z_3017_infrastructure_custom_table_cells`;

-- room_custom_field_values
DROP TABLE IF EXISTS `room_custom_field_values`;
CREATE TABLE IF NOT EXISTS `room_custom_field_values` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_value` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_value` int(11) DEFAULT NULL,
  `textarea_value` text COLLATE utf8mb4_unicode_ci,
  `date_value` date DEFAULT NULL,
  `time_value` time DEFAULT NULL,
  `file` longblob,
  `infrastructure_custom_field_id` int(11) NOT NULL,
  `institution_room_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `infrastructure_custom_field_id` (`infrastructure_custom_field_id`),
  KEY `institution_room_id` (`institution_room_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- custom_modules
CREATE TABLE `z_3017_custom_modules` LIKE  `custom_modules`;
INSERT INTO `z_3017_custom_modules` SELECT * FROM `custom_modules` WHERE 1;

ALTER TABLE `custom_modules` DROP `filter`;
ALTER TABLE `custom_modules` DROP `behavior`;
ALTER TABLE `custom_modules` DROP `supported_field_types`;

INSERT INTO `custom_modules` (`code`, `name`, `model`, `visible`, `parent_id`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Room', 'Institution > Room', 'Institution.InstitutionRooms', 1, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

-- security_functions
UPDATE `security_functions`
SET `_view` = 'Fields.index|Fields.view|Pages.index|Pages.view|Types.index|Types.view|RoomPages.index|RoomPages.view|RoomTypes.index|RoomTypes.view', `_edit` = 'Fields.edit|Pages.edit|Types.edit|RoomPages.edit|RoomTypes.edit', `_add` = 'Fields.add|Pages.add|Types.add|RoomPages.add|RoomTypes.add', `_delete` = 'Fields.remove|Pages.remove|Types.remove|RoomPages.remove|RoomTypes.remove'
WHERE id = 5018;

UPDATE `security_functions`
SET `_view` = 'Infrastructures.index|Infrastructures.view|Rooms.index|Rooms.view', `_edit` = 'Infrastructures.edit|Rooms.edit', `_add` = 'Infrastructures.add|Rooms.add', `_delete` = 'Infrastructures.remove|Rooms.remove'
WHERE id = 1011;

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)  VALUES (uuid(), 'InstitutionRooms', 'institution_infrastructure_id', 'Institutions -> Rooms', 'Parent', 1, 1, NOW());


-- 3.6.2
UPDATE config_items SET value = '3.6.2' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
