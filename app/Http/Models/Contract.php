<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * CountryList
 *
 */
class Contract extends Model {

	/**
	 * @var string
	 * Path to the directory containing countries data.
	 */
	protected $table = 'contract'; 
	//public $timestamps = false;
	
	protected $fillable = [
		'job_id',
		'proposal_id',
		'cancel_contract',
		'ended_contract',
		'approve_contract'
    ];
	
	
	public function getModifiedPostedDateAttribute($value)
    {
	  $created = new Carbon();
      return $created::createFromFormat('Y-m-d H:i:s', $this->created_at)->format('M d, Y');
    }
	
	public function ScopeProposalUserid($query, $id){
		
				$query->WhereHas('proposal', function($query) use ($id){
					$query->where('user_id', $id);
					$query->where('offer', 1);
				});
		
	}
	
	public function ScopeJobUserid($query, $id){
		
				$query->WhereHas('job', function($query) use ($id){
					$query->where('user_id', $id);
				});
		
	}
	
	public function job()
    {
        return $this->hasOne('App\Http\Models\Job', 'id','job_id');
    }
	
	public function proposal()
    {
        return $this->hasOne('App\Http\Models\Proposal', 'id','proposal_id');
    }
	
	public function user()
    {
        return $this->hasOne('LaravelAcl\Authentication\Models\User', 'id','user_id');
    }
	
	public function proposal_selected()
    {
        return $this->hasOne('App\Http\Models\Proposal', 'id','proposal_id')->where('offer',1);
    }
	

}
