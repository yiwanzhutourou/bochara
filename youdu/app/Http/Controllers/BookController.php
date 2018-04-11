<?php

namespace App\Http\Controllers;

use App\Formatters\DoubanFormatter;
use App\Models\MBook;
use App\Utils\ErrorUtils;
use Illuminate\Http\Request;

class BookController extends Controller {

    public function __invoke(Request $request, string $action = 'index') {
        if ($action > 0) {
            return $this->getByIsbn($action);
        } else if (!method_exists($this, $action)) {
            $action = 'index';
        }
        return $this->{$action}($request);
    }

    public function getByIsbn($isbn) {
        $book = MBook::find($isbn);
        return response()->json(DoubanFormatter::bookDetail($book));
    }

    public function index(Request $request) {
        $isbn = $request->input('isbn');
        if (!$isbn) {
            return ErrorUtils::errorResponse('缺少参数 isbn');
        }
        $book = MBook::find($isbn);
        return response()->json(DoubanFormatter::bookDetail($book));
    }
}