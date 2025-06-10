// This file can be replaced during build by using the `fileReplacements` array.
// `ng build --prod` replaces `environment.ts` with `environment.prod.ts`.
// The list of file replacements can be found in `angular.json`.

export const environment = {
  production: false,
  //baseUrl: 'http://127.0.0.1:8000/exams/api' /* Local Laravel V6*/
  // baseUrl: 'http://127.0.0.1:8000' /* Local Laravel V8*/
  //baseUrl: "https://dmo-tst2.openemis.org/exams/api" /* DMO-TST */,
  baseUrl: 'https://dmo-tst.openemis.org/core/api/v4/'
  // baseUrl: 'https://demo.openemis.org/core/api/v4/'

  // baseUrl: 'http://openemis.n2.iworklab.com/api' /* Staging */
  // baseUrl: 'https://testingopenemis-exam.n2.iworklab.com/api' /* Testing */
  // baseUrl: 'http://openemis-exam.n2.iworklab.com/api' /* Main */
};

/*
 * For easier debugging in development mode, you can import the following file
 * to ignore zone related error stack frames such as `zone.run`, `zoneDelegate.invokeTask`.
 *
 * This import should be commented out in production mode because it will have a negative impact
 * on performance if an error is thrown.
 */
// import 'zone.js/dist/zone-error';  // Included with Angular CLI.
