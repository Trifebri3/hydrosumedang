<?php

namespace App\Models;

use Database\Factories\SensorReadingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SensorReading extends Model
{
    /** @use HasFactory<SensorReadingFactory> */
    use HasFactory;

    protected $fillable = [
        'device_id',
        'temperature',
        'tds',
        'ph',
        'voltage',
        'pump_status',
        'pump_states',
        'auto_mode',
        'sensor_data',
        'pump_data',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'temperature' => 'float',
            'tds' => 'float',
            'ph' => 'float',
            'voltage' => 'float',
            'pump_status' => 'boolean',
            'pump_states' => 'array',
            'auto_mode' => 'boolean',
            'sensor_data' => 'array',
            'pump_data' => 'array',
            'raw_payload' => 'array',
        ];
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
