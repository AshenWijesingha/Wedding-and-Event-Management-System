<?php

namespace App\Http\Controllers;

use App\Models\Package;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::active()->orderBy('base_price')->get();

        return view('packages.index', compact('packages'));
    }
}
