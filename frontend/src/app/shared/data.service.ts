import { Injectable } from "@angular/core";
import { Router } from "@angular/router";
import {
  HttpClient,
  HttpHeaders,
  HttpErrorResponse,
} from "@angular/common/http";
import { BehaviorSubject, Observable, Subject, throwError } from "rxjs";
import { catchError } from "rxjs/operators";
import { KdAlertEvent } from "openemis-styleguide-lib";

import { environment } from "../../environments/environment";
import urls from "./config.urls";

@Injectable({ providedIn: "root" })
export class DataService {
 
  // base Url to hold url for api call set in enviroment.ts file
  baseUrl = environment.baseUrl;
  loginPayload = {
    // username:environment.user_name,
    // password:environment.password,
    // api_key:environment.api_key
  }

  // Implementing behaviour subject for enabling and enabling and disabling next button 
  private nextBtn:BehaviorSubject<string> = new BehaviorSubject<string>('enable');
  nextBtnEvent:Observable<string> = this.nextBtn.asObservable();

  private prevBtn:BehaviorSubject<string> = new BehaviorSubject<string>('enable');
  prevBtnEvent:Observable<string> = this.prevBtn.asObservable();

  private landingSub:BehaviorSubject<string> = new BehaviorSubject<string>('');
  landingComponentSub:Observable<string> = this.landingSub.asObservable();

  private enableLoading:BehaviorSubject<boolean> = new BehaviorSubject<boolean>(false);
  loadingSub:Observable<boolean> = this.enableLoading.asObservable();

  private completeButton:BehaviorSubject<boolean> = new BehaviorSubject<boolean>(false);
  completeButtonSub:Observable<boolean> = this.completeButton.asObservable();

  private resetRoute:BehaviorSubject<string> = new BehaviorSubject<string>('Welcome');
  resettoWelcome:Observable<string> = this.resetRoute.asObservable();

  private previousRoute:Subject<boolean> = new Subject<boolean>();
  goBack:Observable<boolean>= this.previousRoute.asObservable();

  constructor(
    private httpClient: HttpClient,
    public router: Router,
    public alert: KdAlertEvent,
  ) { }

  enableNextButton(){
    this.nextBtn.next('enable');
  }

  disableNextButton(){
    this.nextBtn.next('disable');
  }

  enablePreviousButton(){
    this.prevBtn.next('enable');
  }

  disablePrevoiusButton(){
    this.prevBtn.next('disable');
  }

  SendMessage(data:string){
    this.landingSub.next(data);
  }

  loadingEnable(data:boolean){
    this.enableLoading.next(data);
  }

  completeButtonClicked(data:boolean){
    this.completeButton.next(data);
  }

  resetForm(data){
    this.resetRoute.next(data);
  }

  goToPreviousRoute(data:boolean){
    this.previousRoute.next(data);
  }

  private handleError = (error: HttpErrorResponse | any) => {
    switch (error.status) {
      case 400:
        console.log(400);
        break;
      case 401:
        console.log(401);
        let temp = localStorage.getItem("token");
        if (temp) {
          localStorage.clear();
          this.Login();
          setTimeout(() =>{
            this.router.navigate(["/auth"])
          }, 100)
          // this.router.navigate(["/auth"]);
        }
        break;
      case 500:
        console.log(500);
        break;
      case 503:
        console.log(503);
        break;
      // case 429:
      //
      //   console.log(0);
      //   break;
      case 429:
        let toasterConfig: any = {
          type: "error",
          title: "Error",
          showCloseButton: true,
          tapToDismiss: false,
          timeout: 8000,
          body: error.statusText,
        };
        this.alert.error(toasterConfig);
    }

    console.log(error);
    return throwError(error);
  };

  // /*** Add token to request ***/
  setHeader(): any {
    if (localStorage.getItem("token")) {
      let token = JSON.parse(window.atob(localStorage.getItem("token")));
      let headers = new HttpHeaders({ Authorization: `Bearer ${token}` });
      return { headers: headers };
    } else {
      let headers = new HttpHeaders({});
      return { headers: headers };
    }
  }

  login() {
    return this.httpClient
      .post(`${this.baseUrl}/${urls.login}`, this.loginPayload, this.setHeader())
      .pipe(catchError(this.handleError));
  }

  generateOtp(payload){
    return this.httpClient
    .post(
      `${this.baseUrl}/${urls.otpGenerate}`, payload, this.setHeader())
      .pipe(catchError(this.handleError));
  }

  verifyOtp(payload){
    return this.httpClient
    .post(
      `${this.baseUrl}/${urls.verifyOtp}`,payload,this.setHeader()
    )
    .pipe(catchError(this.handleError));
  }
  
  getAcademicPeriod(){
    return this.httpClient
    .get(
      `${this.baseUrl}/${urls.academicPeriods}/${urls.list}`, this.setHeader())
      .pipe(catchError(this.handleError)); 
  }

  getEducationGrade(academic_period_id){
    return this.httpClient
    .get(
      `${this.baseUrl}/${urls.educationGrades}/${urls.list}?academic_period_id=${academic_period_id}`, this.setHeader())
      .pipe(catchError(this.handleError)); 
  }

  getNationality(){
    return this.httpClient
    .get(
      `${this.baseUrl}/${urls.nationalities}/${urls.list}`, this.setHeader())
      .pipe(catchError(this.handleError));
  }

  // getInstitutionList(){
  //   return this.httpClient
  //   .get(
  //     `${this.baseUrl}/${urls.institution}/${urls.list}`, this.setHeader())
  //     .pipe(catchError(this.handleError));
  // }

  searchOpenEmisID(id){
    return this.httpClient
    .get(
      `${this.baseUrl}/${urls.users}/${urls.openEmisId}/${id}`, this.setHeader())
      .pipe(catchError(this.handleError))
  }



  searchIdentityNumber(idenType, identityNumber){
    return this.httpClient
    .get(
      `${this.baseUrl}/${urls.users}/${urls.identityTypes}/${idenType}/${identityNumber}`,this.setHeader()
    )
    .pipe(catchError(this.handleError))
  }


  getOpenEmisCandidateDetail(data){
    return this.httpClient
    .get(
      `${this.baseUrl}/${urls.users}/${data}`,this.setHeader()
    )
    .pipe(catchError(this.handleError));
  }

  getAdministrativeArea(){
    return this.httpClient
    .get(`${this.baseUrl}/${urls.institution}/${urls.area}/${urls.list}`,this.setHeader())
    .pipe(catchError(this.handleError));
  }

    getAddressArea(){
        return this.httpClient
        .get(`${this.baseUrl}/area-administrative/display-address-area-level`, this.setHeader())
        .pipe(catchError(this.handleError));
      }
     
      getBirthPlace(){
        return this.httpClient
        .get(`${this.baseUrl}/area-administrative/display-birthplace-area-level`, this.setHeader())
        .pipe(catchError(this.handleError));
      }


  registerStudent(institution_id,payload){
    return this.httpClient
    .post(`${this.baseUrl}/${urls.institution}/${institution_id}/${urls.studentAdmission}`, payload,this.setHeader())
    .pipe(catchError(this.handleError));
  }

  getCustomField(){
    return this.httpClient
    .get(`${this.baseUrl}/${urls.studentCustomFields}`,this.setHeader())
    .pipe(catchError(this.handleError));
  }

  getIdentityType(){
    return this.httpClient
    .get(`${this.baseUrl}/${urls.identityTypes}/${urls.list}`, this.setHeader())
    .pipe(catchError(this.handleError));
  }

  Login(){
    this.login().subscribe(
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

  readMessage():Observable<any>{
    
    return this.httpClient.get('assets/configuration/configuration.json', {responseType: 'json'})
    .pipe(catchError(this.handleError));
  }

  getInstitution(id:any){
    return this.httpClient
    .get(
      `${this.baseUrl}/${urls.institution}/grades/${id}/list`, this.setHeader())
      .pipe(catchError(this.handleError)); 
  }

  loadLanguage():Observable<any>{
    return this.httpClient.get('assets/configuration/configuration.json', {responseType: 'json'})
    .pipe(catchError(this.handleError));
  }

  // loadLanguage():Promise<any>{
  //   return this.httpClient.get('assets/custom-message/login.json', {responseType: 'json'}).toPromise();
  // }
  public candidateId :any
   // call on view button api
   pluginsView(userId){
    console.log(userId,"userId");
    
    // console.log(`pluginsView , ${id}`);
    let pluginsUrl = `https://dmo-tst.openemis.org/marketplace/api/v1/plugins/${userId}`
    return this.httpClient.get<any>(pluginsUrl, {}).pipe(catchError(this.handleError));    
  }

   // add api data 
   public pluginsAdd(payload){
    let pluginsUrl = `${this.baseUrl}/plugins`
    return this.httpClient.post<any>(pluginsUrl, payload).pipe(catchError(this.handleError))
  }
  
}
