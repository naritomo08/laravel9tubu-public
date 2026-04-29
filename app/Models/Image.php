<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    use HasFactory;

    public function existsOnPublicDisk(): bool
    {
        return Storage::disk('public')->exists($this->path());
    }

    public function publicUrl(): string
    {
        return '/storage/' . $this->path();
    }

    private function path(): string
    {
        return 'images/' . $this->name;
    }
}
