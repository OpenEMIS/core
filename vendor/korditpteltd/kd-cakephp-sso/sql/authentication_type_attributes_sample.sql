-- authentication_type_attributes
-- Sample data for authentication through Google, Saml2 and OAuth2 with OpenID Connect

-- Sample for Google
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('2473f464-e032-4c29-b6a0-ef9e6c45115a','Google','hd','Hosted Domain','kordit.com',NULL,NULL,'2016-08-16 02:37:29',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('97fb12f3-2d68-4f99-ad38-321ead44903c','Google','redirect_uri','Redirect URI','http://localhost:8080/openemis-phpoe/Users/postLogin',NULL,NULL,'2016-08-16 02:37:29',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('ce1c7eee-f227-48dc-b929-0581cc8dccf9','Google','client_id','Client ID','503787316191-70068ljk1ies95s7g78drigkil0410vm.apps.googleusercontent.com',NULL,NULL,'2016-08-16 02:37:29',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('c452b365-7418-40ea-881f-ba744dbbc5e6','Google','client_secret','Client Secret','aCtvUXLvkHuG0cpk_u_3WGvL',NULL,NULL,'2016-08-16 02:37:29',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('3a0b3be8-ad70-11e6-bad3-525400b263eb','Google','allow_create_user','Allow User Creation','',NULL,NULL,'2016-11-18 17:15:41',2);

-- Sample for Saml2
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('11287c92-4fd9-4bca-b483-f4972df9eeda','Saml2','saml_username_mapping','Username Mapping','username',NULL,NULL,'2016-08-16 01:59:40',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('156f00c4-3b46-4b03-89bb-f4168641888b','Saml2','saml_last_name_mapping','Last Name Mapping','',NULL,NULL,'2016-08-16 01:59:40',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('1c1cc1d7-1076-4c7a-8fd0-9255a01e04cd','Saml2','sp_metadata','Service Provider - Metadata','&lt;?xml version=&quot;1.0&quot;?&gt;\n&lt;md:EntityDescriptor xmlns:md=&quot;urn:oasis:names:tc:SAML:2.0:metadata&quot;\n                     validUntil=&quot;2016-08-18T01:59:40Z&quot;\n                     cacheDuration=&quot;PT604800S&quot;\n                     entityID=&quot;http://localhost:8080/openemis-phpoe&quot;&gt;\n    &lt;md:SPSSODescriptor AuthnRequestsSigned=&quot;false&quot; WantAssertionsSigned=&quot;false&quot; protocolSupportEnumeration=&quot;urn:oasis:names:tc:SAML:2.0:protocol&quot;&gt;\n        &lt;md:SingleLogoutService Binding=&quot;urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect&quot;\n                                Location=&quot;http://localhost:8080/openemis-phpoe/Users/logout&quot; /&gt;\n        &lt;md:NameIDFormat&gt;&lt;/md:NameIDFormat&gt;\n        &lt;md:AssertionConsumerService Binding=&quot;urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST&quot;\n                                     Location=&quot;http://localhost:8080/openemis-phpoe/Users/postLogin&quot;\n                                     index=&quot;1&quot; /&gt;\n    &lt;/md:SPSSODescriptor&gt;\n&lt;/md:EntityDescriptor&gt;','2016-08-16 01:59:40',2,'2016-08-16 01:59:40',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('1f01bd68-2acf-4870-97b1-c683bcc765d1','Saml2','idp_slo','Identity Provider - Single Logout Service','https://app.onelogin.com/trust/saml2/http-redirect/slo/513327',NULL,NULL,'2016-08-16 01:59:40',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('2892bdd1-21ce-46f7-89ef-d7b6673e40f4','Saml2','idp_sso_binding','Identity Provider - Single Signon Service Binding','urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',NULL,NULL,'2016-08-16 01:59:40',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('451101ac-ca80-4f60-b7fc-68262c5e6196','Saml2','sp_name_id_format','Service Provider - Name ID Format','',NULL,NULL,'2016-08-16 01:59:40',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('4d3acc7a-99a3-4465-b1fb-c3245c30337f','Saml2','idp_certFingerprintAlgorithm','Identity Provider - Certificate Fingerprint Algorithm','SHA256',NULL,NULL,'2016-08-16 01:59:40',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('4f9c07e3-62c4-440c-9618-87e6fbd8fe82','Saml2','idp_x509cert','Identity Provider - X509 Certificate','MIIELDCCAxSgAwIBAgIUZFHHsPaL+Z7p7BKAa48gqrLjmPYwDQYJKoZIhvcNAQEF\r\nBQAwXzELMAkGA1UEBhMCVVMxGDAWBgNVBAoMD0tvcmQgSVQgUHRlIEx0ZDEVMBMG\r\nA1UECwwMT25lTG9naW4gSWRQMR8wHQYDVQQDDBZPbmVMb2dpbiBBY2NvdW50IDc3\r\nMDk3MB4XDTE2MDEyODA5MjEzMloXDTIxMDEyOTA5MjEzMlowXzELMAkGA1UEBhMC\r\nVVMxGDAWBgNVBAoMD0tvcmQgSVQgUHRlIEx0ZDEVMBMGA1UECwwMT25lTG9naW4g\r\nSWRQMR8wHQYDVQQDDBZPbmVMb2dpbiBBY2NvdW50IDc3MDk3MIIBIjANBgkqhkiG\r\n9w0BAQEFAAOCAQ8AMIIBCgKCAQEAyMi+YL4cNVzrEI93vN5ZDV/ruHJN5rNHIq0d\r\nHAe48QbP81quask9da3gWZtqVTKeVlXHnOBx0kwoJpE66+Xo/dMa2nrgaf1c0rqA\r\n1JtwvG6CiX8TsA/W/6oTucnK2NvG7ZJBN664YbfPcWEtsv9Zp68m23kHQO6DV1HJ\r\nZW6u53nxaDDo3uBrBJBZWDpwM273E2GpXrEQNHiJ7DrSdof3SI7nMPCYqFjEKpec\r\nIYUSPRUedOG1medxi4WS48vJHXRv38Vgw20mE9CH56EsROXmSwyZhh7x+BknA1NF\r\nBnt1/k6bsDoVYbm0Q+MnqJby9YCGXjbHpoF3+hhTSwB699ml8wIDAQABo4HfMIHc\r\nMAwGA1UdEwEB/wQCMAAwHQYDVR0OBBYEFK7EN5it2oKKGpMyXv70AI08ueixMIGc\r\nBgNVHSMEgZQwgZGAFK7EN5it2oKKGpMyXv70AI08ueixoWOkYTBfMQswCQYDVQQG\r\nEwJVUzEYMBYGA1UECgwPS29yZCBJVCBQdGUgTHRkMRUwEwYDVQQLDAxPbmVMb2dp\r\nbiBJZFAxHzAdBgNVBAMMFk9uZUxvZ2luIEFjY291bnQgNzcwOTeCFGRRx7D2i/me\r\n6ewSgGuPIKqy45j2MA4GA1UdDwEB/wQEAwIHgDANBgkqhkiG9w0BAQUFAAOCAQEA\r\nYAcnP4wsR7Ns28ZDKsP/I5byWeIWy5lFRcg4Jkk7MSBMoThNM6QaTg5m6Tb98LLT\r\nFFGU8RlWQ7GnYukT0pvCwjM+lfj4pn3ebR5MAo1hL/mnLYAo3WVVYivmZZssztgr\r\n16+whEFQjOEHcWL0IU+Qb1ONINFtfBWPbMrfzNGAImXaeU9Kn5GqGma3NGlbYCpQ\r\nVcH1yt5CH6AvtK6POAGe4tLCgDAvL4NyVxXegmH5eaCCBE8Ku/VRJr6QxxfrGZOt\r\nUAKibTQBd+KJUM2RMgMCYBs+fRdm/bH1cKVJKy3Oxo3HmleD2l2NZLk04nLKPl2u\r\nFTCQmT1aJppEydYQArg+Mg==',NULL,NULL,'2016-08-16 01:59:40',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('60e14386-ab58-4b1d-bbaf-93f7d5e0f15e','Saml2','sp_slo','Service Provider - Single Logout Service','http://localhost:8080/openemis-phpoe/Users/logout',NULL,NULL,'2016-08-16 01:59:40',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('788d345e-0e1a-4898-bd78-893b5e54a7a4','Saml2','idp_certFingerprint','Identity Provider - Certificate Fingerprint','B4:84:F3:3F:56:0F:F4:7F:50:DE:27:96:09:91:EA:32:E8:E7:D6:E8:70:03:4F:83:30:1C:D0:4E:56:6F:37:4D',NULL,NULL,'2016-08-16 01:59:40',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('83ec17c0-a3b0-4072-ab74-ac3a49f29475','Saml2','saml_gender_mapping','Gender Mapping','',NULL,NULL,'2016-08-16 01:59:40',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('c49f15a8-0f7f-4043-865f-3a44c74c7795','Saml2','saml_first_name_mapping','First Name Mapping','',NULL,NULL,'2016-08-16 01:59:40',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('9b64ff5c-00b2-4f88-bc83-4de6fb09e6ee','Saml2','sp_acs','Service Provider - Assertion Consumer Service','http://localhost:8080/openemis-phpoe/Users/postLogin',NULL,NULL,'2016-08-16 01:59:40',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('b5dab14f-ca66-4393-a0fc-22c2d9302a1a','Saml2','idp_slo_binding','Identity Provider - Single Logout Service Binding','urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',NULL,NULL,'2016-08-16 01:59:40',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('b75770b0-f012-4f96-bd97-e556b1feb31d','Saml2','sp_entity_id','Service Provider - Entity ID','http://localhost:8080/openemis-phpoe',NULL,NULL,'2016-08-16 01:59:40',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('c152c1dc-103c-4400-8b77-4ed1021c0311','Saml2','sp_privateKey','Service Provider - Private Key','',NULL,NULL,'2016-08-16 01:59:40',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('c33398c6-df43-44a0-9e1b-39c38bca31a8','Saml2','idp_entity_id','Identity Provider - Entity ID','https://app.onelogin.com/saml/metadata/513327',NULL,NULL,'2016-08-16 01:59:40',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('d86007ca-54f5-4db5-ad50-1362d5f65e21','Saml2','idp_sso','Identity Provider - Single Signon Service','https://app.onelogin.com/trust/saml2/http-post/sso/513327',NULL,NULL,'2016-08-16 01:59:40',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('e253b181-d2db-4763-ba40-0c312663587d','Saml2','saml_date_of_birth_mapping','Date of birth mapping','',NULL,NULL,'2016-08-16 01:59:40',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('d1559f0d-2a50-11e7-bd1c-525400b263eb','Saml2','saml_role_mapping','Role mapping','role',NULL,NULL,'2016-08-16 01:59:40',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('27dea876-ad70-11e6-bad3-525400b263eb','Saml2','allow_create_user','Allow User Creation','',NULL,NULL,'2016-11-18 17:15:41',2);

-- Sample for OAuth2 with OpenID Connect
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('04c3b7da-4dbc-4f82-9b61-90242dd9be61','OAuth2OpenIDConnect','client_id','Client ID','503787316191-70068ljk1ies95s7g78drigkil0410vm.apps.googleusercontent.com',NULL,NULL,'2016-11-18 17:15:41',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('07c5388a-a34f-49b8-a6fb-9f559ceaaa19','OAuth2OpenIDConnect','issuer','Issuer','https://accounts.google.com',NULL,NULL,'2016-11-18 17:15:41',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('156a062d-3571-4345-b262-9ba41f7ad5e1','OAuth2OpenIDConnect','auth_uri','Authentication URI','https://accounts.google.com/o/oauth2/v2/auth',NULL,NULL,'2016-11-18 17:15:41',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('1c7c965b-3af1-4909-9d1e-ac4bb10e2ac3','OAuth2OpenIDConnect','redirect_uri','Redirect URI','http://localhost:8080/openemis-phpoe/Users/postLogin',NULL,NULL,'2016-11-18 17:15:41',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('1e0cb317-9ea5-459e-92d1-a2fcd874dfae','OAuth2OpenIDConnect','openid_configuration','OpenID Configuration URI','https://accounts.google.com/.well-known/openid-configuration',NULL,NULL,'2016-11-18 17:15:41',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('2916ea2e-537e-458a-9045-c30045709d76','OAuth2OpenIDConnect','gender_mapping','Gender Mapping','gender',NULL,NULL,'2016-11-18 17:15:41',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('3c9acc25-73ba-400b-ad8e-9a733e84539f','OAuth2OpenIDConnect','firstName_mapping','First Name Mapping','given_name',NULL,NULL,'2016-11-18 17:15:41',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('41d5d53c-7b54-4c3b-9667-d4e75ac23449','OAuth2OpenIDConnect','allow_create_user','Allow User Creation','',NULL,NULL,'2016-11-18 17:15:41',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('7c5ab90d-b3cb-44e2-ba55-6b2e91d636b8','OAuth2OpenIDConnect','username_mapping','Username Mapping','email',NULL,NULL,'2016-11-18 17:15:41',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('85b9e5eb-1450-4ec1-be2a-64a30c4052ae','OAuth2OpenIDConnect','dob_mapping','Date of Birth Mapping','date_of_birth',NULL,NULL,'2016-11-18 17:15:41',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('92fba2c0-4b36-4b69-b65a-6dee188ee7e6','OAuth2OpenIDConnect','jwk_uri','Public Key URI','https://www.googleapis.com/oauth2/v3/certs',NULL,NULL,'2016-11-18 17:15:41',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('c2e9add8-b836-484f-8132-838603a8e877','OAuth2OpenIDConnect','userInfo_uri','User Information URI','https://www.googleapis.com/oauth2/v3/userinfo',NULL,NULL,'2016-11-18 17:15:41',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('e492f517-b9d0-48dd-b4e2-e9ebbef9a898','OAuth2OpenIDConnect','client_secret','Client Secret','aCtvUXLvkHuG0cpk_u_3WGvL',NULL,NULL,'2016-11-18 17:15:41',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('ef112e5c-fbe3-44c2-bc7f-785c0a9db3d5','OAuth2OpenIDConnect','lastName_mapping','Last Name Mapping','family_name',NULL,NULL,'2016-11-18 17:15:41',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('f970afe1-9bf7-4b65-b41a-4581471f4a8d','OAuth2OpenIDConnect','token_uri','Token URI','https://www.googleapis.com/oauth2/v4/token',NULL,NULL,'2016-11-18 17:15:41',2);
INSERT INTO `authentication_type_attributes` (`id`,`authentication_type`,`attribute_field`,`attribute_name`,`value`,`modified`,`modified_user_id`,`created`,`created_user_id`) VALUES ('de7b9cd1-2a50-11e7-bd1c-525400b263eb','OAuth2OpenIDConnect','role_mapping','Role Mapping','role',NULL,NULL,'2016-11-18 17:15:41',2);
