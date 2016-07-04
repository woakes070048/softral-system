<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Http\Models\Skill;
use App\Http\Models\Proposal;
use Illuminate\Routing\UrlGenerator;

/**
 * CountryList
 *
 */
class Ad extends Model {

	/**
	 * @var string
	 * Path to the directory containing countries data.
	 */
	protected $table = 'ads'; 
	protected $dates = ['created_at', 'updated_at'];
	
	protected $fillable = [
        'category_id',
		'user_id',
		'title',
		'description',
		'country',
		'state',
		'city',
		'address',
		'price',
		'images',
		'email',
		'phone_no',
		'slug',
		'sold'
    ];
	
	public function categories()
    {
        return $this->hasOne('App\Http\Models\Category', 'id','category_id');
    }
	
	public function children()
    {
        return $this->hasMany('App\Http\Models\Category', 'parent','category_id');
    }
	
	public function messages()
    {
        return $this->hasMany('App\Http\Models\Admessages');
    }
	
	public function getModifiedStateAttribute($value)
    {
		return strtolower(str_replace(' ','-',$this->state));
    }
	
	public function getModifiedCityAttribute($value)
    {
		return strtolower(str_replace(' ','-',$this->city));
    }
	
	public function scopeByCategory($query, $pcategories){
		
		if($pcategories->parent==0):
			return $query->whereHas('categories', function($query) use ($pcategories){
			$query->where('id', $pcategories->id);
			});
		else:
			
		endif;
	}	
	
	public function scopeSubCategory($query, $subcategories){
			$subcategory=implode(',',$subcategories);
			
			if(!empty($subcategories)):
				$query->WhereHas('categories', function($query) use ($subcategories){
					$query->whereIn('id', $subcategories);
				});
			endif;
	}

	public function user()
    {
        return $this->hasOne('LaravelAcl\Authentication\Models\User', 'id','user_id');
    }
	
	public function getModifiedCreatedAtAttribute($value)
    {
        $created = new Carbon($this->created_at);
		$now = Carbon::now();
		return $created->diffForHumans($now);
    }	
	
	public function getModified1CreatedAtAttribute($value)
    {
        $created = strtotime($this->created_at);
		return date('M d', $created);
    }

	public function getCutDescriptionAttribute($value)
    {
       return Str::limit($this->description, 250);
    }
	
	public function getCutSidebarDescriptionAttribute($value)
    {
       return Str::limit($this->description, 100);
    }
	
	
	public function getModifiedImagesAttribute($value)
    {
		
		$images=unserialize($this->images);
		$images_string='';
		if(!empty($images)):
			$images_string=implode(' ', array_map(function ($v, $k) { return "<div class='images_".$k."'><a href='".url()."/download/".$v."' target='_blank'>".$v."</a>&nbsp;<span class='image_edit_delete' id='images_".$k."'>X</span></div>"; }, $images, array_keys($images)));
		endif;
		
		return rtrim($images_string,',');
    }
	public function getEditImagesAttribute($value)
    {
		
		$images=unserialize($this->images);
		$images_string='';
		
		for($i=0;$i<count($images);$i++):
			$images_string .= "<input type='hidden' class='images_".$i."' name='image_string[]' value='".$images[$i]."'>";
		endfor;
		
		return $images_string;
    }
	

}
