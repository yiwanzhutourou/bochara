<?php

namespace App\Formatters;

use App\Models\MBook;

class DoubanFormatter {
    public static function bookDetail($book) {
        /** @var MBook $book */
        if (empty($book)) {
            return [];
        }
        return [
            'rating' => json_decode($book->rating),
            'subtitle' => $book->subtitle,
            'author' => json_decode($book->author),
            'pubdate' => $book->pub_date,
            'tags' => json_decode($book->tags),
            'origin_title' => $book->origin_title,
            'image' => $book->cover,
            'binding' => $book->binding,
            'translator' => json_decode($book->translator),
            'catalog' => $book->catalog,
            'pages' => $book->pages,
            'images' => json_decode($book->images),
            'alt' => $book->alt,
            'id' => $book->isbn,
            'publisher' => $book->publisher,
            'isbn10' => $book->isbn10,
            'isbn13' => $book->true_isbn,
            'title' => $book->title,
            'url' => $book->url,
            'alt_title' => $book->alt_title,
            'author_intro' => $book->author_intro,
            'summary' => $book->summary,
            'series' => json_decode($book->series),
            'price' => $book->price,
        ];
    }
}