<?php namespace App\Models;

use App\Models\Traits\ExtendedMysqlQueries;
use Illuminate\Database\Eloquent\Model;

class GuCangOrderFee extends Model {

    use  ExtendedMysqlQueries;
    public $timestamps = true;
    protected $guarded = [];

    public function order():BelongsTo
    {
        return $this->belongsTo(GuCangOrder::class, 'order_code', 'order_code');
    }
}
