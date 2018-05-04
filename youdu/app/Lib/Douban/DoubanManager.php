<?php

namespace App\Lib\Douban;

use App\Models\MBook;
use App\Utils\JsonUtils;

class DoubanManager {

    /**
     * 复制豆瓣信息到有读
     *
     * @param DoubanBook $doubanBook
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function copy($doubanBook) {
        $book = new MBook();
        $book->title = $doubanBook->title;
        $book->author = JsonUtils::json_stringify($doubanBook->author);
        $book->cover = $doubanBook->image;
        if ($doubanBook->images && $doubanBook->images->large) {
            $book->big_cover = $doubanBook->images->large;
        } else {
            $book->big_cover = '';
        }
        $book->publisher = $doubanBook->publisher;
        $book->true_isbn = empty($doubanBook->isbn13) ? 'fake_isbn' : $doubanBook->isbn13;
        $book->summary = $doubanBook->summary;
        $book->douban_average = 0;
        $book->douban_raters = 0;
        if (!empty($doubanBook->rating)) {
            if (is_numeric($doubanBook->rating->average)) {
                $book->douban_average = $doubanBook->rating->average;
            }
            if (is_numeric($doubanBook->rating->numRaters)) {
                $book->douban_raters = $doubanBook->rating->numRaters;
            }
        }
        $book->rating = JsonUtils::json_stringify($doubanBook->rating);
        $book->subtitle = $doubanBook->subtitle;
        $book->pub_date = $doubanBook->pubdate;
        $book->tags = JsonUtils::json_stringify($doubanBook->tags);
        $book->origin_title = $doubanBook->origin_title;
        $book->binding = $doubanBook->binding;
        $book->translator = JsonUtils::json_stringify($doubanBook->translator);
        $book->catalog = $doubanBook->catalog;
        $book->pages = $doubanBook->pages;
        $book->images = JsonUtils::json_stringify($doubanBook->images);
        $book->alt = $doubanBook->alt;
        $book->isbn10 = $doubanBook->isbn10;
        $book->url = $doubanBook->url;
        $book->alt_title = $doubanBook->alt_title;
        $book->author_intro = $doubanBook->author_intro;
        $book->series = isset($doubanBook->series) ? JsonUtils::json_stringify($doubanBook->series) : '';
        $book->price = $doubanBook->price;

        return MBook::updateOrCreate(['isbn' => $doubanBook->id],
            $book->attributesToArray());
    }
}