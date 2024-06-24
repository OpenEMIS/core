import { NgModule, CUSTOM_ELEMENTS_SCHEMA } from "@angular/core";
import { KdComponentsModule } from "openemis-styleguide-lib";

@NgModule({
  declarations: [],
  imports: [KdComponentsModule],
  providers: [],
  exports: [KdComponentsModule],
  schemas: [CUSTOM_ELEMENTS_SCHEMA]
})
export class SharedModule {}
