<?php
namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class TeacherUser extends Authenticatable
{
    use Notifiable;

	protected $table = 'ViewTeachers';

	protected $primaryKey = 'ID';
	
	protected $connection = 'sqlsrv';
	
}
