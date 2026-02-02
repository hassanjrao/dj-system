<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliverableStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'code',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Scope to filter by completion type statuses.
     */
    public function scopeCompletion($query)
    {
        return $query->where('type', 'completion');
    }

    /**
     * Scope to filter by wave upload type statuses.
     */
    public function scopeWaveUpload($query)
    {
        return $query->where('type', 'wave_upload');
    }

    /**
     * Scope to filter by mp3 upload type statuses.
     */
    public function scopeMp3Upload($query)
    {
        return $query->where('type', 'mp3_upload');
    }

    /**
     * Scope to filter by active statuses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Get all statuses grouped by type.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getAllGroupedByType()
    {
        return static::active()
            ->ordered()
            ->get()
            ->groupBy('type');
    }

    /**
     * Check if this status represents "Done" for completion type.
     *
     * @return bool
     */
    public function isDone(): bool
    {
        return $this->type === 'completion' && $this->code === 'done';
    }

    /**
     * Check if this status represents "Uploaded" for upload types.
     *
     * @return bool
     */
    public function isUploaded(): bool
    {
        return in_array($this->type, ['wave_upload', 'mp3_upload']) && $this->code === 'uploaded';
    }
}
