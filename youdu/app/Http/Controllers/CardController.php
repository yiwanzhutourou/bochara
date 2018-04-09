<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Utils\ErrorUtils;
use Illuminate\Http\Request;

class CardController extends Controller {

    public function __invoke(Request $request, string $action = 'index') {
        if (!method_exists($this, $action)) {
            $action = 'index';
        }
        return $this->{$action}($request);
    }

    public function index(Request $request) {
        $id = $request->input('id');
        if (!$id) {
            return ErrorUtils::invalidParameterResponseGenerator('缺少参数 isbn');
        }
        $card = Card::find($id);
        return response()->json([
            'title'   => $card->title,
            'content' => $card->content,
                                ]);
    }
}