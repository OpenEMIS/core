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

class Google_Service_ServiceManagement_Documentation extends Google_Collection
{
  protected $collection_key = 'rules';
  public $documentationRootUrl;
  public $overview;
  protected $pagesType = 'Google_Service_ServiceManagement_Page';
  protected $pagesDataType = 'array';
  protected $rulesType = 'Google_Service_ServiceManagement_DocumentationRule';
  protected $rulesDataType = 'array';
  public $summary;

  public function setDocumentationRootUrl($documentationRootUrl)
  {
    $this->documentationRootUrl = $documentationRootUrl;
  }
  public function getDocumentationRootUrl()
  {
    return $this->documentationRootUrl;
  }
  public function setOverview($overview)
  {
    $this->overview = $overview;
  }
  public function getOverview()
  {
    return $this->overview;
  }
  public function setPages($pages)
  {
    $this->pages = $pages;
  }
  public function getPages()
  {
    return $this->pages;
  }
  public function setRules($rules)
  {
    $this->rules = $rules;
  }
  public function getRules()
  {
    return $this->rules;
  }
  public function setSummary($summary)
  {
    $this->summary = $summary;
  }
  public function getSummary()
  {
    return $this->summary;
  }
}
