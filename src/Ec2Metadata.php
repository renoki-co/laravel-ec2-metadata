<?php

namespace RenokiCo\Ec2Metadata;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Traits\Macroable;

class Ec2Metadata
{
    use Macroable;

    /**
     * The version for the metadata API.
     *
     * @var string
     */
    protected static $version = 'latest';

    /**
     * The stored token for requests.
     *
     * @var string|null
     */
    protected static $token;

    /**
     * Set the version for the metadata API.
     *
     * @param  string  $version
     * @return void
     */
    public static function version(string $version): void
    {
        static::$version = $version;
    }

    /**
     * Regenerate the token.
     *
     * @param  int  $ttl
     * @return string
     */
    public static function regenerateToken(int $ttl = 21600): string
    {
        return static::$token = static::getToken($ttl);
    }

    /**
     * Forget the token.
     *
     * @return void
     */
    public static function deleteToken(): void
    {
        static::$token = null;
    }

    /**
     * Get the token used to authenticate the metadata endpoints.
     *
     * @param  int  $ttl
     * @return string
     */
    public static function getToken(int $ttl = 21600): string
    {
        return static::call('/api/token', 'PUT', [
            'X-AWS-EC2-Metadata-Token-TTL-Seconds' => $ttl,
        ])->body();
    }

    /**
     * Get the AMI ID of the EC2 instance.
     *
     * @return string
     */
    public static function ami(): string
    {
        return static::get('ami-id');
    }

    /**
     * Get the termination notice, in case the instance is Spot.
     *
     * @return array|null
     * @throws \Illuminate\Http\Client\RequestException
     */
    public static function terminationNotice(): ?array
    {
        return static::getJson('/spot/instance-action');
    }

    /**
     * Shorthand call for any string-returning value from the /meta-data endpoint.
     * For example, you might call get('ami-id') and it will return the /metadata/ami-id response.
     *
     * @param  string  $key
     * @return string
     */
    public static function get(string $key): string
    {
        return static::callWithToken("/meta-data/{$key}")->body();
    }

    /**
     * Shorthand call for any JSON-returning value from the /meta-data endpoint.
     * For example, you might call get('ami-id') and it will return the /metadata/ami-id response.
     *
     * @param  string  $key
     * @return array|null
     */
    public static function getJson(string $key): ?array
    {
        return static::callWithToken("/meta-data/{$key}")->json();
    }

    /**
     * Make a call to the given endpoint.
     *
     * @param  string  $endpoint
     * @param  string  $method
     * @param  array  $headers
     * @return \Illuminate\Http\Client\Response
     */
    public static function call(
        string $endpoint,
        string $method = 'get',
        array $headers = [],
    ): Response {
        return Http::withHeaders($headers)->{$method}('http://169.254.169.254/'.static::$version.$endpoint);
    }

    /**
     * Make a call to the given endpoint, with a passed
     * token. If you don't specify a token, it will be
     * retrieved automatically.
     *
     * @param  string  $endpoint
     * @param  string  $method
     * @param  string|null  $token
     * @return \Illuminate\Http\Client\Response
     * @throws \Illuminate\Http\Client\RequestException
     */
    public static function callWithToken(
        string $endpoint,
        string $method = 'get',
        string $token = null,
    ): Response {
        if (! $token) {
            if (! $token = static::$token) {
                $token = static::regenerateToken();
            }
        }

        /** @var \Illuminate\Http\Response $response */
        $response = Http::withHeaders([
            'X-AWS-EC2-Metadata-Token' => $token,
        ])->{$method}('http://169.254.169.254/'.static::$version.$endpoint);

        // Automatically retry in case of 401 errors (token might be expired).
        if ($response->status() === 401) {
            return static::callWithToken($endpoint, $method, static::regenerateToken());
        }

        return $response;
    }
}
