# Synopsis

Client SDK for the Symplur API

[![Build Status](https://travis-ci.org/symplur/sdk-php.svg?branch=master)](https://travis-ci.org/symplur/sdk-php)
[![Latest Stable Version](https://poser.pugx.org/symplur/sdk/v/stable)](https://packagist.org/packages/symplur/sdk)
[![License](https://poser.pugx.org/symplur/sdk/license)](https://packagist.org/packages/symplur/sdk)

### NOTE: The Symplur API has not yet been released for usage by our customers.  We are not providing API credentials yet.  We expect this to change by the end of Q1 2017.  Thank you for your patience.

# Code Example

```
$client = new Symplur\Api\Client($clientId, $clientSecret);

$data = $client->get('/reports/twitter/people/influencers', [
    'predicates' => ['#hcsm'],
    'start' => 'Dec 1, 2015',
    'end' => 'Dec 31, 2015',
    'timezone' => 'America/New_York'
]);
```

# Motivation

The goal of this SDK is to simplify using the Symplur API in your PHP projects.

# Installation

This SDK is intended to be installed and managed through [Composer](https://getcomposer.org/):

```
composer require symplur/sdk
```

# Reference

The `Symplur\Api\Client` class is the main SDK interface you will be using.  Here is a reference:

### __construct($clientId, $clientSecret, array $options = [])

* `$clientId` (string) Your Oauth client ID
* `$clientSecret` (string) Your Oauth secret
* `$options` - (array) Optional configuration parameters

Values supported in the `$options` array are as follows:

* `base_uri` (string) Alternate API URL. Advanced usage only.
* `timeout` (integer) Can be used to overwrite the default HTTP timeout of 30 seconds.
* `cache_getter` ([callable](http://php.net/manual/en/language.types.callable.php)) Function for retrieving access tokens from a cache. It will be executed with one input argument, which is the name of the property to get. See below for more info.
* `cache_setter` ([callable](http://php.net/manual/en/language.types.callable.php)) Function for storing access tokens to a cache. It will be executed with two input arguments, which are the name of the property to set, and the value of this property.  See below for more info.

The SDK uses your Client ID and Client Secret to obtain an Oauth access token from the API.  It stores this in-memory.  If the `cache_setter` and `cache_getter` are provided, the client will also be able to persist the token for subsequent instantiations of the client. **We strongly recommend using cache** in order to reduce latency and system loads.  Here is a very simple example which makes use of PHP sessions for token storage:

```
$getter = function($name) {
    return @$_SESSION[$name];
};
$setter = function($name, $value) {
    $_SESSION[$name] = $value;
}
$client = new Symplur\Api\Client($clientId, $clientSecret, [
    'cache_getter' => $getter,
    'cache_setter' => $setter
]);
```

### get($relativePath, array $query = [])

Use this to perform a GET request to an API endpoint.

* `$relativePath` (string) URL path of the endpoint, relative to the API's base URI
* `$query` (array) Query arguments

Example:

```
$data = $client->get('/foo/zat', [
	'offset' => 10, 
	'limit' => 20
]);
```

### post($relativePath, array $formParams = [])

Use this to perform a POST request to an API endpoint.

* `$relativePath` (string) URL path of the endpoint, relative to the API's base URI
* `$formParams` (array) Body properties

Example:

```
$data = $client->post('/foo/zat', [
	'name' => 'Thing 1',
	'description' => 'Lorem ipsum dolor...'
]);
```

### put($relativePath, array $formParams = [])

Use this to perform a PUT request to an API endpoint.

* `$relativePath` (string) URL path of the endpoint, relative to the API's base URI
* `$formParams` (array) Body properties

Example:

```
$data = $client->put('/foo/zat/12345', [
	'name' => 'Thing 1',
	'description' => 'Lorem ipsum dolor...'
]);
```

### patch($relativePath, array $formParams = [])

Use this to perform a PATCH request to an API endpoint.

* `$relativePath` (string) URL path of the endpoint, relative to the API's base URI
* `$formParams` (array) Body properties

Example:

```
$data = $client->patch('/foo/zat/12345', [
	'description' => 'Lorem ipsum dolor...'
]);
```

### delete($relativePath, array $formParams = [])

Use this to perform a DELETE request to an API endpoint.

* `$relativePath` (string) URL path of the endpoint, relative to the API's base URI
* `$formParams` (array) Body properties

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
Or if you already have a recent version of PHPUnit installed globally on your system:

```
phpunit
```

# Contributors

SDK development is managed by Symplur Engineering.  Your feedback and pull requests are welcome!

# License

This SDK is provided under the terms of the MIT license.  See `LICENSE` for details.