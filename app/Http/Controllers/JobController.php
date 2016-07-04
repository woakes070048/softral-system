<?php namespace App\Http\Controllers;

use App\Http\Models\Category;
use App\Http\Models\Skill;
use App\Http\Models\Job;
use App\Http\Models\Proposal;
use App\Http\Models\Savejob;
use App\Http\Models\Milestone;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use LaravelAcl\Authentication\Models\ProfileField;
use App\Http\Models\Financial;
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
use Illuminate\Pagination\LengthAwarePaginator;
use Response;
use Illuminate\Support\Str;
use DB;

class JobController extends Controller {

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
		 $this->middleware('guest');
		 $this->auth = $auth;
	}

	/**
	 * Show the application welcome screen to the user.
	 *
	 * @return Response
	 */
	public function addJob()
	{
		 $categories = Category::with('children')->where('type','Job')->get();
		 $skills = Skill::get();
		 $logged_user = $this->auth->getLoggedUser();
		 $parent_selector = array();
		 $cant_post=0;
		 
		if($logged_user->user_profile[0]->profile_field_type->value!='Buyer' && $logged_user->user_profile[0]->profile_field_type->value!='Both'):
			return view('laravel-authentication-acl::client.exceptions.404');
		endif;
		
		foreach($skills as $skill) {
			$parent_selector[$skill->id] = $skill->skill; // I assume name attribute contains client name here
		}
		
		if((Input::get('id'))):
            $job = Job::where('user_id',$logged_user->id)->where('id',Input::get('id'))->first();
			if(empty($job)):
				return view('laravel-authentication-acl::client.exceptions.404');
			endif;
			//$skill_id[]=unserialize($job->skill_id);
        else:
            $job = new Job;
		endif;
		
		if(isset($logged_user->user_profile[0]->id)):
			$employee_type = ProfileField::where('profile_id',$logged_user->user_profile[0]->id)->where('profile_field_type_id','=','1')->first();
			if(isset($employee_type['value']) &&  ($employee_type['value']=='Both' ||  $employee_type['value']=='Buyer')):
		
				$accounts = Financial::where('user_id',$logged_user->id)->Orwhere('person_name','!=','')->where('bank_account','!=','')->get();
				$accounts_array=$accounts->toArray();
				if(empty($accounts_array)):
					$cant_post=1;
				endif;
			endif;
		endif;
		//dd($job);exit;
		 return View::make('job.addjob')->with([ 'categories'   => $categories,'skills'   => $parent_selector,'job'=>$job,'cant_post'=>$cant_post]);
	}
	
	public function Jobsave(Request $request)
	{
		$input = $request->all();

		$validation='';
		
		if($input['job_type']=='hourly'):
			$validation="required";
		endif;
		
		$this->validate($request, [
			'project_name' => 'required|min:8|max:120',
			'category_id' => 'required',
			'skill_id' => 'required',
			'description' => 'required',
			'hourperweek' => $validation,
			'budget' =>'digits_between:1,9',
		]);
		 $logged_user = $this->auth->getLoggedUser();
		 
		 if(isset($logged_user->user_profile[0]->id)):
			$employee_type = ProfileField::where('profile_id',$logged_user->user_profile[0]->id)->where('profile_field_type_id','=','1')->first();
			if(isset($employee_type['value']) &&  ($employee_type['value']=='Both' ||  $employee_type['value']=='Buyer')):
		
				$accounts = Financial::where('user_id',$logged_user->id)->Orwhere('person_name','!=','')->where('bank_account','!=','')->get();
				$accounts_array=$accounts->toArray();
				if(empty($accounts_array)):
					Session::flash('error', 'You must add either Bank account or Financial account to be procceed.');
					return redirect()->route('financial');	
				endif;
			endif;
		endif;
		
		$files = Input::file('images');
		$file_count = count($files);
		
		$file_count = count($files);
    // start count how many uploaded
			$uploadcount = 0;
			$images=array();
			$skill_name='';
			
			if($files[0]!=''):
			foreach($files as $file) {
			
				$destinationPath = 'uploads';
				$filename = $file->getClientOriginalName();
				$upload_success = $file->move($destinationPath, $filename);
				$uploadcount ++;
				$images[]=$filename;
			  }
			 endif;
		
	
		for($sk=0;$sk<count($input['skill_id']);$sk++):
			$skill = Skill::find($input['skill_id'][$sk]);
			$skill_name.=$skill->skill.',';
		endfor;
		
		$input['images']=serialize($images);
		$input['skill_id']=serialize($input['skill_id']);
		$input['user_id']= $logged_user->id;
		
		$input['skill_name']=$skill_name;
		
		$slug = str_slug($input['project_name'], "-");
		$LastSlug = Job::whereRaw("slug REGEXP '^{$slug}(-[0-9]*)?$'")
		->orderBy('slug', 'desc')
		->first();
		
		if(isset($LastSlug->slug)):
			$input['slug'] = "{$slug}-" . ((intval(str_replace("{$slug}-", '', $LastSlug->slug))) + 1);
		else:
			$input['slug'] =str_slug($input['project_name'], "-");
		endif;	
		
		Job::create($input);
		Session::flash('message', 'Job successfully added!');
		
		return redirect()->route('welcome.myJobs');	
	}
	
	public function Jobedit(Request $request)
	{
		$input = $request->all();
		$validation='';
		
		if($input['job_type']=='hourly'):
			$validation="required";
		endif;
		$this->validate($request, [
			'project_name' => 'required|min:8|max:120',
			'category_id' => 'required',
			'skill_id' => 'required',
			'description' => 'required',
			'hourperweek' => $validation,
			'budget' => 'digits_between:1,9',
		]);
		
		 $logged_user = $this->auth->getLoggedUser();
		
		$files = Input::file('images');
		$file_count = count($files);
		
		$file_count = count($files);
    // start count how many uploaded
			$uploadcount = 0;
			$images=array();
			$skill_name='';
			
			if($files[0]!=''):
			foreach($files as $file) {
			
				$destinationPath = 'uploads';
				$filename = $file->getClientOriginalName();
				$upload_success = $file->move($destinationPath, $filename);
				$uploadcount ++;
				$images[]=$filename;
			  }
			 endif;
		
		
		if(isset($input['image_string'])):
			for($image_string=0;$image_string<count($input['image_string']);$image_string++):
				$images[] =$input['image_string'][$image_string];
			endfor;
		endif;
		
		for($sk=0;$sk<count($input['skill_id']);$sk++):
			$skill = Skill::find($input['skill_id'][$sk]);
			$skill_name.=$skill->skill.',';
		endfor;
		
		$input['images']=serialize($images);
		$input['skill_id']=serialize($input['skill_id']);
		$input['user_id']= $logged_user->id;
		
		$input['skill_name']=$skill_name;
		
		$job = Job::findOrFail(Input::get('id'));
		$job->fill($input)->save();	
		
		Session::flash('message', 'Job successfully updated!');
		
		return redirect()->route('welcome.myJobs');	
	}
	
	public function AddProposal($job=null,$id=null)
	{
		$logged_user = $this->auth->getLoggedUser();
		$setting = User::where('id',1)->first();;
		
		if($logged_user->user_profile[0]->profile_field_type->value!='Seller' && $logged_user->user_profile[0]->profile_field_type->value!='Both'):
			return view('laravel-authentication-acl::client.exceptions.404');
		endif;
			$job_detail = Job::with('categories.parent_get','user','children')->where('slug', $job)->first();
			$exist_proposal = Proposal::where('user_id', $logged_user->id)->where('job_id', $job_detail->id)->first();
			
		if($job_detail->selected==1):
			Session::flash('error', 'Sorry, Freelancer selected for this job, You can not edit your proposal.');
			return redirect('job/'.$job_detail['slug']);
		endif;
			
		if(!empty($exist_proposal) && $job_detail['job_close']==1):
			return view('laravel-authentication-acl::client.exceptions.404');
		endif;
		
			try
			{
				$proposal = Proposal::find($id);
				$milestone = Milestone::where('proposal_id',$id)->first();;
			} catch(JacopoExceptionsInterface $e)
			{
				$proposal = new Proposal;
				$milestone = new milestone;
			}
			
			if($setting['employee_fee']!=''):
				$freelancer_fee=$setting['employee_fee'];
			else:
				$freelancer_fee='5';
			endif;
			
			return View::make('job.addProposal')->with([ 'job_detail'   => $job_detail,'proposal'=>$proposal,'milestone'=>$milestone,'freelancer_fee'=>$freelancer_fee]);
	}
	public function SaveProposal(Request $request)
	{
		 
			$input = $request->all();
			$logged_user = $this->auth->getLoggedUser();
			$amount=json_encode($input['amount']);
			$amount_array=$input['amount'];
			
			$input['user_id']= $logged_user->id;
			$input['amount']= $amount;
			$job_detail = Job::where('id', $input['job_id'])->first();
			$job_posted_email=$job_detail->user->email;
			
			if(Input::get('id')):
				$proposal = Proposal::findOrFail(Input::get('id'));
				Milestone::where('proposal_id', Input::get('id'))->update(['posted_date' => $amount_array['duration'],'amount'=>$amount_array['charged_client']]);
				
				$proposal->fill($input)->save();
				Session::flash('message', 'Proposal successfully updated!');
			else:
				$proposal=Proposal::create($input);
				
				$input['proposal_id']= $proposal->id;
				$input['label']= 'Final Deliverble';
				$input['amount']= $amount_array['charged_client'];
				$input['posted_date']= $amount_array['duration'];
				$milestone=Milestone::create($input);
				
				 Mail::send('emails.proposal_sent', ['user'=>$logged_user,'data' => $input,'job_detail'=>$job_detail,'proposal'=>$proposal], function($message) use ($job_posted_email) {
				$message->to($job_posted_email, 'From Softral Job')->subject('Softral - Got Proposal');
			});
				Session::flash('message', 'You have successfully sent a proposal!');
			endif;
		
			//return redirect()->route('job.addProposal/'.$job_detail['slug']);	
			return redirect('job/'.$job_detail['slug']);
	}
	
	public function deleteProposal(){
		$proposal = Proposal::findOrFail(Input::get('id'));
		$proposal->delete();
		Session::flash('message', 'Proposal successfully deleted!');
 
		return redirect()->route('job.myProposals');	
	}
	
	public function deletesaveJob(){
		
		$savejob = Savejob::where('user_id', Input::get('user_id'))->where('job_id', Input::get('job_id'))->delete();
		//$savejob->delete();
		Session::flash('message', 'You have successfully removed job from save jobs!');
 
		return redirect()->route('welcome.saveJobs');	
	}
	
	public function deletemyJob(){
		
		$myjob = Job::findOrFail(Input::get('id'));
		$myjob->delete();
		Session::flash('message', 'You have successfully deleted your job!');
 
		return redirect()->route('welcome.myJobs');	
	}
	
	public function closeJob(){
		
		$job_close = DB::table('jobs')->where('id', Input::get('job-id'))->update(array('job_close' => 1));
		$cancel_contract = DB::table('contract')->where('job_id', Input::get('job-id'))->update(array('ended_contract' => 1));
		
		Session::flash('message', 'You have successfully closed the job!');
		return redirect()->route('welcome.myJobs');	
 
	}	
	
	public function openJob(){
		
		$job_close = DB::table('jobs')->where('id', Input::get('job-id'))->update(array('job_close' => 0));
		
		Session::flash('message', 'You have successfully reopened the job!');
 
		return redirect()->route('welcome.myJobs');	
	}
	
	public function Selectproposal(){
		
		$proposal = DB::table('proposals')->where('id', Input::get('id'))->update(array('offer' => 1));
		$job = DB::table('jobs')->where('id', Input::get('job_id'))->update(array('selected' => 1));
		
		$proposal_detail = Proposal::with('job','user')->where('id', Input::get('id'))->first();
		Session::flash('message', 'You have successfully selected freelancer for your job! Please Escrow money to your job <a href="'.url()."financial/terms_milestone/?p_id=". $proposal_detail->id.'" target="_blank" class="btn btn-danger btn-sm btn-search">Escrow Money</a>');
		
		if($proposal_detail['freelancer_counter_amount']!=''):
			$input_amount=array();
			$amount=json_decode($proposal_detail['amount']);
			
			$input_amount['paid_to']=$proposal_detail['freelancer_counter_amount'];
			$input_amount['softral_fee']=$proposal_detail['freelancer_counter_amount']*5/100;
			$input_amount['charged_client']=$proposal_detail['freelancer_counter_amount']+$input_amount['softral_fee'];
			$input_amount['duration']=$amount->duration;
			$amount=json_encode($input_amount);
			
			Milestone::where('proposal_id', Input::get('id'))->update(['amount' => $input_amount['charged_client']]);
			Proposal::where('id', Input::get('id'))->update(['amount' => $amount]);
		endif;
		
		Mail::send('emails.select_proposal', ['proposal'=>$proposal_detail], function($message) use ($proposal_detail) {
			$message->to($proposal_detail->user->email, 'From Softral Job')->subject('Softral - Congrats, Your proposal has been selected');
		});
 
		return redirect()->route('welcome.myJobs');	
	}
	
	public function viewProposal(){
		$proposal = Proposal::with('job','user_profile.profile_field','contract')->where('id', Input::get('id'))->first();
		$logged_user = $this->auth->getLoggedUser();
		
		if(empty($proposal)):
			return view('laravel-authentication-acl::client.exceptions.404');
		endif;
		
		if($logged_user->id!=$proposal->user_id && $logged_user->id!=$proposal->job->user_id):
			return view('laravel-authentication-acl::client.exceptions.404');
		endif;
		
		$images=unserialize($proposal->job->images);
		$images_string='';

		if(!empty($images)):
			$images_string=implode(' ', array_map(function ($v, $k) { return "<a href='".url()."/download/".$v."' target='_blank'>".$v."</a>,"; }, $images, array_keys($images)));
		endif;
		
		$no_more=0;
		if(isset($proposal->contract) && ($proposal->contract->cancel_contract==1 || $proposal->contract->ended_contract==1)):
			$no_more=1;
		endif;
		
		return View::make('job.viewproposal')->with(['proposal'=>$proposal,'images_string'=>rtrim($images_string,','),'no_more'=>$no_more,'user_id'=>$logged_user->id]);	
	}
	
	public function counterOffer(Request $request){
		$input = $request->all();
		$proposal = Proposal::with('job','user_profile.profile_field','contract')->where('id', $input['p_id'])->first();
		$logged_user = $this->auth->getLoggedUser();
		
		if($logged_user->id!=$proposal->job->user_id && $logged_user->id!=$proposal->user_id):
			return view('laravel-authentication-acl::client.exceptions.404');
		endif;
		
		$counter_offer = Proposal::where('id', $input['p_id'])->firstOrFail();
		
		if(isset($input['counter_amount'])):
			$input['counter_amount']=$input['counter_amount'];
			$offer=$input['counter_amount'];
			$send_mail=$proposal['user']['email'];
		else:
			$input['freelancer_counter_amount']=$input['freelancer_counter_amount'];
			$offer=$input['freelancer_counter_amount'];
			$send_mail=$proposal['job']['user']['email'];
		endif;
		
		$counter_offer->fill($input)->save();
		
		
		Mail::send('emails.send_counteroffer', ['user'=>$logged_user,'proposal' => $proposal,'counter_offer'=>$offer], function($message) use ($send_mail) {
				$message->to($send_mail, 'From Softral Job')->subject('Softral - Got a counter offer');
			});
			
		Session::flash('message', 'You have successfully sent a counter offer.');
		return redirect('job/proposal_view?id='.$input['p_id']);
	}
	
	
	public function Jobdetail($job=null)
	{
		$logged_user = $this->auth->getLoggedUser();
		
		 $job_exist = Job::where('slug',$job)->first();
			if(empty($job_exist)):
				return view('laravel-authentication-acl::client.exceptions.404');
			endif;
			
		$job_detail = Job::with(['categories.get_jobs' => function ($query) {
		$query->take(5);
		$query->orderBy('created_at', 'desc');
		},'categories.parent_get','user','proposals','save_job'])->where('slug', $job)->first();
		$proposals = Proposal::with('user','user_profile.profile_field')->where("job_id", $job_detail['id'])->take(5)->get();
		$save_jobs = Savejob::where("job_id", $job_detail['id'])->where("user_id", $logged_user->id)->first();
		$proposal_selected = Proposal::where("job_id", $job_detail['id'])->where("offer", 1)->first();
		//dd($job_detail);exit;
		$check_proposal = Proposal::where("user_id", $logged_user->id)->where("job_id", $job_detail->id)->first();
		
		if($logged_user->id==$job_detail->user_id):
			$owner='owner';
		else:
			$owner='user';
		endif;
		
		$images=unserialize($job_detail->images);
		$images_string='';
		if(!empty($images)):
			$images_string=implode(' ', array_map(function ($v, $k) { return "<a href='".url()."/download/".$v."' target='_blank'>".$v."</a>,"; }, $images, array_keys($images)));
		endif;
		
		return View::make('job.jobdetail')->with([ 'job_detail'   => $job_detail,'images_string'=>rtrim($images_string,','),'owner'=>$owner,'logged_id'=>$logged_user->id,'proposals'=>$proposals,'check_proposal'=>$check_proposal,'save_jobs'=>$save_jobs,'proposal_selected'=>$proposal_selected]);
	}
	
	public function ajaxProposal($job_id,$page){
        //PDF file is stored under project/public/download/info.pdf
        $proposals = Proposal::with('user','user_profile.profile_field','job')->where("job_id", $job_id)->get();
		$perPage = 5;
	
		$logged_user = $this->auth->getLoggedUser();
		
		$offSet = ($page * $perPage) - $perPage;
		
		$itemsForCurrentPage = array_slice($proposals->toArray(), $offSet, $perPage, true);
		$proposals =  new LengthAwarePaginator($itemsForCurrentPage, count($proposals), $perPage, $page);
		$proposal_selected = Proposal::where("job_id", $job_id)->where("offer", 1)->first();
		//dd($proposals);exit;
		$view=View::make('job.ajaxProposal')->with([ 'proposals'   => (object)$proposals,'logged_id'=>$logged_user->id,'proposal_selected'=>$proposal_selected]);
		//dd($view);exit;
		if(($view)==''):
			$proposals_view='empty';
		else:
			$proposals_view=$view->__tostring();
		endif;
		
		return response()->json(['success' => true, 'proposals' => $proposals_view]);    
		
	}
	
	public function savejob($job_id){
        //PDF file is stored under project/public/download/info.pdf
       
		$logged_user = $this->auth->getLoggedUser();
		$input['job_id']=$job_id;
		$input['user_id']=$logged_user->id;
		Savejob::create($input);
		
		return response()->json(['success' => true]);    
		
	}
	
	public function getDownload($value=null){
        //PDF file is stored under project/public/download/info.pdf
        $file= public_path(). "/uploads/". $value;
       
        return Response::download($file);
	}

	

}
