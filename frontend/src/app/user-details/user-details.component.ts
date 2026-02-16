import { Component, Input, OnInit } from '@angular/core';
import { IDynamicFormApi } from 'openemis-styleguide-lib';
import { ApiService } from '../api.service';

@Component({
  selector: 'app-user-details',
  templateUrl: './user-details.component.html',
  styleUrls: ['./user-details.component.css']
})
export class UserDetailsComponent implements OnInit {
  public maxDate = new Date();
  @Input() _questionBase: Array<any>

  public _formButtons: Array<any> = [
    {
      type: "submit",
      name: "Save",
      icon: "kd-check",
      class: "btn-text",
    },
    {
      type: "cancel",
      name: "Cancel",
      icon: "kd-close",
      class: "btn-outline",
    },
  ];

  public api: IDynamicFormApi = {};
  errorKey: any;

  constructor(
    private Rest: ApiService
  ) { }

  ngOnInit(): void {
    this.Rest.disableNextButton();
  }

  detectValue(event: any) {
    console.log(event, "detectValue event");
    let newQuestion = [...this._questionBase];

    if (event.key == 'identity_type') {
      switch (event.value) {
        case "null":
          newQuestion[4].label = 'Identity Number';
          break;

        case "school":
          newQuestion[4].label = 'School';
          break;

        case "passport":
          newQuestion[4].label = 'Passport';
          break;

        case "birth_certificate":
          newQuestion[4].label = 'Birth Certificate';
          break;

        case "drivers_license":
          newQuestion[4].label = "Driver's License";
          break;

        case "social_security":
          newQuestion[4].label = 'Social Security';
          break;

        case "other":
          newQuestion[4].label = 'Other';
          break;
      }
    }

    this._questionBase[4] = newQuestion[4];
    console.log(this._questionBase, "this._questionBase");

  }

  submitVal(event: any) {
    let hasError = this.checkRequired(event);
    if (hasError == undefined) {
      this.Rest.enableNextButton();
      localStorage.setItem("userDetails", JSON.stringify(event));
      console.log(event, "event");
    }

  }

  checkRequired(event: any) {
    for (let i = 0; i < this._questionBase.length; i++) {
      if (this._questionBase[i].required) {
        for (const [key, value] of Object.entries(event)) {
          let newValue: any = value;
          if (this._questionBase[i].key == key) {
            console.log(value, "value", this._questionBase[i].key);

            if (value == '' || value == 'null' || value == null || value == undefined || newValue?.text == "undefined-undefined-undefined") {
              this.errorKey = this._questionBase[i].key;
              setTimeout(() => {
                this.api.setProperty(this._questionBase[i]["key"], "errors", [
                  "This field is required"
                ]);
              }, 100);
              return true;
            }
          }
        }
      }

    }
  }

  detectGrid(event: any){
    console.log(event,"event event");
    
  }

  reset() {

  }

}
