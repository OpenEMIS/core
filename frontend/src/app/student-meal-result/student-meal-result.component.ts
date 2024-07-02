import { Component, OnInit } from '@angular/core';
import { ApiService } from '../api.service';
import { DEFAULT_TEMPLATE_THEME } from '../shared/config.default-val';
import { IMiniDashboardConfig, IMiniDashboardItem } from '../workbench/component.mini-dashboard.config';
import { MINI_DASHBOARD_CONFIG } from '../student-attendance/student_attendance.config';
import { ITableApi, ITableConfig, KdAlertEvent } from 'openemis-styleguide-lib';
import { timer } from 'rxjs';
import { TABLE_COLUMN_LIST } from './student-meal-result.config';
import { ExcelService } from '../shared/excel.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-student-meal-result',
  templateUrl: './student-meal-result.component.html',
  styleUrls: ['./student-meal-result.component.css']
})
export class StudentMealResultComponent implements OnInit {
  displayLoading: boolean = false;
  displayMiniDashboard: boolean = false;
  counter: number = 0;
  themeArray = DEFAULT_TEMPLATE_THEME;
  public miniDashboardConfig: IMiniDashboardConfig = MINI_DASHBOARD_CONFIG;
  public miniDashboardData: Array<IMiniDashboardItem>;
  public _row: Array<any>;
  public _tableApi: ITableApi = {};
  public dataFromRoute: any;
  public pageheader: any = {
    leftBtn: [{
      type: "back",
      callback: () => {
        this.backToData();
      }
    },
    {
      custom: true,
      icon: 'fa fa-download',
      tooltip: 'Download',
      callback: (): void => {
        this.generateExcel();
      }
    },
    ],
    moreAction: [],
    moreBtn: false,
    pageheaderText: "",
    searchBtn: false,
    searchEvent: ['change', 'keyup']
  }
  meal_report: any;
  public _config: ITableConfig = {
    id: 'listTable',
    rowIdKey: "id",
    gridHeight: "auto",
    rowContentHeight: 25,
    loadType: "infinite",
    externalFilter: false,
    paginationConfig: {
      pagesize: 10000,
      total: 1000,
    },
  };
  public _column: any;
  meal_import_result: any;
  institution_name: string;

  constructor(
    private Rest: ApiService,
    private excelSvc: ExcelService,
    public router: Router,
    private _kdAlertEvent: KdAlertEvent,
  ) {
    this.dataFromRoute = this.router.getCurrentNavigation().extras.state?.['importData'];
  }

  ngOnInit(): void {
    console.log(this.dataFromRoute, "this.dataFromRoute");
    if (!this.dataFromRoute) {
      let toasterConfig: any = {
        title: 'Please fill all fields',
        showCloseButton: true,
        tapToDismiss: true,
      };
      console.log(toasterConfig, "toasterConfig");

      this._kdAlertEvent.warn(toasterConfig);
      this.router.navigateByUrl('Institution/Institutions/ImportStudentMeals/add');
    } else {
      this.meal_import_result = this.dataFromRoute.meal_import_result;
      this.meal_report = this.dataFromRoute.meal_imported;
      this.institution_name = localStorage.getItem("institutionName");
      this.pageheader.pageheaderText = `${this.institution_name} - Import Student Attendances`
      this.loginData();
    }
  }

  loginData() {
    this.Rest.setSession();
    let token = localStorage.getItem("loginToken");
    if (!token) {
      let userName = sessionStorage.getItem('username');
      let password = sessionStorage.getItem('password');

      if (userName == null && password == null) {
        setTimeout(() => {
          this.counter = this.counter + 1;
          if (this.counter <= 5) {
            this.loginData();
          } else {
            alert('Please login again')
          }
        }, 1500);
      } else {
        var decodedPassword = atob(password);
        if (userName && decodedPassword) {
          this.loginApi(userName, decodedPassword);
        } else {
          this.removeSession();
        }
      }
    } else {
      this.setTheme();
      this.getAPIData();
      this.setMiniDashboard();
    }
  }

  loginApi(userName: string, password: string) {
    this.Rest.loginApi(userName, password).subscribe({
      next: (response: any) => {
        if (response) {
          localStorage.setItem("loginToken", response?.data?.token);
          this.setTheme();
          this.getAPIData();
          this.setMiniDashboard();
        }
      },
      error: (error: any) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  removeSession() {
    delete sessionStorage.username;
    delete sessionStorage.password;
  }

  setTheme() {
    this.Rest.getWithToken('themes').subscribe({
      next: (response: any) => {
        console.log(response?.data[3].default_value, "response");
        let selectedThemeData = '';
        if (response?.data[3].value) {
          selectedThemeData = response?.data[3].value;
          selectedThemeData = `#${selectedThemeData}`;
        } else {
          selectedThemeData = response?.data[3].default_value;
          selectedThemeData = `#${selectedThemeData}`;
        }
        this.themeArray.btnGroup[0].dropdownContent.forEach((element: any) => {
          if (element.text == selectedThemeData) {
            document.body.className = element.theme + ' fuelux';
          }
        });
      },
      error: (error: any) => {

      }
    })
  }

  getAPIData() {

  }

  setMiniDashboard() {
    // let mealImportResult = localStorage.getItem("meal_import_result");
    // this.meal_import_result = JSON.parse(mealImportResult);
    console.log(this.meal_import_result, "this.meal_import_result");

    // let mealReport = localStorage.getItem("meal_imported");
    // this.meal_report = JSON.parse(mealReport);
    console.log(this.meal_report, "meal_report");

    this.miniDashboardData = [
      {
        type: 'text',
        label: 'Total Rows:',
        value: this.meal_report?.total_count
      },
      {
        type: 'text',
        label: 'Rows Imported:',
        value: this.meal_report?.records_added?.count
      },
      {
        type: 'text',
        label: 'Rows Updated:',
        value: this.meal_report?.records_updated?.count
      },
      {
        type: 'text',
        label: 'Rows Failed:',
        value: this.meal_report?.records_failed?.count
      },

    ];
    this.displayMiniDashboard = true;

    timer(100).subscribe((): void => {
      this._column = [
        TABLE_COLUMN_LIST.rowNumber,
        TABLE_COLUMN_LIST.date,
        TABLE_COLUMN_LIST.openemis_id,
        TABLE_COLUMN_LIST.meal_programme_code,
        TABLE_COLUMN_LIST.meal_received_code,
        TABLE_COLUMN_LIST.meal_benefit_name,
        TABLE_COLUMN_LIST.comment
      ];
      console.log(this._column, "column");

      let row = [];
      this.meal_report?.records_failed?.rows.forEach((element: any) => {
        let obj = {
          row_number: element?.row_number,
          date: !element?.errors['Date ( DD/MM/YYYY )'] ? element?.data['Date ( DD/MM/YYYY )'] : `${element?.data['Date ( DD/MM/YYYY )']}` == null ? `${element?.data['Date ( DD/MM/YYYY )']} (${element?.errors['Date ( DD/MM/YYYY )']})` : `(${element?.errors['Date ( DD/MM/YYYY )']})`,
          openemis_id: !element?.errors['OpenEMIS ID'] ? element?.data['OpenEMIS ID'] : `${element?.data['OpenEMIS ID']}` == null ? `${element?.data['OpenEMIS ID']} (${element?.errors['OpenEMIS ID']})` : `(${element?.errors['OpenEMIS ID']})`,
          meal_programme_code: !element?.errors['Meal Programme Code'] ? element?.data['Meal Programme Code'] : `${element?.data['Meal Programme Code']}` == null ? `${element?.data['Meal Programme Code']} (${element?.errors['Meal Programme Code']})` : `(${element?.errors['Meal Programme Code']})`,
          meal_received_code: !element?.errors['Meal Received Code'] ? element?.data['Meal Received Code'] : `${element?.data['Meal Received Code']}` == null ? `${element?.data['Meal Received Code']} (${element?.errors['Meal Received Code']})` : `(${element?.errors['Meal Received Code']})`,
          meal_benefit_name: !element?.errors['Meal Benefit Name'] ? element?.data['Meal Benefit Name'] : `${element?.data['Meal Benefit Name']}` == null ? `${element?.data['Meal Benefit Name']} (${element?.errors['Meal Benefit Name']})` : `(${element?.errors['Meal Benefit Name']})`,
          comment: !element?.errors['Comment'] ? element?.data['Comment'] : `${element?.data['Comment']}` == null ? `${element?.data['Comment']} (${element?.errors['Comment']})` : `(${element?.errors['Comment']})`
        }
        row.push(obj);
      });

      this.meal_report?.records_updated?.rows.forEach((element: any) => {
        let obj = {
          row_number: element?.row_number,
          date: element?.errors['Date ( DD/MM/YYYY )'] ? element?.errors['Date ( DD/MM/YYYY )'] : element?.data['Date ( DD/MM/YYYY )'],
          openemis_id: element?.errors['OpenEMIS ID'] ? element?.errors['OpenEMIS ID'] : element?.data['OpenEMIS ID'],
          meal_programme_code: element?.errors['Meal Programme Code'] ? element?.errors['Meal Programme Code'] : element?.data['Meal Programme Code'],
          meal_received_code: element?.errors['Meal Received Code'] ? element?.errors['Meal Received Code'] : element?.data['Meal Received Code'],
          meal_benefit_name: element?.errors['Meal Benefit Name'] ? element?.errors['Meal Benefit Name'] : element?.data['Meal Benefit Name'],
          comment: element?.errors['Comment'] ? element?.errors['Comment'] : element?.data['Comment']
        }
        row.push(obj);
      });

      this.meal_report?.records_added?.rows.forEach((element: any) => {
        let obj = {
          row_number: element?.row_number,
          date: element?.errors['Date ( DD/MM/YYYY )'] ? element?.errors['Date ( DD/MM/YYYY )'] : element?.data['Date ( DD/MM/YYYY )'],
          openemis_id: element?.errors['OpenEMIS ID'] ? element?.errors['OpenEMIS ID'] : element?.data['OpenEMIS ID'],
          meal_programme_code: element?.errors['Meal Programme Code'] ? element?.errors['Meal Programme Code'] : element?.data['Meal Programme Code'],
          meal_received_code: element?.errors['Meal Received Code'] ? element?.errors['Meal Received Code'] : element?.data['Meal Received Code'],
          meal_benefit_name: element?.errors['Meal Benefit Name'] ? element?.errors['Meal Benefit Name'] : element?.data['Meal Benefit Name'],
          comment: element?.errors['Comment'] ? element?.errors['Comment'] : element?.data['Comment']
        }
        row.push(obj);
      });
      console.log(row, "row data");
      this._row = row;
    });

  }

  generateExcel() {
    let dataValidationHeadings = {
      "OpenEMIS ID": "OpenEMIS ID",
      "Meal Programme Code": "Meal Programmes",
      "Meal Received Code": "Meal Received",
      "Meal Benefit Name": "Meal Benefit"
    };

    let dataColumnHeadings = [
      "Date ( DD/MM/YYYY )",
      "OpenEMIS ID",
      "Meal Programme Code",
      "Meal Received Code",
      "Meal Benefit Name",
      "Comment",
      "Errors"
    ];
    let referenceNames = Object.keys(this.meal_import_result.data.References)
    let temp2 = {}; let temp3 = {}; let temp4 = {}; let temp5 = {}; let temp6 = {}; let temp7 = {};
    let assetsArr = this.meal_import_result.data.References["Meal Benefit"].data;
    let statusArr = this.meal_import_result.data.References["Meal Programmes"].data;
    let levelArr = this.meal_import_result.data.References["Meal Received"].data;
    let parentArr = this.meal_import_result.data.References["OpenEMIS ID"].data;
    let responseArr = [
      {
        key: 1,
        value: 'Multiple Choice'
      },
      {
        key: 2,
        value: 'Open Ended'
      },
      {
        key: 3,
        value: 'Short Answer'
      }
    ];
    for (let x in assetsArr) {
      temp2[assetsArr[x].Name] = assetsArr[x].Id;
    }
    for (let x in statusArr) {
      temp3[statusArr[x].Name] = statusArr[x]["Code"];
    }
    for (let x in levelArr) {
      temp4[levelArr[x].Name] = levelArr[x]["Code"];
    }
    for (let x in parentArr) {
      temp5[parentArr[x].Name] = parentArr[x]["OpenEMIS ID"];
    }
    for (let x in responseArr) {
      temp6[responseArr[x].value] = responseArr[x].key;
    }
    if (!Object.keys(temp2).length) {
      temp2 = { '': '' }
    }
    if (!Object.keys(temp3).length) {
      temp3 = { '': '' }
    }
    if (!Object.keys(temp4).length) {
      temp4 = { '': '' }
    }
    if (!Object.keys(temp5).length) {
      temp5 = { '': '' }
    }
    this.meal_import_result.data.References["Meal Benefit"].data = temp2;
    this.meal_import_result.data.References["Meal Received"].data = temp4;
    this.meal_import_result.data.References["Meal Programmes"].data = temp3;
    this.meal_import_result.data.References["OpenEMIS ID"].data = temp5;
    let referenceData = this.meal_import_result.data.References;
    this.meal_report?.records_failed?.rows.forEach((element: any, index: any) => {
      let arrData = [];
      if (element.data['Date ( DD/MM/YYYY )']) {
        arrData.push(element.data['Date ( DD/MM/YYYY )'])
      } else {
        arrData.push("")
      }
      if (element.data['OpenEMIS ID']) {
        arrData.push(element.data['OpenEMIS ID']);
      } else {
        arrData.push("")
      }
      if (element.data['Meal Programme Code']) {
        arrData.push(element.data['Meal Programme Code']);
      } else {
        arrData.push("")
      }
      console.log(arrData, "arrData");

    });
    let dataSheetData: any = [
      ["123", "2382817311"], ["2382817311"]]
    this.excelSvc.init('OpenEMIS_Core_Import_Institution_Meal_Students_Template', 'Import Student Meals Data', dataColumnHeadings, referenceNames, referenceData, dataValidationHeadings, dataSheetData);
  }

  backToData() {
    let tokenData = localStorage.getItem('encoded_url');
    if (tokenData) {
      this.router.navigateByUrl(`Institution/Institutions/${tokenData}/ImportStudentMeals/add`);
    }
  }

}
