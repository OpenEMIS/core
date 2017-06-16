<?php
/*
 * Copyright 2016 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

class Google_Service_Analytics_EntityUserLinkEntity extends Google_Model
{
  protected $accountRefType = 'Google_Service_Analytics_AccountRef';
  protected $accountRefDataType = '';
  protected $profileRefType = 'Google_Service_Analytics_ProfileRef';
  protected $profileRefDataType = '';
  protected $webPropertyRefType = 'Google_Service_Analytics_WebPropertyRef';
  protected $webPropertyRefDataType = '';

  public function setAccountRef(Google_Service_Analytics_AccountRef $accountRef)
  {
    $this->accountRef = $accountRef;
  }
  public function getAccountRef()
  {
    return $this->accountRef;
  }
  public function setProfileRef(Google_Service_Analytics_ProfileRef $profileRef)
  {
    $this->profileRef = $profileRef;
  }
  public function getProfileRef()
  {
    return $this->profileRef;
  }
  public function setWebPropertyRef(Google_Service_Analytics_WebPropertyRef $webPropertyRef)
  {
    $this->webPropertyRef = $webPropertyRef;
  }
  public function getWebPropertyRef()
  {
    return $this->webPropertyRef;
  }
}
