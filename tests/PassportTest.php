<?php

use Illuminate\Support\Facades\Artisan;
use Moloquent\Passport\Token;

class PassportTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        User::create(['name' => 'John Doe', 'age' => 35, 'title' => 'admin']);
    }

    public function tearDown()
    {
        User::truncate();
        Token::truncate();
        parent::tearDown();
    }

    public function testPassportInstall()
    {
        DB::collection('oauth_access_tokens')->delete();
        DB::collection('oauth_access_tokens')->delete();
        DB::collection('oauth_clients')->delete();
        DB::collection('oauth_personal_access_clients')->delete();
        DB::collection('oauth_refresh_tokens')->delete();

        $result = Artisan::call('passport:install', []);
        $this->assertEquals(0, $result);
    }

    /**
     * Test the personal access token
     *
     */
    public function testPersonalAccessToken() {

        $expected = User::first();

        // Create token for test
        $token = $expected->createToken('test-token');
        $this->assertInstanceOf(\Laravel\Passport\PersonalAccessTokenResult::class, $token);

        // Set request on container so that the Auth\RequestGuard gets the right request
        $this->app->bind('request', function() use ($token) {
            return \Illuminate\Http\Request::create('/', 'GET', [], [], [], [
                'HTTP_Authorization' => 'Bearer ' . $token->accessToken
            ]);
        });

        // test the guard
        $user = Auth::guard('passport')->user();
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($expected->id, $user->id);
    }

}
