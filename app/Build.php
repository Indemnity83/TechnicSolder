<?php

/*
 * This file is part of Solder.
 *
 * (c) Kyle Klaus <kklaus@indemnity83.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Build extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Retrieve the first build matching the given modpack slug and build version.
     *
     * @param $modpack
     * @param $version
     * @return self
     */
    public static function findByModpackSlugAndBuildVersion($modpack, $version)
    {
        return self::where('version', $version)
            ->whereExists(function ($query) use ($modpack) {
                $query->select(DB::raw(1))
                    ->from('modpacks')
                    ->where('slug', $modpack)
                    ->whereRaw('builds.modpack_id = modpacks.id');
            })
            ->first();
    }

    /**
     * A Build contains many Releases of various Packages.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function releases()
    {
        return $this->belongsToMany(Release::class);
    }

    /**
     * A Build belongs to a Modpack.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function modpack()
    {
        return $this->belongsTo(Modpack::class);
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'version';
    }

    /**
     * Filter query results to public builds, private builds
     * that have been authorized with the provided client token
     * and all private builds with a valid provided api key.
     *
     * @param Builder $query
     * @param string|null $apiToken
     * @param string|null $clientToken
     *
     * @return Builder
     */
    public function scopeWhereToken($query, $apiToken, $clientToken)
    {
        return $query->where(function ($query) use ($apiToken, $clientToken) {
            $query->where('status', 'public')
                ->orWhere(function ($query) use ($apiToken, $clientToken) {
                    $query->where('status', 'private')
                        ->whereIn('modpack_id', function ($query) use ($clientToken) {
                            return $query->select('modpack_id')
                                ->from('client_modpack')
                                ->join('clients', 'client_modpack.client_id', '=', 'clients.id')
                                ->where('token', $clientToken);
                        });
                })
                ->orWhere(function ($query) use ($apiToken) {
                    $query->where('status', 'private')
                        ->whereExists(function ($query) use ($apiToken) {
                            $query->select(DB::raw(1))
                                ->from('keys')
                                ->where('token', $apiToken);
                        });
                });
        });
    }

    /**
     * Get created date formatted for humans.
     *
     * @return string
     */
    public function getCreatedAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get the most recent builds and associated modpacks.
     *
     * @param $count
     *
     * @return \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection|static[]
     */
    public static function recent($count)
    {
        return self::latest()->take($count)->with('modpack')->get();
    }
}
