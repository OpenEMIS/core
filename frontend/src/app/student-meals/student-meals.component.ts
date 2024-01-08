import { Component, OnInit, ViewChild } from '@angular/core';
import { MINI_DASHBOARD_MEAL_CONFIG, TABLE_COLUMN_LIST } from './student-meals.config';
import { IMiniDashboardConfig, IMiniDashboardItem } from 'openemis-styleguide-lib/kd-components/kd-angular-mini-dashboard/kd-angular-mini-dashboard-interface';
import { ITableApi, ITableColumn, ITableConfig, KdTable } from 'openemis-styleguide-lib';
import { timer } from 'rxjs';

@Component({
  selector: 'app-student-meals',
  templateUrl: './student-meals.component.html',
  styleUrls: ['./student-meals.component.css']
})
export class StudentMealsComponent implements OnInit {
  @ViewChild('changetable') child: KdTable;

  public displayLoading: boolean = true;
  public breadcrumbList = {
    home: { icon: 'fa fa-home', path: '' },
    list: [{
      name: 'Institutions',
      path: '',
    },
    {
      name: 'Avory Primary School',
      path: '',
    },
    {
      name: 'Student Meals',
      path: '',
    }]
  };
  public pageheader = {
    leftBtn: [{
      type: "export",
      callback: (): void => {
        this.backToData();
      }
    },
    {
      type: "import",
      callback: (): void => {
        this.editTableFields();
      }
    },
    {
      type: "edit",
      callback: (): void => {
        this.onEditClick();
      }
    }
    ],
    moreAction: [],
    moreBtn: false,
    pageheaderText: "Avory Primary School - Student Meals",
    searchBtn: false,
    searchEvent: ['change', 'keyup']
  }
  public miniDashboardConfig: any = '';
  public miniDashboardData: Array<IMiniDashboardItem>;
  public displayMiniDashboard: boolean = true;
  public _row: Array<any>;
  public _column: Array<ITableColumn>;
  public _config: ITableConfig = {
    id: "normalTable",
    rowIdKey: "id",
    gridHeight: "auto",
    rowContentHeight: 25,
    loadType: "normal",
    externalFilter: false,
    paginationConfig: {
      pagesize: 10,
      total: 50000,
    },
    context: {
      isMarked: false,
      schoolClosed: false,
      mode: 'view',
      mealPrograme: 2,
      mealTypes: [
        {
          id: 1,
          name: "Received"
        },
        {
          id: 2,
          name: "Not Received"
        },
        {
          id: 3,
          name: "None"
        },
      ],
      mealBenefitTypeOptions: [
        {
          id: 1,
          name: '100%',
          default: 1
        }
      ],
      // date: "2023-12-18",
      education_grade_id: 189,
      period: 1,
      subject_id: 0,
      week: 51
    }
  }

  public academicPeriod: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Academic Period',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'disabled': false,
      'options': [
        {
          'key': 1,
          'value': '2023'
        }, {
          'key': 2,
          'value': '2022'
        }
      ]
    }
  ]
  public week: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Week',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'disabled': false,
      'options': [
        {
          'key': 1,
          'value': 'Week 1 (January 01, 2023 - January 08, 2023)'
        }, {
          'key': 2,
          'value': 'Week 2 (January 09, 2023 - January 15, 2023)'
        }
      ],
    }
  ]
  public day: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Day',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'options': [
        {
          'key': 1,
          'value': 'Monday'
        }, {
          'key': 2,
          'value': 'Tuesday'
        }, {
          'key': 3,
          'value': 'Wednesday'
        }, {
          'key': 4,
          'value': 'Thursday'
        }, {
          'key': 5,
          'value': 'Friday'
        },

      ]
    }
  ]
  public class: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Class',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'options': [
        {
          'key': 1,
          'value': 'Primary 1-A'
        }, {
          'key': 2,
          'value': 'Primary 1-B'
        },

      ]
    }
  ]
  public mealProgram: Array<any> = [
    {
      'key': 'dropdown',
      'label': 'Meal Program',
      'visible': true,
      'required': false,
      'controlType': 'dropdown',
      'options': [
        {
          'key': 1,
          'value': 'WFP 2023'
        },

      ]
    }
  ]
  public filterButtons: Array<any> = [
    {
      name: '',
      class: 'd-none'
    },
    {
      name: '',
      class: 'd-none'
    }
  ]

  public _tableApi: ITableApi = {};
  public displayEditTable: boolean = false


  constructor() { }

  ngOnInit(): void {
    this.displayLoading = false;
    this.setDashboard();
  }

  backToData() {

  }

  editTableFields() {

  }

  onEditClick() {
    this.displayLoading = true;
    this.pageheader = {
      leftBtn: [
        {
          type: "back",
          callback: (): void => {
            this.onBackClick();
          }
        }
      ],
      moreAction: [],
      moreBtn: false,
      pageheaderText: "Avory Primary School - Student Meals",
      searchBtn: false,
      searchEvent: ['change', 'keyup']
    }
    this.displayEditTable = !this.displayEditTable;
    this.child.setStudentMeal(this.displayEditTable);
    this.displayMiniDashboard = false;
    setTimeout(() => {
      this.displayLoading = false;
    }, 1000);
  }

  onBackClick(){
    this.displayLoading = true;
    this.displayEditTable = !this.displayEditTable;
    this.child.setStudentMeal(this.displayEditTable);
    this.displayMiniDashboard = true;
    this.pageheader = {
      leftBtn: [{
        type: "export",
        callback: (): void => {
          this.backToData();
        }
      },
      {
        type: "import",
        callback: (): void => {
          this.editTableFields();
        }
      },
      {
        type: "edit",
        callback: (): void => {
          this.onEditClick();
        }
      }
      ],
      moreAction: [],
      moreBtn: false,
      pageheaderText: "Avory Primary School - Student Meals",
      searchBtn: false,
      searchEvent: ['change', 'keyup']
    }
    setTimeout(() => {
      this.displayLoading = false;
    }, 1000);
  }

  setDashboard() {
    this.miniDashboardData = [
      {
        type: 'text',
        icon: 'kd-students icon',
        label: 'Total Students:',
        // value: this._row.length
        value: 0
      },
      {
        type: 'text',
        label: 'Meal Received',
        // value: this.absent
        value: 0
      }
    ]

    timer(1000).subscribe((): void => {
      this._column = [
        TABLE_COLUMN_LIST.personName,
        TABLE_COLUMN_LIST.openEmisId,
        TABLE_COLUMN_LIST.mealReceived,
        TABLE_COLUMN_LIST.mealBenefit
      ];
    });

    timer(2000).subscribe((): void => {
      this._row = [
        {
          "id": 159,
          "academic_period_id": 32,
          "institution_class_id": 568,
          "institution_id": 6,
          "student_id": 1311,
          "user": {
            "id": 1311,
            "openemis_no": "1522413076",
            "first_name": "Aaron",
            "middle_name": null,
            "third_name": null,
            "last_name": "Butler",
            "preferred_name": null,
            "name": "Aaron Butler",
            "name_with_id": "1522413076 - Aaron Butler",
            "name_with_id_role": "1522413076 - Aaron Butler (Student)",
            "default_identity_type": "",
            "has_special_needs": true
          },
          "institution_student_meal": {
            "date": "2023-12-18",
            "meal_benefit_id": null,
            "meal_benefit": null,
            "meal_received_id": null,
            "meal_received": "None"
          }
        },
        {
          "id": 160,
          "academic_period_id": 32,
          "institution_class_id": 568,
          "institution_id": 6,
          "student_id": 1311,
          "user": {
            "id": 1311,
            "openemis_no": "1522413076",
            "first_name": "Aaron",
            "middle_name": null,
            "third_name": null,
            "last_name": "Butler",
            "preferred_name": null,
            "name": "Aaron Butler",
            "name_with_id": "1522413076 - Aaron Butler",
            "name_with_id_role": "1522413076 - Aaron Butler (Student)",
            "default_identity_type": "",
            "has_special_needs": true
          },
          "institution_student_meal": {
            "date": "2023-12-18",
            "meal_benefit_id": null,
            "meal_benefit": null,
            "meal_received_id": null,
            "meal_received": "None"
          }
        },
        {
          "id": 161,
          "academic_period_id": 32,
          "institution_class_id": 568,
          "institution_id": 6,
          "student_id": 1311,
          "user": {
            "id": 1311,
            "openemis_no": "1522413076",
            "first_name": "Aaron",
            "middle_name": null,
            "third_name": null,
            "last_name": "Butler",
            "preferred_name": null,
            "name": "Aaron Butler",
            "name_with_id": "1522413076 - Aaron Butler",
            "name_with_id_role": "1522413076 - Aaron Butler (Student)",
            "default_identity_type": "",
            "has_special_needs": true
          },
          "institution_student_meal": {
            "date": "2023-12-18",
            "meal_benefit_id": null,
            "meal_benefit": null,
            "meal_received_id": null,
            "meal_received": "None"
          }
        }
      ]
    })
  }

  _submitEvent(event: any, type: any) {

  }

}
