<?php

namespace App\Http\Controllers;

use App\Formatters\CardFormatter;
use App\Http\Controllers\Api\Exceptions\Exception;
use App\Http\Controllers\Api\Lib\Visitor;
use App\Models\MCard;
use App\Models\MCardApproval;
use App\Utils\ErrorUtils;
use Illuminate\Http\Request;

class CardController extends Controller {

    public function __invoke(Request $request, string $action = 'index') {
        if ($action > 0) {
            return $this->getById($action);
        } else if (!method_exists($this, $action)) {
            $action = 'index';
        }
        return $this->{$action}($request);
    }

    public function getById($id) {
        $card = MCard::find($id);
        if (!$card || $card->status === MCard::CARD_STATUS_DELETED) {
            return ErrorUtils::errorResponse('读书卡片不存在',
                Exception::RESOURCE_NOT_FOUND);
        }

        $cardUser = $card->user;
        $book = $card->book;
        $approvals = $card->approvals()
            ->orderByDesc('create_time')
            ->get();

        // has approved
        $hasApproved = false;
        $user = Visitor::instance()->getUser();
        if ($user) {
            $hasApproved = MCardApproval::where('user_id', '=', $user->id)
                    ->where('card_id', '=', $card->id)
                    ->count() > 0;
        }

        // 增加一次浏览
        MCard::where('id', '=', $card->id)
            ->increment('read_count');
        return CardFormatter::detail($card, $cardUser,
            $book, $approvals, $hasApproved);
    }

    public function index(Request $request) {
        $id = $request->input('id');
        if (!$id) {
            return ErrorUtils::errorResponse('缺少参数 isbn');
        }
        return $this->getById($id);
    }
}