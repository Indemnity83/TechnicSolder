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

use Illuminate\Database\Eloquent\Model;

class Version extends Model
{
    protected $guarded = [];

    /**
     * Belongs to a resource.
     */
    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }
}
