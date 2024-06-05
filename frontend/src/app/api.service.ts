import { HttpClient,
  HttpErrorResponse, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';
import { throwError } from "rxjs";
import { map, catchError, tap } from "rxjs/operators";
import { environment } from 'src/environments/environment';

@Injectable({
  providedIn: 'root'
})
export class ApiService {

  constructor(
    private http: HttpClient
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

  setSessionStorage(key:any, value: any) {
    sessionStorage.setItem(key, value);
  }
  
  getSessionStorage(key: any) {
    return sessionStorage.getItem(key);
  }

  login(){
    let obj = {
      "password": "demo",
      "username": "admin",
      "api_key": "apikeytest"
  }
    return this.http.post('https://dmo-tst.openemis.org/core/api/v4/login', obj).pipe(catchError(this.handleError))
  }

  postWithToken(url: any, data: any){
    let token = localStorage.getItem("loginToken");
    const headers = new HttpHeaders().set("Authorization", "Bearer " + token);
    return this.http.post(`https://dmo-tst.openemis.org/core/api/v4/${url}`, data, {
      headers: headers
    }).pipe(catchError(this.handleError));
  }

  getWithoutToken(url: any) {
    return this.http
      .get(url)
      .pipe(catchError(this.handleError));
  }

  getWithToken(url: any){
    let token = localStorage.getItem("loginToken");
    const headers = new HttpHeaders().set("Authorization", "Bearer " + token);
    return this.http.get(`https://dmo-tst.openemis.org/core/api/v4/${url}`, {
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
}
