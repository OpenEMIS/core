import { Injectable } from '@angular/core';
import { CanActivate, ActivatedRouteSnapshot, RouterStateSnapshot, Router } from '@angular/router';
import { Observable } from 'rxjs';
import { KdAlertEvent } from 'openemis-styleguide-lib';
import { AuthService } from './auth/auth.service';

@Injectable({ providedIn: 'root' })
export class AuthGuard implements CanActivate {
  // private userDetails:any;

  constructor(public router: Router, private kdAlertEvent: KdAlertEvent, private authSvc: AuthService) {}

  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<boolean> | Promise<boolean> | boolean {
    const isLoggedIn = localStorage.getItem('opt');
    if (isLoggedIn) {
      return true;
    }
    
    this.router.navigate(['/auth']);
    return false;
  }
  //   return new Promise((res)=> {
  //   if(route.data.page == 'auth'){
  //     console.log('inside auth');
  //     if(!this.isLoggedIn()){
  //       return res(true);
  //     }else{
  //       return res(true);
  //     }
  //   }else if(route.data.page == 'main'){
  //     console.log('inside main');
  //     if(!this.isLoggedIn()){
  //       this.router.navigate(['/auth']);
  //       return res(false);
  //     }else{
  //       this.router.navigate(['/main']);
  //       return res(true);
  //     }
  //   }
  // })

    // return new Promise((res)=>{
    //   if (route.data.page === 'auth') {
    //     if (!this.isLoggedIn()) {
    //       res(true);
    //     } else {
    //       this.toasterCall();
    //       this.router.navigate(['/main']);
    //       res(false);
    //     }
    //   } 
    //   // else {

    //   //   if (this.isLoggedIn()) {
    //   //     this.checkTokenValidity().then((suc) =>{
    //   //       res(true);
    //   //     },(fail)=>{
    //   //       this.toasterCall();
    //   //       this.router.navigate(['/auth']);
    //   //       res(false);
    //   //     })
    //   //   } else {
    //   //     this.toasterCall();
    //   //     this.router.navigate(['/auth']);
    //   //     res(false);
    //   //   }
    //   // }
    // });
  // }

  // isLoggedIn() {
  //   let accessToken: any;
  //   if (localStorage.getItem('token')) {
  //     accessToken = JSON.parse(window.atob(localStorage.getItem('token')));
  //     // this.userDetails = JSON.parse(localStorage.getItem('userInfo'));
  //   }
  //   return accessToken ? true : false;
  // }

  isLoggedIn(){
    console.log("inside login");
    let opt:any;
    if(localStorage.getItem('otp')){
      console.log("checking for login");
      opt = JSON.parse(localStorage.getItem('opt'))
      return true;
    }
    return false;
  }

  // checkTokenValidity(){

  //   return new Promise((resolve, reject)=>{
  //     this.authSvc.fetchPermissionsList().subscribe((res)=>{
  //       this.authSvc.setPermissionsList(res);
  //       resolve(true);
  //     }, (err)=>{
  //       localStorage.clear();
  //       reject(false);
  //     })
  //   })
  // }

  toasterCall(){
    let toasterConfig: any = {
      title: 'Unauthorised Access',
      body: '',
      showCloseButton: true,
      tapToDismiss: false
    };
    this.kdAlertEvent.error(toasterConfig);
  }
}
