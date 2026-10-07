<?php

namespace App\Models;

use Database\Factories\DeviceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    /** @use HasFactory<DeviceFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_code',
        'api_key',
        'name',
        'location',
        'temperature',
        'tds',
        'voltage',
        'has_tds',
        'has_temp',
        'has_pump',
        'has_auto_mode',
        'pump_status',
        'auto_mode',
        'target_tds',
        'ip_address',
        'wifi_ssid',
        'status',
        'notes',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'temperature' => 'float',
            'tds' => 'float',
            'voltage' => 'float',
            'has_tds' => 'boolean',
            'has_temp' => 'boolean',
            'has_pump' => 'boolean',
            'has_auto_mode' => 'boolean',
            'pump_status' => 'boolean',
            'auto_mode' => 'boolean',
            'target_tds' => 'float',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function readings()
    {
        return $this->hasMany(SensorReading::class);
    }

    public function isOnline(): bool
    {
        if (! $this->last_seen_at) {
            return false;
        }

        return $this->last_seen_at->diffInSeconds(now()) <= 15;
    }
}
