<?php namespace App\Http\Controllers;

use App\Http\Models\Category;
use App\Http\Models\Skill;
use App\Http\Models\Page;
use App\Http\Models\Ad;
use LaravelAcl\Authentication\Models\ProfileField;
use App\Http\Models\Contract;
use LaravelAcl\Authentication\Models\User;
use App\Http\Models\Feedback;
use App\Http\Models\Proposal;
use App\Http\Models\Savejob;
use App\Http\Models\Job;
use Illuminate\Http\Request;
use LaravelAcl\Authentication\Models\UserProfile;
use View, Input, Redirect, App, Config,Session,Mail;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination;
use Illuminate\Pagination\LengthAwarePaginator;
use LaravelAcl\Authentication\Interfaces\AuthenticateInterface;

use DB;


class WelcomeController extends Controller {

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
	public function __construct(AuthenticateInterface $auth)
	{
		$this->middleware('guest');
		$this->auth = $auth;
	}

	/**
	 * Show the application welcome screen to the user.
	 *
	 * @return Response
	 */
	public function index()
	{
		$about = Page::where('slug','about-us')->first();
		$hexa_gonal_background = Page::where('id',21)->first();
		
		$freelancers = User::has('user_profile_avtar.profile_field_type_seller')->with('user_profile_avtar.profile_field_type_tagline')->where('activated',1)->orderByRaw("RAND()")->limit(28)->get();
		//dd($freelancers[0]->user_profile[0]->profile_field_type_tagline);
		$employeer_news = Page::with('children_hexa')->where('id',85)->first();
		$freelancer_news = Page::with('children_hexa')->where('id',80)->first();
		//echo '<pre>';print_r($freelancers);exit;
		return view::make('main_welcome')->with([ 'about'   => $about ,'hexa_gonal_background'=>$hexa_gonal_background,'freelancers'   => $freelancers,'employeer_news'=>$employeer_news,'freelancer_news'=>$freelancer_news ]);
	}
	
	public function home($skill=null,$category=null){
		if(Input::get('sorting')):
			$sorting= explode('-',Input::get('sorting'),3);
			if($sorting[0]=='budget'):
				$sorting_keyword=$sorting[0];
				$sorting_value=$sorting[1];
			else:
				$sorting_keyword=$sorting[0].'_'.$sorting[1];
				$sorting_value=$sorting[2];
			endif;
		else:
			$sorting_keyword='ID';
			$sorting_value='DESC';
		endif;
		
		$q = Input::get('q');
		$skill_q='';
		
		if(Input::get('min')):
			$min=Input::get('min');
		else:
			$min=0;
		endif;
		
		if(Input::get('max')):
			$max=Input::get('max');
		else:
			$max=100000;
		endif;
		
		if(Input::get('status')):
			$status_array=array(1);
		else:
			$status_array=array(0);
		endif;
		
		$budget_array=array($min,$max);
		
		$max = Input::get('max');
		$min = Input::get('min');
		$category = \Request::segment(2);
		
		$subcategories=array();
		if($category!=null && $skill=='category'):
			$pcategories = Category::with('children')->where('slug',$category)->first();
			if(!empty($pcategories)):
					array_push($subcategories,$pcategories->id);
				foreach($pcategories->children as $subcategory):
					array_push($subcategories, $subcategory->id);
				endforeach;
			endif;
		endif;
		
		if($category!=null && $skill=='skill'):
			$skill_name = Skill::where('slug',$category)->first();
			$skill_q=$skill_name->skill;
		endif;
		
		if(Input::get('search_type')=='jobs' || Input::get('search_type')==''):
			$jobs = Job::with('categories.parent_get','user','children')->subCategory($subcategories)->where(function($query) use ($q,$budget_array,$skill_q,$status_array) {
					/** @var $query Illuminate\Database\Query\Builder  */
					return $query->where('project_name', 'LIKE', '%'. $q .'%')
						->Where('skill_name','LIKE', '%'. $skill_q .'%')
						->whereIn('job_close', $status_array)
						->WhereBetween('budget', $budget_array);
				})->orderBy($sorting_keyword, $sorting_value)->paginate(10); 
			
			$jobs->appends(array('sorting'=>Input::get('sorting'),'status'=>Input::get('status'),'min'=>$min,'max'=>$max,'q' => Input::get('q')));
		elseif(Input::get('search_type')=='freelancer'):
						$jobs = UserProfile::ByUseractivate(1)->where(function($query) use ($q) {
							return $query->where('first_name', 'LIKE', '%'. $q .'%')
								->orWhere('last_name','LIKE', '%'. $q .'%');
							})->paginate(10); 
			
				$jobs->appends(array('search_type'=>Input::get('search_type'),'q' => Input::get('q')));
		endif;
		//dd($freelancer);exit;
		
		if($category!=null && $skill=='category'):
			$jobs->setPath('');
			$category=$pcategories->category;
		endif;
		
		if($category!=null && $skill=='skill'):
			$jobs->setPath('');
			$category=$skill_name->skill;
		endif;
		
		return view::make('welcome')->with([ 'jobs'   => $jobs,'category'=>$category,'skill'=>ucwords($skill) ]);
	}
	
	public function members(){
		
		if((Input::get('search_type'))):
			$user_type = Input::get('search_type');
		else:
			$user_type='';
		endif;
		
		if((Input::get('q'))):
			$q = Input::get('q');
		else:
			$q='';
		endif;
		
		$users = UserProfile::ByUseractivate(1)->ByUsertype($user_type)->where(function($query) use ($q) {
							return $query->where('first_name', 'LIKE', '%'. $q .'%')
								->orWhere('last_name','LIKE', '%'. $q .'%');
							})->paginate(10); 
		$users->setPath('');	
		$users->appends(array('search_type'=>Input::get('search_type'),'q' => Input::get('q')));
				
		return view::make('members')->with([ 'jobs'   => $users ]);
	}
	
	public function MyProposals(){
		$logged_user = $this->auth->getLoggedUser();
		
		if($logged_user->user_profile[0]->profile_field_type->value!='Seller' && $logged_user->user_profile[0]->profile_field_type->value!='Both'):
			return view('laravel-authentication-acl::client.exceptions.404');
		endif;
		
		$proposals = Proposal::with('user','user_profile.profile_field','job')->where("user_id",  $logged_user->id)->paginate(10);
		$proposals->setPath('');
		//dd($proposals);exit;
		return view::make('proposal.my_proposals')->with([ 'proposals'   => $proposals]);
	}
	
	public function jobProposals(){
		$logged_user = $this->auth->getLoggedUser();
		$job = Job::with('contract')->where("user_id",  $logged_user->id)->where("id",  Input::get('job-id'))->first();
		if(empty($job)):
						return view('laravel-authentication-acl::client.exceptions.404');
		endif;
		$proposals = Proposal::with('user','user_profile.profile_field','job')->where("job_id",  Input::get('job-id'))->paginate(10);
		
		$no_more=0;
		if(isset($job->contract) && ($job->contract->cancel_contract==1 || $job->contract->ended_contract==1)):
			$no_more=1;
		endif;
		
		$proposals->setPath('');
		
		return view::make('proposal.job_proposals')->with([ 'proposals'   => $proposals,'job'=>$job,'no_more'=>$no_more]);
	}
	
	public function saveJobs(){
		$logged_user = $this->auth->getLoggedUser();
		$save_jobs = Savejob::with('job')->where("user_id",  $logged_user->id)->paginate(10);
		$save_jobs->setPath('');
		
		return view::make('job.my_savejobs')->with([ 'save_jobs'   => $save_jobs]);
	}
	
	public function myJobs(){
		$logged_user = $this->auth->getLoggedUser();
		
		if($logged_user->user_profile[0]->profile_field_type->value!='Buyer' && $logged_user->user_profile[0]->profile_field_type->value!='Both'):
			return view('laravel-authentication-acl::client.exceptions.404');
		endif;
		
		$my_jobs = Job::with('user','proposal_selected','contract')->where("user_id",  $logged_user->id)->orderBy('created_at', 'desc')->paginate(10);
		$my_jobs->setPath('');
		
		return view::make('job.my_jobs')->with([ 'my_jobs'   => $my_jobs]);
	}
	
	public function myContracts(){
		$logged_user = $this->auth->getLoggedUser();
		
		if($logged_user->user_profile[0]->profile_field_type->value!='Buyer' && $logged_user->user_profile[0]->profile_field_type->value!='Both'):
			return view('laravel-authentication-acl::client.exceptions.404');
		endif;
		
		$my_jobs = Contract::with('job','proposal','proposal_selected')->JobUserid($logged_user->id)->orderBy('created_at', 'desc')->paginate(10);
		$my_jobs->setPath('');
		
		return view::make('job.my_contracts')->with([ 'my_jobs'   => $my_jobs]);
	}
	
	public function runningJobs(){
		$logged_user = $this->auth->getLoggedUser();
		
		if($logged_user->user_profile[0]->profile_field_type->value!='Seller' && $logged_user->user_profile[0]->profile_field_type->value!='Both'):
			return view('laravel-authentication-acl::client.exceptions.404');
		endif;
		
		$my_jobs = Contract::with('user','job','proposal')->ProposalUserid($logged_user->id)->orderBy('id', 'desc')->paginate(10);
		$my_jobs->setPath('');
		//dd($my_jobs);exit;
		return view::make('job.my_runningjobs')->with([ 'save_jobs'   => $my_jobs]);
	}
	
	public function myAds(){
		$logged_user = $this->auth->getLoggedUser();
		$my_ads = Ad::with('user','messages')->where("user_id",  $logged_user->id)->orderBy('created_at', 'desc')->paginate(10);
		$my_ads->setPath('');
		//dd($proposals);exit;
		return view::make('ad.my_ads')->with([ 'my_ads'   => $my_ads]);
	}
	
	public function userProfileslug($slug){
		
		$profile = UserProfile::with('profile_field','jobs')->where('slug',$slug)->first();
		
		$tag_line = ProfileField::where('profile_id',$profile->id)->where('profile_field_type_id',4)->first();
		$feedback_freelancer = Feedback::where("freelancer_id",  $profile->user_id)->where('freelancer_rating','!=','')->avg('freelancer_rating');
		$feedback_employee = Feedback::where("employee_id",  $profile->user_id)->where('employee_rating','!=','')->avg('employee_rating');
		
		return view::make('user.profile')->with([ 'profile'   => $profile,'tagline'=>$tag_line['value'],'feedback_freelancer'=>$feedback_freelancer,'feedback_employee'=>$feedback_employee]);
	}
	
	public function GetPages($slug){
		$page = Page::with('children')->where('slug',$slug)->first();
		//dd($page);exit;
		return view::make('page')->with([ 'page'   => $page,'slug'=>$slug]);
	}
	
	public function ContactUs(){
		$page = Page::with('children_hexa')->where('id',3)->first();
		
		return view::make('contact')->with([ 'page'   => $page]);
	}
	
	public function sendEmail(Request $request){
		$input = $request->all();
		
		$emails = ['admin@softral.com', 'mednoor1@gmail.com'];
		 Mail::send('emails.contact_message', ['data' => $input], function($message) use ($emails) {
				$message->to($emails, 'From Softral - Contact Us')->subject('Softral - Got Message');
			});
		Session::flash('message', 'You have successfully sent a message to Softral.');
		return redirect('/pages/contact-us');
	}
	
	
}
