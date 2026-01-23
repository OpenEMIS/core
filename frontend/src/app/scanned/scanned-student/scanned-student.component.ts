import { Component, OnInit } from '@angular/core';
import { ITableApi, ITableColumn, ITableConfig, KdAlertEvent } from 'openemis-styleguide-lib';
import { timer } from 'rxjs';
import { TABLE_COLUMN_LIST } from './scanned-student.config';
import { ApiService } from 'src/app/api.service';
import { DEFAULT_TEMPLATE_THEME } from 'src/app/shared/config.default-val';
import { Router } from '@angular/router';
import { SharedService } from 'src/app/shared/shared.service';

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
          this.exportData();
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
    pageheaderText: "",
    searchBtn: false,
    searchEvent: ['change', 'keyup']
  }

  public dropdownWithoutLabel: Array<any> = [{
    'key': 'date_from',
    'placeholder': 'Date From',
    'visible': true,
    'required': false,
    'controlType': 'date',
    'type': 'date',
    'value': ''
  }, {
    'key': 'date_to',
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
          icon: "far fa-eye",
          name: "View",
          custom: true,
          callback: (_rowNode, _tableApi): void => {
            this.setOpenEmisId(_rowNode.data);
          },
        },
      ],
    },
    context: {}
  }
  public _tableApi: ITableApi = {};
  public _row: Array<any> = [];
  public showTable: boolean = false;
  date_from: any;
  date_to: any;
  institution_name: string;

  constructor(private Rest: ApiService,
    public router: Router,
    public _shared: SharedService,
    private _kdAlertEvent: KdAlertEvent) { }

  ngOnInit(): void {
    this.counter = 0;
    this.institution_name = localStorage.getItem("institutionName");
    this.pageheader.pageheaderText = `${this.institution_name} - Scanned List`;
    timer(1000).subscribe((): void => {
      this._column = [
        TABLE_COLUMN_LIST.dateTime,
        TABLE_COLUMN_LIST.openEmisId,
        TABLE_COLUMN_LIST.personName,
        TABLE_COLUMN_LIST.access,
        TABLE_COLUMN_LIST.location
      ];
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
        console.log(decodedPassword, "decodedPassword");
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
    this.Rest.getWithToken('scanned').subscribe({
      next: (response: any) => {
        if (response?.data) {
          let responseData = [];
          response?.data.forEach((element: any) => {
            let obj = {
              'dateTime': element.datetime,
              'openemis_no': element.openemis_no,
              'name': element?.security_user?.full_name,
              'access': element?.access,
              'location': element?.location,
              'id': element?.id
            }
            responseData.push(obj);
          });
          this._row = responseData;
          this.showTable = true;
        }
      },
      error: (error) => {
        if (error) {
          if (error.message == "Token has expired") {
            localStorage.removeItem("loginToken");
            this.loginData();
          }
        }
      }
    })
  }

  _changeEvent(event: any) {
    if (event.key == "date_from") {
      this.date_from = event.value;
    } else {
      this.date_to = event.value;
    }

    if (Object.keys(this.date_from?.obj).length != 0 && Object.keys(this.date_to?.obj).length != 0) {
      if (this.date_from?.text != 'undefined-undefined-undefined' || this.date_to?.text != 'undefined-undefined-undefined') {
        this.Rest.getWithToken(`scanned?date_from=${this.date_from?.text}&date_to=${this.date_to?.text}`).subscribe({
          next: (response: any) => {
            if (response?.data) {
              let responseData = [];
              response?.data.forEach((element: any) => {
                let obj = {
                  'dateTime': element.datetime,
                  'openemis_no': element.openemis_no,
                  'name': element?.security_user?.full_name,
                  'access': element?.access,
                  'location': element?.location,
                  'id': element?.id
                }
                responseData.push(obj);
              });
              this._row = responseData;
              this.showTable = true;
            }
          },
          error: (error) => {
            if (error) {
              if (error.message == "Token has expired") {
                localStorage.removeItem("loginToken");
                this.loginData();
              }
            }
          }
        })
      }
    } else {
      this.Rest.getWithToken(`scanned`).subscribe({
        next: (response: any) => {
          if (response?.data) {
            let responseData = [];
            response?.data.forEach((element: any) => {
              let obj = {
                'dateTime': element.datetime,
                'openemis_no': element.openemis_no,
                'name': element?.security_user?.full_name,
                'access': element?.access,
                'location': element?.location,
                'id': element?.id
              }
              responseData.push(obj);
            });
            this._row = responseData;
            this.showTable = true;
          }
        },
        error: (error) => {
          if (error) {
            if (error.message == "Token has expired") {
              localStorage.removeItem("loginToken");
              this.loginData();
            }
          }
        }
      })
    }
  }

  setOpenEmisId(_rowNode: any) {
    this._shared.setOpenEmisScannedList(_rowNode);
    this.router.navigate(["Institution/Institutions/Scanned/list"]);
  }

  exportData() {
    this.Rest.getItemExport(`scanned/data/export`).subscribe({
      next: (response: any) => {
        let url = window.URL.createObjectURL(response);
        let a = document.createElement('a');
        document.body.appendChild(a);
        a.setAttribute('style', 'display: none');
        a.href = url;
        a.download = response.filename || 'Scanned file';
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();
      },
      error: (error: any) => {
        let toasterConfig: any = {
          title: 'Something went wrong, Please try again later',
          showCloseButton: true,
          tapToDismiss: true,
        };

        this._kdAlertEvent.warn(toasterConfig);
      }
    })
  }

}
