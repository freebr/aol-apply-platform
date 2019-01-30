<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tutor extends Model
{
	use SoftDeletes;

	protected $table = 'tutors';

	protected $primaryKey = 'id';
	
	protected $fillable = ['account_id','account','name','gender','department'];
	
	protected $attributes = ['id' => '0'];
	
	protected $dates = ['deleted_at'];
	
	public function comment($aolapply_id) {
		return $this->morphMany(\App\Comment::class, 'commentable')
					->where('aolapply_id', $aolapply_id);
	}
	
	public static function names($tutors) {
		$arr=array();
		foreach($tutors as $tutor) {
			array_push($arr, $tutor->name);
		}
		return $arr;
	}
}

?>