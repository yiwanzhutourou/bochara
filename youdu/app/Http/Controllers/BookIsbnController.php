<?php

namespace App\Http\Controllers;

use App\Formatters\DoubanFormatter;
use App\Http\Controllers\Api\Exceptions\Exception;
use App\Lib\Douban\DoubanManager;
use App\Lib\HttpClient\HttpClient;
use App\Models\MBook;
use App\Utils\ErrorUtils;
use Illuminate\Http\Request;

class BookIsbnController extends Controller {

    public function __invoke(Request $request, string $action = 'index') {
        if ($action > 0) {
            return $this->isbn($action);
        } else if (!method_exists($this, $action)) {
            $action = 'index';
        }
        return $this->{$action}($request);
    }

    public function isbn($isbn) {
        $book = MBook::where(['true_isbn' => $isbn])->first();
        if (!$book || !$book->price) {
            // check book in Douban
            $url = "https://api.douban.com/v2/book/isbn/{$isbn}";
            $response = HttpClient::get($url);
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
        return $this->isbn($isbn);
    }
}