<?php

namespace App\Http\Controllers\Api;

use App\Formatters\CardFormatter;
use App\Http\Controllers\Api\Exceptions\Exception;
use App\Http\Controllers\Api\Lib\Visitor;
use App\Models\MCard;
use App\Models\MCardApproval;

class Card extends ApiBase {

    /**
     * @param $cardId
     * @return array
     * @throws Exception
     */
    public function getCardById($cardId) {
        $card = MCard::find($cardId);
        if (!$card || $card->status === MCard::CARD_STATUS_DELETED) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, '读书卡片不存在~');
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
}