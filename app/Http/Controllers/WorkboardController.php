<?php

namespace App\Http\Controllers;

use App\Http\Models\Category;
use App\Http\Models\Skill;
use App\Http\Models\Job;
use App\Http\Models\Workboard;
use App\Http\Models\Proposal;
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
use View,
    Input,
    Redirect,
    App,
    Config,
    Session;
use LaravelAcl\Authentication\Interfaces\AuthenticateInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Response;
use DB;

class WorkboardController extends Controller {

    protected $auth;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(AuthenticateInterface $auth) {
        $this->middleware('guest');
        $this->auth = $auth;
    }

    public function viewAllworkboards() {
        $logged_user = $this->auth->getLoggedUser();

        $my_jobs = Job::with('user','proposal_selected')->ApproveContract()->where("user_id", $logged_user->id)->where("selected", 1)->orderBy('created_at', 'desc')->get();
		$proposals = Proposal::with('job')->where("user_id", $logged_user->id)->ApproveContract()->where("offer",1)->orderBy('created_at', 'desc')->get();

        return View::make('job.jobsWorkboardList')->with([ 'my_jobs' => $my_jobs, 'proposals' => $proposals]);
        
    }

    public function viewworkboard($id = "") {
        $projectowner = "";
        $logged_user = $this->auth->getLoggedUser();
        $projectfreelancer = "";
        if (isset($_GET["id"]) && $_GET["id"] != "") {
			$proposal=Proposal::where('id',$_GET["id"])->ApproveContract()->first();
		
            $projectowner = with(new Workboard)->getproject_owner($proposal['job_id']);
            $projectfreelancer = with(new Workboard)->getproject_freelancer($_GET["id"],$proposal['job_id']);
            $assignments = with(new Workboard)->getassignments($_GET["id"],$proposal['job_id']);
        }
        $tablecontent = View::make('job.workboardhtml')->with([ 'projectowner' => $projectowner, 'projectfreelancer' => $projectfreelancer, 'assignments' => $assignments, 'logged_user' => $logged_user]);
        return View::make('job.workboard')->with([ 'tablecontent' => $tablecontent,'job_id'=>$proposal['job_id']]);
    }

    public function changeassignmentstatus() {
        //jobid: jobid,colname:colname,assID:assID,colval:colval
        $logged_user = $this->auth->getLoggedUser();
        $jobid = $_POST["jobid"];
        $proposal_id = $_POST["proposal_id"];
        $colname = $_POST["colname"];
        $assID = $_POST["assID"];
        $colval = $_POST["colval"];
        if ($colval == "null") {
            $data = array(
                "$colname" => NULL
            );
        } else {
            $data = array(
                "$colname" => $colval
            );
        }

        with(new Workboard)->update_assignment($data, $assID);

        $projectowner = with(new Workboard)->getproject_owner($jobid);
        $projectfreelancer = with(new Workboard)->getproject_freelancer($proposal_id,$jobid);
        $assignments = with(new Workboard)->getassignments($proposal_id,$jobid);
        $tablecontent = View::make('job.workboardhtml')->with([ 'projectowner' => $projectowner, 'projectfreelancer' => $projectfreelancer, 'assignments' => $assignments, 'logged_user' => $logged_user]);

        echo $tablecontent;
    }

    public function saveassignment() {
        $projectowner = "";
        $logged_user = $this->auth->getLoggedUser();
        $projectfreelancer = "";
        if (isset($_POST["jobid"]) && $_POST["jobid"] != "") {
            //`asignment_title`, `job_id`  insert_assignment($data)
            $jobid = $_POST["jobid"];
			$assid = $_POST['assid'];
			$proposal_id = $_POST['proposal_id'];
            $asignment_title = $_POST["assignmenttext"];
            $data = array(
                "asignment_title" => $asignment_title,
                "job_id" => $jobid,
                "proposal_id" => $proposal_id
            );
			
			if($assid!=''):
				  with(new Workboard)->updateEmployee_assignment($data,$assid);
			else:
				  with(new Workboard)->insert_assignment($data);
			endif;
           
            if (isset($jobid) && $jobid != "") {

                $projectowner = with(new Workboard)->getproject_owner($jobid);
                $projectfreelancer = with(new Workboard)->getproject_freelancer($proposal_id,$jobid);
                $assignments = with(new Workboard)->getassignments($proposal_id,$jobid);
            }
            $tablecontent = View::make('job.workboardhtml')->with([ 'projectowner' => $projectowner, 'projectfreelancer' => $projectfreelancer, 'assignments' => $assignments, 'logged_user' => $logged_user]);

            echo $tablecontent;
        } else {
            echo "error";
        }
        exit();
    } 
	
	public function replyAssignment() {
        $projectowner = "";
        $logged_user = $this->auth->getLoggedUser();
        $projectfreelancer = "";
        if (isset($_POST["assid"]) && $_POST["assid"] != "") {
            //`asignment_title`, `job_id`  insert_assignment($data)
            
			$assid = $_POST['assid'];
            $asignment_title = $_POST["assignmenttext"];
            $proposal_id = $_POST["proposal_id"];
			
			$proposal_user=Proposal::where('id',$proposal_id)->first();
			if($proposal_user['user_id']== $logged_user->id):
				$data = array(
                "freelancer_comments" => $asignment_title,
                "parent_id" => $assid
            );
			else:
				$data = array(
                "asignment_title" => $asignment_title,
                "parent_id" => $assid
            );
			endif;
            
		
				  with(new Workboard)->insert_assignment($data);
		       echo 'success';
        } else {
            echo "error";
        }
        exit();
    }

    public function getFreelancerComments() {
        if (isset($_POST["assignmentid"]) && $_POST["assignmentid"] != "") {
			
			if(isset($_POST['condition'])):
			 $logged_user = $this->auth->getLoggedUser();
				$proposal_id = $_POST["proposal_id"];
				$proposal_user=Proposal::where('id',$proposal_id)->first();
				if($proposal_user['user_id']== $logged_user->id):
					$res = with(new Workboard)->getAssignments_byParent_id1($_POST["assignmentid"]);
						$all_comments="<div class='panel-body'>";
					foreach($res as $re):
						$all_comments.='<div class=" clearfix">
					<div class="notification-info" style="margin-left: -51px;">
						<ul class="clearfix notification-meta">
						  <div class="col-md-12">
							<li class="pull-left notification-sender">
																			'.$re->asignment_title.'
													</li>
						</div></ul></div>';
					endforeach;
					$all_comments.="<div/>";
				else:
					$res = with(new Workboard)->getAssignments_byParent_id($_POST["assignmentid"]);
						$all_comments="<div class='panel-body'>";
					foreach($res as $re):
						$all_comments.='<div class=" clearfix">
					<div class="notification-info" style="margin-left: -51px;">
						<ul class="clearfix notification-meta">
						  <div class="col-md-12">
							<li class="pull-left notification-sender">
																			'.$re->freelancer_comments.'
													</li>
						</div></ul></div>';
					endforeach;
					$all_comments.="<div/>";
				endif;
				
				if(empty($res)):
					$all_comments.='<div class=" clearfix">
					<div class="notification-info" style="margin-left: -51px;text-align:center"><b>No comments</b></div></div>';
				endif;
				echo $all_comments;exit();
			else:
				$res = with(new Workboard)->getAssignments_onID($_POST["assignmentid"]);
				echo $comm = $res[0]->freelancer_comments;
			endif;
	
           
        }
    }    

	public function getEmployeeComments() {
        if (isset($_POST["assignmentid"]) && $_POST["assignmentid"] != "") {
            $res = with(new Workboard)->getAssignments_onID($_POST["assignmentid"]);
            $comm = $res[0]->asignment_title;
            echo $comm;
        }
    }

    public function savefreelancercomments() {
        //commentstext:txtcommentstext,assid:assidForFreelancer
         $logged_user = $this->auth->getLoggedUser();
        if (isset($_POST['commentstext']) && isset($_POST['assid'])) {
            $commentstext = $_POST['commentstext'];
            $assid = $_POST['assid'];
            $proposal_id = $_POST['proposal_id'];
            $jobid=$_POST['jobid'];   
             $data = array(
                "freelancer_comments" => $commentstext,
                "proposal_id" => $proposal_id,
                
            );
            with(new Workboard)->update_assignment($data, $assid);

            $projectowner = with(new Workboard)->getproject_owner($jobid);
            $projectfreelancer = with(new Workboard)->getproject_freelancer($proposal_id,$jobid);
            $assignments = with(new Workboard)->getassignments($proposal_id,$jobid);
            $tablecontent = View::make('job.workboardhtml')->with([ 'projectowner' => $projectowner, 'projectfreelancer' => $projectfreelancer, 'assignments' => $assignments, 'logged_user' => $logged_user]);

            echo $tablecontent;
        }
    }

}
