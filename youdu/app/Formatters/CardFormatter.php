<?php

namespace App\Formatters;

use App\Http\Controllers\Api\Lib\Visitor;
use App\Models\MBook;
use App\Models\MCard;
use App\Models\MCardApproval;
use App\Models\MUser;
use App\Utils\ImageUtils;

class CardFormatter {
    public static function detail(MCard $card, ?MUser $user,
                                  ?MBook $book, $approvals,
                                  $hasApproved) {
        // approval list
        $approvalList = [];
        if ($approvals) {
            foreach ($approvals as $approval) {
                /** @var MCardApproval $approval */
                if ($approval) {
                    $approvalList[] = [
                        'id'     => $approval->user_id,
                        'avatar' => $approval->user_avatar,
                    ];
                }
            }
        }
        $approvalCount = count($approvalList);

        return [
            'id'            => $card->id,
            'user'          => UserFormatter::simple($user),
            'title'         => $card->title,
            'content'       => $card->content,
            'picUrl'        => ImageUtils::getOriginalImgUrl($card->pic_url),
            'book'          => BookFormatter::simple($book),
            'createTime'    => $card->create_time,
            'isMe'          => Visitor::instance()->isMe($card->user_id),
            'hasApproved'   => $hasApproved,
            'approvalList'  => $approvalList,
            'approvalCount' => $approvalCount,
            'readCount'     => $card->read_count,
            'showBottom'    => true,
        ];
    }
}