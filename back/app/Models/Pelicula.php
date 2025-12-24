<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pelicula extends Model
{
    use SoftDeletes;

    protected $table = 'peliculas';

    protected $fillable = [
        'tmdb_id',
        'title',
        'original_title',
        'original_language',
        'release_date',
        'poster_path',
        'backdrop_path',
        'vote_average',
        'vote_count',
        'popularity',
        'status',
        'trailer_url',
        'tmdb_data',
        'tmdb_videos',
        'user_id',
    ];

    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'tmdb_data' => 'array',
        'tmdb_videos' => 'array',
        'release_date' => 'date:Y-m-d',
    ];
}
