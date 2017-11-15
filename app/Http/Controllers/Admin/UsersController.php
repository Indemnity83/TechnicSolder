<?php

/*
 * This file is part of Solder.
 *
 * (c) Kyle Klaus <kklaus@indemnity83.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Http\Controllers\Admin;

use App\User;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class UsersController extends Controller
{
    /**
     * Browse all users.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('settings.users', [
            'users' => User::orderBy('username')->get(),
        ]);
    }

    /**
     * Store a new user in the system.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store()
    {
        request()->validate([
            'username' => ['required', 'unique:users'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'min:6'],
        ]);

        User::create([
            'username' => request('username'),
            'email' => request('email'),
            'password' => bcrypt(request('password')),
            'is_admin' => request('is_admin') == 'on',
        ]);

        return redirect('/settings/users');
    }

    /**
     * Update a users details.
     *
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(User $user)
    {
        $data = request()->validate([
            'username' => ['required', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
        ]);

        if (request('password') != '') {
            request()->validate([
                'password' => ['min:6'],
            ]);

            $data['password'] = bcrypt(request('password'));
        }

        $data['is_admin'] = request('is_admin') == 'on';

        $user->update($data);

        return redirect('/settings/users/');
    }

    /**
     * Delete a user.
     *
     * @param User $user
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Symfony\Component\HttpFoundation\Response
     */
    public function destroy(User $user)
    {
        if (Auth::user()->is($user)) {
            return response('You may not remove your own user.', 403);
        }

        $user->delete();

        return redirect('/settings/users');
    }
}
