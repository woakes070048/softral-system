<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;
use DB;

/**
 * CountryList
 *
 */
class Money extends Model {

	/**
	 * @var string
	 * Path to the directory containing countries data.
	 */
	protected $table = 'user_money'; 
	protected $dates = ['created_at', 'updated_at'];
	
	protected $fillable = [
        'user_id',
		'proposal_selected_id',
        'job_id',
		'financial_accounts_id',
		'financial_account_name',
		'total_amount',
		'withdraw_amount',
		'completed',
		'add_amount',
    ];
	
	public function user()
    {
        return $this->hasOne('LaravelAcl\Authentication\Models\User', 'id','user_id');
    }
	
	public function job()
    {
        return $this->hasOne('App\Http\Models\Job', 'id','job_id');
    }
	
	public function financial_account()
    {
        return $this->hasOne('App\Http\Models\Financial', 'id','financial_accounts_id');
    }	
	
	public function getModifiedWithdrawAmountAttribute($value)
    {
        return round($this->withdraw_amount,2);
    }
	
	public function getModifiedAddAmountAttribute($value)
    {
        return round($this->add_amount,2);
    }
	
	public function getCreatedAtAttribute($value)
    {
        $created = new Carbon($value);
		$now = Carbon::now();
		return $created::createFromFormat('Y-m-d H:i:s', $value)->format('M d, Y');
    }
	
	function getmoney_has($user_id){
		$money_has = Money::where('user_id',$user_id)->orderBy('id','desc')->first();
		if(!empty($money_has)):
			return $money_has;
		else:
			return false;
		endif;
	}
	public function update_status($id){
		$update_status=DB::table('user_money')
            ->where('id', $id)
            ->update(['completed' => 1]);
		return true;
	}
	
	
	public function insert_money_data($total_amount,$financial_accounts_id,$withdraw_amount,$financial_account_name,$user_id){
		$input['financial_account_name']=$financial_account_name;
		$input['financial_accounts_id']=$financial_accounts_id;
		$input['total_amount']=$total_amount;
		$input['withdraw_amount']=$withdraw_amount;
		$input['user_id']=$user_id;
		
		$insert_data=Money::create($input);
	
		if(!empty($insert_data)):
			return true;
		else:
			return false;
		endif;
	}
	
	public function add_escrow_release($job_id,$user_id,$total_amount,$amount,$proposal_selected_id){
		$input['financial_account_name']='release';
		$input['financial_accounts_id']=0;
		$input['total_amount']=$total_amount;
		$input['add_amount']=$amount;
		$input['user_id']=$user_id;
		$input['completed']=1;
		$input['job_id']=$job_id;
		$input['proposal_selected_id']=$proposal_selected_id;
		
		$insert_data=Money::create($input);
	
		if(!empty($insert_data)):
			return true;
		else:
			return false;
		endif;
	}
	
	public function add_bonus_release($job_id,$user_id,$total_amount,$amount){
		$input['financial_account_name']='bonus';
		$input['financial_accounts_id']=0;
		$input['total_amount']=$total_amount;
		$input['add_amount']=$amount;
		$input['user_id']=$user_id;
		$input['completed']=1;
		$input['job_id']=$job_id;
		
		$insert_data=Money::create($input);
	
		if(!empty($insert_data)):
			return true;
		else:
			return false;
		endif;
	}
	
	public function skrillmoney_add($skrill_id,$id,$account_name,$total_amount,$amount){
		$input['financial_account_name']=$account_name;
		$input['financial_accounts_id']=$skrill_id;
		$input['total_amount']=$total_amount;
		$input['add_amount']=$amount;
		$input['user_id']=$id;
		$input['completed']=1;
		
		$insert_data=Money::create($input);
	
		if(!empty($insert_data)):
			return true;
		else:
			return false;
		endif;
	}
	
	public function escrowmoney_add($job_id,$id,$account_name,$total_amount,$amount){
		$input['financial_account_name']=$account_name;
		$input['financial_accounts_id']=0;
		$input['job_id']=$job_id;
		$input['total_amount']=$total_amount;
		$input['add_amount']=$amount;
		$input['user_id']=$id;
		$input['completed']=1;
		
		$insert_data=Money::create($input);
	
		if(!empty($insert_data)):
			return true;
		else:
			return false;
		endif;
	}
	

	
	
	
	
}
