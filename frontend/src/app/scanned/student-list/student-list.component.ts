import { Component, OnInit } from '@angular/core';
import { IDynamicFormApi } from 'openemis-styleguide-lib';
import { ApiService } from 'src/app/api.service';
import { DEFAULT_TEMPLATE_THEME } from 'src/app/shared/config.default-val';
import { SharedService } from 'src/app/shared/shared.service';

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
        path: `/Institution/Institutions/Scanned/index/${this.setEncodedId()}`,
      },
    ],
    moreAction: [],
    moreBtn: false,
    pageheaderText: "",
    searchBtn: false,
    searchEvent: ['change', 'keyup']
  }
  public _viewQuestion: any = [
    {
      'value': '',
      'key': 'openemis_no',
      'visible': true,
      'label': 'OpenEMIS ID',
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
    {
      'value': '',
      'key': 'created',
      'visible': true,
      'label': 'Created On',
      'type': 'string'
    },
    {
      'value': '',
      'key': 'created_user_id',
      'visible': true,
      'label': 'Created By',
      'type': 'string'
    },
    {
      'value': '',
      'key': 'modified',
      'visible': true,
      'label': 'Modified On',
      'type': 'string'
    },
    {
      'value': '',
      'key': 'modified_user_id',
      'visible': true,
      'label': 'Modified By',
      'type': 'string'
    },
  ]
  scannedId: any;
  institution_name: any;
  constructor(
    private Rest: ApiService,
    public _shared: SharedService,
  ) { }

  ngOnInit(): void {
    this.counter = 0;
    this.institution_name = localStorage.getItem("institutionName");
    this.pageheader.pageheaderText = `${this.institution_name} - Scanned List`;
    this.loginData();
  }

  setEncodedId() {
    let token = localStorage.getItem('encoded_url');
    if (token) {
      return token;
    } else {
      setTimeout(() => {
        this.setEncodedId();
      }, 1000);
    }
  }

  loginData() {
    // // this.Rest.setSession(); //POCOR-9594: CakePHP template injects real credentials via sessionStorage
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
    this.scannedId = this._shared.getOpenEmisScannedList();
    console.log(this.scannedId,"scannedId");
    if(this.scannedId){
      this.Rest.getWithToken(`scanned/user/${this.scannedId?.id}`).subscribe({
        next: (response: any) => {
          if (response) {
            let responseData = response?.data;
            console.log(responseData, "responseData");
            let obj = {
              access: responseData?.access,
              datetime: responseData?.datetime,
              id: responseData?.id,
              latitude: responseData?.latitude,
              longitude: responseData?.longitude,
              location: responseData?.location,
              name: responseData?.security_user?.full_name,
              openemis_no: responseData?.openemis_no,
              created: responseData?.created,
              created_user_id: responseData?.created_user_id,
              modified: responseData?.modified,
              modified_user_id: responseData?.modified_user_id
            }
            for (let i = 0; i < this._viewQuestion.length; i++) {
              this.api.setProperty(this._viewQuestion[i].key, 'value', obj[this._viewQuestion[i].key] ? obj[this._viewQuestion[i].key] : 'NA')
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

}
