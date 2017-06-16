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

class Google_Service_Partners_ListAnalyticsResponse extends Google_Collection
{
  protected $collection_key = 'analytics';
  protected $analyticsType = 'Google_Service_Partners_Analytics';
  protected $analyticsDataType = 'array';
  protected $analyticsSummaryType = 'Google_Service_Partners_AnalyticsSummary';
  protected $analyticsSummaryDataType = '';
  public $nextPageToken;
  protected $responseMetadataType = 'Google_Service_Partners_ResponseMetadata';
  protected $responseMetadataDataType = '';

  public function setAnalytics($analytics)
  {
    $this->analytics = $analytics;
  }
  public function getAnalytics()
  {
    return $this->analytics;
  }
  public function setAnalyticsSummary(Google_Service_Partners_AnalyticsSummary $analyticsSummary)
  {
    $this->analyticsSummary = $analyticsSummary;
  }
  public function getAnalyticsSummary()
  {
    return $this->analyticsSummary;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setResponseMetadata(Google_Service_Partners_ResponseMetadata $responseMetadata)
  {
    $this->responseMetadata = $responseMetadata;
  }
  public function getResponseMetadata()
  {
    return $this->responseMetadata;
  }
}
