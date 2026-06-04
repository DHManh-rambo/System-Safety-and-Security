<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WafSetting extends Model
{
    protected $table = 'waf_settings';
    protected $fillable = ['key', 'value'];
}