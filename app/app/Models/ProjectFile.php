<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProjectFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'url',
        'description',
    ];

    public function pageVersions(): BelongsToMany
    {
        return $this->belongsToMany(PageVersion::class, 'project_file_page_version');
    }
}


