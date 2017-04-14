<?php

/*
 * This file is part of Solder.
 *
 * (c) Kyle Klaus <kklaus@indemnity83.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Http\Controllers\Api;

use App\Modpack;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Access\AuthorizationException;

class ModpackController extends Controller
{
    /**
     * ResourceController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api')->except('index', 'show');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (\Auth::guest()) {
            $modpacks = Modpack::whereStatus('public')->get();
        }

        if (\Auth::check()) {
            $modpacks = Modpack::all();
        }

        return response()->json([
            'data' => $modpacks->map(function ($modpack) {
                return $this->transformModpack($modpack);
            }),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        if ($request->has('data.id')) {
            abort(403, 'Unsupported request to create a resource with a client-generated ID.');
        }

        if ($request->input('data.type') != 'modpack') {
            abort(409, 'The resource objects type is not supported for the collection represented by this endpoint.');
        }

        $this->validate($request, [
            'data.attributes.name' => ['required'],
            'data.attributes.slug' => [Rule::unique('modpacks', 'slug')],
        ]);

        $modpack = Modpack::create([
            'name' => $request->input('data.attributes.name'),
            'slug' => $request->input('data.attributes.slug'),
            'status_as_string' => $request->input('data.attributes.status'),
        ]);

        return response()->json([
            'data' => $this->transformModpack($modpack),
        ], 201)->withHeaders([
            'Location' => $modpack->link_self,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Modpack $modpack
     *
     * @return \Illuminate\Http\Response
     * @throws AuthorizationException
     */
    public function show(Modpack $modpack)
    {
        if (\Auth::guest() && $modpack->status == Modpack::STATUS_PRIVATE) {
            throw new AuthorizationException();
        }

        return response()->json([
            'data' => $this->transformModpack($modpack),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Modpack  $modpack
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Modpack $modpack)
    {
        if ($request->input('data.type') != 'modpack') {
            abort(409, 'The resource objects type is not supported for the collection represented by this endpoint.');
        }

        if ($request->input('data.id') != $modpack->id) {
            abort(409, 'The submitted resource id does not match the resource id of this endpoint.');
        }

        $this->validate($request, [
            'data.attributes.name' => ['filled'],
            'data.attributes.slug' => [Rule::unique('modpacks', 'slug')->ignore($modpack->id)],
        ]);

        $modpack->name = $request->input('data.attributes.name', $modpack->name);
        $modpack->slug = $request->input('data.attributes.slug', $modpack->slug);
        $modpack->status_as_string = $request->input('data.attributes.status', $modpack->status_as_string);
        $modpack->save();

        return response()->json([
            'data' => $this->transformModpack($modpack),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Modpack  $modpack
     * @return \Illuminate\Http\Response
     */
    public function destroy(Modpack $modpack)
    {
        $modpack->delete();

        return response()->json([], 204);
    }

    /**
     * @param $modpack
     *
     * @return array
     */
    private function transformModpack($modpack)
    {
        return [
            'type' => 'modpack',
            'id' => $modpack->id,
            'attributes' => [
                'name' => $modpack->name,
                'slug' => $modpack->slug,
                'status' => $modpack->status_as_string,
            ],
            'links' => [
                'self' => $modpack->link_self,
            ],
            'relationships' => [
                'builds' => [
                    'links' => [
                        'related' => [
                            'href' => $modpack->link_self.'/builds',
                            'meta' => ['count' => $modpack->builds_count],
                        ],
                    ],
                ],
            ],
        ];
    }
}
