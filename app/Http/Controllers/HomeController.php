<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $userName = Auth::user()->name;
        $createTasks = Auth::user()->tasks->count();
        $assignedTasks = Auth::user()->assignedTasks->count();
        $completeTask = Auth::user()->tasks->where('status', 'completed')->count();
        $isAdmin = Auth::user()->isAdmin();
        $isMAnager =  Auth::user()->isManager();
        // $toArrayval = Auth::user()->tasks; 
        // echo "<pre>";
        // print_r($toArrayval->toArray());
        // exit;
        return view('home', [
            'userName' => $userName,
            'createTasks' => $createTasks,
            'assignedTasks' => $assignedTasks,
            'completeTask' => $completeTask,
            'isAdmin' => $isAdmin,
            'isMAnager' => $isMAnager
        ]);
    }
}
