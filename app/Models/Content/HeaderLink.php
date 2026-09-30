<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HeaderLink extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'label', 'position', 'destination', 'page_id', 'url', 'active', 'sort_order'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
