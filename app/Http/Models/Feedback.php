<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * CountryList
 *
 */
class Feedback extends Model {

	/**
	 * @var string
	 * Path to the directory containing countries data.
	 */
	protected $table = 'feedbacks'; 
	//public $timestamps = false;
	
	protected $fillable = [
		'contract_id',
		'freelancer_id',
		'employee_id',
		'freelancer_comment',
		'employee_comment',
		'freelancer_rating',
		'employee_rating',
    ];
	
	
	public function getModifiedPostedDateAttribute($value)
    {
	  $created = new Carbon();
      return $created::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('M d, Y');
    }
	
	
}
