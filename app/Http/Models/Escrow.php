<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * CountryList
 *
 */
class Escrow extends Model {

	/**
	 * @var string
	 * Path to the directory containing countries data.
	 */
	protected $table = 'escrow'; 
	public $timestamps = false;
	
	protected $fillable = [
		'proposal_id',
		'job_id',
		'amount'
    ];
	

	public function job()
    {
        return $this->belongsTo('App\Http\Models\Job', 'job_id');
    }
	
	public function proposal()
    {
        return $this->hasOne('App\Http\Models\Proposal', 'id','proposal_id');
    }
	
	public function add_deposit($job_id,$proposal_id,$amount){
		$input['job_id']=$job_id;
		$input['proposal_id']=$proposal_id;
		$input['amount']=$amount;
		
		$insert_data=Escrow::create($input);
	
		if(!empty($insert_data)):
			return true;
		else:
			return false;
		endif;
	}
	


}
