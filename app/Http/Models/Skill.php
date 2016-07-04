<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CountryList
 *
 */
class Skill extends Model {

	/**
	 * @var string
	 * Path to the directory containing countries data.
	 */
	protected $table = 'skill'; 
	public $timestamps = false;
	
	protected $fillable = [
        'skill',
		'slug'
    ];
	

}
