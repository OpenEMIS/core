import { Component, OnInit } from '@angular/core';
import { IDynamicFormApi } from 'openemis-styleguide-lib';
import { ApiService } from 'src/app/api.service';
import { DEFAULT_TEMPLATE_THEME } from 'src/app/shared/config.default-val';

@Component({
  selector: 'app-student-list',
  templateUrl: './student-list.component.html',
  styleUrls: ['./student-list.component.css']
})
export class StudentListComponent implements OnInit {
  public displayLoading: boolean = false;
  public counter: number;
  themeArray = DEFAULT_TEMPLATE_THEME;
  public api: IDynamicFormApi = {};
  public pageheader: any = {
    leftBtn: [
      {
        type: "back",
        path: "/Institution/Institutions/Scanned/student",
      },
    ],
    moreAction: [],
    moreBtn: false,
    pageheaderText: "Avory Primary School - Scanned List",
    searchBtn: false,
    searchEvent: ['change', 'keyup']
  }
  public _viewQuestion: any = [
    {
      'value': '',
      'key': 'openemis_no',
      'visible': true,
      'label': 'Openemis No.',
      'type': 'string'
    },
    {
      'value': '',
      'key': 'datetime',
      'visible': true,
      'label': 'Date Time',
      'type': 'date'
    },
    {
      'value': '',
      'key': 'name',
      'visible': true,
      'label': 'Name',
      'type': 'string'
    },
    {
      'value': '',
      'key': 'access',
      'visible': true,
      'label': 'Access',
      'type': 'string'
    },
    {
      'value': '',
      'key': 'location',
      'visible': true,
      'label': 'Location',
      'type': 'string'
    },
    {
      'value': '',
      'key': 'latitude',
      'visible': true,
      'label': 'Latitude',
      'type': 'string'
    },
    {
      'value': '',
      'key': 'longitude',
      'visible': true,
      'label': 'Longitude',
      'type': 'string'
    },
  ]
  constructor(private Rest: ApiService) { }

  ngOnInit(): void {
    this.counter = 0;
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
    this.Rest.getWithToken('scanned/1522415305').subscribe({
      next: (response: any) => {
        if (response) {
          let responseData = response?.data[0];
          console.log(responseData, "responseData");
          for (let i = 0; i < this._viewQuestion.length; i++) {
            this.api.setProperty(this._viewQuestion[i].key, 'value', responseData[this._viewQuestion[i].key] ? responseData[this._viewQuestion[i].key] : 'NA')
          }
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

}
