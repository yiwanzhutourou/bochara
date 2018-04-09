<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MobileController extends Controller {

    public function __invoke(Request $request, string $action = 'index') {
        if (!method_exists($this, $action)) {
            $action = 'index';
        }
        return $this->{$action}($request);
    }

    public function index(Request $request) {
        return view('m/index');
    }
}