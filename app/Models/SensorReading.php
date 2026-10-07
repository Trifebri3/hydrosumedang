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
        'voltage',
        'pump_status',
        'auto_mode',
    ];

    protected function casts(): array
    {
        return [
            'temperature' => 'float',
            'tds' => 'float',
            'voltage' => 'float',
            'pump_status' => 'boolean',
            'auto_mode' => 'boolean',
        ];
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
