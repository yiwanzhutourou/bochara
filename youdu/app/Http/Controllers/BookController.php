<?php

namespace App\Http\Controllers;

use App\Formatters\DoubanFormatter;
use App\Models\Book;
use App\Utils\ErrorUtils;
use Illuminate\Http\Request;

class BookController extends Controller {

    public function __invoke(Request $request, string $action = 'index') {
        if (!method_exists($this, $action)) {
            $action = 'index';
        }
        return $this->{$action}($request);
    }

    public function index(Request $request) {
        $isbn = $request->input('isbn');
        if (!$isbn) {
            return ErrorUtils::invalidParameterResponseGenerator('缺少参数 isbn');
        }
        $book = Book::find($isbn);
        return response()->json(DoubanFormatter::bookDetail($book));
    }
}