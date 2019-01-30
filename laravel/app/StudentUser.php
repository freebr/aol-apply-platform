<?php
namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class StudentUser extends Authenticatable
{
    use Notifiable;

	protected $table = 'ViewStudents';

	protected $primaryKey = 'ID';
	
	protected $connection = 'sqlsrv';
	
}
