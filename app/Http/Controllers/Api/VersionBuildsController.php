<?php

namespace App\Http\Controllers\Api;

use App\Version;
use Illuminate\Http\Request;
use App\Transformers\BuildTransformer;

class VersionBuildsController extends ApiController
{
    /**
     * Display a listing of the builds for a modpack.
     *
     * @param Request $request
     * @param Version $version
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function index(Request $request, Version $version)
    {
        $builds = $version->builds;

        $include = $request->input('include');

        return $this
            ->collection($builds, new BuildTransformer(), 'build')
            ->include($include)
            ->response();
    }
}