<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentationComment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'documentation_tab_id',
        'user_id',
        'content',
        'selection_path',
        'selection_offset',
        'selection_text',
        'resolved_at',
        'resolved_by',
        'parent_id'
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'selection_path' => 'array'
    ];

    protected $with = ['user'];

    public function documentationTab()
    {
        return $this->belongsTo(DocumentationTab::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function parent()
    {
        return $this->belongsTo(DocumentationComment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(DocumentationComment::class, 'parent_id');
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }
} 