<?php

namespace App\Formatters;

use App\Models\MBook;

class BookFormatter {

    public static function simple(?MBook $book) {
        if ($book) {
            return [
                'isbn'      => $book->isbn,
                'title'     => $book->title,
                'author'    => self::parseAuthor($book->author),
                'cover'     => $book->cover,
                'publisher' => $book->publisher,
            ];
        }

        return null;
    }

    public static function parseAuthor($authorString) {
        if (empty($authorString)) {
            return '';
        }

        $authors = json_decode($authorString);
        $result = '';
        foreach ($authors as $author) {
            $result .= ($author . ' ');
        }
        if (strlen($result) > 0) {
            $result = substr($result, 0, strlen($result) - 1);
        }
        return $result;
    }
}