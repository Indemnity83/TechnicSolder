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

use App\User;
use App\Modpack;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class LegacyModpackController extends Controller
{
    protected $user;

    /**
     * LegacyModpackController constructor.
     *
     * @internal param $user
     */
    public function __construct()
    {
        $this->user = User::findByToken(request()->query('cid'));
    }

    /**
     * Display a listing of modpacks.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $modpacks = Modpack::whereStatus(['authorized', 'public'], $this->user)
            ->with(['builds' => function ($query) {
                $query->whereStatus(['public', 'authorized'], $this->user);
            }])
            ->get();

        return response()->json([
            'modpacks' => $this->transformModpacks($modpacks),
            'mirror_url' => 'http://solder.example.com/files/',
        ]);
    }

    /**
     * Display the specified modpack.
     *
     * @param $modpackSlug
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($modpackSlug)
    {
        try {
            $modpack = Modpack::whereSlug($modpackSlug)
                ->whereStatus(['authorized', 'public', 'unlisted'], $this->user)
                ->with(['builds' => function ($query) {
                    $query->whereStatus(['public', 'authorized'], $this->user);
                }])
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->notFoundError('Modpack does not exist');
        }

        return response()->json($modpack);
    }

    /**
     * Transform build into response format.
     *
     * @param $modpacks
     *
     * @return mixed
     */
    private function transformModpacks($modpacks)
    {
        return $modpacks->keyBy('slug')->transform(function ($modpack) {
            if (request()->query('include') == 'full') {
                return $modpack;
            }

            return $modpack->name;
        })->all();
    }
}