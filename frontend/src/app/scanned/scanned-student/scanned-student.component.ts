import { Component, OnInit } from '@angular/core';
import { ITableApi, ITableColumn, ITableConfig } from 'openemis-styleguide-lib';
import { timer } from 'rxjs';
import { TABLE_COLUMN_LIST } from './scanned-student.config';
import { ApiService } from 'src/app/api.service';
import { DEFAULT_TEMPLATE_THEME } from 'src/app/shared/config.default-val';

@Component({
  selector: 'app-scanned-student',
  templateUrl: './scanned-student.component.html',
  styleUrls: ['./scanned-student.component.css']
})
export class ScannedStudentComponent implements OnInit {
  public displayLoading: boolean = false;
  public counter: number;
  themeArray = DEFAULT_TEMPLATE_THEME;
  public pageheader: any = {
    leftBtn: [
        {
        type: "export",
        callback: (): void => {
          // this.exportData();
        }
      },
      // {
      //   type: "import",
      //   callback: (): void => {
      //     this.importTableFields();
      //   }
      // },
      // {
      //   type: "edit",
      //   callback: (): void => {
      //     this.onEditClick();
      //   }
      // }
    ],
    moreAction: [],
    moreBtn: false,
    pageheaderText: "Avory Primary School - Scanned",
    searchBtn: false,
    searchEvent: ['change', 'keyup']
  }

  public dropdownWithoutLabel: Array<any> = [{
    'key': 'date',
    'placeholder': 'Date From',
    'visible': true,
    'required': false,
    'controlType': 'date',
    'type': 'date',
    'value': ''
  }, {
    'key': 'date',
    'placeholder': 'Date To',
    'visible': true,
    'required': false,
    'controlType': 'date',
    'type': 'date',
    'value': ''
  }];
  public _column: Array<ITableColumn>;
  public _config: ITableConfig = {
    id: "normalTable",
    rowIdKey: "id",
    gridHeight: "auto",
    rowContentHeight: 50,
    loadType: "normal",
    externalFilter: false,
    paginationConfig: {
      pagesize: 10,
      total: 50000,
    },
    action: {
      enabled: true,
      list: [
        {
          type: "view",
          path: "",
        },
      ],
    },
    context: {}
  }
  public _tableApi: ITableApi = {};
  public _row: Array<any> = [];

  constructor(private Rest: ApiService) { }

  ngOnInit(): void {
    this.counter = 0;
    timer(1000).subscribe((): void => {
      this._column = [
        TABLE_COLUMN_LIST.dateTime,
        TABLE_COLUMN_LIST.openEmisId,
        TABLE_COLUMN_LIST.personName,
        TABLE_COLUMN_LIST.access,
        TABLE_COLUMN_LIST.location
      ];

      this._row = [
        { 'dateTime': 'October 28, 2024 - 08:00', 'openemis_no': 1522413076, 'name': 'Aaron Butler', 'access': 'In', 'location': 'School' },
        { 'dateTime': 'October 28, 2024 - 08:00', 'openemis_no': 1522413076, 'name': 'Aaron Butler', 'access': 'In', 'location': 'School' },
        { 'dateTime': 'October 28, 2024 - 08:00', 'openemis_no': 1522413076, 'name': 'Aaron Butler', 'access': 'In', 'location': 'School' },
        { 'dateTime': 'October 28, 2024 - 08:00', 'openemis_no': 1522413076, 'name': 'Aaron Butler', 'access': 'In', 'location': 'School' },
        { 'dateTime': 'October 28, 2024 - 08:00', 'openemis_no': 1522413076, 'name': 'Aaron Butler', 'access': 'In', 'location': 'School' },
        { 'dateTime': 'October 28, 2024 - 08:00', 'openemis_no': 1522413076, 'name': 'Aaron Butler', 'access': 'In', 'location': 'School' },

      ]
    });
    this.loginData();
  }

  
  loginData() {
    // this.Rest.setSession();
    let token = localStorage.getItem("loginToken");
    if (!token) {
      let userName = sessionStorage.getItem('nbn');
      let password = sessionStorage.getItem('pbn');
      const chars = password.split('.');
      password = chars[0];
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
        // decodedPassword = decodedPassword.replace(/^"(.*)"$/, '$1');
        decodedPassword = decodedPassword.replace(/[\[\]"]/g, '');
        console.log(decodedPassword,"decodedPassword");
        if (userName && decodedPassword) {
          this.loginApi(userName, decodedPassword);
        } else {
          this.removeSession();
        }
      }
    } else {
      this.setTheme();
      this.getAPIData();
    }
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

  loginApi(userName: string, password: string) {
    this.Rest.loginApi(userName, password).subscribe({
      next: (response: any) => {
        if (response) {
          localStorage.setItem("loginToken", response?.data?.token);
          this.setTheme();
          this.getAPIData();
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

  getAPIData() {

  }

  _changeEvent(event: any) {

  }

}
