<?php

namespace App\Formatters;

use App\Models\MArticle;
use App\Models\MAuthor;

class ArticleFormatter {
    public static function detail(MArticle $article, ?MAuthor $author) {
        return [
            'id'            => $article->id,
            'user'          => empty($author) ? [] : [
                'id'       => $author->id,
                'nickname' => $author->nickname,
                'avatar'   => $author->avatar,
            ],
            'title'         => $article->title,
            'content'       => $article->content,
            'picUrl'        => $article->pic_url,
            'createTime'    => $article->create_time,
            'readCount'     => $article->read_count,
        ];
    }
}