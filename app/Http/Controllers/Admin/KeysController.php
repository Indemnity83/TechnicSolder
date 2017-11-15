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

use App\Key;
use App\Http\Controllers\Controller;

class KeysController extends Controller
{
    /**
     * List all keys.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $this->authorize('index', Key::class);

        return view('settings.keys', [
            'keys' => Key::orderBy('name')->get(),
        ]);
    }

    /**
     * Store a posted key.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store()
    {
        $this->authorize('create', Key::class);

        Key::create([
            'token' => request()->token,
            'name' => request()->name,
        ]);

        return redirect('/settings/keys');
    }

    /**
     * Delete a key.
     *
     * @param Key $key
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function destroy(Key $key)
    {
        $key->delete();

        return redirect('/settings/keys');
    }
}
