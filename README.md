Laravel EC2 Metadata
====================

![CI](https://github.com/renoki-co/laravel-ec2-metadata/workflows/CI/badge.svg?branch=master)
[![codecov](https://codecov.io/gh/renoki-co/laravel-ec2-metadata/branch/master/graph/badge.svg)](https://codecov.io/gh/renoki-co/laravel-ec2-metadata/branch/master)
[![StyleCI](https://github.styleci.io/repos/404265901/shield?branch=master)](https://github.styleci.io/repos/404265901)
[![Latest Stable Version](https://poser.pugx.org/renoki-co/laravel-ec2-metadata/v/stable)](https://packagist.org/packages/renoki-co/laravel-ec2-metadata)
[![Total Downloads](https://poser.pugx.org/renoki-co/laravel-ec2-metadata/downloads)](https://packagist.org/packages/renoki-co/laravel-ec2-metadata)
[![Monthly Downloads](https://poser.pugx.org/renoki-co/laravel-ec2-metadata/d/monthly)](https://packagist.org/packages/renoki-co/laravel-ec2-metadata)
[![License](https://poser.pugx.org/renoki-co/laravel-ec2-metadata/license)](https://packagist.org/packages/renoki-co/laravel-ec2-metadata)

Retrieve the EC2 Metadata using Laravel's eloquent syntax.

## 🤝 Supporting

[<img src="https://github-content.s3.fr-par.scw.cloud/static/20.jpg" height="210" width="418" />](https://github-content.renoki.org/github-repo/20)

If you are using one or more Renoki Co. open-source packages in your production apps, in presentation demos, hobby projects, school projects or so, spread some kind words about our work or sponsor our work via Patreon. 📦

[<img src="https://c5.patreon.com/external/logo/become_a_patron_button.png" height="41" width="175" />](https://www.patreon.com/bePatron?u=10965171)

## 🚀 Installation

You can install the package via composer:

```bash
composer require renoki-co/laravel-ec2-metadata
```

## 🙌 Usage

The package was made to be easier for you to implement your own methods and keep it simple, without hassling too much about requests.

In this brief example, you can calculate the seconds left until the EC2 Spot instance will be terminated.

```php
use Carbon\Carbon;
use RenokiCo\Ec2Metadata\Ec2Metadata;

if ($termination = Ec2Metadata::terminationNotice()) {
    // The instance is terminating...

    $secondsRemaining = Carbon::parse($termination['time'])->diffInSeconds(now());

    echo "The instance is terminating in {$secondsRemaining} seconds.";
}
```

## Setting Version

The default version of the Ec2Metadata class is `latest`, but to avoid your code to break due to API changes, define the version to run on.

You can see the list of available versions [in IMDSv2 documentation](https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/instancedata-data-retrieval.html), under `Get the available versions of the instance metadata`:

```php
use RenokiCo\Ec2Metadata\Ec2Metadata;

Ec2Metadata::version('2016-09-02');
```

## Calling Custom Endpoints

The IMDSv2 API is pretty complex, and there are some functions you can use from the `Ec2Metadata` class, just for convenience. When you want to retrieve data from an endpoint that's not implemented, you can either define a macro or use the `get()` and `getJson()` functions to retrieve in plain-text or as a JSON-decoded array:

Take this example for retrieving the kernel ID (under `/meta-data/kernel-id`):

```php
use RenokiCo\Ec2Metadata\Ec2Metadata;

$kernelId = Ec2Metadata::get('kernel-id');
```

To retrieve JSON values, you may call `getJson`. This will work properly only if the expected value from the endpoint you call will be a JSON-encoded response.

In the implementation, `terminationNotice` uses the `getJson()` to retrieve the response:

```php
class Ec2Metadata
{
    public static function terminationNotice(): array
    {
        // Expected response is {"action": "terminate", "time": "2017-09-18T08:22:00Z"}
        return static::getJson('/spot/instance-action');
    }
}
```

## Macros

Alternatively to using `get()` and `getJson()`, you can define macros:

```php
use RenokiCo\Ec2Metadata\Ec2Metadata;

Ec2Metadata::macro('kernelId', function () {
    return static::get('kernel-id');
});

$kernelId = Ec2Metadata::kernelId();
```

## Testing Your Code

The package is using [HTTP Client](https://laravel.com/docs/8.x/http-client), a Laravel feature that leverages Guzzle and you can handle requests and test them by [mocking responses](https://laravel.com/docs/8.x/http-client#testing).

Testing properly your app means you should be fully trained with the [AWS EC2's IMDSv2](https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/configuring-instance-metadata-service.html) API, in order to provider appropriate responses.

When pushing the responses in testing, make sure to take into account that the first call would be the token retrieval.

```php
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RenokiCo\Ec2Metadata\Ec2Metadata;

Http::fake([
    'http://169.254.169.254/*' => Http::sequence()
        ->push('some-token', 200)
        ->push('ami-1234', 200),
]);

$this->assertEquals('ami-1234', Ec2Metadata::ami());

Http::assertSentInOrder([
    function (Request $request) {
        return $request->method() === 'PUT' &&
            $request->url() === 'http://169.254.169.254/latest/api/token' &&
            $request->header('X-AWS-EC2-Metadata-Token-TTL-Seconds') === ['21600'];
    },
    function (Request $request) {
        return $request->method() === 'GET' &&
            $request->url() === 'http://169.254.169.254/latest/meta-data/ami-id' &&
            $request->header('X-AWS-EC2-Metadata-Token') === ['some-token'];
    },
]);
```

## 🐛 Testing

``` bash
vendor/bin/phpunit
```

## 🤝 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 🔒  Security

If you discover any security related issues, please email alex@renoki.org instead of using the issue tracker.

## 🎉 Credits

- [Alex Renoki](https://github.com/rennokki)
- [All Contributors](../../contributors)
