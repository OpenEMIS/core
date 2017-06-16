<?php
/*
 * Copyright 2015 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Google\Auth\Tests;

use Google\Auth\HttpHandler\Guzzle5HttpHandler;
use GuzzleHttp\Message\Response;

class Guzzle5HttpHandlerTest extends BaseTest
{
    public function setUp()
    {
        $this->onlyGuzzle5();

        $this->mockPsr7Request =
            $this
                ->getMockBuilder('Psr\Http\Message\RequestInterface')
                ->getMock();
        $this->mockRequest =
            $this
                ->getMockBuilder('GuzzleHttp\Message\RequestInterface')
                ->getMock();
        $this->mockClient =
            $this
                ->getMockBuilder('GuzzleHttp\Client')
                ->disableOriginalConstructor()
                ->getMock();
    }

    public function testSuccessfullySendsRequest()
    {
        $this->mockClient
            ->expects($this->any())
            ->method('send')
            ->will($this->returnValue(new Response(200)));
        $this->mockClient
            ->expects($this->any())
            ->method('createRequest')
            ->will($this->returnValue($this->mockRequest));

        $handler = new Guzzle5HttpHandler($this->mockClient);
        $response = $handler($this->mockPsr7Request);
        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
    }
}
