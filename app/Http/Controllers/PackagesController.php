<?php

/*
 * This file is part of Solder.
 *
 * (c) Kyle Klaus <kklaus@indemnity83.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Http\Controllers;

use App\Package;
use Illuminate\Validation\Rule;

class PackagesController extends Controller
{
    /**
     * Display all packages.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view('packages.index', [
            'packages' => Package::orderBy('name')->get(),
        ]);
    }

    /**
     * Show details of a specific package and its releases.
     *
     * @param Package $package
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Package $package)
    {
        $package->load(['releases' => function ($query) {
            $query->orderBy('version', 'desc');
        }]);

        return view('packages.show', [
            'package' => $package,
            'packages' => Package::orderBy('name')->get(),
        ]);
    }

    /**
     * Store the posted package.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store()
    {
        $this->authorize('create', Package::class);

        request()->validate([
            'name' => ['required'],
            'slug' => ['required', Rule::unique('packages')],
            'website_url' => ['nullable', 'url'],
            'donation_url' => ['nullable', 'url'],
        ]);

        $package = Package::create(request()->only([
            'name',
            'slug',
            'author',
            'website_url',
            'donation_url',
            'description',
        ]));

        return redirect('library/'.$package->slug);
    }

    public function update(Package $package)
    {
        $this->authorize('update', $package);

        request()->validate([
           'name' => ['sometimes', 'required'],
           'slug' => ['sometimes', 'required', 'alpha_dash', Rule::unique('packages')->ignore($package->id)],
        ]);

        $package->update(request()->only([
            'name',
            'slug',
            'author',
            'website_url',
            'donation_url',
            'description',
        ]));

        return redirect('library/'.$package->slug);
    }

    public function destroy(Package $package)
    {
        $this->authorize('delete', $package);

        $package->delete();

        return redirect('library');
    }
}
