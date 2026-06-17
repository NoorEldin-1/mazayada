<?php

namespace Tests;

use Illuminate\Testing\TestResponse;

/**
 * Base test case for the mobile API.
 *
 * Feature tests reuse a single application instance across all the requests made
 * within one test method. Sanctum's RequestGuard caches the resolved user on that
 * shared instance, so a second bearer-token request would re-use the first
 * request's user — masking token revocation/rotation and account-state changes.
 * Production serves one request per process, so this never happens there.
 *
 * To make token round-trips behave like production, we forget the resolved auth
 * guards before every request that carries an Authorization header. Requests
 * without one (e.g. Sanctum::actingAs(), which sets the user directly) are left
 * untouched, so that helper keeps working.
 */
abstract class ApiTestCase extends TestCase
{
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null): TestResponse
    {
        $hasBearer = isset($this->defaultHeaders['Authorization'])
            || isset($server['HTTP_AUTHORIZATION']);

        if ($hasBearer && $this->app?->bound('auth')) {
            $this->app['auth']->forgetGuards();
        }

        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
    }
}
