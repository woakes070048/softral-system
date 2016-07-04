<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', [
				'as'   => 'welcome',
				'uses' => 'WelcomeController@Index'
		]);
		
Route::get('/shome', [
				'as'   => 'welcome.home',
				'uses' => 'WelcomeController@home'
		]);
		
Route::get('/pages/{slug}', [
				'as'   => 'pages.slug',
				'uses' => 'WelcomeController@GetPages'
		]);
		
Route::get('/contact', [
				'as'   => 'contact',
				'uses' => 'WelcomeController@ContactUs'
		]);
		
Route::post('/contact/send_email', [
				"before" => "csrf",
				'as'   => 'contact.sendEmail',
				'uses' => 'WelcomeController@sendEmail'
		]);

Route::post('/job/ajaxproposal/{id}/{page}', [
				'as'   => 'job.ajaxproposal',
				'uses' => 'JobController@ajaxProposal'
		]);
		
Route::post('/job/savejob/{id}', [
				'as'   => 'job.savejob',
				'uses' => 'JobController@savejob'
		]);
		
Route::post('/ad/savemessage/', [
				'as'   => 'ad.savemessage',
				'uses' => 'AdController@savemessage'
		]);

Route::get('home', 'HomeController@index');

Route::get('download/{download?}', 'JobController@getDownload');

		
Route::get('ad-lists', [
				'as'   => 'job.listAd',
				'uses' => 'AdController@listAd',
				
		]);	
		
Route::get('ad-detail/{ad}', [
				'as'   => 'ad.addetail',
				'uses' => 'AdController@Addetail',
				
		]);
		
Route::get('ad-category/{category?}', ['before' => 'shiftParameter:category','uses' => 'AdController@listAd'])
    ->where('category', '(.*)');
	
Route::get('state/{state?}', ['before' => 'shiftParameter:state','as'=>'ad.searchBystate','uses' => 'AdController@listAd'])
    ->where('state', '(.*)');
		
Route::get('city/{city?}', ['before' => 'shiftParameter:city','as'=>'ad.searchBycity','uses' => 'AdController@listAd'])
    ->where('city', '(.*)');
	
Route::get('country/{country?}', ['before' => 'shiftParameter:country','as'=>'ad.searchBycity','uses' => 'AdController@listAd'])
    ->where('country', '(.*)');

Route::get('category/{category?}', ['before' => 'shiftParameter:category', 'uses' => 'WelcomeController@home'])
    ->where('category', '(.*)');
	
Route::get('skill/{skill?}', ['before' => 'shiftParameter:skill', 'uses' => 'WelcomeController@home'])
    ->where('skill', '(.*)');
	
	Route::filter('shiftParameter', function ($route, $request, $value) {
    // save off the current route parameters    
    $parameters = $route->parameters();
    // unset the current route parameters
    foreach($parameters as $name => $parameter) {
        $route->forgetParameter($name);
    }

    // union the new parameters and the old parameters
    $parameters = ['customParameter0' => $value] + $parameters;
    // loop through the new set of parameters to add them to the route
    foreach($parameters as $name => $parameter) {
        $route->setParameter($name, $parameter);
    }
});


Route::group(['before' => ['logged']], function ()
{
Route::get('add-job', [
				'as'   => 'job.addJob',
				'uses' => 'JobController@addJob',
				
		]);
		
Route::get('all-members', [
				'as'   => 'user.members',
				'uses' => 'WelcomeController@members',
				
		]);
		
Route::get('add-ad', [
				'as'   => 'job.addAd',
				'uses' => 'AdController@addAd',
				
		]);

			
Route::get('/user/profile/{slug}', [
            'as'   => 'users.profile.slug',
            'uses' => 'WelcomeController@userProfileslug'
    ]);
		
Route::get('edit-job', [
				'as'   => 'job.editJob',
				'uses' => 'JobController@addJob',
				
		]);
		
Route::get('edit-ad', [
				'as'   => 'ad.editAd',
				'uses' => 'AdController@addAd',
				
		]);
	
Route::post('/job/jobsave', [
				 "before" => "csrf",
				'as'   => 'job.save',
				'uses' => 'JobController@Jobsave'
		]);
		
Route::post('/ad/adsave', [
				 "before" => "csrf",
				'as'   => 'ad.save',
				'uses' => 'AdController@Adsave'
		]);
		
Route::get('/job/selectproposal', [
				 "before" => "csrf",
				'as'   => 'job.selectProposal',
				'uses' => 'JobController@Selectproposal'
		]);
		
Route::post('/job/jobedit', [
				 "before" => "csrf",
				'as'   => 'job.edit',
				'uses' => 'JobController@Jobedit'
		]);	
		
Route::post('/ad/adedit', [
				 "before" => "csrf",
				'as'   => 'ad.edit',
				'uses' => 'AdController@Adedit'
		]);	
		
Route::get('/my_savejobs', [
				'as'   => 'welcome.saveJobs',
				'uses' => 'WelcomeController@saveJobs'
		]);
		
Route::get('/my_closeJob', [
				 "before" => "csrf",
				'as'   => 'job.closeJob',
				'uses' => 'JobController@closeJob'
		]);
		
Route::get('/my_openJob', [
				 "before" => "csrf",
				'as'   => 'job.openJob',
				'uses' => 'JobController@openJob'
		]);
		
Route::get('/my_runningjobs', [
				'as'   => 'welcome.runningJobs',
				'uses' => 'WelcomeController@runningJobs'
		]);
		
Route::get('/list_messages/{slug}', [
				'as'   => 'ad.listMessages',
				'uses' => 'AdController@listMessages'
		]);
		
Route::get('/my_ads', [
				'as'   => 'ad.myAds',
				'uses' => 'WelcomeController@myAds'
		]);
		
Route::get('/my_jobs', [
				'as'   => 'welcome.myJobs',
				'uses' => 'WelcomeController@myJobs'
		]);	
		
Route::get('/my_contracts', [
				'as'   => 'welcome.myContracts',
				'uses' => 'WelcomeController@myContracts'
		]);	
		
Route::get('add-proposal/{job}', [
				'as'   => 'job.addProposal',
				'uses' => 'JobController@AddProposal',	
		]);
		
Route::get('add-proposal/{job}/{id}', [
				'as'   => 'job.addProposal',
				'uses' => 'JobController@AddProposal',	
		]);
		
Route::get('/job/proposal_delete', [
				 "before" => "csrf",
				'as'   => 'proposal.delete',
				'uses' => 'JobController@deleteProposal'
		]);
		
Route::get('/job/job_delete', [
				 "before" => "csrf",
				'as'   => 'saveJob.delete',
				'uses' => 'JobController@deletesaveJob'
		]);
		
Route::get('/message/message_delete', [
				 "before" => "csrf",
				'as'   => 'myMessage.delete',
				'uses' => 'AdController@deletemyMessage'
		]);
		
Route::get('/message/message_view', [
				'as'   => 'myMessage.view',
				'uses' => 'AdController@viewmyMessage'
		]);
		
Route::get('/job/myjob_delete', [
				 "before" => "csrf",
				'as'   => 'myJob.delete',
				'uses' => 'JobController@deletemyJob'
		]);
		
Route::get('/ad/myad_delete', [
				 "before" => "csrf",
				'as'   => 'myAd.delete',
				'uses' => 'AdController@deletemyAd'
		]);
		
Route::get('/job/proposal_view', [
				'as'   => 'proposal.view',
				'uses' => 'JobController@viewProposal'
		]);
		
Route::get('my-proposals', [
				'as'   => 'job.myProposals',
				'uses' => 'WelcomeController@MyProposals',
				
		]);
		
Route::post('/job/SaveProposal', [
				'as'   => 'job.saveProposal',
				'uses' => 'JobController@SaveProposal',
				
		]);
		
Route::get('job-proposals', [
				'as'   => 'job.jobProposals',
				'uses' => 'WelcomeController@jobProposals',
				
		]);
		
		
Route::get('job/{job}', [
				'as'   => 'job.detail',
				'uses' => 'JobController@Jobdetail',
				
		]);
		
/* Feedback routes */
Route::get('/employee_feedback', [
				 "before" => "csrf",
				'as'   => 'feedback.employeeFeedback',
				'uses' => 'FeedbackController@employeeFeedback'
		]);
		
Route::post('/employee_feedback_rating_save', [
				 "before" => "csrf",
				'as'   => 'EmployeeratingSave',
				'uses' => 'FeedbackController@EmployeeratingSave'
		]);
		
Route::get('/freelancer_feedback', [
				 "before" => "csrf",
				'as'   => 'feedback.freelancerFeedback',
				'uses' => 'FeedbackController@freelancerFeedback'
		]);
		
Route::post('/freelancer_feedback_rating_save', [
				 "before" => "csrf",
				'as'   => 'FreelancerratingSave',
				'uses' => 'FeedbackController@FreelancerratingSave'
		]);
		
/* workboard routes   */


Route::get('my-workboard', [
				'as'   => 'job.myWorkboard',
				'uses' => 'WorkboardController@viewAllworkboards',
				
		]);
Route::get('view_workboard', [
				'as'   => 'job.viewworkboard',
				'uses' => 'WorkboardController@viewworkboard',
				
		]);
Route::post('assignment_changestatus', [
				'as'   => 'workboard.changestatus',
				'uses' => 'WorkboardController@changeassignmentstatus',
				
		]);
Route::post('save_assignment', [
				'as'   => 'workboard.saveassignment',
				'uses' => 'WorkboardController@saveassignment',
				
		]);
		
Route::post('reply_assignment', [
				'as'   => 'workboard.replyAssignment',
				'uses' => 'WorkboardController@replyAssignment',
				
		]);
		

Route::post('workboard-freelancer-comments', [
				'as'   => 'workboard.freelancercomments',
				'uses' => 'WorkboardController@getFreelancerComments',
				
		]);
		
Route::post('workboard-employee-comments', [
				'as'   => 'workboard.employeecomments',
				'uses' => 'WorkboardController@getEmployeeComments',
				
		]);

Route::post('workboard-savefreelancercomments', [
				'as'   => 'workboard.savefreelancercomments',
				'uses' => 'WorkboardController@savefreelancercomments',
				
		]);
		
/* workboard routes  end */

/* Terms and milestone */

	Route::get('/financial/terms_milestone', [
				'as'   => 'termsandMilestone',
				'uses' => 'FinancialController@termsandMilestone'
		]);	
		
	Route::post('/financial/save_milestone', [
				 "before" => "csrf",
				'as'   => 'financial.saveMilestone',
				'uses' => 'FinancialController@saveMilestone'
		]);
		
	Route::post('/job/counter_offer', [
				 "before" => "csrf",
				'as'   => 'job.counter_offer',
				'uses' => 'JobController@counterOffer'
		]);
		
	Route::post('/financial/save_escrow', [
				 "before" => "csrf",
				'as'   => 'financial.saveEscrow',
				'uses' => 'FinancialController@saveEscrow'
		]);	
		
	Route::post('/financial/release_escrow', [
				 "before" => "csrf",
				'as'   => 'financial.releaseEscrow',
				'uses' => 'FinancialController@releaseEscrow'
		]);	
		
	Route::post('/financial/release_bonus', [
				 "before" => "csrf",
				'as'   => 'financial.releaseBonus',
				'uses' => 'FinancialController@releaseBonus'
		]);	
		
	Route::get('/financial/milestone_delete', [
				 "before" => "csrf",
				'as'   => 'milestone.delete',
				'uses' => 'FinancialController@deleteMilestone'
		]);
		
	Route::get('/financial/cancel_contract', [
				 "before" => "csrf",
				'as'   => 'cancel.contract',
				'uses' => 'FinancialController@cancelContract'
		]);
		
	Route::get('/financial/reopen_contract', [
				 "before" => "csrf",
				'as'   => 'reopen.contract',
				'uses' => 'FinancialController@reopenContract'
		]);
		
	Route::post('/financial/freelancer_request_money', [
				 "before" => "csrf",
				'as'   => 'freelancerrequest.money',
				'uses' => 'FinancialController@freelancerrequestMoney'
		]);
		
	Route::post('/financial/edit_getmilestone/{id}', [
				'as'   => 'financial.edit_getmilestone',
				'uses' => 'FinancialController@edit_getmilestone'
		]);
		
	Route::post('/financial/save_terms_milestone', [
				 "before" => "csrf",
				'as'   => 'financial.saveTermsMilestone',
				'uses' => 'FinancialController@saveTermsMilestone'
		]);	
		
	Route::get('/admin/financial/addmoney', [
				'as'   => 'addmoney',
				'uses' => 'FinancialController@addmoney'
		]);
		
	Route::get('/admin/financial/transactions', [
				'as'   => 'financialTransaction',
				'uses' => 'FinancialController@financialTransaction'
		]);
		
	Route::post('/admin/milestone/acceptMilestone', [
				'as'   => 'financial.acceptMilestone',
				'uses' => 'FinancialController@acceptMilestone'
		]);
		
/* Terms and milestone */		
	


});			

Route::group(['before' => ['admin_logged', 'can_see']], function ()
{
	/* Category Route */
	Route::get('/admin/category/list', [
				'as'   => 'category.list',
				'uses' => 'CategoryController@getList'
		]);
		
		
		Route::get('/admin/category/edit', [
				'as'   => 'category.edit',
				'uses' => 'CategoryController@editCategory'
		]);
		
		Route::post('/admin/category/save', [
				 "before" => "csrf",
				'as'   => 'category.save',
				'uses' => 'CategoryController@saveCategory'
		]);
		
		
		Route::get('/admin/category/delete', [
				 "before" => "csrf",
				'as'   => 'category.delete',
				'uses' => 'CategoryController@deleteCategory'
		]);

/* Skills Route */
		Route::get('/admin/skill/list', [
				'as'   => 'skill.list',
				'uses' => 'CategoryController@getskillList'
		]);
		
		
		Route::get('/admin/skill/edit', [
				'as'   => 'skill.edit',
				'uses' => 'CategoryController@editSkill'
		]);
		
		Route::post('/admin/skill/save', [
				 "before" => "csrf",
				'as'   => 'skill.save',
				'uses' => 'CategoryController@saveSkill'
		]);
		
		Route::get('/admin/skill/delete', [
				 "before" => "csrf",
				'as'   => 'skill.delete',
				'uses' => 'CategoryController@deleteSkill'
		]);
		
/* Jobs Route */		
		Route::get('/admin/job/list', [
				'as'   => 'job.list',
				'uses' => 'CategoryController@getjobList'
		]);
		
		Route::get('/admin/job/delete', [
				 "before" => "csrf",
				'as'   => 'job.delete',
				'uses' => 'CategoryController@deleteJob'
		]);
		
		Route::get('/admin/job/view', [
				 "before" => "csrf",
				'as'   => 'job.view',
				'uses' => 'CategoryController@viewJob'
		]);
		
/* Classified Route */		
		Route::get('/admin/ad/list', [
				'as'   => 'ad.list',
				'uses' => 'CategoryController@getadList'
		]);
		
		Route::get('/admin/ad/delete', [
				 "before" => "csrf",
				'as'   => 'ad.delete',
				'uses' => 'CategoryController@deleteAd'
		]);
		
		Route::get('/admin/ad/view', [
				 "before" => "csrf",
				'as'   => 'ad.view',
				'uses' => 'CategoryController@viewAd'
		]);
		
/* Pages Route */		

	Route::get('/admin/page/edit', [
				'as'   => 'page.edit',
				'uses' => 'CategoryController@editPage'
		]);
		
		Route::post('/admin/page/save', [
				 "before" => "csrf",
				'as'   => 'page.save',
				'uses' => 'CategoryController@savePage'
		]);
		
		Route::get('/admin/page/list', [
				'as'   => 'page.list',
				'uses' => 'CategoryController@getpageList'
		]);
		
		Route::get('/admin/page/delete', [
				 "before" => "csrf",
				'as'   => 'page.delete',
				'uses' => 'CategoryController@deletePage'
		]);
		
/* Financials Route */	
	Route::get('/admin/financial/account', [
				'as'   => 'financial',
				'uses' => 'FinancialController@Account'
		]);
		
	Route::get('/admin/financial/addskrill', [
				'as'   => 'addskrill',
				'uses' => 'FinancialController@AddSkrill'
		]);
		
	Route::get('/admin/financial/addpaypal', [
            'as'   => 'addpaypal',
            'uses' => 'FinancialController@AddPaypal'
    ]);
		
	Route::get('/admin/financial/addbank', [
				'as'   => 'addbank',
				'uses' => 'FinancialController@AddBank'
		]);	
		
	Route::get('/admin/financial/addcredit', [
				'as'   => 'addcredit',
				'uses' => 'FinancialController@AddCredit'
		]);
		
	Route::post('/admin/financial/accountsave', [
				 "before" => "csrf",
				'as'   => 'account.save',
				'uses' => 'FinancialController@Accountsave'
		]);
		
	Route::get('/admin/financial/adminaccountview', [
				'as'   => 'adminaccount.view',
				 "before" => "csrf",
				'uses' => 'FinancialController@Adminaccountview'
		]);
		
	 
    Route::post('/admin/financial/paypalsave', [
            "before" => "csrf",
            'as'   => 'paypal.save',
            'uses' => 'FinancialController@Paypalsave'
    ]);
    Route::get('/admin/financial/requestMoney/getDone', [
            "before" => "csrf",
            'as'   => 'paypal.Done',
            'uses' => 'FinancialController@getDone'
    ]);
		
	Route::post('/admin/financial/banksave', [
				 "before" => "csrf",
				'as'   => 'bank.save',
				'uses' => 'FinancialController@Banksave'
		]);	
		
	Route::post('/admin/financial/creditsave', [
				 "before" => "csrf",
				'as'   => 'credit.save',
				'uses' => 'FinancialController@Creditsave'
		]);
		
	Route::get('/admin/financial/withdraw', [
				'as'   => 'withdraw',
				'uses' => 'FinancialController@Withdraw'
		]);
		
	Route::post('/admin/financial/withdrawSave', [
				 "before" => "csrf",
				'as'   => 'withdraw.save',
				'uses' => 'FinancialController@WithdrawSave'
		]);
	Route::get('/admin/financial/myAccount', [
				 "before" => "csrf",
				'as'   => 'myAccount.delete',
				'uses' => 'FinancialController@deletemyAccount'
		]);
		
	Route::get('/admin/financial/allAccounts', [
				'as'   => 'allaccounts',
				'uses' => 'FinancialController@allAccounts'
		]);
		
	
		
	Route::get('/admin/financial/addmoneyskrill', [
				 "before" => "csrf",
				'as'   => 'addmoneyskrill.save',
				'uses' => 'FinancialController@addmoneyskrill'
		]);
		
	Route::post('/admin/financial/addmoneycredit', [
				 "before" => "csrf",
				'as'   => 'addmoneycredit.save',
				'uses' => 'FinancialController@addmoneycredit'
		]);
		
	Route::get('/admin/financial/adminaccountDelete', [
				 "before" => "csrf",
				'as'   => 'adminaccount.delete',
				'uses' => 'FinancialController@adminaccountDelete'
		]);
		
	Route::get('/admin/financial/requestMoney', [
				'as'   => 'requestMoney',
				'uses' => 'FinancialController@requestMoney'
		]);
		
	Route::get('/admin/financial/moneys_send', [
            "before" => "csrf",
            'as'   => 'requestPaypalmoney.send',
            'uses' => 'FinancialController@requestPaypalmoneySend'
    ]);
        
			
	Route::get('/admin/financial/money_send', [
				 "before" => "csrf",
				'as'   => 'requestmoney.send',
				'uses' => 'FinancialController@requestmoneySend'
		]);
		
	Route::get('/admin/financial/requestMoneytDelete', [
				 "before" => "csrf",
				'as'   => 'requestMoney.delete',
				'uses' => 'FinancialController@requestMoneyDelete'
		]);
		
	/* Send Mail Route */	
	Route::get('/admin/mailSend', [
				'as'   => 'mailSend',
				'uses' => 'MailController@mailSend'
		]);
		
	Route::post('/admin/mailSend', [
				'as'   => 'mailSend',
				 "before" => "csrf",
				'uses' => 'MailController@mailSend'
		]);
	
	/* Send Mail Route */	
		/* Admin Contracts listing */
	
	Route::get('/admin/contracts/list', [
				'as'   => 'admin_contracts',
				'uses' => 'CategoryController@contractsList'
		]);
		
	Route::post('/admin/contract_approve_status', [
				'as'   => 'contractsApprove',
				 "before" => "csrf",
				'uses' => 'CategoryController@contractsApprove'
		]);
		
	/* Admin Contracts listing */	
		
});

Route::controllers([
	'auth' => 'Auth\AuthController',
	'password' => 'Auth\PasswordController',
]);
