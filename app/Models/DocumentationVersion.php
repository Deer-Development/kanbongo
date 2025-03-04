<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentationVersion extends Model
{
    protected $fillable = [
        'documentation_tab_id',
        'content',
        'version_number',
        'created_by',
        'comment'
    ];

    public function tab()
    {
        return $this->belongsTo(DocumentationTab::class, 'documentation_tab_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
} 