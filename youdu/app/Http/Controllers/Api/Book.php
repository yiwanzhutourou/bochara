<?php

namespace App\Http\Controllers\Api;

use App\Formatters\BookFormatter;
use App\Http\Controllers\Api\Exceptions\Exception;
use App\Http\Controllers\Api\Lib\Visitor;
use App\Models\MBorrowRequest;
use App\Models\MUser;
use App\Models\MUserBook;
use Illuminate\Support\Facades\DB;

class Book extends ApiBase {

    /**
     * @param $bookIsbns
     * @return array
     * @throws Exceptions\Exception
     */
    public function checkAdded($bookIsbns) {
        $user = Visitor::instance()->checkAuth();
        $isbnList = json_decode($bookIsbns);
        $added = DB::table('bocha_user_book')
            ->select('isbn')
            ->where(['user_id' => $user->id])
            ->whereIn('isbn', $isbnList)
            ->get();
        $userIsbnList = [];
        foreach ($added as $item) {
            $userIsbnList[] = $item->isbn;
        }

        $resultList = [];
        if (!empty($isbnList)) {
            foreach ($isbnList as $isbn) {
                $resultList[] = [
                    'isbn'  => $isbn,
                    'added' => in_array($isbn, $userIsbnList),
                ];
            }
        }

        return $resultList;
    }

    /**
     * @param $userId
     * @return array
     * @throws Exception
     */
    public function getBorrowPageData($userId) {
        $user = MUser::find($userId);
        if (!$user) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, '用户不存在');
        }

        $borrowBooks = $user->borrowBooks()->get();
        $formattedBooks = [];
        foreach ($borrowBooks as $borrowBook) {
            $formattedBooks[] = [
                'isbn'      => $borrowBook->isbn,
                'title'     => $borrowBook->title,
                'author'    => json_decode($borrowBook->author),
                'cover'     => $borrowBook->cover,
                'publisher' => $borrowBook->publisher,
                'leftCount' => $borrowBook->left_count,
            ];
        }

        return [
            'nickname' => $user->nickname,
            'avatar'   => $user->avatar,
            'books'    => $formattedBooks,
        ];
    }

    /**
     * @param $to
     * @param $isbn
     * @return string
     * @throws Exception
     */
    public function borrow($to, $isbn) {
        $self = Visitor::instance()->checkAuth();
        if (intval($to) === $self->id) {
            throw new Exception(Exception::BAD_REQUEST , '不可以借自己的书哦~');
        }

        $userBook = MUserBook::where([
            'user_id' => $to,
            'isbn'    => $isbn,
        ])->first();

        // 判断图书是否存在,是否是闲置图书,是否还有库存
        if (!$userBook) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, 'TA的书房里没有这本书');
        }

        if ($userBook->can_be_borrowed === MUserBook::BOOK_CANNOT_BE_BORROWED) {
            throw new Exception(Exception::BAD_REQUEST, '这本书似乎不是闲置图书哦');
        }

        if ($userBook->left_count <= 0) {
            throw new Exception(Exception::BAD_REQUEST, '这本书似乎已经被借出去了');
        }

        $request = MBorrowRequest::create([
            'from_user'   => $self->id,
            'to_user'     => $to,
            'book_isbn'   => $isbn,
            'create_time' => time(),
            'status'      => MBorrowRequest::BORROW_STATUS_NORMAL,
        ]);

        if ($request) {
            return 'ok';
        }
        throw new Exception(Exception::INTERNAL_ERROR, '服务器繁忙，请稍后再试');
    }

    /**
     * @param $id
     * @param $from
     * @param $isbn
     * @return string
     * @throws Exception
     */
    public function accept($id, $from, $isbn) {
        $self = Visitor::instance()->checkAuth();
        $selfId = $self->id;

        // 不可能发生,简单防一下
        if ($from === $selfId) {
            throw new Exception(Exception::BAD_REQUEST , '不可以处理自己的请求哦~');
        }

        $borrowRequest = MBorrowRequest::find($id);
        if (!$borrowRequest) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, '借阅请求不存在');
        }

        if ($borrowRequest->from_user !== intval($from)
            || $borrowRequest->to_user !== $selfId
            || $borrowRequest->book_isbn !== $isbn) {
            throw new Exception(Exception::BAD_REQUEST , '请求数据不正确~');
        }

        // 书不存在或者库存小于1,可能是用户删除了图书或者把书借给了其他人
        // 直接把状态设置为 BORROW_STATUS_DECLINED
        $userBook = MUserBook::where(['user_id' => $selfId, 'isbn' => $isbn])->first();
        if (!$userBook || $userBook->left_count <= 0) {
            $borrowRequest->status = MBorrowRequest::BORROW_STATUS_DECLINED;
            $borrowRequest->update();
            throw new Exception(Exception::INTERNAL_ERROR, '你的书被删除或者借出去了，不可以完成这个操作~');
        }

        $borrowRequest->status = MBorrowRequest::BORROW_STATUS_ACCEPTED;

        if ($borrowRequest->update()) {
            // 书借出去了,库存减一
            $userBook->decrement('left_count');
        }

        return 'ok';
    }

    /**
     * @param $id
     * @param $from
     * @param $isbn
     * @return string
     * @throws Exception
     */
    public function decline($id, $from, $isbn) {
        $self = Visitor::instance()->checkAuth();
        $selfId = $self->id;

        // 不可能发生,简单防一下
        if ($from === $selfId) {
            throw new Exception(Exception::BAD_REQUEST , '不可以处理自己的请求哦~');
        }

        $borrowRequest = MBorrowRequest::find($id);
        if (!$borrowRequest) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, '借阅请求不存在');
        }

        if ($borrowRequest->from_user !== intval($from)
            || $borrowRequest->to_user !== $selfId
            || $borrowRequest->book_isbn !== $isbn) {
            throw new Exception(Exception::BAD_REQUEST , '请求数据不正确~');
        }

        $borrowRequest->status = MBorrowRequest::BORROW_STATUS_DECLINED;
        $borrowRequest->update();

        return 'ok';
    }

    /**
     * @param $id
     * @param $from
     * @param $isbn
     * @return string
     * @throws Exception
     */
    public function markBookReturn($id, $from, $isbn) {
        $self = Visitor::instance()->checkAuth();
        $selfId = $self->id;

        // 不可能发生,简单防一下
        if ($from === $selfId) {
            throw new Exception(Exception::BAD_REQUEST , '不可以处理自己的请求哦~');
        }

        $borrowRequest = MBorrowRequest::find($id);
        if (!$borrowRequest) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, '借阅请求不存在');
        }

        if ($borrowRequest->from_user !== intval($from)
            || $borrowRequest->to_user !== $selfId
            || $borrowRequest->book_isbn !== $isbn) {
            throw new Exception(Exception::BAD_REQUEST , '请求数据不正确~');
        }

        $borrowRequest->status = MBorrowRequest::BORROW_STATUS_RETURNED;
        if ($borrowRequest->update()) {
            // 书还回来了,库存加一
            $userBook = MUserBook::where(['user_id' => $selfId, 'isbn' => $isbn])->first();
            if ($userBook && $userBook->left_count === 0) {
                $userBook->increment('left_count');
            }
        }

        return 'ok';
    }

    /**
     * @param int $flag
     * @return array
     * @throws Exception
     */
    public function getMyBorrowRequests($flag = 0) {
        $user = Visitor::instance()->checkAuth();

        $status = intval($flag);
        if ($status < MBorrowRequest::BORROW_STATUS_NORMAL
                || $status > MBorrowRequest::BORROW_STATUS_RETURNED) {
            throw new Exception(Exception::BAD_REQUEST, '无法获取数据');
        }

        $requests = $user->newBorrowRequests($status)->get();
        $result = [];
        foreach ($requests as $request) {
            $result[] = [
                'id'        => $request->request_id,
                'user'      => [
                    'id'       => $request->user_id,
                    'nickname' => $request->nickname,
                    'avatar'   => $request->avatar
                ],
                'book'      => [
                    'isbn'      => $request->isbn,
                    'title'     => $request->title,
                    'author'    => BookFormatter::parseAuthor($request->author),
                    'cover'     => $request->cover,
                    'publisher' => $request->publisher,
                ],
                'timestamp' => $request->create_time,
                'status'    => $request->status,
            ];
        }
        return $result;
    }

    /**
     * @param int $flag
     * @return array
     * @throws Exception
     */
    public function getMyOutBorrowRequests($flag = 0) {
        $user = Visitor::instance()->checkAuth();

        $status = intval($flag);
        if ($status < MBorrowRequest::BORROW_STATUS_NORMAL
            || $status > MBorrowRequest::BORROW_STATUS_RETURNED) {
            throw new Exception(Exception::BAD_REQUEST, '无法获取数据');
        }

        $requests = $user->newBorrowRequests($status, true)->get();
        $result = [];
        foreach ($requests as $request) {
            $result[] = [
                'id'        => $request->request_id,
                'user'      => [
                    'id'       => $request->user_id,
                    'nickname' => $request->nickname,
                    'avatar'   => $request->avatar
                ],
                'book'      => [
                    'isbn'      => $request->isbn,
                    'title'     => $request->title,
                    'author'    => BookFormatter::parseAuthor($request->author),
                    'cover'     => $request->cover,
                    'publisher' => $request->publisher,
                ],
                'timestamp' => $request->create_time,
                'status'    => $request->status,
            ];
        }
        return $result;
    }
}