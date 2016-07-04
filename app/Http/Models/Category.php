<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CountryList
 *
 */
class Category extends Model {

	/**
	 * @var string
	 * Path to the directory containing countries data.
	 */
	protected $table = 'categories'; 
	public $timestamps = false;
	
	protected $fillable = [
        'category',
		'parent',
		'slug',
		'type'
    ];
	
	

	public function children()
    {
        return $this->hasMany('App\Http\Models\Category', 'parent');
    }
	
	public function parent_get()
    {
        return $this->hasOne('App\Http\Models\Category','id', 'parent');
    }
	
	public function get_jobs()
    {
        return $this->hasMany('App\Http\Models\Job','category_id', 'id');
    }


	
}
