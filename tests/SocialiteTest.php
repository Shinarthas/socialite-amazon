<?php

namespace Revolution\Socialite\Amazon\Tests;

use Mockery as m;

use stdClass;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User;
use Revolution\Socialite\Amazon\AmazonProvider;
use Revolution\Socialite\Amazon\Tests\TestCase;

class SocialiteTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function testInstance()
    {
        $provider = Socialite::driver('amazon');

        $this->assertInstanceOf(AmazonProvider::class, $provider);
    }

    public function testRedirect()
    {
        $request = Request::create('foo');
        $request->setLaravelSession($session = m::mock(Session::class));
        $session->shouldReceive('put')->once();

        $provider = new AmazonProvider($request, 'client_id', 'client_secret', 'redirect');
        $response = $provider->redirect();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringStartsWith('https://www.amazon.com/ap/oa', $response->getTargetUrl());
    }

    public function testUser()
    {
        $request = Request::create('foo', 'GET', ['state' => str_repeat('A', 40), 'code' => 'code']);
        $request->setLaravelSession($session = m::mock(Session::class));
        $session->shouldReceive('pull')->once()->with('state')->andReturn(str_repeat('A', 40));

        $provider = new AmazonProviderStub($request, 'client_id', 'client_secret', 'redirect_uri');

        $provider->http = m::mock(stdClass::class);

        $provider->http->shouldReceive('post')->once()->with(
            'http://token.url',
            [
                'headers'     => ['Accept' => 'application/json'],
                'form_params' => [
                    'client_id'     => 'client_id',
                    'client_secret' => 'client_secret',
                    'code'          => 'code',
                    'redirect_uri'  => 'redirect_uri',
                    'grant_type'    => 'authorization_code',
                ],
            ]
        )->andReturn($response = m::mock(stdClass::class));

        $response->shouldReceive('getBody')->once()->andReturn(
            '{ "access_token" : "access_token", "refresh_token" : "refresh_token", "expires_in" : 3600 }'
        );

        $user = $provider->user();

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('foo', $user->id);
        $this->assertSame('access_token', $user->token);
        $this->assertSame('refresh_token', $user->refreshToken);
        $this->assertSame('name', $user->name);
        $this->assertSame('email', $user->email);
        $this->assertSame(3600, $user->expiresIn);
    }
}
