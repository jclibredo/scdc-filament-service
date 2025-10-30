<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];
    // In Category.php
    public function csvUploads()
    {
        return $this->hasMany(CsvUpload::class);
    }
}