<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * CountryList
 *
 */
class Financial extends Model {

	/**
	 * @var string
	 * Path to the directory containing countries data.
	 */
	protected $table = 'financial_accounts'; 
	protected $dates = ['created_at', 'updated_at'];
	
	protected $fillable = [
        'user_id',
		'skrill_account',
		'bank_account',
		'person_name',
		'paypal_account',
    ];
	
	public function user()
    {
        return $this->hasOne('LaravelAcl\Authentication\Models\User', 'id','user_id');
    }
	
	public function bank()
    {
        return $this->hasOne('App\Http\Models\Bank', 'financial_accounts_id','id');
    }
	public function credit()
    {
        return $this->hasOne('App\Http\Models\Credit', 'financial_accounts_id','id');
    }
	
	public function getUpdatedAtAttribute($value)
    {
        $created = new Carbon($value);
		$now = Carbon::now();
		return $created::createFromFormat('Y-m-d H:i:s', $value)->format('M d, Y');
    }
	
	
	
	

}
