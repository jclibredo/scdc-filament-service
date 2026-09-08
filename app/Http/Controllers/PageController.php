<?php

namespace App\Http\Controllers;

use App\Models\AboutUs;
use App\Models\Asset;
use App\Models\Event;
use App\Models\Expertise;
use App\Models\Home;
use App\Models\Project;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index()
    {
        return view('webpage', [
            'home'     => Home::first() ?? new Home(),
            'projects' => Project::latest()->get(),
            'events'   => Event::latest()->get(),
            'assets'   => Asset::latest()->get(),
            'about'    => AboutUs::first(),
            'expertises' => Expertise::where('status', true)->latest()->get(),
        ]);
    }
}
