<?php

namespace App\Formatters;

use App\Models\Book;

class DoubanFormatter {
    public static function bookDetail(Book $book) {
        return [
            'rating' => json_decode($book->rating),
            'subtitle' => $book->subtitle,
            'author' => json_decode($book->author),
            'pubdate' => $book->pubDate,
            'tags' => json_decode($book->tags),
            'origin_title' => $book->originTitle,
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
            'isbn13' => $book->trueIsbn,
            'title' => $book->title,
            'url' => $book->url,
            'alt_title' => $book->altTitle,
            'author_intro' => $book->authorIntro,
            'summary' => $book->summary,
            'series' => json_decode($book->series),
            'price' => $book->price,
        ];
    }
}