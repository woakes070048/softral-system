<?php namespace App\Http\Controllers;

use App\Http\Models\Category;
use App\Http\Models\Skill;
use App\Http\Models\Job;
use App\Http\Models\Ad;
use App\Http\Models\Page;
use App\Http\Models\Proposal;
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
use View, Input, Redirect, App, Config,Session,Mail;
use LaravelAcl\Authentication\Interfaces\AuthenticateInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use DB;

class CategoryController extends Controller {

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
	public function getList()
	{
		 $categories = Category::with('children')->where('type',Input::get('type'))->get();
		
		 return View::make('laravel-authentication-acl::admin.category.list')->with([ 'categories'   => $categories]);
	}
	
	public function contractsList()
	{
		 $contracts = Contract::with('job','user','proposal')->get();
		 
		 return View::make('laravel-authentication-acl::admin.contract.list')->with([ 'contracts'   => $contracts]);
	}
	
  
	public function editCategory()
	{
		if(Input::get('id')):
        
            $category = Category::find(Input::get('id'));
			$parents = DB::table('categories')->where(['parent' => 0])->where('id','!=',Input::get('id'))->where('type',Input::get('type'))->get();
        else:
            $category = new Category;
			$parents = DB::table('categories')->where(['parent' => 0])->where('type',Input::get('type'))->get();
       endif;
		
		$parent_selector = array();
		$parent_selector['']='-Select Parent Category-';
		foreach($parents as $parent) {
			$parent_selector[$parent->id] = $parent->category; // I assume name attribute contains client name here
		}
		 return View::make('laravel-authentication-acl::admin.category.edit')->with([
                                                                                          'category'   => $category,
                                                                                          "parents" => $parent_selector
                                                                                  ]);
	}
	
	public function saveCategory(Request $request)
	{
		$this->validate($request, [
			'category' => 'required',
			//'description' => 'required'
		]);
		 $input = $request->all();
		 
		$slug = str_slug($input['category'], "-");
		$LastSlug = Category::whereRaw("slug REGEXP '^{$slug}(-[0-9]*)?$'")
		->orderBy('slug', 'desc')
		->first();
	
		// print_r($input);exit;
		 if((Input::get('id'))):
			 $category = Category::findOrFail(Input::get('id'));
			 $category->fill($input)->save();
			Session::flash('message', 'Category successfully updated!');
		 else:
				
			if(isset($LastSlug->slug)):
				$input['slug'] = "{$slug}-" . ((intval(str_replace("{$slug}-", '', $LastSlug->slug))) + 1);
			else:
				$input['slug'] =str_slug($input['category'], "-");
			endif;	

			Category::create($input);
			Session::flash('message', 'Category successfully added!');
		 endif;
 
		return redirect()->back();
		
	}
	
	public function contractsApprove(Request $request){
		$input = $request->all();
		
		if($input['submit']=='Approve'):
			$input['approve_contract']='0';
		else:
			$input['approve_contract']='1';
		endif;
		
		$contract = Contract::findOrFail($input['contract_id']);
		$contract->fill($input)->save();
		
		$proposal = Proposal::findOrFail($contract['proposal_id']);
		
		$send_mail=$proposal['job']['user']['email'];
		$freelancer_mail=$proposal['user']['email'];
		Mail::send('emails.approve_contract_notification', ['proposal' => $proposal,'label'=>$input['submit']], function($message) use ($send_mail) {
				$message->to($send_mail, 'From Softral Job')->subject('Softral - Contract Confirmation');
			});
			
		Mail::send('emails.approve_contract_notification_freelancer', ['proposal' => $proposal,'label'=>$input['submit']], function($message) use ($freelancer_mail) {
				$message->to($freelancer_mail, 'From Softral Job')->subject('Softral - Contract Confirmation');
			});
			
		Session::flash('message', 'Congrats, You have successfully '.$input['submit'].'d the contract, '.$input['submit'].'d confirmation has been sent to the Employee.');
		return redirect('admin/contracts/list');
		
	}
	
	public function deleteCategory()
	{
		$category = Category::findOrFail(Input::get('id'));
		$category->delete();
		Session::flash('message', 'Category successfully deleted!');
 
		return redirect()->route('category.list');	
	}

 /* Skill List */ 	
	public function getskillList()
	{
		 $skills = Skill::get();
		
		 return View::make('laravel-authentication-acl::admin.skill.list')->with([ 'skills'   => $skills]);
	}
	
	public function editSkill()
	{
		
		try
        {
            $skill = Skill::find(Input::get('id'));
        } catch(JacopoExceptionsInterface $e)
        {
            $skill = new Skill;
        }
		
		 return View::make('laravel-authentication-acl::admin.skill.edit')->with([
                                                                                          'skill'   => $skill,
                                                                                 
                                                                                  ]);
	}
	
	public function saveSkill(Request $request)
	{
		$this->validate($request, [
			'skill' => 'required',
			//'description' => 'required'
		]);
		 $input = $request->all();
		 
		$slug = str_slug($input['skill'], "-");
		$LastSlug = Skill::whereRaw("slug REGEXP '^{$slug}(-[0-9]*)?$'")
		->orderBy('slug', 'desc')
		->first();
		
		
		// print_r($input);exit;
		 if((Input::get('id'))):
			 $skill = Skill::findOrFail(Input::get('id'));
			 $skill->fill($input)->save();
			Session::flash('message', 'Skill successfully updated!');
		 else:
				if(isset($LastSlug->slug)):
				$input['slug'] = "{$slug}-" . ((intval(str_replace("{$slug}-", '', $LastSlug->slug))) + 1);
				else:
				$input['slug'] =str_slug($input['skill'], "-");
				endif;	

			Skill::create($input);
			Session::flash('message', 'Skill successfully added!');
		 endif;
 
		return redirect()->back();
		
	}
	
	public function deleteSkill()
	{
		$skill = Skill::findOrFail(Input::get('id'));
		$skill->delete();
		Session::flash('message', 'Skill successfully deleted!');
 
		return redirect()->route('skill.list');	
	}


/* Job List */  	
	public function getjobList()
	{
		 $jobs = Job::with('categories','categories.parent_get')->get();
		//dd($jobs);exit;
		 return View::make('laravel-authentication-acl::admin.job.list')->with([ 'jobs'   => $jobs]);
	}
	
	public function viewJob()
	{
		$job = Job::findOrFail(Input::get('id'));
		return View::make('laravel-authentication-acl::admin.job.view')->with([ 'job'   => $job]);
	}
	
	public function deleteJob()
	{
		$job = Job::findOrFail(Input::get('id'));
		$job->delete();
		Session::flash('message', 'Job successfully deleted!');
 
		return redirect()->route('job.list');	
	}
	
/* Ad List */  	
	public function getadList()
	{
		 $ads = Ad::with('categories','categories.parent_get')->get();
		//dd($jobs);exit;
		 return View::make('laravel-authentication-acl::admin.ad.list')->with([ 'ads'   => $ads]);
	}
	
	public function viewAd()
	{
		$ad = Ad::findOrFail(Input::get('id'));
		return View::make('laravel-authentication-acl::admin.ad.view')->with([ 'ad'   => $ad]);
	}
	
	public function deleteAd()
	{
		$ad = Ad::findOrFail(Input::get('id'));
		$ad->delete();
		Session::flash('message', 'Classified successfully deleted!');
 
		return redirect()->route('ad.list');	
	}
	
	/* Page List */ 	
	public function getpageList()
	{
		 $pages = Page::with('children_hexa')->get();
		//dd($pages);exit;
		 return View::make('laravel-authentication-acl::admin.page.list')->with([ 'pages'   => $pages]);
	}
	
	public function editPage()
	{
		$parents = DB::table('pages')->where(['parent' => 0])->get();
		$parent_selector = array();
		$parent_selector['']='-Select Parent Page-';
		foreach($parents as $parent) {
			$parent_selector[$parent->id] = $parent->title; // I assume name attribute contains client name here
		}
		
		try
        {
            $page = Page::find(Input::get('id'));
        } catch(JacopoExceptionsInterface $e)
        {
            $page = new Page;
        }
		
		 return View::make('laravel-authentication-acl::admin.page.edit')->with([
                                                                                          'page'   => $page,
																						  'parents'=>$parent_selector
                                                                                  ]);
	}
	
	public function savePage(Request $request)
	{
		$this->validate($request, [
			'title' => 'required',
			'content' => 'required'
		]);
		 $input = $request->all();

		 $slug = str_slug($input['title'], "-");
		$LastSlug = Page::whereRaw("slug REGEXP '^{$slug}(-[0-9]*)?$'")
		->orderBy('slug', 'desc')
		->first();
	
		// print_r($input);exit;
		 if((Input::get('id'))):
				$page = Page::findOrFail(Input::get('id'));
				
				$file = Input::file('image');
				$destinationPath = 'uploads';
				
				if($file!=''):
					$filename = $file->getClientOriginalName();
					$input['image']=$filename;
					$upload_success = $file->move($destinationPath, $filename);
				elseif($input['image_text']!=''):
					$input['image']=$input['image_text'];
				endif;
				
				$page->fill($input)->save();
				Session::flash('message', 'Page successfully updated!');
		 else:
				if(isset($LastSlug->slug)):
					$input['slug'] = "{$slug}-" . ((intval(str_replace("{$slug}-", '', $LastSlug->slug))) + 1);
				else:
					$input['slug'] =str_slug($input['title'], "-");
				endif;	
				
				$file = Input::file('image');
				if($file!=''):
					$destinationPath = 'uploads';
					$filename = $file->getClientOriginalName();
					$upload_success = $file->move($destinationPath, $filename);
					$input['image']=$filename;
				endif;
		
				Page::create($input);
				Session::flash('message', 'Page successfully added!');
		 endif;
 
		return redirect()->back();
		
	}
	
	public function deletePage()
	{
		$page = Page::findOrFail(Input::get('id'));
		$page->delete();
		Session::flash('message', 'Page successfully deleted!');
 
		return redirect()->route('page.list');	
	}

}
