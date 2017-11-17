<?php

class Admin_model extends CI_Model {

    function __construct() {
        parent::__construct();
    }

    function checkAdminLogin($email, $password) {
        $this->db->select('admin_id,email')->from('tbl_admin')->where(array('email' => $email, 'password' => $password));
        $query = $this->db->get();
        return $query->row();
    }

    function checkAdminDetailByid($admin_id) {
        $this->db->select('*')->from('tbl_admin')->where('admin_id', $admin_id);
        $query = $this->db->get();
        return $query->row();
    }

    function checkUserEmail($email) {
        $data = array('email' => $email);
        $this->db->where($data);
        return $this->db->count_all_results('tbl_admin');
    }

    function update_profile($data, $admin_id) {
        $this->db->where('admin_id', $admin_id);
        $this->db->update('tbl_admin', $data);
    }

    function checkValidOldPass($admin_id, $password) {
        $this->db->select('admin_id')->from('tbl_admin')->where(array('admin_id' => $admin_id, 'password' => $password));
        $query = $this->db->get();
        return $query->row();
    }

    function updatePassword($admin_id, $password) {
        $this->db->where('admin_id', $admin_id);
        $this->db->update('tbl_admin', array('password' => $password));
    }

    function checkValidEmail($email) {
        $this->db->select('*')->from('tbl_admin')->where('email',$email);
        return $this->db->get()->row();
    }
    
    function getEmployee($start,$length,$order_by,$sort_by,$search)
    {
        $this->db->select('SQL_CALC_FOUND_ROWS id,firstname,lastname,email,phone,created_date,status,(SELECT AVG(rating) FROM `tbl_rating_review` where employee_id=tbl_users.id) as rating', FALSE)->from('tbl_users')
        ->where(array('user_role' => 2));
        if($search) {   
            $this->db->group_start()
            ->like("firstname", $search)
            ->or_like("lastname", $search)
            ->or_like("email", $search)
            ->or_like("status", $search)
            ->group_end();
        }
        
        $this->db->order_by($order_by, $sort_by)
        ->limit($length, $start);
        $query =  $this->db->get();
        $result['data'] = $query->result_object();
        
        $query = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
        $result['total_result'] = $query->row()->Count;
        
        return $result;
    }
    
    function deleteUser($user_id) {
        $this->db->where('id', $user_id);
        return $this->db->delete('tbl_users');
    }

    function getRequest($start,$length,$order_by,$sort_by,$search,$status)
    {
        $this->db->select('tbl_booking.status,tbl_booking.client_id,tbl_booking.employee_id,tbl_booking.id,
    tbl_booking.datetime, tbl_packages.title,client.firstname as cf,client.lastname as
    cl,employee.firstname as ef,employee.lastname as el')
        ->from('tbl_booking')
        ->join('tbl_packages', 'tbl_booking.package_id = tbl_packages.id')
        ->join('tbl_users client', 'tbl_booking.client_id = client.id','left')
        ->join('tbl_users employee', 'tbl_booking.employee_id = employee.id','left')
        ->where("tbl_booking.status", $status);
        if($search) {   
            $this->db->group_start()
            ->like("client.firstname", $search)
            ->or_like("client.lastname", $search)
            ->or_like("tbl_packages.title", $search)
            ->or_like("employee.firstname", $search)
            ->or_like("employee.lastname", $search)
            ->or_like("tbl_booking.datetime", $search)
            ->group_end();
        }
        
        $this->db->order_by($order_by, $sort_by)
        ->limit($length, $start);
        $query =  $this->db->get();
        $result['data'] = $query->result_object();
        $query = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
        $result['total_result'] = $query->row()->Count;
        
        return $result;
    }

    function getEmployeeAssigned() {  
       $this->db->select('id,firstname,lastname,(SELECT GROUP_CONCAT(CONCAT(b.datetime,"=",t.firstname)) FROM `tbl_booking` b 
        INNER JOIN tbl_users t ON b.client_id=t.id 
        where employee_id=tbl_users.id AND datetime > now()) as datetime')    
       ->from('tbl_users')
       ->where(array("user_role"=>'2',"status"=>'enable'));
       $query =  $this->db->get();
       return $query->result_array();
    }

    function insertUser($data) {
        $this->db->insert('tbl_users', $data);
        return $this->db->insert_id();
    }

    function editUserDetail($id) {
        $data = array('id' => $id);
        $this->db->where($data);
        $query = $this->db->get('tbl_users');
        return $query->row();
    }

    function updateUser($id, $data) {
        $where_data = array('id' => $id);
        $this->db->where($where_data);
        return $this->db->update('tbl_users', $data);
    }

    function getClient($start,$length,$order_by,$sort_by,$search)
    {
        $this->db->select('SQL_CALC_FOUND_ROWS id,firstname,lastname,email,phone,created_date,status', FALSE)->from('tbl_users')
        ->where(array('user_role' => 1));
        if($search) {   
            $this->db->group_start()
            ->like("firstname", $search)
            ->or_like("lastname", $search)
            ->or_like("email", $search)
            ->or_like("status", $search)
            ->group_end();
        }
        
        $this->db->order_by($order_by, $sort_by)
        ->limit($length, $start);
        $query = $this->db->get();
        $result['data'] = $query->result_object();
        
        $query = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
        $result['total_result'] = $query->row()->Count;
        
        return $result;
    }

    function getPackage($start,$length,$order_by,$sort_by,$search)
    {
        $this->db->select('SQL_CALC_FOUND_ROWS id,title,price', FALSE)->from('tbl_packages');
        if($search) {   
            $this->db->like("title", $search)
            ->or_where("price", $search);
        }
        
        $this->db->order_by($order_by, $sort_by)
        ->limit($length, $start);
        $query =  $this->db->get();
        $result['data'] = $query->result_object();
        
        $query = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
        $result['total_result'] = $query->row()->Count;
        
        return $result;
    }

    function editPackageDetail($id) {
        $data = array('id' => $id);
        $this->db->where($data);
        $query = $this->db->get('tbl_packages');
        return $query->row();
    }

    function insertPackage($data) {
        $this->db->insert('tbl_packages', $data);
        return $this->db->insert_id();
    }

    function updatePackage($id, $data) {
        $this->db->where('id',$id);
        return $this->db->update('tbl_packages', $data);
    }

    function deletePackage($id) {
        $this->db->where('id', $id);
        return $this->db->delete('tbl_packages');
    }

    function employeeAssigned($id, $data) {
        $this->db->where('id',$id);
        $this->db->update('tbl_booking', $data);
        return $this->db->affected_rows();
    }

    function getBooking($start,$length,$order_by,$sort_by,$search)
    {
        $this->db->select('SQL_CALC_FOUND_ROWS tbl_booking.status,tbl_booking.client_id,tbl_booking.employee_id,tbl_booking.id,tbl_booking.datetime, tbl_packages.title,client.firstname as cf,client.lastname as cl,employee.firstname as ef,employee.lastname as el', FALSE)
        ->from('tbl_booking')
        ->join('tbl_packages', 'tbl_booking.package_id = tbl_packages.id','left')
        ->join('tbl_users client', 'tbl_booking.client_id = client.id','left')
        ->join('tbl_users employee', 'tbl_booking.employee_id = employee.id','left');
        if($search) {
            $this->db->like("client.firstname", $search)
            ->or_like("client.lastname", $search)
            ->or_like("tbl_packages.title", $search)
            ->or_like("employee.firstname", $search)
            ->or_like("employee.lastname", $search)
            ->or_like("tbl_booking.status", $search);
        }
        
        $this->db->order_by($order_by, $sort_by)
        ->limit($length, $start);
        $query =  $this->db->get();
        $result['data'] = $query->result_object();
        
        $query = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
        $result['total_result'] = $query->row()->Count;
        
        return $result;
    }

    function getClientRequest($start,$length,$order_by,$sort_by,$search,$client_id)
    {
        $this->db->select('SQL_CALC_FOUND_ROWS t.total_time,tbl_booking.status,tbl_booking.client_id,tbl_booking.employee_id,tbl_booking.id,tbl_booking.datetime, tbl_packages.title,client.firstname as cf,client.lastname as cl,employee.firstname as ef,employee.lastname as el', FALSE)->from('tbl_booking')
        ->join('tbl_packages', 'tbl_booking.package_id = tbl_packages.id','left')
        ->join('tbl_users client', 'tbl_booking.client_id = client.id','left')
        ->join('tbl_users employee', 'tbl_booking.employee_id = employee.id','left')
        ->join('tbl_request_tracking t', 'tbl_booking.id = t.request_id','left')
        ->having("tbl_booking.client_id", $client_id);
        if($search) {
            $this->db->group_start()
            ->like("tbl_packages.title", $search)
            ->or_like("employee.firstname", $search)
            ->or_like("employee.lastname", $search)
            ->or_like("tbl_booking.status", $search)
            ->group_end();
        }
        
        $this->db->order_by($order_by, $sort_by)
        ->limit($length, $start);
        $query =  $this->db->get();
        $result['data'] = $query->result_object();
        
        $query = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
        $result['total_result'] = $query->row()->Count;
        
        return $result;
    }

    function getEmployeeRequest($start,$length,$order_by,$sort_by,$search,$employee_id)
    {
        $this->db->select('SQL_CALC_FOUND_ROWS tbl_request_tracking.track_datetime,tbl_request_tracking.total_time,tbl_request_tracking.track_status,tbl_booking.status,tbl_booking.client_id,tbl_booking.employee_id,tbl_booking.id,tbl_booking.datetime, tbl_packages.title,client.firstname as cf,client.lastname as cl,employee.firstname as ef,employee.lastname as el,tbl_rating_review.rating', FALSE)
        ->from('tbl_booking')
        ->join('tbl_packages', 'tbl_booking.package_id = tbl_packages.id','left')
        ->join('tbl_users client', 'tbl_booking.client_id = client.id','left')
        ->join('tbl_users employee', 'tbl_booking.employee_id = employee.id','left')
        ->join('tbl_request_tracking', 'tbl_request_tracking.request_id=tbl_booking.id','left')
        ->join('tbl_rating_review', 'tbl_rating_review.request_id=tbl_booking.id','left')
        ->having("tbl_booking.employee_id", $employee_id);
        if($search) {
            $this->db->group_start()
            ->or_like("tbl_packages.title", $search)
            ->or_like("client.firstname", $search)
            ->or_like("client.lastname", $search)
            ->or_like("tbl_booking.status", $search)
            ->group_end();
        }
        
        $this->db->order_by($order_by, $sort_by)
        ->limit($length, $start);
        $query =  $this->db->get();
        $result['data'] = $query->result_object();
        
        $query = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
        $result['total_result'] = $query->row()->Count;
        
        return $result;
    }

    function editBookingDetail($id) {
        $this->db->where('id',$id);
        $query = $this->db->get('tbl_booking');
        return $query->row();
    }

    function getClients() {
        $this->db->select('id,firstname,lastname')->where('user_role','1');
        $query = $this->db->get('tbl_users');
        return $query->result_array();
    }

    function getEmployees() {
        $this->db->select('id,firstname,lastname')->where('user_role','2');
        $query = $this->db->get('tbl_users');
        return $query->result_array();
    }

    function updateBooking($id, $data) {
        $where_data = array('id' => $id);
        $this->db->where($where_data);
        return $this->db->update('tbl_booking', $data);
    }

    function insertBooking($data) {
        $this->db->insert('tbl_booking', $data);
        return $this->db->insert_id();
    }

    function deleteBooking($id) {
        $this->db->where('id', $id);
        return $this->db->delete('tbl_booking');
    }

    function getProcessRequest($id) {
        $this->db->select('id,track_datetime,track_status,total_time');
        $this->db->where('request_id', $id);
        $query = $this->db->get('tbl_request_tracking');
        return $query->row();
    }

    function updateProcessRequest($id, $data) {
        $where_data = array('id' => $id);
        $this->db->where($where_data);
        return $this->db->update('tbl_request_tracking', $data);
    }

    function insertProcessRequest($data) {
        $this->db->insert('tbl_request_tracking', $data);
        return $this->db->insert_id();
    }

    function getTransaction($start,$length,$order_by,$sort_by,$search,$request_id)
    {
        $this->db->select('SQL_CALC_FOUND_ROWS u.firstname,u.lastname,t.id,t.client_id,t.transaction_id,t.transaction_amount,t.transaction_date,t.request_id,b.datetime,p.title', FALSE)
        ->from('tbl_transactions t')
        ->join('tbl_users u', 'u.id=t.client_id','inner')
        ->join('tbl_booking b', 'b.id=t.request_id','inner')
        ->join('tbl_packages p', 'p.id=b.package_id','inner')
        ->having("t.request_id", $request_id);
        if($search) {
            $this->db->group_start()
            ->like("u.firstname", $search)
            ->or_like("u.lastname", $search)
            ->or_like("p.title", $search)
            ->or_like("t.transaction_id", $search)
            ->or_like("t.transaction_amount", $search)
            ->or_like("t.transaction_date", $search)
            ->group_end();
        }
        
        $this->db->order_by($order_by, $sort_by)
        ->limit($length, $start);
        $query =  $this->db->get();
        $result['data'] = $query->result_object();
        
        $query = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
        $result['total_result'] = $query->row()->Count;
        
        return $result;
    }

    function getPackages() {
        $this->db->select('*');
        $query = $this->db->get('tbl_packages');
        return $query->result_array();
    }    

    function getPages() {
        $this->db->select('*');
        $query = $this->db->get('tbl_pages');
        return $query->result_array();
    }

    function editPageDetail($id) {
        $this->db->where('id',$id);
        $query = $this->db->get('tbl_pages');
        return $query->row();
    }

    function updatePage($id, $data) {
        $this->db->where('id',$id);
        return $this->db->update('tbl_pages', $data);
    }

    function getReportAnIssue($start,$length,$order_by,$sort_by,$search)
    {
        $this->db->select('SQL_CALC_FOUND_ROWS r.*,u.firstname,u.lastname,u.user_role', FALSE)->from('tbl_report_an_issue r')
        ->join('tbl_users u', 'r.user_id=u.id','inner');
        if($search) {   
            $this->db->like("firstname", $search)
            ->or_like("lastname", $search)
            ->or_like("datetime", $search)
            ->or_like("description", $search)
            ->or_like("r.status", $search);
        }
        
        $this->db->order_by($order_by, $sort_by)
        ->limit($length, $start);
        $query = $this->db->get();
        $result['data'] = $query->result_object();
        
        $query = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
        $result['total_result'] = $query->row()->Count;
        
        return $result;
    }

    function deleteReportAnIssue($id) {
        $this->db->where('id', $id);
        return $this->db->delete('tbl_report_an_issue');
    }

    function reportResolved($id) {
        $this->db->where('id',$id);
        return $this->db->update('tbl_report_an_issue', array('status'=>'resolved'));
    }

    function getReviewRating($start,$length,$order_by,$sort_by,$search)
    {
        $this->db->select('SQL_CALC_FOUND_ROWS r . * , p.title, c.firstname as cf, c.lastname as cl, e.firstname as ef, e.lastname as el', FALSE)->from('tbl_rating_review r')
        ->join('tbl_booking b', 'r.request_id = b.id','left')
        ->join('tbl_packages p', 'b.package_id = p.id','left')
        ->join('tbl_users c', 'r.client_id = c.id','left')
        ->join('tbl_users e', 'r.employee_id = e.id','left');
        if($search) {   
            $this->db->like("c.firstname", $search)
            ->or_like("c.lastname", $search)
            ->or_like("p.title", $search)
            ->or_like("e.firstname", $search)
            ->or_like("e.lastname", $search)
            ->or_like("r.review", $search)
            ->or_like("r.rating", $search)
            ->or_like("r.datetime", $search);
        }
        
        $this->db->order_by($order_by, $sort_by)
        ->limit($length, $start);
        $query =  $this->db->get();
        $result['data'] = $query->result_object();
        
        $query = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
        $result['total_result'] = $query->row()->Count;
        
        return $result;
    }

    function deleteReviewRating($id) {
        $this->db->where('id', $id);
        return $this->db->delete('tbl_rating_review');
    }

    function getChartData() {
        $days = '15';
        $this->db->select('count(id) as total,DATE(created_date) as date, user_role')
        ->where('created_date BETWEEN DATE_SUB(NOW(), INTERVAL '.$days.' DAY) AND NOW()')
        ->group_by('date,user_role');
        $query = $this->db->get('tbl_users');
        $result = $query->result_object();
        $data['client']=$data['employee']=$data_ary=array();
        $start_date = date("Y-m-d");
        if($result){
            foreach ($result as $value) {
                if($value->user_role==1){
                    $data['client'][$value->date] = $value->total; 
                }else{
                    $data['employee'][$value->date] = $value->total;
                }
            }
        }
        $k1= $k2 = 0;
        for ($i = 0; $i < $days; $i++) {
            $type_data = isset($data['client'][$start_date])?$data['client'][$start_date]:"0";
            $type_data2 = isset($data['employee'][$start_date])?$data['employee'][$start_date]:"0";
            
            $data_ary['client'][] = array('x'=>$start_date,'y'=>$type_data);
            $data_ary['employee'][] = array('x'=>$start_date,'y'=>$type_data2);
            $start_date = date("Y-m-d", strtotime("-1 day", strtotime($start_date)));
        }
        // else{
        //     $data_ary['client'][] = array('x'=>$start_date,'y'=>0);
        //     $data_ary['employee'][] = array('x'=>$start_date,'y'=>0);
        // }
        return $data_ary;
    }

    function insertReviewRating($data) {
        $this->db->insert('tbl_rating_review', $data);
        return $this->db->insert_id();
    }

    function getClientEmployeeBooking($client_id,$employee_id) {
        $this->db->select('b.id,p.title')
        ->where(array('b.client_id'=>$client_id,'b.employee_id'=>$employee_id))
        ->join('tbl_packages p','b.package_id=p.id');
        $query = $this->db->get('tbl_booking b');
        return $query->result_array();
    }

    function getPromoCode($start,$length,$order_by,$sort_by,$search)
    {
        $this->db->select('SQL_CALC_FOUND_ROWS *', FALSE)->from('tbl_promo_codes');
        if($search) {   
            $this->db->or_where("type", $search)
            ->or_where("max_usage", $search)
            ->or_where("no_of_usage", $search)
            ->or_where("max_usage_per_customer", $search)
            ->like("promo_code", $search)
            ->or_like("amount", $search)
            ->or_like("valid_from", $search)
            ->or_like("valid_to", $search);
        }
        
        $this->db->order_by($order_by, $sort_by)
        ->limit($length, $start);
        $query =  $this->db->get();
        $result['data'] = $query->result_object();
        
        $query = $this->db->query('SELECT FOUND_ROWS() AS `Count`');
        $result['total_result'] = $query->row()->Count;
        
        return $result;
    }

    function editPromoCodeDetail($id) {
        $data = array('id' => $id);
        $this->db->where($data);
        $query = $this->db->get('tbl_promo_codes');
        return $query->row();
    }

    function insertPromoCode($data) {
        $this->db->insert('tbl_promo_codes', $data);
        return $this->db->insert_id();
    }

    function updatePromoCode($id, $data) {
        $this->db->where('id',$id);
        return $this->db->update('tbl_promo_codes', $data);
    }

    function deletePromoCode($id) {
        $this->db->where('id', $id);
        return $this->db->delete('tbl_promo_codes');
    }

    function getInvoiceDetails($id) {
        $this->db->select('t.id, t.transaction_id, t.transaction_amount, t.transaction_date, b.request_firstname, b.request_lastname, b.request_email, b.request_phone, b.request_address, b.request_zipcode, p.title, p.price, p.currency, pc.promo_code, pc.type, pc.amount')
        ->from('tbl_transactions t')
        ->join('tbl_booking b', 'b.id=t.request_id')
        ->join('tbl_users c', 'c.id=t.client_id')
        ->join('tbl_packages p', 'p.id=t.package_id')
        ->join('tbl_promo_codes pc', 'pc.id=t.promo_code_id','left')
        ->where('t.id',$id);
        $query =  $this->db->get();
        return $query->row();
    }

    function getNotifications() {
        $this->db->select('*');
        $query = $this->db->get('tbl_notifications');
        return $query->result_array();
    }

    function editNotificationDetail($id) {
        $this->db->where('id',$id);
        $query = $this->db->get('tbl_notifications');
        return $query->row();
    }

    function updateNotification($id, $data) {
        $this->db->where('id',$id);
        return $this->db->update('tbl_notifications', $data);
    }

    function getNotificationSubject($key) {
        $this->db->select('subject')->from('tbl_notifications')
        ->where('key',$key);
        $result = $this->db->get()->row();
        if($result){
            return $result->subject;
        }
        return false;
    }
}
