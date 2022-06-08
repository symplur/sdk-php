# Symplur SDK for PHP

[![Build Status](https://travis-ci.org/symplur/sdk-php.svg?branch=master)](https://travis-ci.org/symplur/sdk-php)
[![Latest Stable Version](https://poser.pugx.org/symplur/sdk/v/stable)](https://packagist.org/packages/symplur/sdk)
[![License](https://poser.pugx.org/symplur/sdk/license)](https://packagist.org/packages/symplur/sdk)

This library is intended to simplify using the [Symplur API](https://www.symplur.com/product/symplur-api/) in your PHP applications.

#### Healthcare Social Graph

The Symplur API gives access to insights from the [Healthcare Social Graph®](https://www.symplur.com/technology/healthcare-social-graph/) – the vast neural network of public healthcare communities, conversations and people, hand curated by Symplur and powered by machine learning.

# Quick Start

First, contact [ Symplur](https://www.symplur.com/contact/) to get your API credentials. This will consist of a Client ID and a Client Secret, which represent your organization. Then install this library using [Composer](https://getcomposer.org/) and construct the client object by inputting those strings. That's it! You're ready to start using it.

```
# composer require symplur/sdk
```

```
<?php
require_once 'vendor/autoload.php';

use Symplur\Api\Client;

$client = new Client($clientId, $clientSecret);

$data = $client->get('twitter/analytics/people/influencers', [
    'databases' => '#hcsm, #bcsm',
    'start' => '1 week ago',
    'end' => 'yesterday'
]);
```

A great place to start your journey is to first read the [Getting Started](https://docs.symplur.com/page/getting-started) document.
Please see the [Symplur API Documentation](https://docs.symplur.com) for details on the endpoints, inputs, and outputs. 

# Client Reference

The `Symplur\Api\Client` class is the main SDK interface you will be using. Here is a reference:

### \_\_construct($clientId, $clientSecret, array $options = [])

*   `$clientId` (string) Your Symplur Client ID
*   `$clientSecret` (string) Your Symplur Client Secret
*   `$options` - (array) Optional extra configuration parameters passed into the constructor for [Guzzle HTTP Client](http://guzzlephp.org/), which is used internally. NOTE: This array is not usually necessary.

### get($relativePath, array $query = [])

Use this to perform a GET request to an API endpoint.

*   `$relativePath` (string) URL path of the endpoint, relative to the API's base URI
*   `$query` (array) Optional params to be passed as a URL query string

Example:

```
$data = $client->get('foo/zat', [
	'offset' => 10,
	'limit' => 20
]);
```

### post($relativePath, array $formParams = [])

Use this to perform a POST request to an API endpoint.

*   `$relativePath` (string) Endpoint path relative to the API's base URI
*   `$formParams` (array) Optional params to be passsed in the request body

Example:

```
$data = $client->post('/foo/zat', [
	'name' => 'Thing 1',
	'description' => 'Lorem ipsum dolor...'
]);
```

### put($relativePath, array $formParams = [])

Use this to perform a PUT request to an API endpoint.

*   `$relativePath` (string) Endpoint path relative to the API's base URI
*   `$formParams` (array) Optional params to be passsed in the request body

Example:

```
$data = $client->put('/foo/zat/12345', [
	'name' => 'Thing 1',
	'description' => 'Lorem ipsum dolor...'
]);
```

### patch($relativePath, array $formParams = [])

Use this to perform a PATCH request to an API endpoint.

*   `$relativePath` (string) Endpoint path relative to the API's base URI
*   `$formParams` (array) Params to be passed in the request body. Assumes RFC 7396 Json Merge Patch format.

Example:

```
$data = $client->patch('/foo/zat/12345', [
	'description' => 'Lorem ipsum dolor...'
]);
```

### delete($relativePath, array $formParams = [])

Use this to perform a DELETE request to an API endpoint.

*   `$relativePath` (string) Endpoint path relative to the API's base URI
*   `$formParams` (array) Optional params to be passsed in the request body

Example:

```
$data = $client->delete('/foo/zat/12345');
```

# Tests

The test suite is built upon [PHPUnit](https://phpunit.de/).
A simple `phpunit.xml` config file is included. You may run the tests this way:

```
./vendor/bin/phpunit
```

# Contributors

SDK development is managed by Symplur Engineering. Your feedback and pull requests are welcome!

# License

This SDK is provided under the terms of the MIT license. See `LICENSE` for details.
