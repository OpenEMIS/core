import { Injectable } from "@angular/core";
import {
  HttpClient,
  HttpHeaders,
  HttpErrorResponse,
} from "@angular/common/http";
import { Router } from "@angular/router";
import { throwError, BehaviorSubject } from "rxjs";
import { catchError } from "rxjs/operators";

import { environment } from "../../../environments/environment";
import urls from "../config.urls";

@Injectable({ providedIn: "root" })
export class AuthService {
  private accessList: any = null;
  // public accessListChanged = new BehaviorSubject<any>({ ...this.accessList });

  constructor(private http: HttpClient, private router: Router) {}

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
          this.router.navigate(["/auth"]);
        }
        break;
      case 500:
        console.log(500);
        break;
      case 503:
        console.log(503);
        break;
    }
    console.log(error);
    return throwError(error);
  };

  private setHeader(): any {
    if (localStorage.getItem("token")) {
      let token = JSON.parse(window.atob(localStorage.getItem("token")));
      let headers = new HttpHeaders({ Authorization: `Bearer ${token}` });
      return { headers: headers };
    } else {
      let headers = new HttpHeaders({});
      return { headers };
    }
  }

  setPermissionsList(permissions) {
    this.simplifyPermissionsData(permissions);
    // this.accessList = permissions;
  }

  public simplifyPermissionsData(permissions) {
    let permissionsObj = {};
    // let c = 0;
    // let check = {};
    // for (let x in this.userPermissions.data) {
    //   for(let key in this.userPermissions.data[x]){
    //     c++;
    //     check[key] ? check[key].push(x) : check[key] = [x];
    //     permissionsObj[key] = this.userPermissions.data[x][key];
    //   }
    // }
    for (let x in permissions.data) {
      for (let key in permissions.data[x]) {
        let something = {};
        for (let value in permissions.data[x][key]) {
          something[value] = +permissions.data[x][key][value];
        }
        permissionsObj[x + key] = something;
      }
    }
    this.accessList = permissionsObj;
    // console.log("🚀 ~  this.accessList", this.accessList);
  }

  getPermissionsList() {
    return this.accessList;
  }

  // fetchPermissionsList() {
  //   return this.http.get(
  //     `${environment.baseUrl}/permissionlist`,
  //     this.setHeader()
  //   );
  //   // .pipe(catchError(this.handleError))
  //   // .subscribe(
  //   //   (res) => {
  //   //     // console.log('res', res);
  //   //   },
  //   //   (err) => {
  //   //     console.log('err', err);
  //   //   }
  //   // );
  // }

  public toolbarPermissions(activatedRoute, toolbarButtons) {
    let permissions = this.getPermissionsList();
    if (Object.keys(permissions).length == 0) return toolbarButtons;
    let pmKey = activatedRoute.snapshot.data["permissionKey"];
    let prKey = activatedRoute.snapshot.data["parentKey"];
    for (let i = toolbarButtons.length; i > 0; i--) {
      if (toolbarButtons[i - 1].type == undefined) {
        continue;
      }
      if (permissions[prKey + pmKey][toolbarButtons[i - 1].type] == undefined) {
        if (
          toolbarButtons[i - 1].type == "import" &&
          !permissions[prKey + pmKey]["add"]
        ) {
          toolbarButtons.splice(i - 1, 1);
        } else if (
          toolbarButtons[i - 1].type == "generate" &&
          !permissions[prKey + pmKey]["execute"]
        ) {
          toolbarButtons.splice(i - 1, 1);
        } else {
          continue;
        }
      } else {
        if (permissions[prKey + pmKey][toolbarButtons[i - 1].type]) {
          continue;
        } else {
          toolbarButtons.splice(i - 1, 1);
        }
      }
    }
    return toolbarButtons;
  }
}

/* http://openemis.n2.iworklab.com/api/permissionlist */
