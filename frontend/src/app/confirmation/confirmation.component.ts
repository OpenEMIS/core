import { Component, Input, OnInit } from '@angular/core';
import { IDynamicFormApi, ITreeConfig } from 'openemis-styleguide-lib';
import { ApiService } from '../api.service';

@Component({
  selector: 'app-confirmation',
  templateUrl: './confirmation.component.html',
  styleUrls: ['./confirmation.component.css']
})
export class ConfirmationComponent implements OnInit {
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


  constructor(
    private Rest: ApiService
  ) { }

  ngOnInit(): void {
    this.Rest.disableNextButton();
  }

  detectValue(event: any) {

  }

  submitVal(event: any) {
    console.log(event,"event");
    localStorage.setItem("userDetails", JSON.stringify(event));
    this.Rest.enableNextButton();
  }

  reset() {

  }

}
