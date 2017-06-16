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

class Google_Service_Urlshortener_AnalyticsSummary extends Google_Model
{
  protected $allTimeType = 'Google_Service_Urlshortener_AnalyticsSnapshot';
  protected $allTimeDataType = '';
  protected $dayType = 'Google_Service_Urlshortener_AnalyticsSnapshot';
  protected $dayDataType = '';
  protected $monthType = 'Google_Service_Urlshortener_AnalyticsSnapshot';
  protected $monthDataType = '';
  protected $twoHoursType = 'Google_Service_Urlshortener_AnalyticsSnapshot';
  protected $twoHoursDataType = '';
  protected $weekType = 'Google_Service_Urlshortener_AnalyticsSnapshot';
  protected $weekDataType = '';

  public function setAllTime(Google_Service_Urlshortener_AnalyticsSnapshot $allTime)
  {
    $this->allTime = $allTime;
  }
  public function getAllTime()
  {
    return $this->allTime;
  }
  public function setDay(Google_Service_Urlshortener_AnalyticsSnapshot $day)
  {
    $this->day = $day;
  }
  public function getDay()
  {
    return $this->day;
  }
  public function setMonth(Google_Service_Urlshortener_AnalyticsSnapshot $month)
  {
    $this->month = $month;
  }
  public function getMonth()
  {
    return $this->month;
  }
  public function setTwoHours(Google_Service_Urlshortener_AnalyticsSnapshot $twoHours)
  {
    $this->twoHours = $twoHours;
  }
  public function getTwoHours()
  {
    return $this->twoHours;
  }
  public function setWeek(Google_Service_Urlshortener_AnalyticsSnapshot $week)
  {
    $this->week = $week;
  }
  public function getWeek()
  {
    return $this->week;
  }
}
