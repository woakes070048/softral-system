<?php namespace App\Http\Controllers;

use App\Http\Models\Category;
use App\Http\Models\Skill;
use App\Http\Models\Job;
use App\Http\Models\Ad;
use App\Http\Models\Page;
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
use View, Input, Redirect, App, Config,Session,Mail;
use LaravelAcl\Authentication\Interfaces\AuthenticateInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use DB;

class MailController extends Controller {

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

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		//$this->middleware('guest');
	}

	/**
	 * Show the application welcome screen to the user.
	 *
	 * @return Response
	 */
	public function mailSend(Request $request)
	{
		
			$input = $request->all();
			$users = User::has('user_profile_avtar.profile_field_type_seller')->where('activated',1)->where('banned',0)->where('id','!=',1)->get();
			foreach($users as $user) {
				$parent_selector[$user->email] = $user->email; // I assume name attribute contains client name here
			}
			
			if($input):
			 $message1=$input['message'];	
			 $users_array=$input['users'];	
			  for($i=0;$i<count($users_array);$i++):
			    $logged_email=$users_array[$i];
				$user_info = User::where('activated',1)->where('banned',0)->where('id','!=',1)->first();
				Mail::send('emails.admin_send_mail', ['user'=>$user_info,'message1'=>$message1], function($message) use ($logged_email) {
					$message->to($logged_email, 'From Softral')->subject('Softral - Notification');
				});
			  endfor;	
			 
			 Session::flash('message', 'You have successfully sent a message to the users.');
			 return redirect()->route('mailSend');	
			endif;
			return view::make('financial.sendmail')->with(['users'=>$parent_selector]);
	}
	
  
	

}
