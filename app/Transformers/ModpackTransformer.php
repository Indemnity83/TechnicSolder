<?php

namespace App\Transformers;

use App\Modpack;
use League\Fractal\TransformerAbstract;

class ModpackTransformer extends TransformerAbstract
{
    protected $availableIncludes = [
        'builds',
        'icon',
        'logo',
        'background',
        'promoted',
        'latest',
    ];

    public function transform(Modpack $modpack)
    {
        return [
            'name' => $modpack->name,
            'id' => $modpack->getRouteKey(),
            'published_at' => $modpack->published_at->format('c'),
            'created_at' => $modpack->created_at->format('c'),
            'updated_at' => $modpack->updated_at->format('c'),
        ];
    }

    public function includeLatest(Modpack $modpack)
    {
        if (empty($modpack->latest)) {
            return $this->null();
        }

        return $this->item($modpack->latest, new VersionTransformer(), 'version');
    }

    public function includePromoted(Modpack $modpack)
    {
        if (empty($modpack->promoted)) {
            return $this->null();
        }

        return $this->item($modpack->promoted, new VersionTransformer(), 'version');
    }

    public function includeBuilds(Modpack $modpack)
    {
    }

    public function includeIcon(Modpack $modpack)
    {
    }

    public function includeLogo(Modpack $modpack)
    {
    }

    public function includeBackground(Modpack $modpack)
    {
    }
}
