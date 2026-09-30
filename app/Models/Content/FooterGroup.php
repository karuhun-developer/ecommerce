<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FooterGroup extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'name', 'active', 'sort_order'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class, 'footer_group', 'key');
    }
}
