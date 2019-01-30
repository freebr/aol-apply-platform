<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Tutor;

class Comment extends Model
{
	use SoftDeletes;

	protected $table = 'comments';

	protected $primaryKey = 'id';

	protected $fillable = ['aolapply_id','author_id','is_pass','content','commentable_id','commentable_type'];
	
	protected $attributes = ['content' => ''];

	protected $with = ['author'];

	protected $dates = ['deleted_at'];
	
	public function commentable() {
		return $this->morphTo();
	}

	public function author() {
		return $this->belongsTo(Tutor::class);
	}
}
