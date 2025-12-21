<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryProblemSupportingDoc extends Model
{
    use HasFactory;

    // Nama tabel
    protected $table = 'delivery_problem_supporting_docs';

    // Primary key
    protected $primaryKey = 'id';

    // Kolom yang bisa diisi
    protected $fillable = [
        'delivery_problem_id',
        'file_name',
        'file_path',
        'created_by',
        'created_at',
    ];

    public $timestamps = false;

    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        return asset(ltrim($this->file_path, '/'));
    }

    public function problem()
    {
        return $this->belongsTo(DeliveryProblemNote::class, 'delivery_problem_id');
    }

    public static function forProblem($problemId)
    {
        return self::where('delivery_problem_id', $problemId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
