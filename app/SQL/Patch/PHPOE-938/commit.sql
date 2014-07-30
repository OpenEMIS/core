update `navigations` set `action` = 'SecurityGroup', `pattern` = 'SecurityGroup' where id = 39;

update `security_functions` set visible = 0 where id = 45;

update `security_functions` 
set parent_id = -1,
_view = 'index|systems|levels|cycles|programmes|grades|subjects|certifications|orientations|fields|reorder'
where id = 46;

update `security_functions`
set _view = 'Area.index|Area.view|index|areas|areasView|areasEducation|areasEducationView',
_edit = 'Area.edit|areasEdit|areasReorder|areasMove|areasEducationEdit|areasEducationReorder|areasEducationMove',
_add = '_view:Area.add|areasAdd|areasEducationAdd'
where id = 44;

update `security_functions`
set _view = 'SecurityGroup|SecurityGroup.index|groupsView',
_add = 'SecurityGroup.add|groupsAdd'
where id = 55;

update `security_functions` set _view = 'index|view|advanced' where id = 8;
update `security_functions` set _add = '_view:attachmentsAdd' where id = 10;
update `security_functions` set _add = '_view:bankAccountsAdd' where id = 11;
update `security_functions` set _add = NULL, _delete = NULL where id = 13;
update `security_functions` set _add = '_view:positionsAdd' where id = 14;
update `security_functions` set _add = '_view:classesAdd' where id = 15;
update `security_functions` set _add = '_view:staffAdd|staffSave' where id = 27;
update `security_functions` set _add = '_view:feeAdd' where id = 195;
