<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $table = 'contact';
    protected $primaryKey = 'id_contact';
    public $timestamps = false;

    protected $fillable = [
        'fname',
        'lname',
    ];

    protected $casts = [
        'id_contact' => 'int',
    ];

    public function getFullNameAttribute(): string
    {
        $f = trim((string)($this->fname ?? ''));
        $l = trim((string)($this->lname ?? ''));
        return trim($f . ' ' . $l);
    }
}
