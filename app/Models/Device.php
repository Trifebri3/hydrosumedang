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
        'device_code',
        'name',
        'location',
        'temperature',
        'tds',
        'voltage',
        'pump_status',
        'auto_mode',
        'target_tds',
        'ip_address',
        'wifi_ssid',
        'status',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'temperature' => 'float',
            'tds' => 'float',
            'voltage' => 'float',
            'pump_status' => 'boolean',
            'auto_mode' => 'boolean',
            'target_tds' => 'float',
            'last_seen_at' => 'datetime',
        ];
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
