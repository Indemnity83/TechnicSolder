<?php

/*
 * This file is part of Solder.
 *
 * (c) Kyle Klaus <kklaus@indemnity83.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Browser;

use App\User;
use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class LoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    /** @test */
    public function logging_in_successfully()
    {
        factory(User::class)->create([
            'email' => 'jane@example.com',
            'password' => bcrypt('super-secret-password'),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'jane@example.com')
                ->type('password', 'super-secret-password')
                ->press('Log in')
                ->assertPathIs('/')
                ->clickLink(' Log Out ');
        });
    }

    /** @test */
    public function logging_in_successfully_from_previous_page()
    {
        factory(User::class)->create([
            'email' => 'jane@example.com',
            'password' => bcrypt('super-secret-password'),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/library')
                ->assertPathIs('/login')
                ->type('email', 'jane@example.com')
                ->type('password', 'super-secret-password')
                ->press('Log in')
                ->assertPathIs('/library');
        });
    }

    /** @test */
    public function logging_in_with_invalid_credentials()
    {
        factory(User::class)->create([
            'email' => 'jane@example.com',
            'password' => bcrypt('super-secret-password'),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('email', 'jane@example.com')
                ->type('password', 'wrong-password')
                ->press('Log in')
                ->assertPathIs('/login')
                ->assertInputValue('email', 'jane@example.com')
                ->assertSee('credentials do not match');
        });
    }
}
