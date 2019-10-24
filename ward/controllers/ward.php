<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
--------------------------------------------------------------------------------
HHIMS - Hospital Health Information Management System
Copyright (c) 2011 Information and Communication Technology Agency of Sri Lanka
<http: www.hhims.org/>
----------------------------------------------------------------------------------
This program is free software: you can redistribute it and/or modify it under the
terms of the GNU Affero General Public License as published by the Free Software 
Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,but WITHOUT ANY 
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR 
A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License along 
with this program. If not, see <http://www.gnu.org/licenses/> 




---------------------------------------------------------------------------------- 
Date : June 2016
Author: Mr. Jayanath Liyanage   jayanathl@icta.lk

Programme Manager: Shriyananda Rathnayake
URL: http://www.govforge.icta.lk/gf/project/hhims/
----------------------------------------------------------------------------------
*/
class Ward extends MX_Controller {
	 function __construct(){
		parent::__construct();
		$this->checkLogin();
		$this->load->library('session');
		if(isset($_GET["mid"])){
			$this->session->set_userdata('mid', $_GET["mid"]);
		}			
	 }

	public function index()
	{
		//$this->load->view('patient');
		$this->ward_search();
	}
	public function nurse_chart($wid,$dte=null){
		if(!isset($wid) ||(!is_numeric($wid) )){
			$data["error"] = "Ward  not found";
			$this->load->vars($data);
			$this->load->view('admission/admission_error');	
			return;
		}
		if (!$dte) $dte = date("Y-m-d");
		$this->load->model('mward');
		$this->load->model('mpersistent');
		$data["ward_info"]=$this->mpersistent->open_id($wid,"ward","WID"); //get the ward info
		$data["patient_list"]=$this->mward->get_patient_list($wid); //get the list of active admission/patient
		if (!empty($data["patient_list"])){
			for ($i=0;$i<count($data["patient_list"]); ++$i){
				$data["prescribe_items_list"][$data["patient_list"][$i]["admission_prescription_id"]] =$this->mward->get_prescribe_items($data["patient_list"][$i]["admission_prescription_id"],null);
				
				if (!empty($data["prescribe_items_list"][$data["patient_list"][$i]["admission_prescription_id"]])){
					for ($j=0;$j<count($data["prescribe_items_list"][$data["patient_list"][$i]["admission_prescription_id"]]); ++$j){
						$data["prescribe_items_list"][$data["patient_list"][$i]["admission_prescription_id"]][$j]["dispence_info"]=$this->mward->get_dispense_info($data["prescribe_items_list"][$data["patient_list"][$i]["admission_prescription_id"]][$j]["prescribe_items_id"],$dte);
					}
				}
			}
		}
		//print_r($data["patient_list"]);
		//print_r($data["prescribe_items_list"]);
		//exit;
		$this->load->vars($data);

		$this->load->view('ward_nurse_chart');			
	}
	public function view($wid,$ops=null){
		$this->load->model('mpersistent');
		$data["ward_info"] = $this->mpersistent->open_id($wid,"ward","WID");
      $qry = "SELECT 
	  admission.ADMID,
	  admission.BHT,
          patient.HIN,
	  CONCAT(patient.Personal_Title, ' ' ,patient.Full_Name_Registered,' ', patient.Personal_Used_Name) ,
	  admission.AdmissionDate,
	  admission.Complaint,
	  admission.OutCome,
	  admission.DischargeDate
	  from admission 
	  LEFT JOIN `patient` ON patient.PID = admission.PID 
	  where (admission.Active =1) and (admission.Ward= '$wid') and (admission.Status= 'Admitted')
	  
			";
		if ($ops == "discharged"){ 
			$qry .= " and (admission.OutCome != '') ";
		}
		else{
			$qry .= " and (admission.OutCome is null) ";
		}

        $this->load->model('mpager',"page");
		
        $page = $this->page;
        $page->setSql($qry);
        $page->setDivId("patient_list"); //important
        $page->setDivClass('');
        $page->setRowid('ADMID');
        if ($ops == "discharged"){ 
        $page->setCaption("Discharged Patients");
                }
 else {
     $page->setCaption("Current Admitted Patients");
 }
        $page->setShowHeaderRow(true);
        $page->setShowFilterRow(true);
        $page->setShowPager(true);
        $page->setColNames(array("","BHT","HIN", "Patient","Admission Date","Complaint","OutCome","DischargeDate"));
        $page->setRowNum(25);
        $page->setColOption("ADMID", array("search" => false, "hidden" => true));
        $page->setColOption("BHT", array("search" => true, "hidden" => false));
        $page->setColOption("HIN", array("search" => true, "hidden" => false));
        //$page->setColOption("patient_name", array("search" => true, "hidden" => false));
        $page->setColOption("AdmissionDate", array("search" => true, "hidden" => false));
        
		if ($ops != "discharged"){ 
		$page->setColOption("DischargeDate", array("search" => true, "hidden" => true));
        $page->setColOption("OutCome", array("search" => true, "hidden" => true));
		}
        $page->gridComplete_JS
            = "function() {
        $('#patient_list .jqgrow').mouseover(function(e) {
            var rowId = $(this).attr('id');
            $(this).css({'cursor':'pointer'});
        }).mouseout(function(e){
        }).click(function(e){
            var rowId = $(this).attr('id');
            window.location='".site_url("/admission/view")."/'+rowId+'?BACK=ward/view/$wid';
        });
        }";
        $page->setOrientation_EL("L");
		$data['pager'] = $page->render(false);
		$this->load->vars($data);
        $this->load->view('patient_list');		
	}
        
        	public function pending_patient($wid){
		$this->load->model('mpersistent');
		$data["ward_info"] = $this->mpersistent->open_id($wid,"ward","WID");
      $qry = "SELECT 
	  admission.ADMID,
	  admission.BHT,
          patient.HIN,
	  CONCAT(patient.Personal_Title, ' ' ,patient.Full_Name_Registered,' ', patient.Personal_Used_Name) ,
	  admission.AdmissionDate,
	  admission.Complaint,
	  admission.OutCome,
	  admission.DischargeDate
	  from admission 
	  LEFT JOIN `patient` ON patient.PID = admission.PID 
	  where (admission.Active =1) and (admission.Ward= '$wid')and (admission.Status='Pending')
	  and (admission.OutCome is null)
			";

        $this->load->model('mpager',"page");
		
        $page = $this->page;
        $page->setSql($qry);
        $page->setDivId("patient_list"); //important
        $page->setDivClass('');
        $page->setRowid('ADMID');
        $page->setCaption("Pending Patient list");
        $page->setShowHeaderRow(true);
        $page->setShowFilterRow(true);
        $page->setShowPager(true);
        $page->setColNames(array("","BHT","HIN", "Patient","Admission Date","Complaint","OutCome","DischargeDate"));
        $page->setRowNum(25);
        $page->setColOption("ADMID", array("search" => false, "hidden" => true));
        $page->setColOption("BHT", array("search" => true, "hidden" => false));
        $page->setColOption("HIN", array("search" => true, "hidden" => false));
        //$page->setColOption("patient_name", array("search" => true, "hidden" => false));
        $page->setColOption("AdmissionDate", array("search" => true, "hidden" => false));
       // $page->setColOption("AdmissionDate", $page->getDateSelector(date("Y-m-d")));
		//if ($ops != "discharged"){ 
		$page->setColOption("DischargeDate", array("search" => true, "hidden" => true));
        $page->setColOption("OutCome", array("search" => true, "hidden" => true));
		//}
        $page->gridComplete_JS
            = "function() {
        $('#patient_list .jqgrow').mouseover(function(e) {
            var rowId = $(this).attr('id');
            $(this).css({'cursor':'pointer'});
        }).mouseout(function(e){
        }).click(function(e){
            var rowId = $(this).attr('id');
            window.location='".site_url("/admission/confirm")."/'+rowId+'?BACK=ward/view/$wid';
        });
        }";
        $page->setOrientation_EL("L");
		$data['pager'] = $page->render(false);
		$this->load->vars($data);
        $this->load->view('patient_list');		
	}
        
	public function ward_search(){

		$new_page   =   base_url()."index.php/search/ward/";
		header("Status: 200");
		header("Location: ".$new_page);
	}	
	
	
} 


//////////////////////////////////////////

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */