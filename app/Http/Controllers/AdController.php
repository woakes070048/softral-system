<?php 

namespace App\Http\Controllers;

use App\Http\Models\Category;
use App\Http\Models\Skill;
use App\Http\Models\Ad;
use App\Http\Models\Proposal;
use App\Http\Models\Admessages;
use App\Http\Models\Savejob;
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
use View, Input, Redirect, App, Config,Session,Mail,Blade;
use LaravelAcl\Authentication\Interfaces\AuthenticateInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Response;
use DB;

class AdController extends Controller {

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
	

	public function addAd()
	{
		 $categories = Category::with('children')->where('type','Classified')->get();
		 $logged_user = $this->auth->getLoggedUser();
		 $parent_selector = array();
		 
		if($logged_user->user_profile[0]->profile_field_type->value!='Buyer' && $logged_user->user_profile[0]->profile_field_type->value!='Both'):
			return view('laravel-authentication-acl::client.exceptions.404');
		endif;
		
		if((Input::get('id'))):
            $ad = Ad::where('user_id',$logged_user->id)->where('id',Input::get('id'))->first();
			if(empty($ad)):
				return view('laravel-authentication-acl::client.exceptions.404');
			endif;
			//$skill_id[]=unserialize($job->skill_id);
        else:
            $ad = new Ad;
		endif;
		//dd($job);exit;
		 return View::make('ad.addad')->with([ 'categories'   => $categories,'ad'=>$ad]);
	}
	
	public function Adsave(Request $request)
	{
		$this->validate($request, [
			'title' => 'required|min:8|max:120',
			'category_id' => 'required',
			'description' => 'required',
			'state' => 'required',
			'city' => 'required',
			'price' => 'digits_between:1,5',
			'email'=>'required|email',
			'phone_no' => 'required',
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
		
		$input = $request->all();
		
		$input['images']=serialize($images);
		$input['user_id']= $logged_user->id;
		
		if(isset($logged_user->user_profile[0]->country)):
			$input['country']= $logged_user->user_profile[0]->country;
		endif;
	
		$slug = str_slug($input['title'], "-");
		$LastSlug = Ad::whereRaw("slug REGEXP '^{$slug}(-[0-9]*)?$'")
		->orderBy('slug', 'desc')
		->first();
		
		if(isset($LastSlug->slug)):
			$input['slug'] = "{$slug}-" . ((intval(str_replace("{$slug}-", '', $LastSlug->slug))) + 1);
		else:
			$input['slug'] =str_slug($input['title'], "-");
		endif;	
		
		Ad::create($input);
		Session::flash('message', 'Classified successfully added!');
		
		return redirect()->route('ad.myAds');	
	}
	
	public function Adedit(Request $request)
	{
		$this->validate($request, [
			'title' => 'required|min:8|max:120',
			'category_id' => 'required',
			'description' => 'required',
			'state' => 'required',
			'city' => 'required',
			'price' => 'digits_between:1,5',
			'email'=>'required|email',
			'phone_no' => 'required',
		]);
		
		$logged_user = $this->auth->getLoggedUser();
		
		$files = Input::file('images');
		$file_count = count($files);
		
		$file_count = count($files);
    // start count how many uploaded
			$uploadcount = 0;
			$images=array();
		
			if($files[0]!=''):
			foreach($files as $file) {
			
				$destinationPath = 'uploads';
				$filename = $file->getClientOriginalName();
				$upload_success = $file->move($destinationPath, $filename);
				$uploadcount ++;
				$images[]=$filename;
			  }
			 endif;
		
		$input = $request->all();
		
		if(isset($input['image_string'])):
			for($image_string=0;$image_string<count($input['image_string']);$image_string++):
				$images[] =$input['image_string'][$image_string];
			endfor;
		endif;
		
		$input['images']=serialize($images);
		$input['user_id']= $logged_user->id;
		if(isset($logged_user->user_profile[0]->country)):
			$input['country']= $logged_user->user_profile[0]->country;
		endif;
	
		$ad = Ad::findOrFail(Input::get('id'));
		$ad->fill($input)->save();	
		
		Session::flash('message', 'Classified successfully updated!');
		
		return redirect()->route('ad.myAds');	
	}
	
	public function deletemyAd(){
		
		$myad = Ad::findOrFail(Input::get('id'));
		$myad->delete();
		Session::flash('message', 'You have successfully deleted your Classified!');
 
		return redirect()->route('ad.myAds');	
	}
	
	public function deletemyMessage(){
		
		$mymessage = Admessages::findOrFail(Input::get('id'));
		$ad_id=$mymessage->ad_id;
		$mymessage->delete();
		Session::flash('message', 'You have successfully deleted a Message!');
		
		$ad = Ad::where('id',$ad_id)->first();
		return redirect('/list_messages/'.$ad->slug);	
	}
	
	public function viewmyMessage(){
				 $mymessage = Admessages::where('id',Input::get('id'))->first();
				 $logged_user = $this->auth->getLoggedUser();
				 
				 if(!empty($mymessage)):
					$ad = Ad::where('user_id',$logged_user->id)->where('id',$mymessage->ad_id)->first();
					if(empty($ad)):
						return view('laravel-authentication-acl::client.exceptions.404');
					endif;
				endif;
				return view::make('ad.viewmyMessage')->with([ 'mymessage'   => $mymessage]);
	}
	
	public function listAd($state=null,$category=null){
		
		if(Input::get('sorting')):
			$sorting= explode('-',Input::get('sorting'),3);
			if($sorting[0]=='price' || $sorting[0]=='title'):
				$sorting_keyword=$sorting[0];
				$sorting_value=$sorting[1];
			else:
				$sorting_keyword=$sorting[0].'_'.$sorting[1];
				$sorting_value=$sorting[2];
			endif;
		else:
			$sorting_keyword='id';
			$sorting_value='DESC';
		endif;
		
		$q = Input::get('q');
		$state_name='';
		$city_name='';
		$country_name='';
		$category_label='';
		
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
			$status_array=array(0);
		else:
			$status_array=array(0,1);
		endif;
		
		$budget_array=array($min,$max);
		
		$max = Input::get('max');
		$min = Input::get('min');
		$category = \Request::segment(2);
		
		$subcategories=array();
		if($category!=null && $state=='category'):
			$pcategories = Category::with('children')->where('slug',$category)->first();
			if(!empty($pcategories)):
					array_push($subcategories,$pcategories->id);
				foreach($pcategories->children as $subcategory):
					array_push($subcategories, $subcategory->id);
				endforeach;
			endif;
		endif;
		
		if($category!=null && $state=='state'):
			$state_name=str_replace('-',' ',$category);
			$category_label='State';
		elseif($category!=null && $state=='city'):
			$city_name=str_replace('-',' ',$category);
			$category_label='City';
		elseif($category!=null && $state=='country'):
			$country_name=str_replace('-',' ',$category);
			$category_label='Country';
		endif;
		
		//print_r($budget_array);exit;
		$ads = Ad::with('categories.parent_get','user','children')->subCategory($subcategories)->where(function($query) use ($q,$status_array,$budget_array,$state_name,$city_name,$country_name) {
                /** @var $query Illuminate\Database\Query\Builder  */
                return $query->where('title', 'LIKE', '%'. $q .'%')
                    ->whereIn('sold', $status_array)
                    ->where('country', 'LIKE',  '%'.$country_name.'%' )
                    ->where('state', 'LIKE',  '%'.$state_name.'%' )
                    ->where('city', 'LIKE',  '%'.$city_name.'%')
					->WhereBetween('price', $budget_array);
            })->orderBy($sorting_keyword, $sorting_value)->paginate(10); 
		
		$ads->appends(array('sorting'=>Input::get('sorting'),'status'=>Input::get('status'),'min'=>$min,'max'=>$max,'q' => Input::get('q')));
		$ads->setPath('');
		
		if($category!=null && $state=='category'):
			$category=$pcategories->category;
			$category_label='Category';
		endif;
		$category=str_replace('-',' ',$category);
	
		return view::make('ad.listAd')->with([ 'ads'   => $ads,'category_label'=>$category_label,'category'=>ucwords($category)]);
	}

	
	public function listMessages($slug=null){
		$ad_detail = Ad::with('user','messages')->where('slug', $slug)->first();
		$logged_user = $this->auth->getLoggedUser();
		
		if($slug):
            $ad = Ad::where('user_id',$logged_user->id)->where('id',$ad_detail->id)->first();
			if(empty($ad)):
				return view('laravel-authentication-acl::client.exceptions.404');
			endif;
		endif;
		$messages = Admessages::where("ad_id",  $ad_detail->id)->orderBy('created_at', 'desc')->paginate(10);
		$messages->setPath('');
		return view::make('ad.listMessages')->with([ 'ad_detail'   => $ad_detail,'messages'=>$messages]);
	}
	
	public function Addetail($ad=null){
		$logged_user = $this->auth->getLoggedUser();
		$owner='user';
		$logged_user_id=0;
		$ad_exist = Ad::where('slug',$ad)->first();
			if(empty($ad_exist)):
				return view('laravel-authentication-acl::client.exceptions.404');
			endif;
			
		$ad_detail = Ad::with('categories.parent_get','user')->where('slug', $ad)->first();
		
		if(isset($logged_user->id)):
			if($logged_user->id==$ad_detail->user_id):
				$owner='owner';
			else:
				$owner='user';
			endif;
			$logged_user_id=$logged_user->id;
		endif;
		
		$images=unserialize($ad_detail->images);
			
		return View::make('ad.addetail')->with([ 'ad_detail'   => $ad_detail,'owner'=>$owner,'logged_id'=>$logged_user_id]);
	}
	
	public function Savemessage(Request $request){
		$input = $request->all();
		$logged_user_id=0;
		$logged_user = $this->auth->getLoggedUser();
		
		if(isset($logged_user->id)):
			$logged_user_id=$logged_user->id;
		endif;
		$input['user_id']= $logged_user_id;
		$ad_detail = Ad::with('user')->where('id',$input['ad_id'])->first();
		
		$data=$input;
		Admessages::create($input);
		
		Mail::send('emails.message_template', ['data' => $data,'ad_detail'=>$ad_detail], function($message) use ($ad_detail) {
			$message->to($ad_detail->email, 'From Softral Classified')->subject('Softral - You have got a message for your classified');
		});
		
		Session::flash('message', 'You have successfully sent a message!');
		return redirect('ad-detail/'.$input['ad_slug']);
	}
	
	

}
