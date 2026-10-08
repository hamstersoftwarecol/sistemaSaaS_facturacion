<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    protected $guarded = ['id'];

    public const INVOICE_STYLES = [
        'modern' => 'Moderno',
        'classic' => 'Clásico',
        'corporate' => 'Corporativo',
        'elegant' => 'Elegante',
        'bold' => 'Llamativo',
    ];

    protected function casts(): array
    {
        return ['settings' => 'array'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class)->withoutGlobalScopes();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->withoutGlobalScopes();
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->withoutGlobalScopes()->latestOfMany();
    }

    public function setting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    public function prefix(string $type): string
    {
        return $this->setting("prefix_{$type}", Document::TYPES[$type]['prefix'] ?? strtoupper(substr($type, 0, 3)).'-');
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    /**
     * Logo embebido en base64 (para PDF y vistas de impresión).
     */
    public function logoDataUri(): ?string
    {
        if (! $this->logo_path || ! Storage::disk('public')->exists($this->logo_path)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($this->logo_path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($this->logo_path));
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function fullAddress(): string
    {
        return collect([$this->address, $this->city, $this->state, $this->postal_code, $this->country])->filter()->implode(', ');
    }
}
