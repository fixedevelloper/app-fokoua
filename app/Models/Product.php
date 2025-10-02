<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable=[
      'category_id','name','image_url','price','type','is_active'
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function supplements()
    {
        return $this->belongsToMany(Supplement::class)
            ->withPivot(['quantity', 'extra_price'])
            ->withTimestamps();
    }

    public function accompaniments()
    {
        return $this->belongsToMany(Accompaniment::class)->withTimestamps();
    }

}
