<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * CountryList
 *
 */
class Page extends Model {

	/**
	 * @var string
	 * Path to the directory containing countries data.
	 */
	protected $table = 'pages'; 
	public $timestamps = false;
	
	protected $fillable = [
        'title',
		'content',
		'image',
		'active',
		'slug',
		'parent'
    ];
	
	public function children()
    {
        return $this->hasMany('App\Http\Models\Page', 'parent')->where('active',1);
    }
	
	public function children_hexa()
    {
        return $this->hasMany('App\Http\Models\Page', 'parent')->orderByRaw("RAND()")->limit(28);
    }
	
	public function getCutContentAttribute($value)
    {
       return Str::limit(strip_tags($this->content), 250);
    }
	
	public function getModifiedActiveAttribute($value)
    {
        if($this->active==1):
			return 'Active';
		else:
			return 'Inactive';
		endif;
    }
	
	
	
}
