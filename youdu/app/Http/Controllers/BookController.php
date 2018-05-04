<?php

namespace App\Http\Controllers;

use App\Formatters\DoubanFormatter;
use App\Http\Controllers\Api\Exceptions\Exception;
use App\Lib\Douban\DoubanManager;
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
        if (!$book || empty($book->price)) {
            // check book in Douban
            $url = "https://api.douban.com/v2/book/{$isbn}";
            $response = file_get_contents($url);
            $doubanBook = json_decode($response);
            if ($doubanBook === null || empty($doubanBook->id)) {
                return ErrorUtils::errorResponse('无法获取图书信息',
                    Exception::RESOURCE_NOT_FOUND);
            } else {
                $book = DoubanManager::copy($doubanBook);
            }
        }
        return response()->json(DoubanFormatter::bookDetail($book));
    }

    public function index(Request $request) {
        $isbn = $request->input('isbn');
        if (!$isbn) {
            return ErrorUtils::errorResponse('缺少参数 isbn');
        }
        return $this->getByIsbn($isbn);
    }

    // start 这里指 page
    public function search(Request $request) {
        $q = $request->input('q');
        if (!$q) {
            return ErrorUtils::errorResponse('缺少参数 q');
        }
        $count = $request->input('count') ?? 20;
        $start = $request->input('start') ?? 0;
        $url = "https://api.douban.com/v2/book/search?"
            . http_build_query([
                'q'     => $q,
                'start' => $count * $start,
                'count' => $count
            ]);
        $response = file_get_contents($url);
        $json = json_decode($response);
        return response()->json($json);
    }
}