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
class Job extends Model {

	/**
	 * @var string
	 * Path to the directory containing countries data.
	 */
	protected $table = 'jobs'; 
	protected $dates = ['created_at', 'updated_at'];
	
	protected $fillable = [
        'category_id',
		'user_id',
		'project_name',
		'skill_id',
		'skill_name',
		'description',
		'images',
		'job_type',
		'hourperweek',
		'duration',
		'budget',
		'job_close',
		'slug',
		'selected',
    ];
	
	public function categories()
    {
        return $this->hasOne('App\Http\Models\Category', 'id','category_id');
    }
	
	public function children()
    {
        return $this->hasMany('App\Http\Models\Category', 'parent','category_id');
    }
	
	public function save_job()
    {
        return $this->hasOne('App\Http\Models\Savejob');
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
	
	public function contract()
    {
        return $this->hasOne('App\Http\Models\Contract');
    }
	
	public function approve_contract()
    {
        return $this->hasOne('App\Http\Models\Contract')->where('approve_contract',0);
    }
	
	public function scopeApproveContract($query){
			return $query->whereHas('contract', function($query) {
			$query->where('approve_contract', 0);
			});
	}
	
	public function proposals()
    {
        return $this->hasMany('App\Http\Models\Proposal', 'job_id','id');
    }
	
	public function proposal_selected()
    {
        return $this->hasOne('App\Http\Models\Proposal', 'job_id','id')->where('offer',1);
    }
	
	public function last_escrow()
    {
        return $this->hasOne('App\Http\Models\Escrow', 'job_id','id')->orderBy('id','desc');
    }
	
	public function getCreatedAtAttribute($value)
    {
		$created = new Carbon();
		return $created::createFromFormat('Y-m-d H:i:s', $value)->format('M d, Y');
    }

	public function getCutDescriptionAttribute($value)
    {
       return Str::limit($this->description, 250);
    }
	
	public function getCutSidebarDescriptionAttribute($value)
    {
       return Str::limit($this->description, 100);
    }
	
	public function getModifiedSkillIdAttribute($value)
    {
      $skills=unserialize($this->skill_id);
	  $skill_data=array();
	  
	  for($i=0;$i<count($skills);$i++):
			$skill = Skill::find($skills[$i]);
		if($skill):
			$skill_data[$skill['slug']] = $skill['skill']; 
		endif;
	  endfor;
	  
	  $data=implode(' ', array_map(function ($v, $k) { return "<a href='".url()."/skill/".$k."'>".$v."</a>,"; }, $skill_data, array_keys($skill_data)));
	  return rtrim($data,',');
    }
	
	public function getMainSkillIdAttribute($value)
    {
		$skills='';
		if($this->skill_id!=''):
			$skills=unserialize($this->skill_id);
			$skills = array_values($skills);
		endif;
		return $skills;
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
