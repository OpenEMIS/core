import { Injectable } from "@angular/core";
import {
  CanActivate,
  ActivatedRouteSnapshot,
  RouterStateSnapshot,
  Router,
} from "@angular/router";
import { Observable } from "rxjs";
import { KdAlertEvent } from "openemis-styleguide-lib";
import { AuthService } from "./auth/auth.service";

@Injectable({ providedIn: "root" })
export class PermissionsGuard implements CanActivate {
  // private userDetails:any;
  //

  constructor(
    public router: Router,
    private kdAlertEvent: KdAlertEvent,
    private authSvc: AuthService
  ) {}

  canActivate(
    route: ActivatedRouteSnapshot,
    state: RouterStateSnapshot
  ): Observable<boolean> | Promise<boolean> | boolean {
    let permissions = this.authSvc.getPermissionsList();
    // console.log("🚀 ~ permissions", permissions);
    let routePermissionKey = route.data.permissionKey
      ? route.data.permissionKey
      : false;
    let routeParentKey = route.data.parentKey ? route.data.parentKey : false;
    if (permissions && Object.keys(permissions).length == 0) {
      return true;
    }

    if (
      permissions &&
      routePermissionKey &&
      routeParentKey &&
      permissions[routeParentKey + routePermissionKey] &&
      permissions[routeParentKey + routePermissionKey].view
    ) {
      return true;
    } else {
      this.toasterCall();
      return false;
    }
  }

  toasterCall() {
    let toasterConfig: any = {
      title: "Unauthorized Access",
      body: "",
      showCloseButton: true,
      tapToDismiss: false,
    };
    this.kdAlertEvent.error(toasterConfig);
  }
}
