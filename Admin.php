<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Admin extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('Admin_model')
        ->helper('admin_helper')
        ->library('Settings');
        
        $exclude_function = array('login','forgot_password');
        if(!in_array($this->router->method, $exclude_function))
            if(!is_admin_login())
                redirect(base_url('admin/login'));
    }

    public function index() {
        if ($this->input->post()) {
            $data['employee_id'] = $this->input->post('employee_id');
            $data['status'] = 'assigned';
            $this->Admin_model->employeeAssigned($this->input->post('id'), $data);
            $this->session->set_flashdata(array('success' => "Employee assigned successfully."), "flash");
            redirect('admin');
        }
        $data['Title'] = "Admin | Dashboard";
        $data['employee'] = $this->Admin_model->getEmployeeAssigned();
        $data['chart_data'] = json_encode($this->Admin_model->getChartData());
        $this->load->AdminTemplate('dashboard', $data);
    }

    public function get_request() {
        if($this->uri->segment(3)=='pending'){
            $columns = array( 'id','client_id','employee_id','title','datetime','status');
        }else{
            $columns = array( 'id','client_id','employee_id','title','datetime','ef','status');
        }      

        $results = $this->Admin_model->getRequest($this->input->post('start'),$this->input->post('length'),$columns[$this->input->post('order')[0]['column']],$this->input->post('order')[0]['dir'],$this->input->post('search')['value'],$this->uri->segment(3));
        $user_data = $results['data'];
        $total_results = $results['total_result'];

        $data = array();
        foreach($user_data as $user)
        {
            $action = "<a href='".base_url("admin/add_booking/$user->id")."' class='btn btn-default btn-xs'><i class='fa fa-edit'></i></a><a href='javascript:delete_booking(".$user->id.")' class='btn btn-default btn-xs'><i class='fa fa-times'></i></a><a href='#' class='assign btn btn-default btn-xs' data-id='".$user->id."' data-employee='".$user->employee_id."'>Assign</a><a href='".base_url("admin/manage_transaction/$user->id")."' class='btn btn-default btn-xs'>Transaction</a>";
            $link = "<a class='btn-link' href='".base_url("admin/add_client/".$user->client_id."/v")."'>".$user->cf." ".$user->cl."</a>";
            $link2 = "<a class='btn-link' href='".base_url("admin/add_employee/".$user->employee_id."/v")."'>".$user->ef." ".$user->el."</a>";
            if($this->uri->segment(3)=='pending'){
                $action = "<a href='#' class='assign btn btn-default btn-xs' data-id='".$user->id."' data-employee='".$user->employee_id."'>Assign</a>";
                $data[] = array($user->id,$link,$user->title,$user->datetime,ucfirst($user->status),$action);
            }else{
                $data[] = array($user->id,$link,$user->title,$user->datetime,$link2,ucfirst($user->status),$action);
            }
        }

        $json_data = array(
                        "draw"            => intval( $this->input->post('draw') ),
                        "recordsTotal"    => intval( count($user_data) ), 
                        "recordsFiltered" => intval( $total_results ),
                        "data"            => $data  
                        );

        echo json_encode($json_data);
    }


    public function login() {        
        $data['Title'] = "Admin Login";        
        $this->form_validation->set_rules('email', 'email', 'trim|required|valid_email')
        ->set_rules('password', 'Password', 'trim|required');
        if ($this->form_validation->run()) {
            $user_detail = $this->Admin_model->checkAdminLogin(set_value('email'),$this->load->hashPassword(set_value('password')));
            if($user_detail)
            {
                $this->session->set_userdata(array(
                    'admin_logged_in' => true,
                    'admin' => $user_detail
                ));
                $this->session->set_flashdata('success','You are successfully logged in.');
                redirect(base_url('admin'));
            }
            else
            {
               $this->session->set_flashdata('error','Invalid Email or Password.');
            }
        }
        $this->load->view('admin/login', $data);
    }
    
    public function change_password() {        
        $data['Title'] = "Change Password";
        $admin_id = $this->session->userdata('admin')->admin_id;
        $this->form_validation->set_rules('password', 'Password', 'required')
        ->set_rules('confirm_password', 'Confirm Password', 'required|matches[password]')
        ->set_rules('old_password', 'old Password', 'required');
        
        if($this->form_validation->run())
        {
            $old_pass_hash = $this->load->hashPassword(set_value('old_password'));
            if($this->Admin_model->checkValidOldPass($admin_id,$old_pass_hash))
            {
                $this->Admin_model->updatePassword($admin_id,$this->load->hashPassword(set_value('password')));
                $this->session->set_flashdata('success','Your password is updated successfully.');
            }
            else
            {
                $this->session->set_flashdata('error','Old password is not correct.');
            }
            redirect('admin/change_password');
        }
        else
        {
            $this->load->AdminTemplate('change_password', $data);
        }
    }
    
    public function logout() {
        $this->session->unset_userdata(array('admin_logged_in', 'admin'));
        redirect('admin/login');
    }
    
    public function profile() {
        $admin_id = $this->session->userdata('admin')->admin_id;
        $data['Title'] = "Admin | Profile";
        $data['Admin'] = $this->Admin_model->checkAdminDetailByid($admin_id);

        $this->form_validation->set_rules('username', 'Username', 'trim|required')
        ->set_rules('firstname', 'Firstname', 'trim|required')
        ->set_rules('lastname', 'Lastname', 'trim|required')
        ->set_rules('email', 'Email', 'trim|required|valid_email');
        if ($this->form_validation->run()) {
            $post = $this->input->post();
            if($post){
                if($_FILES['admin_image']['name']){
                    $upload = $this->do_upload('./assets/images/', 'admin_image');
                    if(count($upload) > 1){
                        $post['admin_image']=$upload['file_name'];
                    }else{
                        $this->session->set_flashdata('error', $upload);
                        redirect(base_url('admin/profile'));
                    }
                }
                $this->Admin_model->update_profile($post, $admin_id);
                $this->session->set_flashdata('success', 'Your profile is successfully updated.');
                redirect(base_url('admin/profile'));
            }
        }
        $this->load->AdminTemplate('profile', $data);
    }
    
    public function forgot_password() {
        $this->form_validation->set_rules('email', 'Email ID', 'required|valid_email');
        if ($this->form_validation->run()) {
            $email = $this->input->post('email');
            $adminData = $this->Admin_model->checkValidEmail($email);
            if ($adminData) {
                $password = $this->load->getRandomString(8);
                $data['password'] = $this->load->hashPassword($password);
                $this->Admin_model->update_profile($data,$adminData->admin_id);

                $message = array('site_name' => 'Fog Cleaning', 'firstname' => $adminData->firstname, 'password' => $password, 'email' => $email);
                $this->load->SendMail($email,'forgot_password_admin','Forgot Password',$message);

                $this->session->set_flashdata(array('success' => "Please check your mail we have sent password "));
            } else {
                $this->session->set_flashdata(array('error' => "Please enter correct email."));
            }
        } else {
            $this->session->set_flashdata(array('error' => "Please enter valid email."));
        }
        redirect(base_url('admin/login'));
    }

    public function manage_client() {
        $data['Title'] = "Admin | Manage Client";
        $this->load->AdminTemplate('list_client', $data);
    }
    
    public function get_client() {
        $columns = array('id','firstname','lastname','email','phone','created_date','status');
        $results = $this->Admin_model->getClient($this->input->post('start'),$this->input->post('length'),$columns[$this->input->post('order')[0]['column']],$this->input->post('order')[0]['dir'],$this->input->post('search')['value']);
        $user_data = $results['data'];
        $total_results = $results['total_result'];

        $data = array();
        foreach($user_data as $user)
        {
            // <a href='".base_url("admin/add_client/$user->id/v")."' class='btn btn-default btn-xs'><i class='fa fa-eye'></i></a>
            $action = "<a href='".base_url("admin/add_client/$user->id")."' class='btn btn-default btn-xs'><i class='fa fa-edit'></i></a><a href='javascript:delete_client(".$user->id.")' class='btn btn-default btn-xs'><i class='fa fa-times'></i></a><a href='".base_url("admin/add_client/$user->id/r")."' class='btn btn-default btn-xs'>Request</a>";
            $data[] = array($user->id,$user->firstname,$user->lastname,$user->email,$user->phone,$user->created_date,ucfirst($user->status),$action);
        }

        $json_data = array(
                        "draw"            => intval( $this->input->post('draw') ),
                        "recordsTotal"    => intval( count($user_data) ), 
                        "recordsFiltered" => intval( $total_results ),
                        "data"            => $data  
                        );

        echo json_encode($json_data);
    }
    
    public function add_client() {
        $id = $this->uri->segment(3);
        if($id)
        {
            if($this->uri->segment(4)=='r'){
                $data['Title'] = "Manage Request";
                $this->load->AdminTemplate('list_client_request',$data);
            }else{
                $data['User'] = $this->Admin_model->editUserDetail($id);
                if($this->uri->segment(4)){
                    $data['Title'] = "View Client";
                    $this->load->AdminTemplate('view_user',$data);
                }else{
                    $data['Title'] = "Edit Client";
                    $this->form_validation->set_rules('firstname', 'firstname', 'required|trim')
                    ->set_rules('email', 'Email address', 'required|trim|valid_email')
                    ->set_rules('phone', 'phone', 'trim');

                    if($this->form_validation->run())
                    {  
                        $post_data = $this->input->post();
                        unset($post_data['cpassword']);
                        if($post_data['password']){
                            $post_data['password'] = $this->load->hashPassword($post_data['password']);
                        }else{
                            unset($post_data['password']);
                        }
                        if($_FILES['profile_picture']['name']){
                            $upload = $this->do_upload('./assets/images/user/', 'profile_picture');
                            if(count($upload) > 1){
                                $post_data['profile_picture']=$upload['file_name'];
                                $resized_path = './assets/images/user/thumb/';
                                $this->load->library('image_lib');
                                $this->image_resize($upload,$resized_path,160,160);
                            }else{
                                $this->session->set_flashdata('error', $upload);
                                redirect(base_url('admin/add_client/'.$id));
                            }
                        }
                        $this->Admin_model->updateUser($id, $post_data);
                        $this->session->set_flashdata(array('success' => "Client is updated successfully."), "flash");
                        redirect('admin/manage_client');
                    }
                    $this->load->AdminTemplate('edit_user',$data);
                }
            }
        }
        else
        {
            $this->form_validation->set_rules('firstname', 'firstname', 'required|trim')
            ->set_rules('email', 'Email address', 'required|trim|valid_email|is_unique[tbl_users.email]')
            ->set_rules('password', 'password', 'required')
            ->set_rules('cpassword', 'Confirm password', 'required|matches[password]')
            ->set_rules('phone', 'phone', 'trim');
            if($this->form_validation->run())
            {   
                $post_data = $this->input->post();
                unset($post_data['cpassword']);
                $post_data['created_date'] = date('Y-m-d h:i:s');
                $post_data['verification_status'] = '1';
                $post_data['user_role'] = '1';
                if($_FILES['profile_picture']['name']){
                    $upload = $this->do_upload('./assets/images/user/', 'profile_picture');
                    if(count($upload) > 1){
                        $post_data['profile_picture']=$upload['file_name'];
                        $resized_path = './assets/images/user/thumb/';
                        $this->load->library('image_lib');
                        $this->image_resize($upload,$resized_path,160,160);
                    }else{
                        $this->session->set_flashdata('error', $upload);
                        redirect(base_url('admin/add_client'));
                    }
                }
                $password = $post_data['password'];
                $post_data['password'] = $this->load->hashPassword($password);

                $insertId = $this->Admin_model->insertUser($post_data);
                $template = 'add_client_mail';
                $subject = $this->Admin_model->getNotificationSubject($template);
                $message = array('site_name' => 'Fog Cleaning', 'firstname' => $post_data['firstname'], 'password' => $password, 'email' => $post_data['email']);
                $this->load->SendMail($post_data['email'],$template,$subject,$message);
                $this->session->set_flashdata(array('success' => "Client is added successfully."), "flash");
                redirect('admin/manage_client');
            }
            $data['Title'] = "Add Client";
            $this->load->AdminTemplate('edit_user',$data);
        }
    }

    public function delete_client() {
        $this->Admin_model->deleteUser($this->input->post('delete_id'));
        $this->session->set_flashdata(array('success' => "Client is deleted successfully."), "flash");
        redirect('admin/manage_client');
    }

    public function get_client_request() {
        $columns = array( 'id','title','datetime','employee_id','total_time','status');
        $results = $this->Admin_model->getClientRequest($this->input->post('start'),$this->input->post('length'),$columns[$this->input->post('order')[0]['column']],$this->input->post('order')[0]['dir'],$this->input->post('search')['value'],$this->uri->segment(3));
        $user_data = $results['data'];
        $total_results = $results['total_result'];

        $data = array();
        foreach($user_data as $user)
        {
            $action = "<a href='".base_url("admin/add_booking/$user->id")."' class='btn btn-default btn-xs'><i class='fa fa-edit'></i></a><a href='javascript:delete_booking(".$user->id.")' class='btn btn-default btn-xs'><i class='fa fa-times'></i></a>";
            $link2 = "<a class='btn-link' href='".base_url("admin/add_employee/".$user->employee_id."/v")."'>".$user->ef." ".$user->el."</a>";            
            $data[] = array($user->id,$user->title,$user->datetime,$link2,$user->total_time,ucfirst($user->status),$action);
        }

        $json_data = array(
                        "draw"            => intval( $this->input->post('draw') ),
                        "recordsTotal"    => intval( count($user_data) ), 
                        "recordsFiltered" => intval( $total_results ),
                        "data"            => $data  
                        );

        echo json_encode($json_data);
    }

    public function manage_employee() {
        $data['Title'] = "Admin | Manage Employee";
        $this->load->AdminTemplate('list_employee', $data);
    }
    
    public function get_employee() {
        $columns = array( 'id','firstname','lastname','email','phone','rating','created_date','status');
        $results = $this->Admin_model->getEmployee($this->input->post('start'),$this->input->post('length'),$columns[$this->input->post('order')[0]['column']],$this->input->post('order')[0]['dir'],$this->input->post('search')['value']);
        $user_data = $results['data'];
        $total_results = $results['total_result'];

        $data = array();
        foreach($user_data as $user)
        {
            // <a href='".base_url("admin/add_employee/$user->id/v")."' class='btn btn-default btn-xs'><i class='fa fa-eye'></i></a>
            $action = "<a href='".base_url("admin/add_employee/$user->id")."' class='btn btn-default btn-xs'><i class='fa fa-edit'></i></a><a href='javascript:delete_employee(".$user->id.")' class='btn btn-default btn-xs'><i class='fa fa-times'></i></a><a href='".base_url("admin/add_employee/$user->id/r")."' class='btn btn-default btn-xs'>Request</a>";
            $data[] = array($user->id,$user->firstname,$user->lastname,$user->email,$user->phone,$user->rating,$user->created_date,ucfirst($user->status),$action);
        }

        $json_data = array(
                        "draw"            => intval( $this->input->post('draw') ),
                        "recordsTotal"    => intval( count($user_data) ), 
                        "recordsFiltered" => intval( $total_results ),
                        "data"            => $data  
                        );

        echo json_encode($json_data);
    }
    
    public function add_employee() {
        $id = $this->uri->segment(3);
        if($id)
        {
            if($this->uri->segment(4)=='r'){
                $data['Title'] = "Manage Employee Request";
                $this->load->AdminTemplate('list_employee_request',$data);
            }else{
                $data['User'] = $this->Admin_model->editUserDetail($id);
                if($this->uri->segment(4)){
                    $data['Title'] = "View Employee";
                    $this->load->AdminTemplate('view_user',$data);
                }else{
                    $data['Title'] = "Edit Employee";
                    $this->form_validation->set_rules('firstname', 'firstname', 'required|trim')
                    ->set_rules('email', 'Email address', 'required|trim|valid_email')
                    ->set_rules('phone', 'phone', 'trim');

                    if($this->form_validation->run())
                    {  
                        $post_data = $this->input->post();
                        if($post_data['dob']){
                            $post_data['dob'] =date('Y-m-d',strtotime($post_data['dob']));
                        }
                        unset($post_data['cpassword']);
                        if($_FILES['profile_picture']['name']){
                            $upload = $this->do_upload('./assets/images/user/', 'profile_picture');
                            if(count($upload) > 1){
                                $post_data['profile_picture']=$upload['file_name'];
                                $resized_path = './assets/images/user/thumb/';
                                $this->load->library('image_lib');
                                $this->image_resize($upload,$resized_path,160,160);
                            }else{
                                $this->session->set_flashdata('error', $upload);
                                redirect(base_url('admin/add_client/'.$id));
                            }
                        }
                        if($post_data['password']){
                            $post_data['password'] = $this->load->hashPassword($post_data['password']);
                        }else{
                            unset($post_data['password']);
                        }             
                        $this->Admin_model->updateUser($id, $post_data);
                        $this->session->set_flashdata(array('success' => "Employee is updated successfully."), "flash");
                        redirect('admin/manage_employee');
                    }
                    $this->load->AdminTemplate('edit_user',$data);
                }
            }
        }
        else
        {
            $this->form_validation->set_rules('firstname', 'firstname', 'required|trim')
            ->set_rules('email', 'Email address', 'required|trim|valid_email|is_unique[tbl_users.email]')
            ->set_rules('password', 'password', 'required')
            ->set_rules('cpassword', 'Confirm password', 'required|matches[password]')
            ->set_rules('phone', 'phone', 'trim');

            if($this->form_validation->run())
            {   $post_data = $this->input->post();
                unset($post_data['cpassword']);
                $post_data['created_date'] = date('Y-m-d h:i:s');
                $post_data['verification_status'] = '1';
                $post_data['user_role'] = '2';
                if($_FILES['profile_picture']['name']){
                    $upload = $this->do_upload('./assets/images/user/', 'profile_picture');
                    if(count($upload) > 1){
                        $post_data['profile_picture']=$upload['file_name'];
                        $resized_path = './assets/images/user/thumb/';
                        $this->load->library('image_lib');
                        $this->image_resize($upload,$resized_path,160,160);
                    }else{
                        $this->session->set_flashdata('error', $upload);
                        redirect(base_url('admin/add_client'));
                    }
                }
                $password = $post_data['password'];
                $post_data['password'] = $this->load->hashPassword($password);
                $this->Admin_model->insertUser($post_data);
                $template = 'add_employee_mail';
                $subject = $this->Admin_model->getNotificationSubject($template);
                $message = array('site_name' => 'Fog Cleaning', 'firstname' => $post_data['firstname'], 'password' => $password, 'email' => $post_data['email']);
                $this->load->SendMail($post_data['email'],$template,$subject,$message);
                $this->session->set_flashdata(array('success' => "Employee is added successfully."), "flash");
                redirect('admin/manage_employee');
            }
            $data['Title'] = "Add Employee";
            $this->load->AdminTemplate('edit_user',$data);
        }
    }

    public function delete_employee() {
        $this->Admin_model->deleteUser($this->input->post('delete_id'));
        $this->session->set_flashdata(array('success' => "Employee is deleted successfully."), "flash");
        redirect('admin/manage_employee');
    }

    public function get_employee_request() {
        $columns = array( 'id','client_id','title','datetime','total_time','rating','status');
        $results = $this->Admin_model->getEmployeeRequest($this->input->post('start'),$this->input->post('length'),$columns[$this->input->post('order')[0]['column']],$this->input->post('order')[0]['dir'],$this->input->post('search')['value'],$this->uri->segment(3));
        $user_data = $results['data'];
        $total_results = $results['total_result'];

        $data = array();
        foreach($user_data as $user)
        {
            $track_status = $user->track_status;
            $action = '';
            if($track_status!='end'){
                $start = $pause = $resume = $end = '';
                if (!$track_status) {
                    $start = 'true';
                } else {
                    if ($track_status == 'start') {
                        $pause = $end = 'true';
                    } elseif ($track_status == 'pause') {
                        $resume = $end = 'true';
                    } elseif ($track_status == 'resume') {
                        $pause = $end = 'true';
                    } else {
                        $start = 'true';
                    }
                }

                $action = '<button class="btn-success btn-xs request_track request_start ';
                if (!$start) { $action .= "hide"; }
                $action .= '" data-status="start" data-id="'.$user->id.'">Start</button>                              
                            <button class="btn-success btn-xs request_track request_pause ';
                if (!$pause) { $action .= "hide";} 
                $action .= '" data-status="pause" data-id="'.$user->id.'">Pause</button>
                            <button class="btn-success btn-xs request_track request_resume ';
                if (!$resume) { $action .= "hide";} 
                $action .= '" data-status="resume" data-id="'.$user->id.'">Resume</button>
                            <button class="btn-success btn-xs request_track request_end ';
                if (!$end) {$action .= "hide"; }
                $action .= '" data-status="end" data-id="'.$user->id.'">End</button>';
            }
            $link = "<a class='btn-link' href='".base_url("admin/add_client/".$user->client_id."/v")."'>".$user->cf." ".$user->cl."</a>";
            $data[] = array($user->id,$link,$user->title,$user->datetime,$user->total_time,$user->rating,ucfirst($user->status),$action);
        }

        $json_data = array(
                        "draw"            => intval( $this->input->post('draw') ),
                        "recordsTotal"    => intval( count($user_data) ), 
                        "recordsFiltered" => intval( $total_results ),
                        "data"            => $data  
                        );

        echo json_encode($json_data);
    }
    
    public function app_setting() {
        $data['Title'] = "Settings";
        $this->load->AdminTemplate('app_setting', $data);
    }
    
    public function save_settings() {
        $this->form_validation->set_rules('paypal_email', 'paypal_email', 'trim')
        ->set_rules('mail_sent_name', 'Mail Sent Name', 'trim|required')
        ->set_rules('email_from', 'Email From', 'trim')
        ->set_rules('api_username', 'API Username', 'trim')
        ->set_rules('api_password', 'API Password', 'trim')
        ->set_rules('api_signature', 'API Signature', 'trim')
        ->set_rules('address_api_key', 'API Key', 'trim');

        if ($this->form_validation->run()) {
            $this->settings->setValue('paypal_email', $this->input->post('paypal_email'));
            $this->settings->setValue('mail_sent_name', $this->input->post('mail_sent_name'));
            $this->settings->setValue('email_from', $this->input->post('email_from'));
            $this->settings->setValue('api_username', $this->input->post('api_username'));
            $this->settings->setValue('api_password', $this->input->post('api_password'));
            $this->settings->setValue('api_signature', $this->input->post('api_signature'));
            $this->settings->setValue('address_api_key', $this->input->post('address_api_key'));
            $this->session->set_flashdata('success', 'Setting is added successfully.');
        } else {
            $this->session->set_flashdata('Error', validation_errors());
        }
        redirect('admin/app_setting');
    }

    public function manage_package() {
        $data['Title'] = "Admin | Manage Package";
        $this->load->AdminTemplate('list_package', $data);
    }

    public function get_package() {
        $columns = array( 'id','title','price');
        $results = $this->Admin_model->getPackage($this->input->post('start'),$this->input->post('length'),$columns[$this->input->post('order')[0]['column']],$this->input->post('order')[0]['dir'],$this->input->post('search')['value']);
        $user_data = $results['data'];
        $total_results = $results['total_result'];

        $data = array();
        foreach($user_data as $user)
        {   
            // <a href='".base_url("admin/add_package/$user->id/v")."' class='btn btn-default btn-xs'><i class='fa fa-eye'></i></a>
            $action = "<a href='".base_url("admin/add_package/$user->id")."' class='btn btn-default btn-xs'><i class='fa fa-edit'></i></a><a href='javascript:delete_package(".$user->id.")' class='btn btn-default btn-xs'><i class='fa fa-times'></i></a>";
            $data[] = array($user->id,$user->title,$user->price,$action);
        }

        $json_data = array(
                        "draw"            => intval( $this->input->post('draw') ),
                        "recordsTotal"    => intval( count($user_data) ), 
                        "recordsFiltered" => intval( $total_results ),
                        "data"            => $data  
                        );

        echo json_encode($json_data);
    }

    public function add_package() {
        $ID = $this->uri->segment(3);
        $viewID = $this->uri->segment(4);
        if (!empty($ID)) {
            $data['data'] = $this->Admin_model->editPackageDetail($ID);
        }
        $images = '';
        if ($this->input->post('images')) {
            foreach ($this->input->post('images') as $key => $value) {
                if ($key != 0) {
                    $images .= ',';
                }
                $exp = explode('base64,', $value);
                $type = rtrim(ltrim($exp[0], 'data:image/'), ';');
                $images .= $this->decodeImage($exp[1], $type);
            }
            $_POST['images'] = $images;
        }

        $this->form_validation->set_rules('title', 'Package Title', 'required|trim')
        ->set_rules('description', 'Package Description', 'required|trim')
        ->set_rules('price', 'Package Price', 'required|numeric|trim')
        ->set_rules('currency', 'Currency', 'required|trim');
        if ($this->form_validation->run()) {
            $UserData = $this->input->post();
            if($_FILES['logo']['name']){
                $upload = $this->do_upload('./assets/images/tmp/', 'logo');
                if(count($upload) > 1){
                    $UserData['logo']=$upload['file_name'];
                }else{
                    $this->session->set_flashdata('error', $upload);
                    redirect(base_url('admin/edit_package'));
                }
            }
            if (!empty($ID)) {
                if ($this->input->post('images')=='') {
                    $UserData['images'] = '';
                }
                $insertID = $this->Admin_model->updatePackage($ID, $UserData);
                $this->session->set_flashdata(array('success' => "Package is updated successfully."), "flash");
            } else {
                $insertID = $this->Admin_model->insertPackage($UserData);
                $this->session->set_flashdata(array('success' => "Package is added successfully."), "flash");
            }

            $package_id = !empty($ID)?$ID:$insertID;
            if($package_id){
                if($_FILES['logo']['name']){
                    $directoryName = './assets/images/package/'.$package_id.'/logo';
                    if(!is_dir($directoryName)){
                        mkdir($directoryName, 0777, TRUE);
                    }

                    $thumbDirectoryName = './assets/images/package/'.$package_id.'/logo_thumb';
                    if(!is_dir($thumbDirectoryName)){
                        mkdir($thumbDirectoryName, 0777, TRUE);
                    }

                    $file = './assets/images/tmp/'.$UserData['logo'];
                    $newfile = $directoryName.'/'.$UserData['logo'];

                    copy($file, $newfile);
                    $image_data['full_path'] = $file;
                    $resized_path = $thumbDirectoryName.'/'.$UserData['logo'];
                    $this->load->library('image_lib');
                    $this->image_resize($image_data,$resized_path,80,80);
                    unlink($file);
                }

                if ($this->input->post('images')) {
                    $directoryName2 = './assets/images/package/'.$package_id.'/images';
                    if(!is_dir($directoryName2)){
                        mkdir($directoryName2, 0777, TRUE);
                    }

                    $thumbDirectoryName2 = './assets/images/package/'.$package_id.'/images_thumb_170_160';
                    if(!is_dir($thumbDirectoryName2)){
                        mkdir($thumbDirectoryName2, 0777, TRUE);
                    }

                    $thumbDirectoryName3 = './assets/images/package/'.$package_id.'/images_thumb_170_180';
                    if(!is_dir($thumbDirectoryName3)){
                        mkdir($thumbDirectoryName3, 0777, TRUE);
                    }

                    $exp = explode(",", $images);
                    foreach ($exp as $value) {
                        $file = './assets/images/tmp/'.$value;
                        $newfile = $directoryName2.'/'.$value;

                        copy($file, $newfile);
                        $this->load->library('image_lib');
                        $image_data['full_path'] = $file;
                        $resized_path = $thumbDirectoryName2.'/'.$value;
                        $this->image_resize($image_data,$resized_path,170,160);

                        $resized_path = $thumbDirectoryName3.'/'.$value;
                        $this->image_resize($image_data,$resized_path,170,180);
                        unlink($file);
                    }
                }
            }

            if (!empty($insertID)) {
                redirect('admin/manage_package');
            } else {
                $data['error'] = "Data not submitted Please try again later";
            }
        }
        if (!empty($viewID)) {
            $data['Title'] = "View Package";
            $this->load->AdminTemplate('view_package', $data);
        } elseif (!empty($ID)) {
            $data['Title'] = "Edit Package";
            $this->load->AdminTemplate('edit_package', $data);
        } else {
            $data['Title'] = "Add Package";
            $this->load->AdminTemplate('edit_package', $data);
        }
    }


    public function delete_package() {
        $this->Admin_model->deletePackage($this->input->post('delete_id'));
        $this->session->set_flashdata(array('success' => "Package is deleted successfully."), "flash");
        redirect('admin/manage_package');
    }

    public function manage_booking() {
        if ($this->input->post()) {
            $ID = $this->input->post('id');
            $employee_id = $this->input->post('employee_id');
            $UserData['employee_id'] = $this->input->post('employee_id');
            $UserData['status'] = 'assigned';
            $this->Admin_model->employeeAssigned($ID, $UserData);

            $booking_data = $this->Admin_model->editBookingDetail($ID);
            $employee_data = $this->Admin_model->editUserDetail($booking_data->employee_id);
            $package_data = $this->Admin_model->editPackageDetail($booking_data->package_id);
            $package_title = $package_data->title;
            $currency = $package_data->currency;
            $template = 'employee_assigned';
            $subject = $this->Admin_model->getNotificationSubject($template);
            $message = array('site_name' => 'Fog Cleaning', 'firstname' => $booking_data->request_firstname,'lastname' => $booking_data->request_lastname, 'email' => $booking_data->request_email, 'phone' => $booking_data->request_phone, 'address' => $booking_data->request_address, 'zipcode' => $booking_data->request_zipcode,'package_title' =>$package_title,'booking_date'=>$booking_data->datetime,'amount'=>$currency.$booking_data->amount,'employee_firstname'=>$employee_data->firstname);
            $this->load->SendMail($employee_data->email,$template,$subject,$message);

            $this->session->set_flashdata(array('success' => "Employee assigned successfully."), "flash");
            redirect('admin/manage_booking');
        }
        $data['Title'] = "Admin | Manage Booking";
        $data['employee'] = $this->Admin_model->getEmployeeAssigned();
        $this->load->AdminTemplate('list_booking', $data);
    }

    public function get_booking() {
        $columns = array( 'id','client_id','title','datetime','employee_id','status');
        $results = $this->Admin_model->getBooking($this->input->post('start'),$this->input->post('length'),$columns[$this->input->post('order')[0]['column']],$this->input->post('order')[0]['dir'],$this->input->post('search')['value']);
        $user_data = $results['data'];
        $total_results = $results['total_result'];

        $data = array();
        foreach($user_data as $user)
        {   
            $assigned = '';
            if($user->status=='pending'){
                $assigned = "<a href='#' class='assign btn btn-default btn-xs' data-id='".$user->id."' data-employee='".$user->employee_id."'>Assign</a>";
            }
            $action = "<a href='".base_url("admin/add_booking/$user->id")."' class='btn btn-default btn-xs'><i class='fa fa-edit'></i></a><a href='javascript:delete_booking(".$user->id.")' class='btn btn-default btn-xs'><i class='fa fa-times'></i></a>".$assigned."<a href='".base_url("admin/manage_transaction/$user->id")."' class='btn btn-default btn-xs'>Transaction</a>";
            $link = "<a class='btn-link' href='".base_url("admin/add_client/".$user->client_id."/v")."'>".$user->cf." ".$user->cl."</a>";
            $link2 = "<a class='btn-link' href='".base_url("admin/add_employee/".$user->employee_id."/v")."'>".$user->ef." ".$user->el."</a>";
            $data[] = array($user->id,$link,$user->title,$user->datetime,$link2,ucfirst($user->status),$action);
        }

        $json_data = array(
                        "draw"            => intval( $this->input->post('draw') ),
                        "recordsTotal"    => intval( count($user_data) ), 
                        "recordsFiltered" => intval( $total_results ),
                        "data"            => $data  
                        );

        echo json_encode($json_data);
    }

    public function add_booking() {
        $ID = $this->uri->segment(3);
        $data['packages'] = $this->Admin_model->getPackages();
        $data['client'] = $this->Admin_model->getClients();
        if (!empty($ID)) {
            $data['data'] = $this->Admin_model->editBookingDetail($ID);
        }
        $this->form_validation
        ->set_rules('package_id', 'Package', 'required|trim')
        ->set_rules('datetime', 'Date Time', 'required|trim')
        ->set_rules('amount', 'Date Time', 'required|trim');
        if ($this->form_validation->run()) {
            $UserData = $this->input->post();
            $UserData['datetime'] = date('Y-m-d H:i:s', strtotime($this->input->post('datetime')));
            $orderby = 'id ASC';
            if (!empty($ID)) {
                $insertID = $this->Admin_model->updateBooking($ID, $UserData);
                $this->session->set_flashdata(array('success' => "Booking is updated successfully."), "flash");
                redirect('admin/manage_booking');
            } else {
                $insertID = $this->Admin_model->insertBooking($UserData);

                $client_data = $this->Admin_model->editUserDetail($UserData['client_id']);
                $package_data = $this->Admin_model->editPackageDetail($UserData['package_id']);
                $package_title = $package_data->title;
                $currency = $package_data->currency;
                $template = 'booking';
                $subject = $this->Admin_model->getNotificationSubject($template);
                $message = array('site_name' => 'Fog Cleaning', 'firstname' => $UserData['request_firstname'],'lastname' => $UserData['request_lastname'], 'email' => $UserData['request_email'], 'phone' => $UserData['request_phone'], 'address' => $UserData['request_address'], 'zipcode' => $UserData['request_zipcode'],'package_title' =>$package_title,'booking_date'=>$UserData['datetime'],'transaction_id'=>'','amount'=>$currency.$UserData['amount']);
                $this->load->SendMail($client_data->email,$template,$subject,$message);

                $this->session->set_flashdata(array('success' => "Booking is added successfully."), "flash");
            }
            if (!empty($insertID)) {
                redirect('admin/manage_booking');
            } else {
                $data['error'] = "Data not submitted Please try again later";
            }
        }

        if (!empty($ID)) {
            $data['Title'] = "Edit booking";
            $this->load->AdminTemplate('edit_booking', $data);
        } else {
            $data['Title'] = "Add booking";
            $this->load->AdminTemplate('edit_booking', $data);
        }
    }

    public function delete_booking() {
        $this->Admin_model->deleteBooking($this->input->post('delete_id'));
        $this->session->set_flashdata(array('success' => "Booking is deleted successfully."));
        redirect('admin/manage_booking');
    }

    public function process_request() {
        $request_id = $this->input->post('id');
        $data = $this->Admin_model->getProcessRequest($request_id);
        $Data['request_id'] = $request_id;
        $date = date('Y-m-d H:i:s');
        $Data['track_datetime'] = $date;
        $status = $this->input->post('status');
        $Data['track_status'] = $status;

        if ($data) {
            if ($status == 'pause' || ($status == 'end' && $data->track_status == 'resume')) {
                $Data['total_time'] = date('H:i:s',strtotime($data->total_time) + (strtotime($date) - strtotime($data->track_datetime)));
            }

            if ($status == 'start' || $status == 'end') {
                if ($status == 'start') {
                    $Data2['status'] = 'active';
                } else {
                    $Data2['status'] = 'completed';
                }
                $this->Admin_model->updateBooking($request_id, $Data2);
            }

            $this->Admin_model->updateProcessRequest($data->id, $Data);
        } else {
            $insertID = $this->Admin_model->insertProcessRequest($Data);
        }
    }

    public function manage_transaction() {
        $data['Title'] = "Admin | Transaction List";
        $this->load->AdminTemplate('list_transaction', $data);
    }

    public function get_transaction() {
        $columns = array( 'id','client_id','title','transaction_id','transaction_amount','datetime');
        $results = $this->Admin_model->getTransaction($this->input->post('start'),$this->input->post('length'),$columns[$this->input->post('order')[0]['column']],$this->input->post('order')[0]['dir'],$this->input->post('search')['value'],$this->uri->segment(3));
        $user_data = $results['data'];
        $total_results = $results['total_result'];

        $data = array();
        foreach($user_data as $user)
        {
            $action = "<a href='".base_url("admin/view_invoice/$user->id")."' class='btn btn-default btn-xs'>View Invoice</a>";
            $link = "<a class='btn-link' href='".base_url("admin/add_client/".$user->client_id."/v")."'>".$user->firstname." ".$user->lastname."</a>";
            $data[] = array($user->id,$link,$user->title,$user->transaction_id,$user->transaction_amount,$user->datetime,$action);
        }

        $json_data = array(
                        "draw"            => intval( $this->input->post('draw') ),
                        "recordsTotal"    => intval( count($user_data) ), 
                        "recordsFiltered" => intval( $total_results ),
                        "data"            => $data  
                        );

        echo json_encode($json_data);
    }

    public function manage_cms() {
        $data['Title'] = "Admin | Manage CMS";
        $data['data'] = $this->Admin_model->getPages();
        $this->load->AdminTemplate('list_pages', $data);
    }

    public function edit_page() {
        $ID = $this->uri->segment(3);
        if (!empty($ID)) {
            $data['data'] = $this->Admin_model->editPageDetail($ID);
        }

        $this->form_validation->set_rules('title', 'Title', 'required|trim')
        ->set_rules('content', 'Content', 'required|trim');
        if ($this->form_validation->run()) {
            $UserData = $this->input->post();
            $this->Admin_model->updatePage($ID, $UserData);
            $this->session->set_flashdata(array('success' => "Page is updated successfully."), "flash");
            redirect('admin/manage_cms');
        }
        $data['Title'] = "Edit Page";
        $this->load->AdminTemplate('edit_page', $data);        
    }

    public function manage_report_an_issue() {
        $data['Title'] = "Admin | Manage Report An Issue";
        $this->load->AdminTemplate('list_report_an_issue', $data);
    }

    public function get_report_an_issue() {
        $columns = array( 'id','user_id','description','datetime','status');
        $results = $this->Admin_model->getReportAnIssue($this->input->post('start'),$this->input->post('length'),$columns[$this->input->post('order')[0]['column']],$this->input->post('order')[0]['dir'],$this->input->post('search')['value']);
        $user_data = $results['data'];
        $total_results = $results['total_result'];

        $data = array();
        foreach($user_data as $user)
        {   
            $resolved = "";
            if($user->status=='pending'){
                $resolved = "<a href='".base_url("admin/report_resolved/$user->id")."' class='btn btn-default btn-xs'>Resolve</a>";
            }
            $action = "<a href='javascript:delete_report_an_issue(".$user->id.")' class='btn btn-default btn-xs'><i class='fa fa-times'></i></a>".$resolved."<a href='javascript:info($user->id);' class='btn btn-default btn-xs'><i class='fa fa-eye'></i></a><span style='display:none;' id='data$user->id'>$user->description</span>";
            $link = "<a class='btn-link' href='".base_url("admin/add_client/".$user->user_id."/v")."'>".$user->firstname." ".$user->lastname."</a>";
            $description = strlen($user->description) > 50 ? substr($user->description,0,50)."..." : $user->description;
            $data[] = array($user->id,$link,$description,$user->datetime,ucfirst($user->status),$action);
        }

        $json_data = array(
                        "draw"            => intval( $this->input->post('draw') ),
                        "recordsTotal"    => intval( count($user_data) ), 
                        "recordsFiltered" => intval( $total_results ),
                        "data"            => $data  
                        );

        echo json_encode($json_data);
    }

    public function delete_report_an_issue() {
        $this->Admin_model->deleteReportAnIssue($this->input->post('delete_id'));
        $this->session->set_flashdata(array('success' => "Report an issue is deleted successfully."));
        redirect('admin/manage_report_an_issue');
    }

    public function report_resolved() {
        $this->Admin_model->reportResolved($this->uri->segment(3));
        $this->session->set_flashdata(array('success' => "Reported issue is resolved."));
        redirect('admin/manage_report_an_issue');
    }

    public function manage_review_rating() {
        $data['Title'] = "Admin | Manage Review Rating";
        $this->load->AdminTemplate('list_review_rating', $data);
    }

    public function get_review_rating() {
        $columns = array( 'id','client_id','employee_id','title','review','rating','datetime');
        $results = $this->Admin_model->getReviewRating($this->input->post('start'),$this->input->post('length'),$columns[$this->input->post('order')[0]['column']],$this->input->post('order')[0]['dir'],$this->input->post('search')['value']);
        $user_data = $results['data'];
        $total_results = $results['total_result'];

        $data = array();
        foreach($user_data as $user)
        { 
            $action = "<a href='javascript:delete_review_rating(".$user->id.")' class='btn btn-default btn-xs'><i class='fa fa-times'></i></a>";
            $link = "<a class='btn-link' href='".base_url("admin/add_client/".$user->client_id."/v")."'>".$user->cf." ".$user->cl."</a>";
            $link2 = "<a class='btn-link' href='".base_url("admin/add_employee/".$user->employee_id."/v")."'>".$user->ef." ".$user->el."</a>";
            $data[] = array($user->id,$link,$link2,$user->title,$user->review,$user->rating,$user->datetime,$action);
        }

        $json_data = array(
                        "draw"            => intval( $this->input->post('draw') ),
                        "recordsTotal"    => intval( count($user_data) ), 
                        "recordsFiltered" => intval( $total_results ),
                        "data"            => $data  
                        );

        echo json_encode($json_data);
    }

    public function delete_review_rating() {
        $this->Admin_model->deleteReviewRating($this->input->post('delete_id'));
        $this->session->set_flashdata(array('success' => "Review rating is deleted successfully."));
        redirect('admin/manage_review_rating');
    }

    public function add_review_rating() {
        $data['client'] = $this->Admin_model->getClients();
        $data['employee'] = $this->Admin_model->getEmployees();
        $this->form_validation->set_rules('client_id', 'Client', 'required|trim')
        ->set_rules('employee_id', 'Employee', 'required|trim')
        ->set_rules('request_id', 'Request', 'required|trim')
        ->set_rules('review', 'Review', 'required|trim')
        ->set_rules('rating', 'Rating', 'required|trim');
        if ($this->form_validation->run()) {
            $this->Admin_model->insertReviewRating($this->input->post());
            $this->session->set_flashdata(array('success' => "Review rating is added successfully."), "flash");
            redirect('admin/manage_review_rating');
        }
            $data['Title'] = "Add Review Rating";
            $this->load->AdminTemplate('add_review_rating', $data);
    }

    function get_client_employee_booking() {
        echo json_encode($this->Admin_model->getClientEmployeeBooking($this->input->post('client_id'),$this->input->post('employee_id')));
    }

    function manage_promo_code() {
        $data['Title'] = "Admin | Manage Promo Code";
        $this->load->AdminTemplate('list_promo_code', $data);
    }

    function get_promo_code() {
        $columns = array( 'id','promo_code','type','amount','valid_from','valid_to','max_usage','no_of_usage','max_usage_per_customer');
        $results = $this->Admin_model->getPromoCode($this->input->post('start'),$this->input->post('length'),$columns[$this->input->post('order')[0]['column']],$this->input->post('order')[0]['dir'],$this->input->post('search')['value']);
        $user_data = $results['data'];
        $total_results = $results['total_result'];

        $data = array();
        foreach($user_data as $user)
        {
            $action = "<a href='".base_url("admin/add_promo_code/$user->id")."' class='btn btn-default btn-xs'><i class='fa fa-edit'></i></a><a href='javascript:delete_promo_code(".$user->id.")' class='btn btn-default btn-xs'><i class='fa fa-times'></i></a>";
            $data[] = array($user->id,$user->promo_code,$user->type,$user->amount,$user->valid_from,$user->valid_to,$user->max_usage,$user->no_of_usage,$user->max_usage_per_customer,$action);
        }

        $json_data = array(
                        "draw"            => intval( $this->input->post('draw') ),
                        "recordsTotal"    => intval( count($user_data) ), 
                        "recordsFiltered" => intval( $total_results ),
                        "data"            => $data  
                        );

        echo json_encode($json_data);
    }

    public function add_promo_code() {
        $ID = $this->uri->segment(3);
        if (!empty($ID)) {
            $data['data'] = $this->Admin_model->editPromoCodeDetail($ID);
        }

        $this->form_validation->set_rules('promo_code', 'Promo Code', 'required|trim')
        ->set_rules('type', 'Type', 'required|trim')
        ->set_rules('amount', 'Amount', 'required|trim')
        ->set_rules('valid_from', 'valid From', 'required|trim')
        ->set_rules('valid_to', 'Valid To', 'required|trim');
        if ($this->form_validation->run()) {
            $UserData = $this->input->post();
            $UserData['valid_from'] = date("Y-m-d",strtotime($this->input->post('valid_from')));
            $UserData['valid_to'] = date("Y-m-d",strtotime($this->input->post('valid_to')));
            if (!empty($ID)) {
                $insertID = $this->Admin_model->updatePromoCode($ID, $UserData);
                $this->session->set_flashdata(array('success' => "Promo code is updated successfully."), "flash");
            } else {
                $UserData['created_date'] = date('Y-m-d H:i:s');
                $insertID = $this->Admin_model->insertPromoCode($UserData);
                $this->session->set_flashdata(array('success' => "Promo code is added successfully."), "flash");
            }

            if (!empty($insertID)) {
                redirect('admin/manage_promo_code');
            } else {
                $data['error'] = "Data not submitted Please try again later";
            }
        }
        if (!empty($ID)) {
            $data['Title'] = "Edit Promo Code";
            $this->load->AdminTemplate('edit_promo_code', $data);
        } else {
            $data['Title'] = "Add Promo Code";
            $this->load->AdminTemplate('edit_promo_code', $data);
        }
    }


    public function delete_promo_code() {
        $this->Admin_model->deletePromoCode($this->input->post('delete_id'));
        $this->session->set_flashdata(array('success' => "Package is deleted successfully."), "flash");
        redirect('admin/manage_promo_code');
    }

    public function view_invoice() {
        $data['Title'] = "View Invoice";
        $data['data'] = $this->Admin_model->getInvoiceDetails($this->uri->segment(3));
        $this->load->AdminTemplate('view_invoice', $data);
    }

    public function manage_notification() {
        $data['Title'] = "Admin | Manage Notification";
        $data['data'] = $this->Admin_model->getNotifications();
        $this->load->AdminTemplate('list_notification', $data);
    }

    public function edit_notification() {
        $ID = $this->uri->segment(3);
        $data['data'] = $this->Admin_model->editNotificationDetail($this->uri->segment(3));
        $template = $data['data']->key;

        $this->form_validation->set_rules('subject', 'Subject', 'required|trim');
        if ($this->form_validation->run()) {
            $UserData['subject'] = $this->input->post('subject');
            $this->load->helper('file');
            $template_path=APPPATH.'/views/template/email/'.$template.".tpl";
            $content ='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'
            .'<html xmlns="http://www.w3.org/1999/xhtml">'
            .'<head>'
            .'<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" style="background-color: #ffffff;">'
            .$this->input->post('content')
            .'</div></body></html>';
            write_file($template_path, $content);
            
            $this->Admin_model->updateNotification($ID, $UserData);
            $this->session->set_flashdata(array('success' => "Notification is updated successfully."), "flash");
            redirect('admin/manage_notification');
        }
        $data['Title'] = "Edit Notification";
        $data['notification_template'] = $this->load->setEmailTemplate($template, '');
        $this->load->AdminTemplate('edit_notification', $data);
    }

    public function do_upload($path, $file_element_name) {
        $config['upload_path'] = $path;
        $config['allowed_types'] = 'gif|jpg|png|jpeg';
        // $config['max_size'] = 1000;
        // $config['max_width'] = 10240;
        // $config['max_height'] = 7680;
        $CI = & get_instance();
        $CI->load->library('upload', $config);
        if (!$CI->upload->do_upload($file_element_name)) {
            $data = $CI->upload->display_errors();
        } else {
            $data = $CI->upload->data();
        }
        return $data;
    }

    public function image_resize($image_data,$resized_path,$width,$height) {
        $config = array(
            'source_image'      => $image_data['full_path'], //path to the uploaded image
            'new_image'         => $resized_path, //path to
            'maintain_ratio'    => false,
            'width'             => $width,
            'height'            => $height
        );

        $this->image_lib->initialize($config);
        $this->image_lib->resize();
    }

    public function decodeImage($image, $type = 'jpg') {
        if ($image) {
            $name = time() . rand(0, 9999) . "." . $type;
            $url = FCPATH . "assets/images/tmp/" . $name;
            $fp = fopen($url, "w");
            $decoded = base64_decode(trim($image));
            file_put_contents($url, $decoded);
            return $name;
        }
    }
}
?>
