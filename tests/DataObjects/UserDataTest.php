<?php

use App\DataObjects\UserData;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Behaviour-preservation tests for the UserData parameter object (smell #5).
 *
 * These lock the exact array shapes that addUser()/updateUser() previously built
 * inline: the same keys, the same key ORDER, and the same default rules
 * (profile-picture fallback, password_change_required default). If a future
 * change alters what UserData emits, these fail.
 *
 * @internal
 */
final class UserDataTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper(['global_functions_helper']); // getDefaultProfileImagePath()
    }

    private function request(array $post): \CodeIgniter\HTTP\IncomingRequest
    {
        $request = service('request');
        $request->setGlobal('post', $post);

        return $request;
    }

    private function fullPost(): array
    {
        return [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'username' => 'janedoe',
            'email' => 'jane@example.com',
            'password' => 'S3cret!',
            'status' => 'active',
            'role' => 'admin',
            'profile_picture' => 'uploads/jane.png',
            'twitter_link' => 'https://twitter.com/jane',
            'facebook_link' => 'https://facebook.com/jane',
            'instagram_link' => 'https://instagram.com/jane',
            'linkedin_link' => 'https://linkedin.com/in/jane',
            'about_summary' => 'Hello there.',
            'password_change_required' => '1',
        ];
    }

    public function testToCreateArrayMatchesOriginalShape(): void
    {
        $arr = UserData::fromRequest($this->request($this->fullPost()), true)->toCreateArray();

        // Exact key order as the original addUser() array.
        $this->assertSame([
            'first_name', 'last_name', 'username', 'email', 'password', 'status',
            'role', 'profile_picture', 'twitter_link', 'facebook_link',
            'instagram_link', 'linkedin_link', 'about_summary', 'password_change_required',
        ], array_keys($arr));

        // Values carried through verbatim.
        $this->assertSame('Jane', $arr['first_name']);
        $this->assertSame('janedoe', $arr['username']);
        $this->assertSame('jane@example.com', $arr['email']);
        $this->assertSame('active', $arr['status']);
        $this->assertSame('admin', $arr['role']);
        $this->assertSame('uploads/jane.png', $arr['profile_picture']);
        $this->assertSame('https://twitter.com/jane', $arr['twitter_link']);
        $this->assertSame('1', $arr['password_change_required']);

        // Password is hashed (not stored raw), exactly like the original.
        $this->assertNotSame('S3cret!', $arr['password']);
        $this->assertTrue(password_verify('S3cret!', $arr['password']));
    }

    public function testToUpdateArrayMatchesOriginalShape(): void
    {
        $arr = UserData::fromRequest($this->request($this->fullPost()))->toUpdateArray();

        // Update array has NO credentials and matches original updateUser() order.
        $this->assertSame([
            'first_name', 'last_name', 'status', 'role', 'profile_picture',
            'twitter_link', 'facebook_link', 'instagram_link', 'linkedin_link',
            'about_summary', 'password_change_required',
        ], array_keys($arr));

        $this->assertArrayNotHasKey('username', $arr);
        $this->assertArrayNotHasKey('email', $arr);
        $this->assertArrayNotHasKey('password', $arr);
        $this->assertSame('Doe', $arr['last_name']);
        $this->assertSame('https://linkedin.com/in/jane', $arr['linkedin_link']);
    }

    public function testDefaultsMatchOriginalFallbacks(): void
    {
        // profile_picture + password_change_required omitted -> original fallbacks.
        $post = $this->fullPost();
        unset($post['profile_picture'], $post['password_change_required']);

        $arr = UserData::fromRequest($this->request($post), true)->toCreateArray();

        $this->assertSame(getDefaultProfileImagePath(), $arr['profile_picture']);
        $this->assertFalse($arr['password_change_required']);
    }
}
