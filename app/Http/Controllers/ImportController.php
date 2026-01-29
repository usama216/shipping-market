<?php

namespace App\Http\Controllers;

use App\Imports\UsersImport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Redirect;

class ImportController extends Controller
{
    public function index()
    {
        return Inertia::render('Import/Import');
    }

    public function importUsers(Request $request)
    {
        ini_set('memory_limit', -1);
        Excel::queueImport(new UsersImport, $request->file('file'));
        return Redirect::route('admin.import')->with('status', 'Import file successfully.');
    }
}
