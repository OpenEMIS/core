import {
  HttpClient,
  HttpErrorResponse, HttpHeaders
} from '@angular/common/http';
import { Injectable } from '@angular/core';
import { KdAlertEvent } from 'openemis-styleguide-lib';
import { BehaviorSubject, Observable, throwError } from "rxjs";
import { map, catchError, tap } from "rxjs/operators";
//POCOR-9594: environment URL removed — base URL derived dynamically from window.location.origin

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  private nextBtn: BehaviorSubject<string> = new BehaviorSubject<string>('enable');
  nextBtnEvent: Observable<string> = this.nextBtn.asObservable();

  //POCOR-9594: dynamic base URLs — always call the same origin the browser is on
  get apiV4BaseUrl(): string {
    const base = localStorage.getItem('baseCoreUrl') || window.location.href;
    return new URL('api/v4/', base).href;
  }
  get apiV5BaseUrl(): string {
    const base = localStorage.getItem('baseCoreUrl') || window.location.href;
    return new URL('api/v5/', base).href;
  }

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

    //POCOR-9594: normalise 401 errors — server returns {error:"Token Expired"|"Token Invalid"}
    // but component handlers check error.message == "Token has expired". Unify here so all
    // components get a consistent message without each needing its own string mapping.
    const body = (error.error && typeof error.error === 'object') ? error.error : { error: error.error };
    if (error.status === 401) {
      return throwError({ ...body, message: 'Token has expired' });
    }
    return throwError(body);
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
    return this.http.post(`${this.apiV4BaseUrl}login`, obj).pipe(catchError(this.handleError))
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
    return this.http.post(`${this.apiV4BaseUrl}login`, obj).pipe(catchError(this.handleError))
  }

  postWithToken(url: any, data: any) {
    let token = localStorage.getItem("loginToken");
    const headers = new HttpHeaders().set("Authorization", "Bearer " + token);
    return this.http.post(`${this.apiV4BaseUrl}${url}`, data, {
      headers: headers
    }).pipe(catchError(this.handleError));
  }

  putWithToken(url: any, data: any, v5?: boolean) {
    let baseUrl = this.apiV4BaseUrl;
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

  getWithToken(url: any, v5?: boolean) {
    let baseUrl = this.apiV4BaseUrl;
    if(v5 && !baseUrl.includes('/v5/')){
      baseUrl = baseUrl.replace('/v4/', '/v5/');
    }
    let token = localStorage.getItem("loginToken");
    const headers = new HttpHeaders().set("Authorization", "Bearer " + token);
    return this.http.get(`${baseUrl}${url}`, {
      headers: headers
    }).pipe(catchError(this.handleError));
  }

  deleteWithToken(url: any) {
    let token = localStorage.getItem("loginToken");
    const headers = new HttpHeaders().set("Authorization", "Bearer " + token);
    return this.http.delete(`${this.apiV4BaseUrl}${url}`, {
      headers: headers
    }).pipe(catchError(this.handleError));
  }

  getItemImportTemplate(url: any) {
    let token = localStorage.getItem("loginToken");
    const headers = new HttpHeaders().set("Authorization", "Bearer " + token);
    return this.http
      .get(`${this.apiV4BaseUrl}${url}`, {
        headers: headers
      })
      .pipe(catchError(this.handleError));
  }

  getItemExport(url: any) {
    let token = localStorage.getItem("loginToken");
    const headers = new HttpHeaders().set("Authorization", "Bearer " + token);
    return this.http
      .get(`${this.apiV4BaseUrl}${url}`, {
        headers: headers,
        responseType: 'blob'
      })
      .pipe(catchError(this.handleError));
  }

  postImportTemplete(url: any, data: any) {
    let token = localStorage.getItem("loginToken");
    const headers = new HttpHeaders().set("Authorization", "Bearer " + token);
    return this.http.post(`${this.apiV4BaseUrl}${url}`, data, {
      headers: headers
    }).pipe(catchError(this.handleError));
  }

  get(url: any, token: any) {
    const headers = new HttpHeaders().set("Authorization", "Bearer " + token);
    return this.http
      .get(`${this.apiV4BaseUrl}${url} `, {
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
