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

class Google_Service_ServiceUser_AuthenticationRule extends Google_Collection
{
  protected $collection_key = 'requirements';
  public $allowWithoutCredential;
  protected $oauthType = 'Google_Service_ServiceUser_OAuthRequirements';
  protected $oauthDataType = '';
  protected $requirementsType = 'Google_Service_ServiceUser_AuthRequirement';
  protected $requirementsDataType = 'array';
  public $selector;

  public function setAllowWithoutCredential($allowWithoutCredential)
  {
    $this->allowWithoutCredential = $allowWithoutCredential;
  }
  public function getAllowWithoutCredential()
  {
    return $this->allowWithoutCredential;
  }
  public function setOauth(Google_Service_ServiceUser_OAuthRequirements $oauth)
  {
    $this->oauth = $oauth;
  }
  public function getOauth()
  {
    return $this->oauth;
  }
  public function setRequirements($requirements)
  {
    $this->requirements = $requirements;
  }
  public function getRequirements()
  {
    return $this->requirements;
  }
  public function setSelector($selector)
  {
    $this->selector = $selector;
  }
  public function getSelector()
  {
    return $this->selector;
  }
}
