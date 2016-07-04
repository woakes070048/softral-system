<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * CountryList
 *
 */
class Proposal extends Model {

	/**
	 * @var string
	 * Path to the directory containing countries data.
	 */
	protected $table = 'proposals'; 
	protected $dates = ['created_at', 'updated_at'];
	
	protected $fillable = [
        'user_id',
		'job_id',
		'amount',
		'proposal',
		'counter_amount',
		'freelancer_counter_amount',
		'terms_milestone',
		'offer',
    ];
	
	public function user()
    {
        return $this->hasOne('LaravelAcl\Authentication\Models\User', 'id','user_id');
    }
	
	public function job()
    {
        return $this->belongsTo('App\Http\Models\Job', 'job_id');
    }
	
	public function milestones()
    {
        return $this->hasMany('App\Http\Models\Milestone', 'proposal_id','id')->orderBy('id','DESC');
    }
	
	public function user_profile()
    {
        return $this->hasOne('LaravelAcl\Authentication\Models\UserProfile', 'user_id','user_id');
    }
	
	public function contract()
    {
        return $this->hasOne('App\Http\Models\Contract', 'proposal_id','id');
    }
	
	public function scopeApproveContract($query){
			return $query->whereHas('contract', function($query) {
			$query->where('approve_contract', 0);
			});
	}

	
	public function getCreatedAtAttribute($value)
    {
        $created = new Carbon($value);
		$now = Carbon::now();
		return $created->diffForHumans($now);
    }
	
	public function getModifiedUpdatedAtAttribute($value)
    {
        $created = new Carbon();
		return $created::createFromFormat('Y-m-d H:i:s', $this->updated_at)->format('M d, Y');
    }
	
	public function getModifiedEndedDateAttribute($value)
    {
		$value = json_decode($this->amount);
        $created = new Carbon();
		
		if(!empty($value)  && isset($value->duration)):
			if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$value->duration)):
				return $created::createFromFormat('Y-m-d', $value->duration)->format('M d, Y');
			else:
				return "Date of submission : ".$value->duration." Weeks";
			endif;
		else:
			return 'Not specified';
		endif;
    }
	
	public function getMainAmountAttribute($value)
    {
        $value = json_decode($this->amount);
		
		if(!empty($value)  && isset($value->paid_to)):
				return $value->paid_to;
		endif;
    }
	
	public function getFeeAmountAttribute($value)
    {
		
        $value = json_decode($this->amount);
		
		if(!empty($value) && isset($value->softral_fee)):
				return $value->softral_fee;
		endif;
    }
	
	public function getClientAmountAttribute($value)
    {
        $value = json_decode($this->amount);
		
		if(!empty($value) && isset($value->charged_client)):
				return $value->charged_client;
		endif;
    }	
	
	public function getHoursperweekAmountAttribute($value)
    {
		
        $value = json_decode($this->amount);
		
		if(!empty($value) && isset($value->hoursperweek)):
				return $value->hoursperweek;
		endif;
    }
	
	public function getDurationAmountAttribute($value)
    {
		
        $value = json_decode($this->amount);
		$created = new Carbon();
		
		if(!empty($value) && isset($value->duration)):
			if (preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$value->duration)):
				return "Date of submission : ".$created::createFromFormat('Y-m-d', $value->duration)->format('M d, Y');
			else:
				return "Date of submission : ".$value->duration." Weeks";
			endif;
		endif;
    }
	

}
