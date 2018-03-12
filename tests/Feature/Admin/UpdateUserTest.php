<?php

/*
 * This file is part of Solder.
 *
 * (c) Kyle Klaus <kklaus@indemnity83.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Feature\Admin;

use App\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateUserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_admin_can_update_a_user()
    {
        $user = factory(User::class)->states('admin')->create([
            'username' => 'John',
            'email' => 'john@example.com',
            'password' => bcrypt('super-secret-password'),
            'is_admin' => true,
        ]);

        $response = $this->actingAs($user)->from('/settings/users')->post('/settings/users/'.$user->id, [
            'username' => 'Jane',
            'email' => 'jane@example.com',
            'password' => 'updated-password',
        ]);

        tap(User::first(), function ($user) use ($response) {
            $response->assertRedirect('/settings/users');
            $response->assertSessionMissing('errors');

            $this->assertEquals('Jane', $user->username);
            $this->assertEquals('jane@example.com', $user->email);
            $this->assertTrue(Hash::check('updated-password', $user->password));
            $this->assertFalse($user->is_admin);
        });
    }

    /** @test */
    public function a_authorized_user_can_update_a_user()
    {
        $user = factory(User::class)->create($this->originalParams());
        $user->grantRole('manage-users');

        $response = $this->actingAs($user)->from('/settings/users')
            ->post('/settings/users/'.$user->id, $this->validParams());

        $response->assertRedirect('/settings/users');
        $response->assertSessionMissing('errors');
        $this->assertArraySubset($this->validParams(), $user->fresh()->getAttributes());
    }

    /** @test */
    public function an_unauthorized_user_can_not_update_a_user()
    {
        $user = factory(User::class)->create([
            'username' => 'John',
            'email' => 'john@example.com',
            'password' => bcrypt('super-secret-password'),
            'is_admin' => false,
        ]);

        $response = $this->actingAs($user)->from('/settings/users')
            ->post('/settings/users/'.$user->id, $this->validParams());

        $response->assertStatus(403);
        tap(User::first(), function ($user) use ($response) {
            $this->assertEquals('John', $user->username);
            $this->assertEquals('john@example.com', $user->email);
            $this->assertTrue(Hash::check('super-secret-password', $user->password));
            $this->assertFalse($user->is_admin);
        });
    }

    /** @test */
    public function an_authorized_user_can_not_make_a_user_admin()
    {
        $user = factory(User::class)->create($this->originalParams([
            'is_admin' => false,
        ]));
        $user->grantRole('manage-users');

        $response = $this->actingAs($user)->from('/settings/users')
            ->post('/settings/users/'.$user->id, $this->validParams([
                'is_admin' => true,
            ]));

        $response->assertStatus(403);
        tap(User::first(), function ($user) use ($response) {
            $this->assertEquals('John', $user->username);
            $this->assertEquals('john@example.com', $user->email);
            $this->assertTrue(Hash::check('super-secret-password', $user->password));
            $this->assertFalse($user->is_admin);
        });
    }

    /** @test */
    public function a_guest_cannot_udpdate_a_user()
    {
        $user = factory(User::class)->create([
            'username' => 'John',
            'email' => 'john@example.com',
            'password' => bcrypt('super-secret-password'),
            'is_admin' => false,
        ]);

        $response = $this->post('/settings/users/'.$user->id, $this->validParams());

        tap(User::first(), function ($user) use ($response) {
            $response->assertRedirect('/login');

            $this->assertEquals('John', $user->username);
            $this->assertEquals('john@example.com', $user->email);
            $this->assertTrue(Hash::check('super-secret-password', $user->password));
        });
    }

    /** @test */
    public function user_must_exists()
    {
        $user = factory(User::class)->states('admin')->create();

        $response = $this->actingAs($user)->post('/settings/users/99', $this->validParams());

        $response->assertStatus(404);
    }

    /** @test */
    public function username_is_required()
    {
        $user = factory(User::class)->states('admin')->create($this->originalParams());

        $response = $this->actingAs($user)->from('/settings/users')->post('/settings/users/'.$user->id, $this->validParams([
            'username' => '',
        ]));

        $response->assertRedirect('/settings/users');
        $response->assertSessionHasErrors('username');
    }

    /** @test */
    public function username_is_unique()
    {
        $otherUser = factory(User::class)->create(['username' => 'Jane']);
        $user = factory(User::class)->states('admin')->create($this->originalParams());

        $response = $this->actingAs($user)->from('/settings/users')->post('/settings/users/'.$user->id, $this->validParams([
            'username' => 'Jane',
        ]));

        $response->assertRedirect('/settings/users');
        $response->assertSessionHasErrors('username');
    }

    /** @test */
    public function allow_resetting_same_username()
    {
        $user = factory(User::class)->states('admin')->create($this->originalParams([
            'username' => 'Jane',
        ]));

        $response = $this->actingAs($user)->from('/settings/users')->post('/settings/users/'.$user->id, $this->validParams([
            'username' => 'Jane',
        ]));

        $response->assertRedirect('/settings/users');
        $response->assertSessionMissing('errors');
    }

    /** @test */
    public function email_is_required()
    {
        $user = factory(User::class)->states('admin')->create($this->originalParams());

        $response = $this->actingAs($user)->from('/settings/users')->post('/settings/users/'.$user->id, $this->validParams([
            'email' => '',
        ]));

        $response->assertRedirect('/settings/users');
        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function email_is_unique()
    {
        $otherUser = factory(User::class)->create(['email' => 'jane@example.com']);
        $user = factory(User::class)->states('admin')->create($this->originalParams());

        $response = $this->actingAs($user)->from('/settings/users')->post('/settings/users/'.$user->id, $this->validParams([
            'email' => 'jane@example.com',
        ]));

        $response->assertRedirect('/settings/users');
        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function allow_resetting_same_email()
    {
        $user = factory(User::class)->states('admin')->create($this->originalParams([
            'email' => 'jane@example.com',
        ]));

        $response = $this->actingAs($user)->from('/settings/users')->post('/settings/users/'.$user->id, $this->validParams([
            'email' => 'jane@example.com',
        ]));

        $response->assertRedirect('/settings/users');
        $response->assertSessionMissing('errors');
    }

    /** @test */
    public function email_is_valid_format()
    {
        $user = factory(User::class)->states('admin')->create($this->originalParams());

        $response = $this->actingAs($user)->from('/settings/users')->post('/settings/users/'.$user->id, $this->validParams([
            'email' => 'not-an-email',
        ]));

        $response->assertRedirect('/settings/users');
        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function password_is_optional()
    {
        $this->withoutExceptionHandling();
        $user = factory(User::class)->states('admin')->create($this->originalParams([
            'password' => bcrypt('original-password'),
        ]));

        $response = $this->actingAs($user)->from('/settings/users')->post('/settings/users/'.$user->id, $this->validParams([
            'password' => '',
        ]));

        $response->assertRedirect('/settings/users');
        $response->assertSessionMissing('errors');
        $this->assertTrue(Hash::check('original-password', $user->fresh()->password));
    }

    /** @test */
    public function password_is_at_least_6_chars_long()
    {
        $user = factory(User::class)->states('admin')->create($this->originalParams());

        $response = $this->actingAs($user)->from('/settings/users')->post('/settings/users/'.$user->id, $this->validParams([
            'password' => 'abcde',
        ]));

        $response->assertRedirect('/settings/users');
        $response->assertSessionHasErrors('password');
    }

    /** @test */
    public function if_is_admin_is_missing_its_unchecked()
    {
        $user = factory(User::class)->states('admin')->create($this->originalParams([
            'is_admin' => true,
        ]));

        $response = $this->actingAs($user)->from('/settings/users')->post('/settings/users/'.$user->id, [
            'username' => 'Jane',
            'email' => 'jane@example.com',
            'password' => 'updated-password',
        ]);

        $response->assertRedirect('settings/users');
        $response->assertSessionMissing('errors');
        $this->assertFalse($user->fresh()->is_admin);
    }

    /**
     * @param array $overrides
     *
     * @return array
     */
    private function originalParams($overrides = [])
    {
        return array_merge([
            'username' => 'John',
            'email' => 'john@example.com',
            'password' => bcrypt('super-secret-password'),
        ], $overrides);
    }

    /**
     * @param array $overrides
     *
     * @return array
     */
    private function validParams($overrides = [])
    {
        return array_merge([
            'username' => 'Jane',
            'email' => 'jane@example.com',
            'is_admin' => false,
        ], $overrides);
    }
}
