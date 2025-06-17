import { Component, OnInit } from '@angular/core';
import { ActivatedRoute, Router } from '@angular/router';
import { KdPageBase, KdPageBaseEvent } from 'openemis-styleguide-lib';
import { PAGE_DATA } from './config';
import { ApiService } from '../api.service';
import { DEFAULT_TEMPLATE_THEME } from '../shared/config.default-val';

@Component({
  selector: 'app-classes',
  templateUrl: './classes.component.html',
  styleUrls: ['./classes.component.css']
})
export class ClassesComponent extends KdPageBase implements OnInit {

  counter: number = 0;

  public pageheader = {
    leftBtn: [
      {
        type: "back",
        callback: (): void => {
          // this.backToData();
        }
      },
      {
        type: "list",
        callback: (): void => {
          // this.editTableFields();
        }
      },
    ],
    moreAction: [],
    moreBtn: false,
    pageheaderText: "Avory Primary School - Classes",
    searchBtn: false,
    searchEvent: ['change', 'keyup']
  }

  public pageData = PAGE_DATA

  public actionButtons: Array<any> = [{
    type: 'submit',
    name: 'Submit',
    icon: 'kd-check',
    class: 'btn-text',
    disabled: false
  }, {
    type: 'reset',
    name: 'Reset',
    class: 'btn-outline',
    icon: 'kd-cross',
    disabled: false
  }];
  themeArray = DEFAULT_TEMPLATE_THEME;

  constructor(
    private _router: Router,
    private _activatedRoute: ActivatedRoute,
    public pageEvent: KdPageBaseEvent,
    private Rest: ApiService
  ) {
    super({
      router: _router,
      activatedRoute: _activatedRoute,
      pageEvent: pageEvent
    })
  }

  ngOnInit(): void {
    this.counter = 0;
    this.loginData();
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
      this.removeSession();
      this.setTheme();
      this.getAPIData();
    }
  }

  loginApi(userName: string, password: string) {
    this.Rest.loginApi(userName, password).subscribe({
      next: (response: any) => {
        if (response) {
          localStorage.setItem("loginToken", response?.data?.token);
          this.setTheme();
          this.getAPIData();
          this.removeSession();
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

  setTheme() {
    this.Rest.getWithToken('themes').subscribe({
      next: (response: any) => {
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

  removeSession() {
    delete sessionStorage.username;
    delete sessionStorage.password;
  }

  outputResult(event: any) {

  }

  detect(event: any) {
    console.log(event)
  }

}
