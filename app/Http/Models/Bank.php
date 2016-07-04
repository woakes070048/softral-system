<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * CountryList
 *
 */
class Bank extends Model {

	/**
	 * @var string
	 * Path to the directory containing countries data.
	 */
	protected $table = 'bank_info'; 
	protected $dates = ['created_at', 'updated_at'];
	
	protected $fillable = [
		'user_id',
		'account_number',
		'branch_name',
		'account_name',
		'recipient_address',
		'swift_code',
		'iban_code',
		'ifsc_code',
		'routing_number',
		'financial_accounts_id',
    ];
	
	public function user()
    {
        return $this->hasOne('LaravelAcl\Authentication\Models\User', 'id','user_id');
    }
	
	public function financial()
    {
        return $this->hasOne('App\Http\Models\financial', 'id','financial_accounts_id');
    }
	
	public function getModifiedAccountNumberAttribute($value)
    {
       return '**********'.substr($this->account_number,-4);
    }
	
	public function getModifiedRoutingNumberAttribute($value)
    {
		return '******';
    }
	
	public function getCreatedAtAttribute($value)
    {
        $created = new Carbon($value);
		$now = Carbon::now();
		return $created::createFromFormat('Y-m-d H:i:s', $value)->format('M d, Y');
    }
	
	
	
	

}
