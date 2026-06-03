<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Website;
use Illuminate\Support\Str;

class WebsiteController extends Controller
{
    public function store(Request $request)
    {
        
        $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'required|string|max:255',
        ]);

       
        $website = Website::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $request->name,
            'domain' => $request->domain,
            'embed_token' => Str::random(32),
        ]);

        
        return response()->json([
            'message' => 'Website created successfully',
            'website' => $website
        ]);
    }
}
