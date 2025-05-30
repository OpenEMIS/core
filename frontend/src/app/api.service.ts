import {
  HttpClient,
  HttpErrorResponse, HttpHeaders
} from '@angular/common/http';
import { Injectable } from '@angular/core';
import { KdAlertEvent } from 'openemis-styleguide-lib';
import { BehaviorSubject, Observable, throwError } from "rxjs";
import { map, catchError, tap } from "rxjs/operators";
import { environment } from 'src/environments/environment';

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  private nextBtn: BehaviorSubject<string> = new BehaviorSubject<string>('enable');
  nextBtnEvent: Observable<string> = this.nextBtn.asObservable();

  constructor(
    private http: HttpClient,
    public alert: KdAlertEvent
  ) { }

  private handleError(error: HttpErrorResponse) {
    if (error.error instanceof ErrorEvent) {
      console.error("An error occurred:", error.error.message);
    } else {
      console.error(
        `Backend returned code ${error.status}, ` + `body was: ${error.error}`
      );
    }

    return throwError(error.error);
  }

  setSessionStorage(key: any, value: any) {
    sessionStorage.setItem(key, value);
  }

  getSessionStorage(key: any) {
    return sessionStorage.getItem(key);
  }

  login() {
    let obj = {
      "password": "demo",
      "username": "admin",
      "api_key": "apikeytest"
    }
    return this.http.post(`${environment.baseUrl}login`, obj).pipe(catchError(this.handleError))
  }

  setSession(){
    sessionStorage.setItem("nbn", 'admin');
    sessionStorage.setItem("pbn", 'WyJkZW1vIl0.MTBhZTAzM2FkNjc2YjRmZjAwZWMxYmFkMzM5YzE2OGNlMDIwNDJmMmU5Y2VlY2EzZWUyNTUyZmYyMDEyZGYxNA');

    // sessionStorage.setItem("username", 'teacher');
    // sessionStorage.setItem("password", 'cGFzc3dvcmQ=');

    // sessionStorage.setItem("username", 'principal');
    // sessionStorage.setItem("password", 'cGFzc3dvcmQ=');
  }

  loginApi(userName: string, password: string) {
    let obj = {
      "password": password,
      "username": userName,
      "api_key": "apikeytest"
    }
    return this.http.post(`${environment.baseUrl}login`, obj).pipe(catchError(this.handleError))
  }

  postWithToken(url: any, data: any) {
    let token = localStorage.getItem("loginToken");
    const headers = new HttpHeaders().set("Authorization", "Bearer " + token);
    return this.http.post(`${environment.baseUrl}${url}`, data, {
      headers: headers
    }).pipe(catchError(this.handleError));
  }

  putWithToken(url: any, data: any, v5?: boolean) {
    let baseUrl = environment.baseUrl;
    if(v5 && !baseUrl.includes('/v5/')){
      baseUrl = baseUrl.replace('/v4/', '/v5/');
    }
    let token = localStorage.getItem("loginToken");
    const headers = new HttpHeaders().set("Authorization", "Bearer " + token);
    return this.http.put(`${baseUrl}${url}`, data, {
      headers: headers
    }).pipe(catchError(this.handleError));
  }

  getWithoutToken(url: any) {
    return this.http
      .get(url)
      .pipe(catchError(this.handleError));
  }

  getWithToken(url: any) {
    let token = localStorage.getItem("loginToken");
    const headers = new HttpHeaders().set("Authorization", "Bearer " + token);
    return this.http.get(`${environment.baseUrl}${url}`, {
      headers: headers
    }).pipe(catchError(this.handleError));
  }

  deleteWithToken(url: any) {
    let token = localStorage.getItem("loginToken");
    const headers = new HttpHeaders().set("Authorization", "Bearer " + token);
    return this.http.delete(`${environment.baseUrl}${url}`, {
      headers: headers
    }).pipe(catchError(this.handleError));
  }

  getItemImportTemplate(url: any) {
    let token = localStorage.getItem("loginToken");
    const headers = new HttpHeaders().set("Authorization", "Bearer " + token);
    return this.http
      .get(`${environment.baseUrl}${url}`, {
        headers: headers
      })
      .pipe(catchError(this.handleError));
  }

  getItemExport(url: any) {
    let token = localStorage.getItem("loginToken");
    const headers = new HttpHeaders().set("Authorization", "Bearer " + token);
    return this.http
      .get(`${environment.baseUrl}${url}`, {
        headers: headers,
        responseType: 'blob'
      })
      .pipe(catchError(this.handleError));
  }

  postImportTemplete(url: any, data: any) {
    let token = localStorage.getItem("loginToken");
    const headers = new HttpHeaders().set("Authorization", "Bearer " + token);
    return this.http.post(`${environment.baseUrl}${url}`, data, {
      headers: headers
    }).pipe(catchError(this.handleError));
  }

  get(url: any, token: any) {
    const headers = new HttpHeaders().set("Authorization", "Bearer " + token);
    return this.http
      .get(`${environment.baseUrl}${url} `, {
        headers: headers
      })
      .pipe(catchError(this.handleError));
  }

  disableNextButton() {
    this.nextBtn.next('disable');
  }

  enableNextButton() {
    this.nextBtn.next('enable');
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
}
