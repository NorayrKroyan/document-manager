<?php

namespace App\Http\Controllers\DocumentManager;

use App\Http\Controllers\Controller;

class DocumentManagerController extends Controller
{
    public function index()
    {
        return view('document-manager.index');
    }
}
