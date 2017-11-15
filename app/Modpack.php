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
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

class Modpack extends Model
{
    protected $guarded = [];

    /**
     * The "booting" method of the model.
     */
    public static function boot()
    {
        parent::boot();

        self::deleting(function ($modpack) {
            if ($modpack->icon_path != null) {
                Storage::delete($modpack->icon_path);
            }

            $modpack->builds->each->delete();
        });
    }

    /**
     * A Modpack has many builds.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function builds()
    {
        return $this->hasMany(Build::class);
    }

    /**
     * A Modpack has many authorized clients.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function clients()
    {
        return $this->belongsToMany(Client::class);
    }

    /**
     * A Modpack has many collaborators.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function collaborators()
    {
        return $this->hasMany(Collaborator::class);
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Filter query results to public modpack, private modpacks
     * that have been authorized with the provided client token
     * and all private modpacks with a valid provided api key.
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
            /* @var Builder $query */
            $query->where('status', 'public')
                ->orWhere(function ($query) use ($apiToken, $clientToken) {
                    $query->where('status', 'private')
                        ->whereIn('id', function ($query) use ($clientToken) {
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
     * Get the promoted build for the Modpack.
     *
     * @return mixed
     */
    public function getRecommendedBuildAttribute()
    {
        return optional(Build::where('id', $this->recommended_build_id)->first());
    }

    /**
     * Get the latest build for the Modpack.
     *
     * @return mixed
     */
    public function getLatestBuildAttribute()
    {
        return optional(Build::where('id', $this->latest_build_id)->first());
    }

    /**
     * Get the two letter Monogram of the Modpack.
     *
     * @return string
     */
    public function getMonogramAttribute()
    {
        return substr($this->name, 0, 2);
    }

    /**
     * Get the modpack icon url.
     *
     * @return string
     */
    public function getIconUrlAttribute()
    {
        return Storage::url($this->icon_path);
    }

    public function addCollaborator($userId)
    {
        return Collaborator::create([
            'user_id' => $userId,
            'modpack_id' => $this->id,
        ]);
    }

    public function userIsCollaborator($user)
    {
        return Collaborator::where('modpack_id', $this->id)
            ->where('user_id', $user->id)
            ->exists();
    }
}
