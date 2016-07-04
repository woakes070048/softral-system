<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * CountryList
 *
 */
class Credit extends Model {

	/**
	 * @var string
	 * Path to the directory containing countries data.
	 */
	protected $table = 'card_info'; 
	public $timestamps = false;
	
	protected $fillable = [
		'city',
		'user_id',
		'financial_accounts_id',
		'card_number',
		'exp_month',
		'exp_year',
		'security_code',
		'country',
		'address',
		'state',
		'city',
		'zipcode',
    ];
	
	public function user()
    {
        return $this->hasOne('LaravelAcl\Authentication\Models\User', 'id','user_id');
    }
	
	public function financial()
    {
        return $this->hasOne('App\Http\Models\financial', 'id','financial_accounts_id');
    }
	
	function is_valid_card($number) {
		// Strip any non-digits (useful for credit card numbers with spaces and hyphens)
		$number=preg_replace('/\D/', '', $number);
		// Set the string length and parity
		$number_length=strlen($number);
		$parity=$number_length % 2;
		// Loop through each digit and do the maths
		$total=0;
		for ($i=0; $i<$number_length; $i++) {
		$digit=$number[$i];
		// Multiply alternate digits by two
		if ($i % 2 == $parity) {
		$digit*=2;
		// If the sum is two digits, add them together (in effect)
		if ($digit > 9) {
		$digit-=9;
		}
		}
		// Total up the digits
		$total+=$digit;
		}
		// If the total mod 10 equals 0, the number is valid
		return ($total % 10 == 0) ? TRUE : FALSE;
	}
	
	public function cc_encrypt($str)
	{
		# Add PKCS7 padding.
		$EncKey = "25c6c7dd"; //For security
		$block = mcrypt_get_block_size('des', 'ecb');
		if (($pad = $block - (strlen($str) % $block)) < $block) {
		$str .= str_repeat(chr($pad), $pad);
		}
		return base64_encode(mcrypt_encrypt(MCRYPT_DES, $EncKey, $str, MCRYPT_MODE_ECB));
	}
	
	public function getModifiedCardNumberAttribute($value)
    {
		$str=$this->card_number;
		$EncKey = "25c6c7dd";
		$str = mcrypt_decrypt(MCRYPT_DES, $EncKey, base64_decode($str), MCRYPT_MODE_ECB);
		# Strip padding out.
		$block = mcrypt_get_block_size('des', 'ecb');
		$pad = ord($str[($len = strlen($str)) - 1]);
		if ($pad && $pad < $block && preg_match(
		'/' . chr($pad) . '{' . $pad . '}$/', $str
		)
		) {
		$card=substr($str, 0, strlen($str) - $pad);
		return '***********'.substr($card,-4);
		}
		return '***********'.substr($str,-4);
    }
	
	public function getModifiedExpiredYearAttribute($value)
    {
		return '20**';
    }
	public function getModifiedSecurityCodeAttribute($value)
    {
		return '***';
    }	
	public function getModifiedExpiredMonthAttribute($value)
    {
		return '**';
    }
	

}
