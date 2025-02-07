import { Injectable, EventEmitter } from "@angular/core";
import { Router } from "@angular/router";
// import { DataService } from './data.service';
import { environment } from "../../environments/environment";
import { BehaviorSubject, Observable, Subject } from "rxjs";
import { DataService } from "./data.service";
import { KdAlertEvent } from "openemis-styleguide-lib";
import { HttpHeaders } from "@angular/common/http";

@Injectable({
  providedIn: "root",
})
export class SharedService {
  /**
   *  data of no use
   *  need to remove after some time
   *  start from here
   */
  public stepObserver: Subject<boolean> = new Subject();
  public formValueObserver: Subject<any> = new Subject();
  public registerSummryObserver: Subject<any> = new Subject();
  public buttonStatusObserver: BehaviorSubject<boolean> = new BehaviorSubject(
    false
  );
  public stepValueObserver: Subject<any> = new Subject();
  // ======== End Here ========

  public candidateEditObserver: Subject<any> = new Subject();
  public dropdownValues: Object;
  public candidateId: string;
  public studentId: any;
  public gridTypeId: string;
  public gridEditId: string;
  public importDataList: any;
  public importMarkDatakList: any;
  public importCommponentList: any;
  public gridType: any;
  public importList: any;
  openEmisList: any;

  constructor(
    public router: Router,
    public dataService: DataService,
    public alert: KdAlertEvent
  ) {}

  /**
   *  data of no use
   *  need to remove after some time
   *  start from here
   */
  regFormValueOf(id) {
    this.stepObserver.next(id);
  }

  setFormValue(stepId, data) {
    let formValue: any;
    if (stepId || data) {
      formValue = {
        stepId: stepId,
        formValue: data,
      };
    }
    this.formValueObserver.next(formValue);
  }

  setRegisterSummry(formData, apiData) {
    let temp = {
      formData: formData,
      apiData: apiData,
    };
    this.registerSummryObserver.next(temp);
  }

  buttonStatus(data) {
    this.buttonStatusObserver.next(data);
  }

  setToaster(data) {
    switch (data.type) {
      case "success": {
        this.alert.success(data);
        break;
      }
      case "error": {
        this.alert.error(data);
        break;
      }
      case "warning": {
        this.alert.warn(data);
        break;
      }
      case "info": {
        this.alert.info(data);
        break;
      }
    }
  }

  setCandidateEditDetail(data: any) {
    this.candidateEditObserver.next(data);
  }

  setCandidateId(id: string) {
    this.candidateId = id;
  }

  getCandidateId() {
    return this.candidateId;
  }

  // student_id
  setStudentId(id: string) {
    this.studentId = id;
  }

  getStudentId() {
    return this.studentId;
  }

  setCandidateImportDetail(event: any) {
    this.importDataList = event;
  }

  getCandidateImportDetail() {
    return this.importDataList;
  }

  setCollectionImportDetail(event: any) {
    this.importList = event;
  }

  getCollectionImportDetail() {
    return this.importList;
  }

  setGridType(event: any) {
    this.gridTypeId = event;
  }

  getGridType() {
    return this.gridTypeId;
  }
  setGridEditId(event: any) {
    this.gridTypeId = event;
  }
  getGridEditId(event: any) {
    this.gridTypeId = event;
  }

  setMarkDataList(event: any) {
    this.importMarkDatakList = event;
  }
  getMarkDataList() {
    return this.importMarkDatakList;
  }

  setComponentDataList(event: any) {
    this.importCommponentList = event;
  }
  getComponentDataList() {
    return this.importCommponentList;
  }

  setOpenEmisScannedList(event: any) {
    this.openEmisList = event;
  }

  getOpenEmisScannedList() {
    return this.openEmisList;
  }

  setGradingType(data) {
    this.gridType = data;
  }

  getGradingType() {
    return this.gridType;
  }

  /**
   * @description Function to get default dropdown values used in app.
   */
  public getDropdownValues(freshCall?) {
    return new Observable((observer) => {
      if (this.dropdownValues && !freshCall) {
        observer.next(this.dropdownValues);
      } else {
        // this.dataService.getDefaultDropdownValues().subscribe(
        //   (data) => {
        //     this.dropdownValues = data;
        //     observer.next(this.dropdownValues);
        //   },
        //   (err) => {
        //     observer.error();
        //   }
        // );
      }
    });
  }

  public humanize(str) {
    return str
      .replace(/^[\s_]+|[\s_]+$/g, "")
      .replace(/[_\s]+/g, " ")
      .replace(/^[a-z]/, function (m) {
        return m.toUpperCase();
      });
  }

  public isEmpty(_obj) {
    // null and undefined are "empty"
    if (_obj === "" || _obj === null || typeof _obj === "undefined") {
      return true;
    }

    // Assume if it has a length property with a non-zero value
    // that that property is correct.
    if (_obj.length) {
      if (_obj.length > 0) return false;
      if (_obj.length === 0) return true;
    }

    if (typeof _obj == "object") {
      if (Object.keys(_obj).length === 0) return true;
    }

    // Otherwise, does it have any properties of its own?
    // Note that this doesn't handle
    // toString and valueOf enumeration bugs in IE < 9
    // for (let key in _obj) {
    //     if (hasOwnProperty.call(_obj, key)) return false;
    // }

    return false;
  }

  sharedServiceLogin(){
    this.dataService.login().subscribe(
      (res: any) => {
        if (res.data) {
          localStorage.setItem('token', window.btoa(JSON.stringify(res.data['token'])));
        }
      },
      (err: any) => {
        console.log(err);
      }
    ); 
  }
}
