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

class Google_Service_SearchConsole_RunMobileFriendlyTestResponse extends Google_Collection
{
  protected $collection_key = 'resourceIssues';
  public $mobileFriendliness;
  protected $mobileFriendlyIssuesType = 'Google_Service_SearchConsole_MobileFriendlyIssue';
  protected $mobileFriendlyIssuesDataType = 'array';
  protected $resourceIssuesType = 'Google_Service_SearchConsole_ResourceIssue';
  protected $resourceIssuesDataType = 'array';
  protected $screenshotType = 'Google_Service_SearchConsole_Image';
  protected $screenshotDataType = '';
  protected $testStatusType = 'Google_Service_SearchConsole_TestStatus';
  protected $testStatusDataType = '';

  public function setMobileFriendliness($mobileFriendliness)
  {
    $this->mobileFriendliness = $mobileFriendliness;
  }
  public function getMobileFriendliness()
  {
    return $this->mobileFriendliness;
  }
  public function setMobileFriendlyIssues($mobileFriendlyIssues)
  {
    $this->mobileFriendlyIssues = $mobileFriendlyIssues;
  }
  public function getMobileFriendlyIssues()
  {
    return $this->mobileFriendlyIssues;
  }
  public function setResourceIssues($resourceIssues)
  {
    $this->resourceIssues = $resourceIssues;
  }
  public function getResourceIssues()
  {
    return $this->resourceIssues;
  }
  public function setScreenshot(Google_Service_SearchConsole_Image $screenshot)
  {
    $this->screenshot = $screenshot;
  }
  public function getScreenshot()
  {
    return $this->screenshot;
  }
  public function setTestStatus(Google_Service_SearchConsole_TestStatus $testStatus)
  {
    $this->testStatus = $testStatus;
  }
  public function getTestStatus()
  {
    return $this->testStatus;
  }
}
