<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * CountryList
 *
 */
class Milestone extends Model {

	/**
	 * @var string
	 * Path to the directory containing countries data.
	 */
	protected $table = 'milestones'; 
	//public $timestamps = false;
	
	protected $fillable = [
		'job_id',
		'proposal_id',
		'label',
		'posted_date',
		'amount',
		'status'
    ];
	
	public function job()
    {
        return $this->hasOne('App\Http\Models\Job', 'id','job_id');
    }
	
	public function proposal()
    {
        return $this->hasOne('App\Http\Models\Proposal', 'id','proposal_id');
    }
	
	public function escrow()
    {
        return $this->hasOne('App\Http\Models\Escrow', 'job_id','job_id')->orderBy('id','desc');
    }
	
	public function getModifiedPostedDateAttribute($value)
    {
	  $created = new Carbon();
      return $created::createFromFormat('Y-m-d H:i:s', $this->posted_date)->format('M d, Y');
    }
	
	public function getModifiedUpdatedAtAttribute($value)
    {
	  $created = new Carbon();
      return $created::createFromFormat('Y-m-d H:i:s', $this->updated_at)->format('M d, Y');
    }
	
	
	

}
