<?php

namespace App\Models\Content;

use App\Services\Content\SanitizeHtml;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Page extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'slug', 'body', 'published', 'footer_group', 'sort_order'];

    protected function casts(): array
    {
        return ['published' => 'boolean', 'sort_order' => 'integer'];
    }

    public function footerGroup(): BelongsTo
    {
        return $this->belongsTo(FooterGroup::class, 'footer_group', 'key');
    }

    protected function body(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): string => app(SanitizeHtml::class)->handle($value),
            set: fn (?string $value): string => app(SanitizeHtml::class)->handle($value),
        );
    }
}
