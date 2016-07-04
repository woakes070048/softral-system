<?php namespace App\Http\Controllers;

use App\Http\Models\Money;
use App\Http\Models\Financial;
use App\Http\Models\Bank;
use App\Http\Models\Credit;
use App\Http\Models\Proposal;
use App\Http\Models\Job;
use App\Http\Models\Escrow;
use App\Http\Models\Contract;
use App\Http\Models\Milestone;
use App\Http\Models\Workboard;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use LaravelAcl\Authentication\Exceptions\PermissionException;
use LaravelAcl\Authentication\Exceptions\ProfileNotFoundException;
use LaravelAcl\Authentication\Helpers\DbHelper;
use LaravelAcl\Authentication\Models\UserProfile;
use LaravelAcl\Authentication\Presenters\UserPresenter;
use LaravelAcl\Authentication\Services\UserProfileService;
use LaravelAcl\Authentication\Validators\UserProfileAvatarValidator;
use LaravelAcl\Library\Exceptions\NotFoundException;
use LaravelAcl\Authentication\Models\User;
use LaravelAcl\Authentication\Helpers\FormHelper;
use LaravelAcl\Authentication\Exceptions\UserNotFoundException;
use LaravelAcl\Authentication\Validators\UserValidator;
use LaravelAcl\Library\Exceptions\JacopoExceptionsInterface;
use LaravelAcl\Authentication\Validators\UserProfileValidator;
use View, Input, Redirect, App, Config,Session,Mail,Crypt,Paypal;
use LaravelAcl\Authentication\Interfaces\AuthenticateInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use DB;

class FinancialController extends Controller {

	/*
	|--------------------------------------------------------------------------
	| Welcome Controller
	|--------------------------------------------------------------------------
	|
	| This controller renders the "marketing page" for the application and
	| is configured to only allow guests. Like most of the other sample
	| controllers, you are free to modify or remove it as you desire.
	|
	*/
	protected $auth;
	
	private $_apiContext;
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	 
	    
    public function action($action, $parameters = [], $absolute = true)
    {
        if ($this->rootNamespace && !(strpos($action, '\\') === 0)) {
            $action = $this->rootNamespace.'\\'.$action;
        } else {
            $action = trim($action, '\\');
        }
    
        if (!is_null($route = $this->routes->getByAction($action))) {
            return $this->toRoute($route, $parameters, $absolute);
        }
    
        throw new InvalidArgumentException("Action {$action} not defined.");
    }
    
	
	public function __construct(AuthenticateInterface $auth)
	{
		//$this->middleware('auth');
		 $this->auth = $auth;
	$this->_apiContext = PayPal::ApiContext(
                config('services.paypal.client_id'),
                config('services.paypal.secret'));
         
         $this->_apiContext->setConfig(array(
                'mode' => 'sandbox',
                'service.EndPoint' => 'https://api.sandbox.paypal.com',
                'http.ConnectionTimeOut' => 30,
                'log.LogEnabled' => true,
                'log.FileName' => storage_path('logs/paypal.log'),
                'log.LogLevel' => 'FINE'
         ));
    }
    public function paypalPaymentlist($id){
        
        $logged_user = $this->auth->getLoggedUser();
        
       $list=   Paypal::getAll(array('count' => 1, 'start_index' => 0), $this->_apiContext);
        
    }
    
    public function paypalPaymentDetails($id){
    
        $logged_user = $this->auth->getLoggedUser();
    
        $detail=Paypal::getById($payment_id, $this->_apiContext);
    
    }

	/**
	 * Show the application welcome screen to the user.
	 *
	 * @return Response
	 */
	public function Account()
	{
		$logged_user = $this->auth->getLoggedUser();
		$skrill_accounts = Financial::where('user_id',$logged_user->id)->where('skrill_account','!=','')->get();
		$bank_accounts = Bank::with('financial')->where('user_id',$logged_user->id)->get();
		$credit_accounts = Credit::where('user_id',$logged_user->id)->get();
		$paypal_accounts = Financial::where('user_id',$logged_user->id)->where('paypal_account','!=','')->get();
		
		return View::make('laravel-authentication-acl::admin.financial.account')->with([ 'skrill_accounts'   => $skrill_accounts,'bank_accounts'   => $bank_accounts,'credit_accounts'   => $credit_accounts,'paypal_accounts'=>$paypal_accounts]);
	}
	
	public function AddSkrill()
	{
		$logged_user = $this->auth->getLoggedUser();
		
		return View::make('laravel-authentication-acl::admin.financial.addSkrill');
	}
	
	public function AddBank()
	{
		$logged_user = $this->auth->getLoggedUser();
		
		return View::make('laravel-authentication-acl::admin.financial.addBank');
	}
	
	public function AddCredit()
	{
		$logged_user = $this->auth->getLoggedUser();
		$years=array();
		
		$year=date('Y');
		$year1=date('Y')+20;
		for($year=$year;$year<=$year1;$year++):
			$years[$year]=$year;
		endfor;
		
		$countries = DB::table('countries')->get();
		$countries_selector = array();
		$countries_selector['']='-Select Country-';
		foreach($countries as $country) {
			$countries_selector[$country->name] = $country->name; // I assume name attribute contains client name here
		}
		
		return View::make('laravel-authentication-acl::admin.financial.addCredit')->with([ 'years'   => $years,'countries'=>$countries_selector]);
	}
	
	 public function AddPaypal()
    {
        $logged_user = $this->auth->getLoggedUser();
        return View::make('laravel-authentication-acl::admin.financial.addPaypal');
    }
	
	public function allAccounts(){
		$logged_user = $this->auth->getLoggedUser();
		$accounts = Financial::with('user','bank','credit')->orderBy('id','desc')->get();
		
		return View::make('laravel-authentication-acl::admin.financial.allaccounts')->with([ 'accounts'   => $accounts]);
	}
	
	public function Adminaccountview(){
		$account_view = Financial::with('user')->where('id',Input::get('id'))->first();
		
		return View::make('laravel-authentication-acl::admin.financial.adminaccount_view')->with([ 'account_view'   => $account_view]);
	}
	
	public function requestMoney(){
		$logged_user = $this->auth->getLoggedUser();
		
		$confirm=Input::get('confirm');
		if(isset($confirm)):
			$update_status=with(new Money)->update_status(Input::get('transaction_id'));
			Session::flash('message', 'You have successfully sent payment!');
		endif;
		$moneys = Money::with('user','financial_account')->where('withdraw_amount','!=',0)->orderBy('id','desc')->get();
		
		return View::make('laravel-authentication-acl::admin.financial.requestMoney')->with([ 'moneys'   => $moneys]);
	}
	
	public function requestmoneySend(){
		$send_money = Money::where('id',Input::get('id'))->first();
		$logged_user = $this->auth->getLoggedUser();
		
		return View::make('laravel-authentication-acl::admin.financial.requestmoneySend')->with([ 'send_money'   => $send_money,'admin_email'=>$logged_user->email]);
	}
	
	public function requestPaypalmoneySend(){
        $send_money = Money::where('id',Input::get('id'))->first();
		Session::put('money_id', Input::get('id'));
    
        $logged_user = $this->auth->getLoggedUser();
        
        return View::make('laravel-authentication-acl::admin.financial.requestPaypalmoneySend')->with([ 'send_money'   => $send_money,'admin_email'=>$logged_user->email]);
    }
	
	public function Withdraw()
	{
		$logged_user = $this->auth->getLoggedUser();
		$skrill_methods = Financial::where('user_id',$logged_user->id)->where('skrill_account','!=','')->get();
		$paypal_methods = Financial::where('user_id',$logged_user->id)->where('paypal_account','!=','')->get();
		$bank_methods = Financial::where('user_id',$logged_user->id)->where('bank_account','!=','')->get();
		$credit_methods = Financial::where('user_id',$logged_user->id)->where('person_name','!=','')->get();
		$money = Money::where('user_id',$logged_user->id)->orderBy('id','desc')->first();
		if(isset($money->total_amount)):
			$money= round($money->total_amount,2);
		else:
			$money=0.00;
		endif;
		return View::make('laravel-authentication-acl::admin.financial.withdraw')->with([ 'skrill_methods'   => $skrill_methods,'money'=>$money,'bank_methods'   => $bank_methods,'credit_methods'   => $credit_methods,'paypal_methods'=>$paypal_methods]);
	}
	
	public function addmoney(){
	
		$logged_user = $this->auth->getLoggedUser();
		$skrill_methods = Financial::where('user_id',$logged_user->id)->where('skrill_account','!=','')->get();
		$bank_methods = Financial::where('user_id',$logged_user->id)->where('bank_account','!=','')->get();
		$credit_methods = Financial::where('user_id',$logged_user->id)->where('person_name','!=','')->get();
		$money = Money::where('user_id',$logged_user->id)->orderBy('id','desc')->first();
		// Available alpha caracters
		$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

		// generate a pin based on 2 * 7 digits + a random character
		$pin = mt_rand(1000000, 9999999)
			. mt_rand(1000000, 9999999)
			. $characters[rand(0, strlen($characters) - 1)];

		// shuffle the result
		$string = str_shuffle($pin);
		
		if(isset($money->total_amount)):
			$money= round($money->total_amount,2);
		else:
			$money=0.00;
		endif;
		return View::make('laravel-authentication-acl::admin.financial.addmoney')->with([ 'skrill_methods'   => $skrill_methods,'money'=>$money,'credit_methods'   => $credit_methods,'code'=>$string]);
	}
	
	public function addmoneyskrill(){
		
		$logged_user = $this->auth->getLoggedUser();
		
		if(Input::get('_token')):
			Session::put('token', Input::get('_token'));
		endif;
		
		$token=Session::get('token');
		Session::put('token1', Input::get('token'));
		//Session::keep(['token']);
		
		$confirm=Input::get('confirm');
		
		if(isset($confirm) && (Session::get('token')==Session::get('token1'))):
			
			$amount=Input::get('amount');
			$skrill_id=Input::get('skrill_id');
			$id=$logged_user->id;
			
			$money = Money::where('user_id',$logged_user->id)->orderBy('id','desc')->first();
			if(!empty($money)):
				$total_amount=$money['total_amount']+$amount;
			else:
				$total_amount=0.00;
			endif;
			
			$money_has = with(new Money)->skrillmoney_add($skrill_id,$id,'skrill_account',$total_amount,$amount);
			Session::forget('token');
			
			Session::flash('message', 'You have successfully added the money.');
			return redirect('admin/financial/addmoney');
        elseif(isset($confirm) && (Session::get('token')!=Session::get('token1'))):
			Session::flash('error', 'Sorry, You have already done the transaction.');
			return redirect('admin/financial/addmoney');
		endif;
		
		return View::make('laravel-authentication-acl::admin.financial.moneyaddskrill')->with([ 'skrill_id'   => Input::get('skrill_id'),'add_money'   => Input::get('amount'),'admin_email'=>$logged_user->email,'id'=>$logged_user->id,'token'=>Session::get('token')]);
	}
	
	public function addmoneycredit(Request $request){
		$input = $request->all();
		$logged_user = $this->auth->getLoggedUser();
		
		$id=$logged_user->id;
		$key_value =substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8); 
		$pass = $key_value; 
		
		$encrypted_text =Crypt::encrypt(MCRYPT_DES, $key_value, $pass, MCRYPT_ENCRYPT);
		$id1 = urlencode(base64_encode($encrypted_text));
		
		if(isset($_POST['submit_credit'])):
			Session::put('financial_accounts_id_for_credit_shopping_cart', $input['financial_accounts_id']);
			
			$redirect='http://pronoor.com/shopping_cart?email='.$logged_user['email'].'&username='.$logged_user['email'].'&key='.$key_value.'&encrypt_pass='. urlencode($id1).'&amount='.$input['add_amount'].'&token='.$input['_token'];
			return redirect($redirect);
		endif;

		
		if(isset($_POST['price'])):
			$amount=$_POST['price'];
			$skrill_id=Session::get('financial_accounts_id_for_credit_shopping_cart');
			$money = Money::where('user_id',$logged_user->id)->orderBy('id','desc')->first();
				if(!empty($money)):
					$total_amount=$money['total_amount']+$amount;
				else:
					$total_amount=$amount;
				endif;
			
			
			Session::flash('message', 'You have successfully added the money.');
			$money_has = with(new Money)->skrillmoney_add($skrill_id,$id,'credit_card',$total_amount,$amount);
			Session::forget('financial_accounts_id_for_credit_shopping_cart');
			return redirect('admin/financial/addmoney');
		endif;	
		
	}
	
	public function termsandMilestone(){
		$proposal = Proposal::with('milestones','contract')->where('id',Input::get('p_id'))->where('offer',1)->first();
		
		$logged_user = $this->auth->getLoggedUser();
		$money = Money::where('user_id',$logged_user->id)->orderBy('id','desc')->first();
		$escrow_money = Escrow::where('job_id',$proposal->job_id)->orderBy('id','desc')->first();
		$credit_methods = Financial::where('user_id',$logged_user->id)->where('person_name','!=','')->get();
		
		/*if(!empty($proposal) && $logged_user->id==$proposal['user_id'] && $proposal['contract']['approve_contract']==1):
			Session::flash('error', 'Sorry, Your contract has not been approved yet.');
			return view('laravel-authentication-acl::client.exceptions.404');
		endif;*/
		
		if(!empty($escrow_money)):
			$escrow_money=round($escrow_money['amount'],2);
		else:
			$escrow_money=0.00;
		endif;	
		
		if(!empty($money)):
			$money=round($money['total_amount'],2);
		else:
			$money=0;
		endif;
		
		
		if(isset($proposal->contract->cancel_contract)&& $proposal->contract->cancel_contract==1):
			return view('laravel-authentication-acl::client.exceptions.404');
		endif;
		
		if(!empty($proposal) && $logged_user->id==$proposal['user_id']):
				return View::make('financial.termsandmilestone')->with([ 'proposal'   => $proposal,'user_id'=>$logged_user->id,'amount'=>'','escrow_money'=>$escrow_money,'credit_methods'   => $credit_methods]);
		elseif($logged_user->id==$proposal['job']['user_id'] && !empty($proposal)):
				return View::make('financial.termsandmilestone')->with([ 'proposal'   => $proposal,'user_id'=>$logged_user->id,'amount'=>$money,'credit_methods'   => $credit_methods,'escrow_money'=>$escrow_money]);
		else:
				return view('laravel-authentication-acl::client.exceptions.404');
		endif;
	
		
	}
	
	public function cancelContract(){
		$proposal = Proposal::with('milestones')->where('id',Input::get('p_id'))->where('offer',1)->first();
		
		$logged_user = $this->auth->getLoggedUser();
		
		if(!empty($proposal) && $logged_user->id==$proposal['user_id']):
				$proposal = Proposal::findOrFail(Input::get('p_id'));
				
				$input['terms_milestone']=0;
				$input['offer']=0;
				$proposal->fill($input)->save();
				
				$job = Job::findOrFail($proposal['job_id']);
				$input1['selected']=0;
				$job->fill($input1)->save();
				
				$contract = Contract::where('proposal_id', Input::get('p_id'))->where('job_id',$proposal['job_id'])->firstOrFail();
				$input2['cancel_contract']=1;
				$contract->fill($input2)->save();
				
				$send_mail=$proposal['job']['user']['email'];
				//echo $proposal['job']['user']['user_profile'][0]['first_name'];exit;
				Mail::send('emails.cancel_contract', ['user'=>$logged_user,'proposal' => $proposal,'label'=>'Cancelled'], function($message) use ($send_mail) {
				$message->to($send_mail, 'From Softral Job')->subject('Softral - Cancel the Contract');
			});
				Session::flash('error', 'You have successfully cancelled the contract.');
				return redirect('my-workboard');	
		elseif($logged_user->id==$proposal['job']['user_id'] && !empty($proposal)):
				$proposal = Proposal::findOrFail(Input::get('p_id'));
				$input['offer']=0;
				$input['terms_milestone']=0;
				$proposal->fill($input)->save();
				
				$job = Job::findOrFail($proposal['job_id']);
				$input1['selected']=0;
				$job->fill($input1)->save();
				
				$contract = Contract::where('proposal_id', Input::get('p_id'))->where('job_id',$proposal['job_id'])->firstOrFail();
				$input2['cancel_contract']=1;
				$contract->fill($input2)->save();
				
				$send_mail=$proposal['job']['user']['email'];
				//echo $proposal['job']['user']['user_profile'][0]['first_name'];exit;
				Mail::send('emails.cancel_contract', ['user'=>$logged_user,'proposal' => $proposal,'label'=>'Cancelled'], function($message) use ($send_mail) {
				$message->to($send_mail, 'From Softral Job')->subject('Softral - Cancel the Contract');
			});
			
				Session::flash('error', 'You have successfully cancelled the contract.');
				return redirect('my-workboard');	
		else:
				return view('laravel-authentication-acl::client.exceptions.404');
		endif;
	
		
	}
	
	public function reopenContract(){
		
		$proposal = Proposal::with('milestones','user')->where('id',Input::get('p_id'))->where('offer',1)->first();
		
		$logged_user = $this->auth->getLoggedUser();
		if($logged_user->id==$proposal['job']['user_id'] && !empty($proposal)):
				$contract = Contract::where('proposal_id', Input::get('p_id'))->where('proposal_id',Input::get('p_id'))->firstOrFail();
				$input['ended_contract']=0;
				$contract->fill($input)->save();
				
				$job = Job::where('id', $proposal['job_id'])->firstOrFail();
				$input1['job_close']=0;
				$job->fill($input1)->save();
				
				$send_mail=$proposal['user']['email'];
			Mail::send('emails.cancel_contract', ['user'=>$logged_user,'proposal' => $proposal,'label'=>'Started'], function($message) use ($send_mail) {
				$message->to($send_mail, 'From Softral Job')->subject('Softral - Restart the Contract');
			});
			
				Session::flash('message', 'You have successfully started the contract.');
				return redirect('my_jobs');
		else:
			return view('laravel-authentication-acl::client.exceptions.404');
		endif;
	}
	
	public function freelancerrequestMoney(Request $request){
			$input = $request->all();
			$logged_user = $this->auth->getLoggedUser();
			
			$commentstext = "<div class='requested_comment'>Freelancer has requested money for this job</div>";
            $jobid=$input['job_id'];   
			$proposal = Proposal::where('user_id', $logged_user->id)->where('id',$input['p_id'])->where('offer',1)->first();
			$job_posted_email=$proposal['job']['user']['email'];
			 Mail::send('emails.freelancer_request_money', ['user'=>$logged_user,'proposal'=>$proposal], function($message) use ($job_posted_email) {
				$message->to($job_posted_email, 'From Softral Job')->subject('Softral - Got Notification');
			});
			
             $data = array(
                "freelancer_comments" => $commentstext,
                "job_id" =>$jobid,
                
            );
            with(new Workboard)->insert_assignment($data);
			Session::flash('message', 'You have successfully sent a payment request to the Client!');
			
			return redirect('financial/terms_milestone?p_id='.$input['p_id']);
          
	}
	
	public function saveMilestone(Request $request){
		$input = $request->all();
		$total_sum = Milestone::where('proposal_id',$input['proposal_id'])->sum('amount');
		$last_get = Milestone::where('proposal_id',$input['proposal_id'])->where('job_id', '=', $input['job_id'])->where('proposal_id', '=', $input['proposal_id'])->orderBy('id','ASC')->first();
		$last_get_date = Milestone::where('proposal_id',$input['proposal_id'])->where('job_id', '=', $input['job_id'])->where('proposal_id', '=', $input['proposal_id'])->orderBy('id','DESC')->first();
		
		if(isset($input['milestone_id']) && $input['milestone_id']!=''):
			$current_milestone = Milestone::where('job_id', '=', $input['job_id'])->where('proposal_id', '=', $input['proposal_id'])->find($input['milestone_id']);
			$previous_milestone = Milestone::where('id', '<', $input['milestone_id'])->where('job_id', '=', $input['job_id'])->where('proposal_id', '=', $input['proposal_id'])->orderBy('id','desc')->take(1)->get()->first();
			$next_milestone = Milestone::where('id', '>', $input['milestone_id'])->where('job_id', '=', $input['job_id'])->where('proposal_id', '=', $input['proposal_id'])->orderBy('id','desc')->take(1)->get()->first();
			
			$current_milestone_update = $input['amount']-$current_milestone->amount;
			
			if($current_milestone_update>$last_get->amount):
				Session::flash('error', 'Your milestone exceeds by the total amount.');
				return redirect('financial/terms_milestone?p_id='.$input['proposal_id']);
			endif;
			
			if((isset($previous_milestone->posted_date) && $input['posted_date']<$previous_milestone->posted_date) || (isset($next_milestone->posted_date) && $input['posted_date']>$next_milestone->posted_date)):
				Session::flash('error', 'Your date can not be smaller then previous milestone date.');
				return redirect('financial/terms_milestone?p_id='.$input['proposal_id']);
			endif;
			
				$milestone_update = Milestone::findOrFail($input['milestone_id']);
				$milestone_update->fill($input)->save();
				Session::flash('message', 'Milestone successfully updated!');
				$update_milestone = $last_get->amount-$current_milestone_update;
			
		else:
				if($input['amount']>$last_get->amount):
				Session::flash('error', 'Your milestone exceeds by the total amount.');
				return redirect('financial/terms_milestone?p_id='.$input['proposal_id']);
				endif;
		
				if($input['posted_date']<$last_get_date->posted_date):
					Session::flash('error', 'Your date can not be smaller then previous milestone date.');
					return redirect('financial/terms_milestone?p_id='.$input['proposal_id']);
				endif;
				
				if($input['posted_date']>$last_get->posted_date):
					Session::flash('error', 'Your date can not be greater then Final Delivery date.');
					return redirect('financial/terms_milestone?p_id='.$input['proposal_id']);
				endif;
			
				
				Milestone::create($input);
				Session::flash('message', 'You have added a new milestone.');
				$update_milestone = $last_get->amount-$input['amount'];
				
		endif;
	
		$input1['amount']=$update_milestone;
		
		$milestone = Milestone::findOrFail($last_get->id);
		$milestone->fill($input1)->save();
		
		return redirect('financial/terms_milestone?p_id='.$input['proposal_id']);
	}
	
	public function saveEscrow(Request $request){
		$input = $request->all();
		$logged_user = $this->auth->getLoggedUser();
		
		$money = Money::where('user_id',$logged_user->id)->orderBy('id','desc')->first();
		$key_value =substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8); 
		$pass = $key_value; 
		
		$encrypted_text =Crypt::encrypt(MCRYPT_DES, $key_value, $pass, MCRYPT_ENCRYPT);
		$id1 = urlencode(base64_encode($encrypted_text));
		
		
		if(isset($input['deposit_for']) && $input['deposit_for']=='yes'):
			$amount1=$input['amount'];
			$escrow_money = Escrow::where('job_id',$input['job_id'])->orderBy('id','desc')->first();
			$job_id=$input['job_id'];
			$proposal_id=$input['proposal_id'];
			
			if($amount1>$money['total_amount']):
				Session::flash('error', 'Sorry, You have insufficient balance to add amount.');
				return redirect('financial/terms_milestone?p_id='.$proposal_id);
			endif;
			
			if(!empty($escrow_money)):
				$amount=$escrow_money['amount']+$amount1;
			else:
				$amount=$amount1;
			endif;
			
			if(!empty($money)):
					$total_amount=$money['total_amount']-$amount1;
				else:
					$total_amount=$amount1;
			endif;
			
			$money_has = with(new Money)->escrowmoney_add($job_id,$logged_user->id,'escrow',$total_amount,$amount);
				
		elseif(isset($input['deposit_for']) && $input['deposit_for']=='no'):

			Session::put('financial_accounts_id_for_credit_shopping_cart', $input['financial_accounts_id']);
			Session::put('job_id_credit_shopping_cart', $input['job_id']);
			Session::put('proposal_id_credit_shopping_cart', $input['proposal_id']);
			
			$redirect='http://pronoor.com/shopping_cart?email='.$logged_user['email'].'&username='.$logged_user['email'].'&key='.$key_value.'&encrypt_pass='. urlencode($id1).'&amount='.$input['amount'].'&token='.$input['_token'].'&escrow=1';
			return redirect($redirect);
		
		endif;

		if(isset($_POST['price'])):
			$amount1=$_POST['price'];
			$escrow_money = Escrow::where('job_id',Session::get('job_id_credit_shopping_cart'))->orderBy('id','desc')->first();
			$job_id=Session::get('job_id_credit_shopping_cart');
			$proposal_id=Session::get('proposal_id_credit_shopping_cart');
			$skrill_id=Session::get('financial_accounts_id_for_credit_shopping_cart');
			
			if(!empty($escrow_money)):
				$amount=$escrow_money['amount']+$amount1;
			else:
				$amount=$amount1;
			endif;
		
			Session::forget('financial_accounts_id_for_credit_shopping_cart');
	
		endif;			
	
			
			$escrow_has = with(new Escrow)->add_deposit($job_id,$proposal_id,$amount);
			Session::flash('message', 'You have successfully deposit money to escrow.');
			Session::forget('job_id_credit_shopping_cart');
			Session::forget('proposal_id_credit_shopping_cart');
			
			$proposal = Proposal::findOrFail($proposal_id);
			$freelancer_email=$proposal['user']['email'];
			$employee_email=$proposal['job']['user']['email'];
			
			Mail::send('emails.save_escrow_money_employee', ['proposal' => $proposal,'amount'=>$amount], function($message) use ($employee_email) {
				$message->to($employee_email, 'From Softral Job')->subject('Softral - Funded amount');
			});
			
			return redirect('financial/terms_milestone?p_id='.$proposal_id);
		
		
	}
	
	public function releaseEscrow(Request $request){
		$input = $request->all();
		
		$logged_user = $this->auth->getLoggedUser();
		$escrow_money = Escrow::where('job_id',$input['job_id'])->orderBy('id','desc')->first();
		$money = Money::where('user_id',$escrow_money['proposal']['user_id'])->orderBy('id','desc')->first();
		$softral_amount = Money::where('user_id',1)->orderBy('id','desc')->first();
		$freelancer_amount = User::where('id',1)->first();
		//echo $escrow_money['proposal']['user']['user_profile'][0]['first_name'];exit;
		$amount2=$input['amount'];
		$amount_to_softral=$freelancer_amount['employee_fee'];
		$job_id=$input['job_id'];
		$proposal_id=$input['proposal_id'];
		$amount1=$amount2-$amount_to_softral;
	
		if(!empty($escrow_money)):
			if($amount2>$escrow_money['amount']):
				Session::flash('error', 'Sorry, You have insufficient balance in your Escrow to release amount.');
				return redirect('financial/terms_milestone?p_id='.$proposal_id);
			endif;
			$amount=$escrow_money['amount']-$amount2;
		else:
			Session::flash('error', 'Sorry you cannot release this payment due to insufficient in your Escrow account.');
			return redirect('financial/terms_milestone?p_id='.$proposal_id);
		endif;
		
		if($amount_to_softral>$amount2):
				Session::flash('error', 'Sorry, You must release higher amount then Softral Employee Fee.');
				return redirect('financial/terms_milestone?p_id='.$proposal_id);
		endif;
		
		if(!empty($money)):
					$total_amount=$money['total_amount']+$amount1;
				else:
					$total_amount=$amount1;
		endif;	
		
		if(!empty($softral_amount)):
					$softral_amount=$softral_amount['total_amount']+$amount_to_softral;
				else:
					$softral_amount=$amount_to_softral;
		endif;
		
			Session::flash('message', 'You have successfully released amount from escrow.');
			
			$escrow_has = with(new Escrow)->add_deposit($job_id,$proposal_id,$amount);
			$escrow_has = with(new Money)->add_escrow_release($job_id,$escrow_money['proposal']['user_id'],$total_amount,$amount1,0);
			$amount_to_softral = with(new Money)->add_escrow_release($job_id,1,$softral_amount,$amount_to_softral,$escrow_money['proposal']['user_id']);
			
			$send_mail=config('app.admin_email');
			Mail::send('emails.escrow_release', ['employee' => $logged_user,'freelancer'=>$escrow_money,'amount'=>$amount2], function($message) use ($send_mail) {
				$message->to($send_mail, 'From Softral Job')->subject('Softral - Employee Released Payment');
			});
			
			return redirect('financial/terms_milestone?p_id='.$proposal_id);
		
	}	
	
	public function releaseBonus(Request $request){
		$input = $request->all();
		$logged_user = $this->auth->getLoggedUser();
		
		$money = Money::where('user_id',$logged_user->id)->orderBy('id','desc')->first();
		
			$amount1=$input['amount'];
			$job_id=$input['job_id'];
			$proposal_id=$input['proposal_id'];
			$proposal = Proposal::where('job_id',$input['job_id'])->orderBy('id','desc')->first();
			
			$money_freelancer = Money::where('user_id',$proposal['user_id'])->orderBy('id','desc')->first();
			
			if($amount1>$money['total_amount']):
				Session::flash('error', 'Sorry, You have insufficient balance to send bonus.');
				return redirect('financial/terms_milestone?p_id='.$proposal_id);
			endif;
			
			if(!empty($money)):
					$total_amount=$money['total_amount']-$amount1;
				else:
					$total_amount=$amount1;
			endif;	
			
			if(!empty($money_freelancer)):
					$total_amount_freelancer=$money_freelancer['total_amount']+$amount1;
				else:
					$total_amount_freelancer=$amount1;
			endif;
			
			$money_has = with(new Money)->escrowmoney_add($job_id,$logged_user->id,'bonus',$total_amount,$amount1);
			
			$escrow_has = with(new Money)->add_bonus_release($job_id,$proposal['user_id'],$total_amount_freelancer,$amount1);
	
			Session::flash('message', 'You have successfully sent a bonus to freelancer.');
			return redirect('financial/terms_milestone?p_id='.$proposal_id);
		
		
	}
	
	public function saveTermsMilestone(Request $request){
		$input = $request->all();
		
		if(isset($input['accept'])):
			$input['terms_milestone']=1;
			
			$milestone = Proposal::findOrFail($input['proposal_id']);
			$milestone->fill($input)->save();
			
			$input['job_id']=$milestone['job_id'];
			$input['proposal_id']=$input['proposal_id'];
			$contract=Contract::create($input);
			
			$send_mail=config('app.admin_email');
			Mail::send('emails.approve_contract', ['proposal' => $milestone], function($message) use ($send_mail) {
				$message->to($send_mail, 'From Softral Job')->subject('Softral - Approve the Contract');
			});
			
			$freelancer_email=$milestone['user']['email'];
			Mail::send('emails.escrow_money_freelancer', ['proposal' => $milestone], function($message) use ($freelancer_email) {
				$message->to($freelancer_email, 'From Softral Job')->subject('Softral - Job Notification');
			});
			
			//$employee_mail=$proposal['job']['user']['email'];
			$employee_mail=$milestone['job']['user']['email'];
			Mail::send('emails.escrow_money_employee', ['proposal' => $milestone], function($message) use ($employee_mail) {
				$message->to($employee_mail, 'From Softral Job')->subject('Softral - Escrow the money');
			});
			
			Session::flash('message', 'You have successfully approved the terms and condition. Please wait until Softral Admin Approve the contract.');
			return redirect('financial/terms_milestone?p_id='.$input['proposal_id']);
		else:
			$milestone = Proposal::findOrFail($input['proposal_id']);
			$contract = Contract::where('proposal_id', $input['proposal_id'])->where('job_id',$milestone['job_id'])->firstOrFail();
			$input2['ended_contract']=1;
			$contract->fill($input2)->save();
			
			$send_mail=$milestone['job']['user']['email'];
			
			Mail::send('emails.cancel_contract', ['proposal' => $milestone,'label'=>'Ended'], function($message) use ($send_mail) {
				$message->to($send_mail, 'From Softral Job')->subject('Softral - End the Contract');
			});
			
			Session::flash('message', 'You have successfully ended the contract.');
			return redirect('employee_feedback?c_id='.$contract->id);	
		endif;
	}
	
	public function Accountsave(Request $request)
	{
		$this->validate($request, [
			'skrill_account' => 'required'
		]);
		
		$input = $request->all();
		$logged_user = $this->auth->getLoggedUser();
		$input['user_id']= $logged_user->id;
		$skrill = Financial::where('user_id',$logged_user->id)->first();
		$skrill_account_exist = Financial::where('skrill_account',$input['skrill_account'])->first();
		
		if(!empty($skrill_account_exist)):
			Session::flash('error', 'This account is already integrated by another user. Please choose another account!');
			return redirect()->route('addskrill');	
		endif;
		
			Financial::create($input);
			Session::flash('message', 'Skrill account successfully Added!');
		
		return redirect()->route('addskrill');	
	}
	
	 public function Paypalsave(Request $request)
    {
        $this->validate($request, [
                'paypal_account' => 'required'
        ]);
    
        $input = $request->all();
        
        $logged_user = $this->auth->getLoggedUser();
        $input['user_id']= $logged_user->id;
        $paypal = Financial::where('user_id',$logged_user->id)->first();
        
        $paypal_account_exist = Financial::where('paypal_account',$input['paypal_account'])->first();
     
        if(!empty($paypal_account_exist)):
        Session::flash('error', 'This account is already integrated by another user. Please choose another account!');
        return redirect()->route('addpaypal');
        endif;
    
        Financial::create($input);
        Session::flash('message', 'Paypal account successfully Added!');
    
        return redirect()->route('addpaypal');
    }
	
	public function Banksave(Request $request)
	{
		$validation='';
		$logged_user = $this->auth->getLoggedUser();
		if($logged_user->user_profile[0]->country=='United States'):
			$validation="required";
		endif;
		
		$this->validate($request, [
			'bank_account' => 'required',
			'account_name' => 'required',
			'account_number' => 'required|numeric',
			//'branch_name' => 'required',
			'recipient_address' => 'required',
			//'swift_code' => 'required',
			//'iban_code' => 'required',
			'routing_number' => $validation,
		]);
		
		$input = $request->all();
		
		$input['user_id']= $logged_user->id;
		$input['routing_number']=with(new Credit)->cc_encrypt($input['routing_number']);
		
		if((Input::get('id'))):
			
		else:
			$insert=Financial::create($input);
			$input['financial_accounts_id']=$insert->id;
			Bank::create($input);
			Session::flash('message', 'Bank account successfully Added! It takes 3 to 5 business days for your account to be approved.');
		endif;
		
		return redirect()->route('addbank');	
	}
	
	public function Creditsave(Request $request)
	{
		$this->validate($request, [
			'card_number' => 'required|numeric',
			'person_name' => 'required',
			'security_code' => 'required|numeric',
			'address' => 'required',
			'country' => 'required',
			'state' => 'required',
			'city' => 'required',
			'zipcode' => 'required'
		]);
		$input = $request->all();
		
		$card_number=with(new Credit)->is_valid_card($input['card_number']);
		if(!$card_number):
			Session::flash('error', 'Your credit card is not valid! Please verify your credit card number.');
			return redirect()->route('addcredit');	
		endif;
	
		$input['card_number']=with(new Credit)->cc_encrypt($input['card_number']);
		$input['exp_month']=with(new Credit)->cc_encrypt($input['exp_month']);
		$input['exp_year']=with(new Credit)->cc_encrypt($input['exp_year']);
		$input['security_code']=with(new Credit)->cc_encrypt($input['security_code']);
		
		$logged_user = $this->auth->getLoggedUser();
		$input['user_id']= $logged_user->id;
		
		if((Input::get('id'))):
			
		else:
			$insert=Financial::create($input);
			$input['financial_accounts_id']=$insert->id;
			Credit::create($input);
			Session::flash('message', 'Credit card info successfully Added!');
		endif;
		
		return redirect()->route('addcredit');	
	}
	
	public function WithdrawSave(Request $request){
		
		$money=new Money();
		$input = $request->all();
		$logged_user = $this->auth->getLoggedUser();
		$methods = Financial::where('user_id',$logged_user->id)->get();
		$logged_email=$logged_user->email;
		$softral_amount = Money::where('user_id',1)->orderBy('id','desc')->first();
	
		$freelancer_amount = User::where('id',1)->first();
		$amount_to_softral=$freelancer_amount['freelancer_fee'];
		
		$money_has = with(new Money)->getmoney_has($logged_user->id);
		if(empty($methods)):
			Session::flash('error', 'Please add atleast one Payment Method to Withdraw amount.');
			return redirect()->route('withdraw');	
		endif;
		
		if(!$money_has):
			Session::flash('error', 'Sorry, You do not have sufficient amount to be withdrawn.');
		else:
			 $total_amount= round($money_has->total_amount,2);
			 $result = $total_amount-$input['withdraw_amount'];
			 $withdraw_amount=$input['withdraw_amount']-$freelancer_amount['freelancer_fee'];
			 $financial_account_name=str_replace('_',' ',$input['financial_account_name']);
			 
			 	if(!empty($softral_amount)):
					$softral_amount=$softral_amount['total_amount']+$amount_to_softral;
				else:
					$softral_amount=$amount_to_softral;
				endif;
		
			
			 if($input['withdraw_amount']>$total_amount):
				Session::flash('error', 'Sorry, Your money is exceeded then total amount');
				return redirect()->route('withdraw');	
			 endif;
			 
			 $insert_money_data = with(new Money)->insert_money_data($result,$input['financial_accounts_id'],$withdraw_amount,$input['financial_account_name'],$logged_user->id);
			 $amount_to_softral = with(new Money)->add_escrow_release(0,1,$softral_amount,$amount_to_softral,$logged_user->id);
			 
			 $account_email = Financial::where('id',$input['financial_accounts_id'])->first();
			 
			 $send_mails=config('app.admin_email');
			 Mail::send('emails.skrill_money_request', ['user'=>$logged_user,'data' => $input,'account_type'=>str_replace('_',' ',$input['financial_account_name']),'result'=>$result,'account_email'=> $account_email], function($message) use ($logged_email,$send_mails) {
				$message->to($logged_email, 'From Softral Payment System')->subject('Softral - Withdraw Request');
				$message->bcc($send_mails, 'From Softral Payment System')->subject('Softral - Withdraw Request');
			});
			 Session::flash('message', 'You have successfully withdrawn amount using '. $financial_account_name.', You will shortly get an Email');
		endif;
		
		return redirect()->route('withdraw');	
		
	}
	
	public function financialTransaction(){
		$logged_user = $this->auth->getLoggedUser();
		
		if($logged_user->id==1):
			$jobs = Job::where('selected',1)->select('id')->get();
			$proposals = Proposal::where('offer',1)->select('job_id','id')->get();
		else:
			$jobs = Job::where('user_id',$logged_user->id)->where('selected',1)->select('id')->get();
			$proposals = Proposal::where('user_id',$logged_user->id)->where('offer',1)->select('job_id','id')->get();
		endif;
		
		$job_array=array();
		$proposal_array=array();
		
		foreach($jobs as $job_id):
			$job_array[]=$job_id->id;
		endforeach;
		
		foreach($proposals as $proposal_id):
			$job_array[]=$proposal_id->job_id;
			$proposal_array[]=$proposal_id->id;
		endforeach;
		$job_array[]=0;
	
		$money = Money::where('user_id',$logged_user->id)->orderBy('id','desc')->first();
		//$all_transactions = Money::whereIn('job_id', $job_array)->orderBy('id','desc')->get();
		$all_transactions = Milestone::whereIn('job_id', $job_array)->orderBy('id','desc')->groupBy('job_id')->get();
		
		$transactions=array();
		foreach($all_transactions as $transaction):
			$transactions[]= Milestone::with('job','proposal','escrow')->where('job_id', $transaction['job_id'])->where('proposal_id', $transaction['proposal_id'])->orderBy('id','desc')->get();
		endforeach;
		//echo $transactions[0]['job']['id'];exit;
		//echo '<pre>';print_r($transactions);exit;
		if(isset($money->total_amount)):
			$money= round($money->total_amount,2);
		else:
			$money=0.00;
		endif;
			
		return View::make('laravel-authentication-acl::admin.financial.financialTransaction')->with([ 'money'=>$money,'all_transactions'=>$transactions,'user_id'=>$logged_user->id]);
	}
	
	public function acceptMilestone(Request $request){
		$input = $request->all();
		
		$logged_user = $this->auth->getLoggedUser();
		$escrow_money = Escrow::where('job_id',$input['job_id'])->orderBy('id','desc')->first();
		$money = Money::where('user_id',$escrow_money['proposal']['user_id'])->orderBy('id','desc')->first();
		$softral_amount = Money::where('user_id',1)->orderBy('id','desc')->first();
		$freelancer_amount = User::where('id',1)->first();
		//echo $escrow_money['proposal']['user']['user_profile'][0]['first_name'];exit;
		$amount2=$input['amount'];
		$amount_to_softral=$freelancer_amount['employee_fee'];
		$job_id=$input['job_id'];
		$proposal_id=$input['proposal_id'];
		$amount1=$amount2-$amount_to_softral;
	
		if(!empty($escrow_money)):
			if($amount2>$escrow_money['amount']):
				Session::flash('error', 'Sorry, You have insufficient balance in your Escrow to release amount.');
				return redirect('financial/terms_milestone?p_id='.$proposal_id);
			endif;
			$amount=$escrow_money['amount']-$amount2;
		else:
			Session::flash('error', 'Sorry you cannot release this payment due to insufficient in your Escrow account.');
			return redirect('financial/terms_milestone?p_id='.$proposal_id);
		endif;
		
		if($amount_to_softral>$amount2):
				Session::flash('error', 'Sorry, You must release higher amount then Softral Employee Fee.');
				return redirect('financial/terms_milestone?p_id='.$proposal_id);
		endif;
		
		if(!empty($money)):
					$total_amount=$money['total_amount']+$amount1;
				else:
					$total_amount=$amount1;
		endif;	
		
		if(!empty($softral_amount)):
					$softral_amount=$softral_amount['total_amount']+$amount_to_softral;
				else:
					$softral_amount=$amount_to_softral;
		endif;
			$upd=Milestone::where('id', $input['m_id'])->update(['status' => 1]);
			Session::flash('message', 'You have successfully released amount from escrow.');
			
			$escrow_has = with(new Escrow)->add_deposit($job_id,$proposal_id,$amount);
			$escrow_has = with(new Money)->add_escrow_release($job_id,$escrow_money['proposal']['user_id'],$total_amount,$amount1,0);
			$amount_to_softral = with(new Money)->add_escrow_release($job_id,1,$softral_amount,$amount_to_softral,$escrow_money['proposal']['user_id']);
			
			$send_mail=config('app.admin_email');
			Mail::send('emails.escrow_release', ['employee' => $logged_user,'freelancer'=>$escrow_money,'amount'=>$amount2], function($message) use ($send_mail) {
				$message->to($send_mail, 'From Softral Job')->subject('Softral - Employee Released Payment');
			});
			
		Session::flash('message', 'You have successfully accepted the milestone.');
		
		return redirect('financial/terms_milestone?p_id='.$input['proposal_id']);	
	}
	
	public function edit_getmilestone($id){
		$milestone = Milestone::where("id", $id)->first();
		
		return response()->json(['success' => true, 'label' => $milestone['label'], 'posted_date' => $milestone['posted_date'], 'amount' => $milestone['amount']]);
	}

	public function deletemyAccount(){
		
		$account = Financial::findOrFail(Input::get('id'));
		$account->delete();
		Session::flash('message', 'You have successfully deleted your account.');
 
		return redirect()->route('financial');	
	}
	
	public function adminaccountDelete(){
		
		$account = Financial::findOrFail(Input::get('id'));
		$account->delete();
		Session::flash('message', 'You have successfully deleted the account.');
 
		return redirect()->route('allaccounts');	
	}
	
	public function deleteMilestone(){
		
		$milestone_delete = Milestone::findOrFail(Input::get('id'));
		
		$get_amount = Milestone::where('id',Input::get('id'))->first();
		$last_get_amount = Milestone::where('proposal_id',Input::get('p_id'))->orderBy('id','ASC')->first();

		$update_milestone = $last_get_amount['amount']+$get_amount['amount'];
		
		$input1['amount']=$update_milestone;
		
		$milestone = Milestone::findOrFail($last_get_amount->id);
		$milestone->fill($input1)->save();
		
		$milestone_delete->delete();
		Session::flash('message', 'You have successfully deleted the milestone!');
 
		return redirect('financial/terms_milestone?p_id='.Input::get('p_id'));
	}
	
	public function requestMoneyDelete(){
		
		$money = Money::findOrFail(Input::get('id'));
		$money->delete();
		Session::flash('message', 'You have successfully deleted the item.');
 
		return redirect()->route('requestMoney');	
	}
	
	 public function getCheckout()
    {
        $payer = PayPal::Payer();
        $payer->setPaymentMethod('paypal');
    
        $amount = PayPal:: Amount();
        $amount->setCurrency('USD');
        $amount->setTotal(42); // This is the simple way,
        // you can alternatively describe everything in the order separately;
        // Reference the PayPal PHP REST SDK for details.
    
        $transaction = PayPal::Transaction();
        $transaction->setAmount($amount);
        $transaction->setDescription('What are you selling?');
    
        $redirectUrls = PayPal:: RedirectUrls();
        $redirectUrls->setReturnUrl(action('FinancialController@getDone'));
        $redirectUrls->setCancelUrl(action('FinancialController@getCancel'));
    
        $payment = PayPal::Payment();
        $payment->setIntent('sale');
        $payment->setPayer($payer);
        $payment->setRedirectUrls($redirectUrls);
        $payment->setTransactions(array($transaction));
    
        $response = $payment->create($this->_apiContext);
        $redirectUrl = $response->links[1]->href;
    
        return Redirect::to( $redirectUrl );
    }
 
    
     public function getDone()
    {     
        
        $logged_user = $this->auth->getLoggedUser();
        
		$id = Session::get('money_id');
       
       
        $upd=DB::table('user_money')->where('id', $id)->update(['completed' => 1]);
 
        Session::flash('message', 'You have successfully sent payment to the freelancer.');
        
        $moneys = Money::with('user','financial_account')->where('withdraw_amount','!=',0)->orderBy('id','desc')->get();
        Session::forget('money_id');
        return View::make('laravel-authentication-acl::admin.financial.requestMoney')->with([ 'moneys'   => $moneys]);
       
    }
    
    public function getCancel()
    {
        // Curse and humiliate the user for cancelling this most sacred payment (yours)
        return view('checkout.cancel');
    }
  
}
