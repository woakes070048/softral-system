<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Workboard
 *
 * @author User
 */


namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Http\Models\Skill;
use App\Http\Models\Proposal;
use Illuminate\Routing\UrlGenerator;
use DB;
class Workboard extends Model{
    //put your code here
    public function __construct(array $attributes = array()) {
        parent::__construct($attributes);
    }
    public function ScopeFreelancerUserid($query, $id){
		
				$query->WhereHas('job', function($query) use ($id){
					$query->where('user_id', $id);
				});
		
	}
    
     public function getfreelancer_projects($freelancerid) {
		
        $projects = DB::table('proposals')
                ->join('jobs', 'proposals.job_id', '=', 'jobs.id')
                ->where('proposals.user_id', '=', $freelancerid)
                ->where('proposals.offer', '=', 1)
                ->select('jobs.*')
                ->get();
        return $projects;
    }
    
    public function getproject_owner($jobid) {
        $projects = DB::table('jobs')
                ->join('user_profile', 'jobs.user_id', '=', 'user_profile.user_id')
                ->where('jobs.id', '=', $jobid)
              
                ->select('jobs.project_name','jobs.id as jobid','user_profile.*')
                ->get();
        return $projects;
    }
    
     public function getproject_freelancer($proposal_id,$jobid) {
        $projects = DB::table('proposals')
                ->join('jobs', 'proposals.job_id', '=', 'jobs.id')
                ->join('user_profile', 'proposals.user_id', '=', 'user_profile.user_id')
                ->where('jobs.id', '=', $jobid)
                ->where('proposals.id', '=', $proposal_id)
                ->where('proposals.offer', '=', 1)
                
                ->select('jobs.project_name','jobs.id as jobid','user_profile.*')
                ->get();
        return $projects;
    }
    
    public function insert_assignment($data){
        DB::table('assignments')->insert($data);
    }
	
	public function updateEmployee_assignment($data,$assid){
        DB::table('assignments')
			->where('id', $assid)
            ->update($data);
    }
	
     public function update_assignment($data,$assid){
        DB::table('assignments')
            ->where('id', $assid)
            ->update($data);
    }
    
    public function getassignments($proposal_id,$jobid){
        
        $assignments = DB::table('assignments')
                 ->where('assignments.job_id', '=', $jobid)
                 ->where('assignments.proposal_id', '=', $proposal_id)
               ->select('assignments.*')
                ->get();
        return $assignments;
        
    }
    
    public function getAssignments_onID($assID){
         $assignments = DB::table('assignments')
                 ->where('assignments.id', '=', $assID)
               ->select('assignments.*')
                ->get();
        return $assignments;
    }   

	public function getAssignments_byParent_id($assID){
         $assignments = DB::table('assignments')
                 ->where('assignments.parent_id', '=', $assID)
                 ->where('assignments.freelancer_comments', '!=', '')
                 ->orderBy('assignments.id','DESC')
               ->select('assignments.*')
                ->get();
        return $assignments;
    }	
	
	public function getAssignments_byParent_id1($assID){
         $assignments = DB::table('assignments')
                 ->where('assignments.parent_id', '=', $assID)
                 ->where('assignments.asignment_title', '!=', '')
                 ->orderBy('assignments.id','DESC')
               ->select('assignments.*')
                ->get();
        return $assignments;
    }
}
