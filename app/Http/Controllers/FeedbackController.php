<?php namespace App\Http\Controllers;


use App\Http\Models\Job;
use App\Http\Models\Proposal;
use App\Http\Models\Feedback;
use App\Http\Models\Contract;
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
use View, Input, Redirect, App, Config,Session,Mail,Crypt;
use LaravelAcl\Authentication\Interfaces\AuthenticateInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use DB;

class FeedbackController extends Controller {

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
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct(AuthenticateInterface $auth)
	{
		//$this->middleware('auth');
		 $this->auth = $auth;
	}

	/**
	 * Show the application welcome screen to the user.
	 *
	 * @return Response
	 */
	public function employeeFeedback()
	{
		$c_id=Input::get('c_id');
		$logged_user = $this->auth->getLoggedUser();
		$contract=Contract::with('proposal','job')->where('id',$c_id)->first();
		
		$check_authenticate=Job::where('id',$contract['job_id'])->where('user_id',$logged_user['id'])->first();
		if(empty($check_authenticate)):
			return view('laravel-authentication-acl::client.exceptions.404');
		endif;
		
		$feedback=Feedback::where('contract_id',$c_id)->where('freelancer_id',$contract['proposal']['user_id'])->where('employee_id',$contract['job']['user_id'])->where('freelancer_comment','!=','')->first();
		if(!empty($feedback)):
			return view('laravel-authentication-acl::client.exceptions.404');
		endif;
		
		return View::make('feedback.employee_feedback')->with([ 'contract'   => $contract]);
	}
	
	public function freelancerFeedback()
	{
		$p_id=Input::get('p_id');
		$logged_user = $this->auth->getLoggedUser();
		$contract=Contract::with('proposal','job')->where('proposal_id',$p_id)->where('job_id',Input::get('job_id'))->where('ended_contract',1)->first();
		
		$check_authenticate=Proposal::where('id',$contract['proposal_id'])->where('user_id',$logged_user['id'])->first();
		if(empty($check_authenticate)):
			return view('laravel-authentication-acl::client.exceptions.404');
		endif;
		
		$feedback=Feedback::where('freelancer_id',$contract['proposal']['user_id'])->where('employee_id',$contract['job']['user_id'])->where('employee_comment','!=','')->first();
		if(!empty($feedback)):
			return view('laravel-authentication-acl::client.exceptions.404');
		endif;
		
		return View::make('feedback.freelancer_feedback')->with([ 'contract'   => $contract]);
	}
	
	public function EmployeeratingSave(Request $request){
		$input=$request->all();
		
		$this->validate($request, [
			'freelancer_comment' => 'required'
		]);
		$feedback=Contract::with('proposal','job')->where('id',$input['contract_id'])->first();
		
		$feedback_condition=Feedback::where('contract_id',$input['contract_id'])->where('freelancer_id',$feedback['proposal']['user_id'])->where('employee_id',$feedback['job']['user_id'])->where('freelancer_comment','!=','')->first();
		if(!empty($feedback_condition)):
			return view('laravel-authentication-acl::client.exceptions.404');
		endif;
		
		
		$input['freelancer_id']=$feedback['proposal']['user_id'];
		$input['employee_id']=$feedback['job']['user_id'];
		
		Feedback::create($input);
		Session::flash('message', 'Feedback has been successfully Added!');
		
		return redirect()->route('welcome.myJobs');	
	}
	
	public function FreelancerratingSave(Request $request){
		$input=$request->all();
		
		$this->validate($request, [
			'employee_comment' => 'required'
		]);
		$feedback=Contract::with('proposal','job')->where('id',$input['contract_id'])->first();
		
		$feedback_condition=Feedback::where('contract_id',$input['contract_id'])->where('freelancer_id',$feedback['proposal']['user_id'])->where('employee_id',$feedback['job']['user_id'])->where('employee_comment','!=','')->first();
		if(!empty($feedback_condition)):
			return view('laravel-authentication-acl::client.exceptions.404');
		endif;
		
		
		$input['freelancer_id']=$feedback['proposal']['user_id'];
		$input['employee_id']=$feedback['job']['user_id'];
		
		Feedback::create($input);
		Session::flash('message', 'Feedback has been successfully Added!');
		
		return redirect()->route('welcome.runningJobs');	
	}
	
  
}
