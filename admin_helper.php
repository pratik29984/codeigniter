<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

function is_admin_login() {
    $CI = & get_instance();
    $CI->load->library('session');
    return $CI->session->userdata('admin_logged_in');
}

function getLoggedInAdminId() {
    $CI = & get_instance();
    $CI->load->library('session');
    return $CI->session->userdata('admin')->admin_id;
}

function getLoginAdminDetails($key = '') {
    $CI = & get_instance();
    $CI->load->library('session');
    $CI->load->model('Admin_model');
    $user_id = $CI->session->userdata('admin')->admin_id;
    return $CI->Admin_model->checkAdminDetailByid($user_id);
}

?>