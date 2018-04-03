<?php

/*
 * This file is part of Solder.
 *
 * (c) Kyle Klaus <kklaus@indemnity83.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Policies;

use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Check for authorization before the intended policy method is actually called.
     *
     * @param \App\User $user
     *
     * @param $ability
     * @return bool
     */
    public function before($user, $ability)
    {
        if ($user->is_admin && $ability != 'delete') {
            return true;
        }
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->roles()->where('tag', 'manage-users')->exists()
            && request('is_admin') != 'on';
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function update(User $user)
    {
        return $user->roles()->where('tag', 'manage-users')->exists()
            && request('is_admin') === false;
    }

    /**
     * Determine whether the user can manage models.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function manage(User $user)
    {
        return $user->roles()->where('tag', 'manage-users')->exists();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\User  $user
     * @param  \App\User  $model
     * @return mixed
     */
    public function delete(User $user, User $model)
    {
        if ($user->is_admin && $user->isNot($model)) {
            return true;
        }

        return $user->roles()->where('tag', 'manage-users')->exists()
            && $user->isNot($model)
            && ! $model->is_admin;
    }
}
