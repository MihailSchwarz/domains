<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property mixed $domain
 * @property mixed $search_query
 * @property int|mixed|string $position
 * @property Carbon|mixed $expires_at
 * @property bool|mixed $no_expiration_date
 */
class Domain extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $casts = [
        'expires_at' => 'datetime'
    ];
}
