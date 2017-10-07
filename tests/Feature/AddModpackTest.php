<?php

/*
 * This file is part of Solder.
 *
 * (c) Kyle Klaus <kklaus@indemnity83.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Feature;

use App\User;
use App\Modpack;
use Tests\TestCase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AddModpackTest extends TestCase
{
    use RefreshDatabase;

    private function validParams($overrides = [])
    {
        return array_merge([
            'name' => 'Iron Tanks',
            'slug' => 'iron-tanks',
            'is_published' => true,
        ], $overrides);
    }

    /** @test */
    public function a_user_can_create_a_modpack()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->post('/modpacks', [
            'name' => 'Iron Tanks',
            'slug' => 'iron-tanks',
            'is_published' => true,
        ]);

        tap(Modpack::first(), function ($modpack) use ($response) {
            $response->assertRedirect('/modpacks/iron-tanks');

            $this->assertEquals('Iron Tanks', $modpack->name);
            $this->assertEquals('iron-tanks', $modpack->slug);
            $this->assertTrue($modpack->is_published);
        });
    }

    /** @test */
    public function a_guest_cannot_create_a_modpack()
    {
        $response = $this->post('/modpacks', $this->validParams());

        $response->assertRedirect('/login');
        $this->assertEquals(0, Modpack::count());
    }

    /** @test */
    public function name_is_required()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->from('/dashboard')->post('/modpacks', $this->validParams([
            'name' => '',
        ]));

        $response->assertRedirect('/dashboard');
        $response->assertSessionHasErrors('name');
        $this->assertEquals(0, Modpack::count());
    }

    /** @test */
    public function slug_is_required()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->from('/dashboard')->post('/modpacks', $this->validParams([
            'slug' => '',
        ]));

        $response->assertRedirect('/dashboard');
        $response->assertSessionHasErrors('slug');
        $this->assertEquals(0, Modpack::count());
    }

    /** @test */
    public function slug_is_unique()
    {
        $user = factory(User::class)->create();
        factory(Modpack::class)->create(['slug' => 'existing-slug']);

        $response = $this->actingAs($user)->from('/dashboard')->post('/modpacks', $this->validParams([
            'slug' => 'existing-slug',
        ]));

        $response->assertRedirect('/dashboard');
        $response->assertSessionHasErrors('slug');
        $this->assertEquals(1, Modpack::count());
    }

    /** @test */
    public function slug_is_url_safe()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->from('/dashboard')->post('/modpacks', $this->validParams([
            'slug' => 'non url $safe slug',
        ]));

        $response->assertRedirect('/dashboard');
        $response->assertSessionHasErrors('slug');
        $this->assertEquals(0, Modpack::count());
    }

    /** @test */
    public function if_published_is_omitted_its_assumed_false()
    {
        $this->withoutExceptionHandling();
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->from('/')->post('/modpacks', $this->validParams([
            'is_published' => '',
        ]));

        tap(Modpack::first(), function ($modpack) use ($response) {
            $response->assertRedirect('/modpacks/iron-tanks');

            $this->assertFalse($modpack->is_published);
        });
    }

    /** @test */
    public function is_published_is_boolean()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->from('/')->post('/modpacks', $this->validParams([
            'is_published' => 'invalid',
        ]));

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('is_published');
        $this->assertEquals(0, Modpack::count());
    }

    /** @test */
    public function modpack_icon_is_uploaded_if_included()
    {
        Storage::fake();
        $user = factory(User::class)->create();
        $file = File::image('modpack-icon.png', 50, 50);

        $response = $this->actingAs($user)->post('/modpacks', $this->validParams([
            'modpack_icon' => $file,
        ]));

        tap(Modpack::first(), function ($modpack) use ($file) {
            $this->assertNotNull($modpack->icon_path);
            Storage::assertExists($modpack->icon_path);
            $this->assertFileEquals(
                $file->getPathname(),
                Storage::path($modpack->icon_path)
            );
        });
    }

    /** @test */
    public function modpack_icon_must_be_an_image()
    {
        Storage::fake('s3');
        $user = factory(User::class)->create();
        $file = File::create('not-an-icon.pdf');

        $response = $this->actingAs($user)->from('/dashboard')->post('/modpacks', $this->validParams([
            'modpack_icon' => $file,
        ]));

        $response->assertRedirect('/dashboard');
        $response->assertSessionHasErrors('modpack_icon');
        $this->assertEquals(0, Modpack::count());
    }

    /** @test */
    public function modpack_icon_must_be_at_least_50px_wide()
    {
        Storage::fake('s3');
        $user = factory(User::class)->create();
        $file = File::image('modpack_icon.png', 49, 49);

        $response = $this->actingAs($user)->from('/dashboard')->post('/modpacks', $this->validParams([
            'modpack_icon' => $file,
        ]));

        $response->assertRedirect('/dashboard');
        $response->assertSessionHasErrors('modpack_icon');
        $this->assertEquals(0, Modpack::count());
    }

    /** @test */
    public function modpack_icon_must_have_square_aspect_ratio()
    {
        Storage::fake('s3');
        $user = factory(User::class)->create();
        $file = File::image('poster.png', 100, 101);

        $response = $this->actingAs($user)->from('/dashboard')->post('/modpacks', $this->validParams([
            'modpack_icon' => $file,
        ]));

        $response->assertRedirect('/dashboard');
        $response->assertSessionHasErrors('modpack_icon');
        $this->assertEquals(0, Modpack::count());
    }

    /** @test */
    public function modpack_icon_is_optional()
    {
        $this->withoutExceptionHandling();
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->post('/modpacks', $this->validParams([
            'modpack_icon' => null,
        ]));

        tap(Modpack::first(), function ($modpack) use ($response) {
            $response->assertRedirect('/modpacks/iron-tanks');

            $this->assertNull($modpack->icon_path);
        });
    }
}
