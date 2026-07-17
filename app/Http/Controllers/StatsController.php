<?php

namespace App\Http\Controllers;

class StatsController extends Controller
{
    /**
     * Database Statistics — placeholder for now.
     * Real metrics/charts wired in a later pass.
     */
    public function database()
    {
        return view('stats.database');
    }
}
