<?php

namespace App\Models;

use Database\Factories\DeviceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
        'ph',
        'voltage',
        'has_tds',
        'has_temp',
        'has_ph',
        'has_pump',
        'pump_count',
        'pump_labels',
        'pump_states',
        'has_auto_mode',
        'sensor_schema',
        'pump_controls',
        'extra_sensors',
        'last_payload',
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
            'ph' => 'float',
            'voltage' => 'float',
            'has_tds' => 'boolean',
            'has_temp' => 'boolean',
            'has_ph' => 'boolean',
            'has_pump' => 'boolean',
            'pump_count' => 'integer',
            'pump_labels' => 'array',
            'pump_states' => 'array',
            'has_auto_mode' => 'boolean',
            'sensor_schema' => 'array',
            'pump_controls' => 'array',
            'extra_sensors' => 'array',
            'last_payload' => 'array',
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

    /**
     * Get list of all pump controls dynamically from JSON document.
     * Supports single pump or multi-pump setups (e.g. 5 dosing pumps).
     */
    public function getPumpsList(): array
    {
        if (! empty($this->pump_controls) && is_array($this->pump_controls)) {
            return $this->pump_controls;
        }

        if (! empty($this->pump_states) && is_array($this->pump_states)) {
            $list = [];
            $labels = is_array($this->pump_labels) ? $this->pump_labels : [];
            foreach ($this->pump_states as $key => $status) {
                $friendlyName = $labels[$key] ?? Str::headline(str_replace(['_', '-'], ' ', $key));
                $list[] = [
                    'key' => $key,
                    'name' => $friendlyName,
                    'status' => (bool) $status,
                ];
            }
            if (! empty($list)) {
                return $list;
            }
        }

        if ($this->has_pump) {
            return [
                [
                    'key' => 'pompa_sirkulasi',
                    'name' => 'Pompa Sirkulasi',
                    'status' => (bool) $this->pump_status,
                ],
            ];
        }

        return [];
    }

    /**
     * Check if device has pH sensor either from column or JSON payload.
     */
    public function hasPh(): bool
    {
        if ($this->has_ph || $this->ph !== null) {
            return true;
        }

        if (! empty($this->extra_sensors) && isset($this->extra_sensors['ph'])) {
            return true;
        }

        if (! empty($this->sensor_schema) && is_array($this->sensor_schema)) {
            foreach ($this->sensor_schema as $s) {
                if (($s['key'] ?? '') === 'ph') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get list of dynamic sensors parsed from NoSQL-style JSON document.
     */
    public function getSensorsList(): array
    {
        $list = [];

        if ($this->has_tds) {
            $list[] = [
                'key' => 'tds',
                'name' => 'Kepekatan Nutrisi',
                'value' => (float) ($this->tds ?? 0),
                'unit' => 'PPM',
            ];
        }

        if ($this->has_temp) {
            $list[] = [
                'key' => 'temperature',
                'name' => 'Suhu Air',
                'value' => (float) ($this->temperature ?? 0),
                'unit' => '°C',
            ];
        }

        if ($this->hasPh()) {
            $val = $this->ph ?? ($this->extra_sensors['ph'] ?? 6.0);
            $list[] = [
                'key' => 'ph',
                'name' => 'Derajat Keasaman',
                'value' => (float) $val,
                'unit' => 'pH',
            ];
        }

        // Add any other dynamic sensors from extra_sensors JSON document
        if (! empty($this->extra_sensors) && is_array($this->extra_sensors)) {
            foreach ($this->extra_sensors as $k => $v) {
                if (in_array($k, ['tds', 'temperature', 'temp', 'ph'])) {
                    continue;
                }
                $list[] = [
                    'key' => $k,
                    'name' => Str::headline(str_replace(['_', '-'], ' ', $k)),
                    'value' => is_numeric($v) ? (float) $v : $v,
                    'unit' => is_numeric($v) ? '' : '',
                ];
            }
        }

        return $list;
    }
}
