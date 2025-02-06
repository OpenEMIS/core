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
      tooltip: 'Failed Download',
      callback: (): void => {
        this.generateExcel();
      }
    },
    {
      custom: true,
      icon: 'fa fa-download',
      tooltip: 'Success Download',
      callback: (): void => {
        this.generateSuccessExcel();
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
    //   this.meal_report = {
    //     "total_count": 2,
    //     "records_added": {
    //         "count": 0,
    //         "rows": []
    //     },
    //     "records_updated": {
    //         "count": 0,
    //         "rows": []
    //     },
    //     "records_failed": {
    //         "count": 2,
    //         "rows": [
    //             {
    //                 "row_number": 2,
    //                 "data": {
    //                     "Date ( DD/MM/YYYY )": 36809,
    //                     " OpenEMIS ID": 2382817343,
    //                     "Meal Programme Code": "Meal Programme",
    //                     "Meal Received Code": "NotReceived",
    //                     "Meal Benefit Name": 2,
    //                     "Comment": "Test"
    //                 },
    //                 "errors": {
    //                     "Date ( DD/MM/YYYY )": "Invalid date format."
    //                 }
    //             },
    //             {
    //                 "row_number": 3,
    //                 "data": {
    //                     "Date ( DD/MM/YYYY )": 36840,
    //                     " OpenEMIS ID": null,
    //                     "Meal Programme Code": "Meal Programme",
    //                     "Meal Received Code": null,
    //                     "Meal Benefit Name": 1,
    //                     "Comment": "Test"
    //                 },
    //                 "errors": {
    //                     "Date ( DD/MM/YYYY )": "Invalid date format.",
    //                     " OpenEMIS ID": "OpenEMIS Id is required.",
    //                     "Meal Received Code": "Meal received code is required."
    //                 }
    //             }
    //         ]
    //     }
    // }

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
    let dataValidationHeadings = {};

    let dataColumnHeadings = [
      "Date ( DD/MM/YYYY )",
      "OpenEMIS ID",
      "Meal Programme Code",
      "Meal Received Code",
      "Meal Benefit Name",
      "Comment",
      "Errors"
    ];
    let data = {
      header: [],
      References: {}
    }
    if (this.meal_report.records_failed.count > 0) {
      for (var key in this.meal_report.records_failed.rows[0].data) {
        data.header.push(key);
        data.References[key] = {
          data: [],
          header: []
        }
      }
      data.References["Errors"] = {
        data: [],
        header: []
      }
      console.log(this.meal_report.records_failed.rows,"Topa");
      
      this.meal_report.records_failed.rows.forEach((element: any, index: any) => {
        console.log(element, "element 123");
        if (element.data['Date ( DD/MM/YYYY )']) {
          let obj = {
            Name: element.data['Date ( DD/MM/YYYY )']
          }
          data.References['Date ( DD/MM/YYYY )'].data.push(obj);
        } else {
          let obj = {
            Name: ''
          }
          data.References['Date ( DD/MM/YYYY )'].data.push(obj);
        }

        if (element.data['Meal Programme Code']) {
          let obj = {
            Name: element.data['Meal Programme Code']
          }
          data.References['Meal Programme Code'].data.push(obj);
        } else {
          let obj = {
            Name: ''
          }
          data.References['Meal Programme Code'].data.push(obj);
        }

        if (element.data['Meal Received Code']) {
          let obj = {
            Name: element.data['Meal Received Code']
          }
          data.References['Meal Received Code'].data.push(obj);
        } else {
          let obj = {
            Name: ''
          }
          data.References['Meal Received Code'].data.push(obj);
        }

        if (element.data['Meal Benefit Name']) {
          let obj = {
            Name: element.data['Meal Benefit Name']
          }
          data.References['Meal Benefit Name'].data.push(obj);
        } else {
          let obj = {
            Name: ''
          }
          data.References['Meal Benefit Name'].data.push(obj);
        }

        if (element.data['Comment']) {
          let obj = {
            Name: element.data['Comment']
          }
          data.References['Comment'].data.push(obj);
        } else {
          let obj = {
            Name: ''
          }
          data.References['Comment'].data.push(obj);
        }

        //For error code
        let dateObj: any = {};
        let programObj: any = {};
        let receivedObj: any = {};
        if (element.errors['Date ( DD/MM/YYYY )']) {
          dateObj = {
            error: element.errors['Date ( DD/MM/YYYY )']
          }
          // data.References['Errors'].data.push(obj);
        }
        if (element.errors['Meal Programme Code']) {
          programObj = {
            error: element.errors['Meal Programme Code']
          }
          // data.References['Errors'].data.push(obj);
        }
        if (element.errors['Meal Received Code']) {
          receivedObj = {
            error: element.errors['Meal Received Code']
          }
          // data.References['Errors'].data.push(obj);
        }
        if(dateObj?.error || programObj?.error || receivedObj?.error){
          let dateData = dateObj?.error != undefined ? dateObj?.error : '';
          let programData = programObj?.error != undefined ? programObj?.error : '';
          let receivedData = receivedObj?.error != undefined ? receivedObj?.error : '';

          let str = `${dateData} ${programData} ${receivedData}`;
          console.log(str,"str");
          data.References['Errors'].data.push(str)
        }
      });
    }
    console.log(data, "data 123");

    let referenceNames = Object.keys(data.References);
    let temp2 = {}; let temp3 = {}; let temp4 = {}; let temp5 = {}; let temp6 = {}; let temp7 = {};
    let assetsArr = data.References["Comment"].data;
    let statusArr = data.References["Date ( DD/MM/YYYY )"].data;
    let levelArr = data.References["Meal Benefit Name"].data;
    let parentArr = data.References["Meal Programme Code"].data;
    let receivedArr = data.References["Meal Received Code"].data;
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
      temp2[assetsArr[x].Name] = assetsArr[x].Name;
    }
    for (let x in statusArr) {
      temp3[statusArr[x].Name] = statusArr[x].Name;
    }
    for (let x in levelArr) {
      temp4[levelArr[x].Name] = levelArr[x].Name;
    }
    for (let x in parentArr) {
      temp5[parentArr[x].Name] = parentArr[x].Name;
    }
    for (let x in receivedArr) {
      temp5[receivedArr[x].Name] = receivedArr[x].Name;
    }
    // for (let x in responseArr) {
    //   temp6[responseArr[x].value] = responseArr[x].key;
    // }
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
    // data.References["Comment"].data = temp2;
    // data.References["Date ( DD/MM/YYYY )"].data = temp4;
    // data.References["Meal Benefit Name"].data = temp3;
    // data.References["Meal Programme Code"].data = temp5;
    // data.References["Meal Received Code"].data = temp6;
    let referenceData = data.References;
    // this.meal_report?.records_failed?.rows.forEach((element: any, index: any) => {
    //   let arrData = [];
    //   if (element.data['Date ( DD/MM/YYYY )']) {
    //     arrData.push(element.data['Date ( DD/MM/YYYY )'])
    //   } else {
    //     arrData.push("")
    //   }
    //   if (element.data['OpenEMIS ID']) {
    //     arrData.push(element.data['OpenEMIS ID']);
    //   } else {
    //     arrData.push("")
    //   }
    //   if (element.data['Meal Programme Code']) {
    //     arrData.push(element.data['Meal Programme Code']);
    //   } else {
    //     arrData.push("")
    //   }
    //   console.log(arrData, "arrData");

    // });
    console.log(referenceData,"referenceData Topa");
    
    this.excelSvc.init('OpenEMIS_Core_Import_Institution_Meal_Students_Template', 'Import Student Meals Data', dataColumnHeadings, referenceNames, data.References, dataValidationHeadings);
  }

  generateSuccessExcel(){
    let dataValidationHeadings = {};
    let updatedMealRecord: any = [];
    updatedMealRecord.push(...this.meal_report.records_added.rows, ...this.meal_report.records_updated.rows);
    console.log(updatedMealRecord,"updatedMealRecord");
    
    // updatedMealRecord.push(this.meal_report.records_updated.rows);
    let dataColumnHeadings = [
      "Date ( DD/MM/YYYY )",
      "OpenEMIS ID",
      "Meal Programme Code",
      "Meal Received Code",
      "Meal Benefit Name",
      "Comment",
    ];
    let data = {
      header: [],
      References: {}
    }

    if (updatedMealRecord.length > 0) {
      for (var key in updatedMealRecord[0].data) {
        data.header.push(key);
        data.References[key] = {
          data: [],
          header: []
        }
      }
      // data.References["Errors"] = {
      //   data: [],
      //   header: []
      // }
      console.log(updatedMealRecord,"Topa");
      
      updatedMealRecord.forEach((element: any, index: any) => {
        console.log(element, "element 123");
        if (element.data['Date ( DD/MM/YYYY )']) {
          let obj = {
            Name: element.data['Date ( DD/MM/YYYY )']
          }
          data.References['Date ( DD/MM/YYYY )'].data.push(obj);
        } else {
          let obj = {
            Name: ''
          }
          data.References['Date ( DD/MM/YYYY )'].data.push(obj);
        }

        if (element.data['Meal Programme Code']) {
          let obj = {
            Name: element.data['Meal Programme Code']
          }
          data.References['Meal Programme Code'].data.push(obj);
        } else {
          let obj = {
            Name: ''
          }
          data.References['Meal Programme Code'].data.push(obj);
        }

        if (element.data['Meal Received Code']) {
          let obj = {
            Name: element.data['Meal Received Code']
          }
          data.References['Meal Received Code'].data.push(obj);
        } else {
          let obj = {
            Name: ''
          }
          data.References['Meal Received Code'].data.push(obj);
        }

        if (element.data['Meal Benefit Name']) {
          let obj = {
            Name: element.data['Meal Benefit Name']
          }
          data.References['Meal Benefit Name'].data.push(obj);
        } else {
          let obj = {
            Name: ''
          }
          data.References['Meal Benefit Name'].data.push(obj);
        }

        if (element.data['Comment']) {
          let obj = {
            Name: element.data['Comment']
          }
          data.References['Comment'].data.push(obj);
        } else {
          let obj = {
            Name: ''
          }
          data.References['Comment'].data.push(obj);
        }

        //For error code
        let dateObj: any = {};
        let programObj: any = {};
        let receivedObj: any = {};
        if (element.errors['Date ( DD/MM/YYYY )']) {
          dateObj = {
            error: element.errors['Date ( DD/MM/YYYY )']
          }
          // data.References['Errors'].data.push(obj);
        }
        if (element.errors['Meal Programme Code']) {
          programObj = {
            error: element.errors['Meal Programme Code']
          }
          // data.References['Errors'].data.push(obj);
        }
        if (element.errors['Meal Received Code']) {
          receivedObj = {
            error: element.errors['Meal Received Code']
          }
          // data.References['Errors'].data.push(obj);
        }
        if(dateObj?.error || programObj?.error || receivedObj?.error){
          let dateData = dateObj?.error != undefined ? dateObj?.error : '';
          let programData = programObj?.error != undefined ? programObj?.error : '';
          let receivedData = receivedObj?.error != undefined ? receivedObj?.error : '';

          let str = `${dateData} ${programData} ${receivedData}`;
          console.log(str,"str");
          data.References['Errors'].data.push(str)
        }
      });
    }

    console.log(data, "data 123");

    let referenceNames = Object.keys(data.References);
    let temp2 = {}; let temp3 = {}; let temp4 = {}; let temp5 = {}; let temp6 = {}; let temp7 = {};
    let assetsArr = data.References["Comment"].data;
    let statusArr = data.References["Date ( DD/MM/YYYY )"].data;
    let levelArr = data.References["Meal Benefit Name"].data;
    let parentArr = data.References["Meal Programme Code"].data;
    let receivedArr = data.References["Meal Received Code"].data;
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
      temp2[assetsArr[x].Name] = assetsArr[x].Name;
    }
    for (let x in statusArr) {
      temp3[statusArr[x].Name] = statusArr[x].Name;
    }
    for (let x in levelArr) {
      temp4[levelArr[x].Name] = levelArr[x].Name;
    }
    for (let x in parentArr) {
      temp5[parentArr[x].Name] = parentArr[x].Name;
    }
    for (let x in receivedArr) {
      temp5[receivedArr[x].Name] = receivedArr[x].Name;
    }
    // for (let x in responseArr) {
    //   temp6[responseArr[x].value] = responseArr[x].key;
    // }
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
    // data.References["Comment"].data = temp2;
    // data.References["Date ( DD/MM/YYYY )"].data = temp4;
    // data.References["Meal Benefit Name"].data = temp3;
    // data.References["Meal Programme Code"].data = temp5;
    // data.References["Meal Received Code"].data = temp6;
    let referenceData = data.References;
    // this.meal_report?.records_failed?.rows.forEach((element: any, index: any) => {
    //   let arrData = [];
    //   if (element.data['Date ( DD/MM/YYYY )']) {
    //     arrData.push(element.data['Date ( DD/MM/YYYY )'])
    //   } else {
    //     arrData.push("")
    //   }
    //   if (element.data['OpenEMIS ID']) {
    //     arrData.push(element.data['OpenEMIS ID']);
    //   } else {
    //     arrData.push("")
    //   }
    //   if (element.data['Meal Programme Code']) {
    //     arrData.push(element.data['Meal Programme Code']);
    //   } else {
    //     arrData.push("")
    //   }
    //   console.log(arrData, "arrData");

    // });
    console.log(referenceData,"referenceData Topa");
    
    this.excelSvc.init('OpenEMIS_Core_Import_Institution_Meal_Students_Template', 'Import Student Meals Data', dataColumnHeadings, referenceNames, data.References, dataValidationHeadings);
  }

  backToData() {
    let tokenData = localStorage.getItem('encoded_url');
    if (tokenData) {
      this.router.navigateByUrl(`Institution/Institutions/${tokenData}/ImportStudentMeals/add`);
    }
  }

}
