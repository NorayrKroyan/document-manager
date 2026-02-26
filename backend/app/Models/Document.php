<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $table = 'document';
    protected $primaryKey = 'id_document';
    public $timestamps = false;

    protected $fillable = [
        'path',
        'id_doctype',
        'doc_name',
        'doc_description',
        'id_docowner',
        'id_owner',
        'doc_expiration',
        'date_created',
        'date_modified',
        'is_deleted',
        'document_size',
    ];

    protected $casts = [
        'id_document' => 'int',
        'id_doctype' => 'int',
        'id_docowner' => 'int',
        'id_owner' => 'int',
        'is_deleted' => 'int',
        'document_size' => 'int',
        'doc_expiration' => 'date:Y-m-d',
    ];

    public function doctype()
    {
        return $this->belongsTo(DocType::class, 'id_doctype', 'id_doctype');
    }

    public function docowner()
    {
        return $this->belongsTo(DocOwner::class, 'id_docowner', 'id_docowner');
    }
}
