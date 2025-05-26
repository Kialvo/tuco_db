<?php
// app/Models/RollbackStorage.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RollbackStorage extends Model
{
    protected $fillable = ['token','storage_id','snapshot'];
    protected $casts    = ['snapshot'=>'array'];
}
