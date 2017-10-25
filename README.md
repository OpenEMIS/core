# OpenEMIS Core (POCOR)
OpenEMIS Core is a sector wide Open Source Education Management Information System (EMIS) that facilitates the collection, processing and management of education information. OpenEMIS Core is a customizable web application that supports the day-to-day activities involved in managing a sector wide education system.

## Getting Started
The following instructions will get you a copy of OpenEMIS Core, install, and setup the application accordingly.

### Pre-requisites
![OS](https://img.shields.io/badge/OS-Linux-lightgrey.svg) ![WebServer](https://img.shields.io/badge/WebServer-Apache/NGINX-blue.svg) ![MySQL](https://img.shields.io/badge/MySQL->=5.7.0-orange.svg) ![PHP](https://img.shields.io/badge/PHP->=7.0-brightgreen.svg)

## Installation
#### Sourcetree
* Clone the application's repository
```
New -> Clone from URL
```
* Fill in the details
```
Source URL : [username]@bitbucket.org:korditpteltd/pocor-openemis-core.git
Destination Path : /your/working/path/here/
Name : pocor-openemis-core (this is an example)
```
* Press Clone

#### Command Line Interface
* Clone the application's respository
```
git clone [username]@bitbucket.org:korditpteltd/pocor-openemis-core.git

```

## Configuration
* Go to the application's config directory
```
cd config/
```
* Create a datasource.php from the default file
```
cp datasource.default.php datasource.php
```
* Update datasource.php with the database connection details
```
vi datasource.php
```
* Create app_extra.php from the default file
```
cp app_extra.default.php app_extra.php
```
* Generate a private key
```
openssl genrsa -out private.key 1024
```
* Generate a public key
```
openssl rsa -in private.key -pubout -out public.key
```
