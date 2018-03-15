<?php

/*
 * This file is part of Solder.
 *
 * (c) Kyle Klaus <kklaus@indemnity83.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Platform;

use Illuminate\Database\Eloquent\Model;

class Key extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Determine if the given token is valid.
     *
     * @param string $token
     *
     * @return bool
     */
    public static function isValid($token)
    {
        return self::where('token', $token)->exists();
    }
}
