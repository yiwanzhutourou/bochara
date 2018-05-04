<?php

namespace App\Models;

/**
 * App\Models\MBook
 *
 * @mixin \Eloquent
 * @property mixed isbn
 * @property mixed title
 * @property mixed author
 * @property mixed cover
 * @property mixed big_cover
 * @property mixed publisher
 * @property mixed true_isbn
 * @property mixed summary
 * @property mixed douban_average
 * @property mixed douban_raters
 * @property mixed rating
 * @property mixed subtitle
 * @property mixed pub_date
 * @property mixed tags
 * @property mixed origin_title
 * @property mixed binding
 * @property mixed translator
 * @property mixed catalog
 * @property mixed pages
 * @property mixed images
 * @property mixed alt
 * @property mixed isbn10
 * @property mixed url
 * @property mixed alt_title
 * @property mixed author_intro
 * @property mixed series
 * @property mixed price
 */
class MBook extends \Eloquent {

    // table name
    protected $table = 'bocha_book';

    // primary key
    protected $primaryKey = 'isbn';
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'isbn', 'title', 'author', 'cover', 'big_cover', 'publisher',
        'true_isbn', 'summary', 'douban_average', 'douban_raters',
        'rating', 'subtitle', 'pub_date', 'tags', 'origin_title',
        'binding', 'translator', 'catalog', 'pages', 'images', 'alt',
        'isbn10', 'url', 'alt_title', 'author_intro', 'series', 'price',
    ];
}