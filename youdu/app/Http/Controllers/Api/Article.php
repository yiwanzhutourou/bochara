<?php

namespace App\Http\Controllers\Api;

use App\Formatters\ArticleFormatter;
use App\Http\Controllers\Api\Exceptions\Exception;
use App\Models\MArticle;

class Article extends ApiBase {

    /**
     * @param $id
     * @return array
     * @throws Exception
     */
    public function getArticle($id) {
        $article = MArticle::find($id);
        if (!$article || $article->status === MArticle::CARD_STATUS_DELETED) {
            throw new Exception(Exception::RESOURCE_NOT_FOUND, '文章不存在~');
        }

        $author = $article->author;
        // 增加一次浏览
        MArticle::where('id', '=', $article->id)
            ->increment('read_count');
        return ArticleFormatter::detail($article, $author);
    }
}