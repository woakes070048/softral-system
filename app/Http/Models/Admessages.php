<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CountryList
 *
 */
class Admessages extends Model {

	/**
	 * @var string
	 * Path to the directory containing countries data.
	 */
	protected $table = 'ad_messages'; 
	
	protected $fillable = [
        'ad_id',
		'user_id',
		'name',
		'email',
		'message'
    ];
	
	public function ad()
    {
        return $this->hasOne('App\Http\Models\Ad', 'id','ad_id');
    }
	

}
