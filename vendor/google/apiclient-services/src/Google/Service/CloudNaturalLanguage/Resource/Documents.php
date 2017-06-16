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

/**
 * The "documents" collection of methods.
 * Typical usage is:
 *  <code>
 *   $languageService = new Google_Service_CloudNaturalLanguage(...);
 *   $documents = $languageService->documents;
 *  </code>
 */
class Google_Service_CloudNaturalLanguage_Resource_Documents extends Google_Service_Resource
{
  /**
   * Finds named entities (currently finds proper names) in the text, entity
   * types, salience, mentions for each entity, and other properties.
   * (documents.analyzeEntities)
   *
   * @param Google_Service_CloudNaturalLanguage_AnalyzeEntitiesRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_CloudNaturalLanguage_AnalyzeEntitiesResponse
   */
  public function analyzeEntities(Google_Service_CloudNaturalLanguage_AnalyzeEntitiesRequest $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('analyzeEntities', array($params), "Google_Service_CloudNaturalLanguage_AnalyzeEntitiesResponse");
  }
  /**
   * Analyzes the sentiment of the provided text. (documents.analyzeSentiment)
   *
   * @param Google_Service_CloudNaturalLanguage_AnalyzeSentimentRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_CloudNaturalLanguage_AnalyzeSentimentResponse
   */
  public function analyzeSentiment(Google_Service_CloudNaturalLanguage_AnalyzeSentimentRequest $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('analyzeSentiment', array($params), "Google_Service_CloudNaturalLanguage_AnalyzeSentimentResponse");
  }
  /**
   * Analyzes the syntax of the text and provides sentence boundaries and
   * tokenization along with part of speech tags, dependency trees, and other
   * properties. (documents.analyzeSyntax)
   *
   * @param Google_Service_CloudNaturalLanguage_AnalyzeSyntaxRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_CloudNaturalLanguage_AnalyzeSyntaxResponse
   */
  public function analyzeSyntax(Google_Service_CloudNaturalLanguage_AnalyzeSyntaxRequest $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('analyzeSyntax', array($params), "Google_Service_CloudNaturalLanguage_AnalyzeSyntaxResponse");
  }
  /**
   * A convenience method that provides all the features that analyzeSentiment,
   * analyzeEntities, and analyzeSyntax provide in one call.
   * (documents.annotateText)
   *
   * @param Google_Service_CloudNaturalLanguage_AnnotateTextRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_CloudNaturalLanguage_AnnotateTextResponse
   */
  public function annotateText(Google_Service_CloudNaturalLanguage_AnnotateTextRequest $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('annotateText', array($params), "Google_Service_CloudNaturalLanguage_AnnotateTextResponse");
  }
}
