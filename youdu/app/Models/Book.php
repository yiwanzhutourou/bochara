<?php

namespace App\Models;

/**
 * App\Models\Book
 *
 * @mixin \Eloquent
 * @property mixed isbn
 * @property mixed title
 * @property mixed author
 * @property mixed cover
 * @property mixed bigCover
 * @property mixed publisher
 * @property mixed trueIsbn
 * @property mixed summary
 * @property mixed doubanAverage
 * @property mixed doubanRaters
 * @property mixed rating
 * @property mixed subtitle
 * @property mixed pubDate
 * @property mixed tags
 * @property mixed originTitle
 * @property mixed binding
 * @property mixed translator
 * @property mixed catalog
 * @property mixed pages
 * @property mixed images
 * @property mixed alt
 * @property mixed isbn10
 * @property mixed url
 * @property mixed altTitle
 * @property mixed authorIntro
 * @property mixed series
 * @property mixed price
 */
class Book extends \Eloquent {

    // table name
    protected $table = 'bocha_book';

    // primary key
    protected $primaryKey = 'isbn';
}