<?php
use Migrations\AbstractMigration;

class POCOR5685a extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        // Backup locale_contents table
        $this->execute('CREATE TABLE `z_5685_labels` LIKE `labels`');
        $this->execute('INSERT INTO `z_5685_labels` SELECT * FROM `labels`');
        // End

        //Insert Data into labels tables
        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'Profiles', 'profile_name', 'Institution -> General-> Profiles', 'Profile Name', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'InstitutionClasses', 'capacity', 'Institution -> Academic -> Classes', 'Capacity', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'InstitutionMealProgrammes', 'date_received', 'Institution -> meals -> Programme', 'Date Received', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'InstitutionMealProgrammes', 'quantity_received', 'Institution -> meals -> Programme', 'Quantity Received', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'InstitutionMealProgrammes', 'delivery_status_id', 'Institution -> meals -> Programme', 'Delivery Status', 1, 1, NOW())");


        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'InstitutionPositions', 'current_staff', 'Institution -> Appointment -> Positions', 'Current Staff', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'FeederOutgoingInstitutions', 'recipient_institution', 'Institution -> Academic -> Feeders -> outgoing', 'Recipient Institution', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'FeederOutgoingInstitutions', 'area_education', 'Institution -> Academic -> Feeders -> outgoing', 'District', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'FeederIncomingInstitutions', 'feeder_institution_id', 'Institution -> Academic -> Feeders -> incoming', 'Feeder Institution', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'FeederIncomingInstitutions', 'no_of_students', 'Institution -> Academic -> Feeders -> incoming', 'No Of Students', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'FeederIncomingInstitutions', 'area_education', 'Institution -> Academic -> Feeders -> incoming', 'District', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'StudentOutcomes', 'outcome_template', 'Institution -> performance -> outcomes', 'Outcome Template', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'ReportCardStatuses', 'report_queue', 'Institution -> performance -> Report Card', 'Report Queue', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'ReportCardStatuses', 'email_status', 'Institution -> performance -> Report Card', 'Email Status', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'ScheduleTimetables', 'institution_schedule_interval_id', 'Institution -> Academic -> schedules -> Timetables', 'Interval', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'InstitutionStaffDuties', 'staff_duties_id', 'Institution -> Appointment -> Duties', 'Duty Type', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'InstitutionBudgets', 'amount', 'Institution -> finance -> Budget', 'Amount (PM)', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'InstitutionIncomes', 'income_source_id', 'Institution -> finance -> Income', 'Source', 1, 1, NOW())");


        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'InstitutionIncomes', 'amount', 'Institution -> finance -> Income', 'Amount (PM)', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'InstitutionExpenditures', 'amount', 'Institution -> finance -> Expenditure', 'Amount (PM)', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'InstitutionExpenditures', 'budget_type_id', 'Institution -> finance -> Expenditure', 'Budget', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'InfrastructureNeeds', 'name', 'Institution -> infrastructures -> Needs', 'Need Type', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'InfrastructureNeeds', 'priority', 'Institution -> infrastructures -> Needs', 'Priority', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'InfrastructureProjects', 'infrastructure_project_funding_source_id', 'Institution -> infrastructures -> Projects', 'Funding Source', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'InfrastructureProjects', 'contract_date', 'Institution -> infrastructures >- Projects', 'Contract Date', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'InstitutionBuses', 'plate_number', 'Institution -> Transport -> Buses', 'Plate Number', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'InstitutionBuses', 'bus_type_id', 'Institution -> Transport -> Buses', 'Bus Type', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'InstitutionBuses', 'capacity', 'Institution -> Transport -> Buses', 'Capacity', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'InstitutionTrips', 'trip_type_id', 'Institution -> Transport -> Trips', 'Trip Type', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'InstitutionTrips', 'institution_bus_id', 'Institution -> Transport -> Trips', 'Bus', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'InstitutionTrips', 'repeat', 'Institution -> Transport -> Trips', 'Repeat', 1, 1, NOW())");

        $this->execute("INSERT INTO labels (id, module, field, module_name, field_name, visible, created_user_id, created)
        values (uuid(), 'InstitutionTestCommittees', 'chairperson', 'Institution -> Committees', 'Chairperson', 1, 1, NOW())");
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `labels`');
        $this->execute('RENAME TABLE `z_5685_labels` TO `labels`');
    }
}
