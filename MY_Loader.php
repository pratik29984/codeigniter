<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Loader extends CI_Loader {

    public function AdminTemplate($template,$vars=array())
    {
        $content = $this->view('template/admin/header.php',$vars);
        $content = $this->view('admin/'.$template,$vars);
        $content = $this->view('template/admin/footer.php',$vars);
        return $content;
    }
    
    public function SendMail($to,$template,$subject,$message,$fromemail="",$fromname="",$cc=array(),$bcc=array())
    {
        $CI =& get_instance();
        $CI->load->library('settings');
        $from_mail = $fromemail ? $fromemail : $CI->settings->getValue('EMAIL_FROM');
        $from_name = $fromname ? $fromname : $CI->settings->getValue('EMAIL_NAME');
        
        if ($_SERVER['HTTP_HOST'] == 'localhost') {
            $config = array(
                'protocol' => 'smtp',
                'smtp_host' => 'ssl://smtp.googlemail.com',
                'smtp_port' => 465,
                'smtp_user' => 'project.tester24x7@gmail.com',
                'smtp_pass' => 'AdminTester2',
                'mailtype'  => 'html',
                'wordwrap'  => TRUE
            );
        }else{
            $config = array(
                'mailtype'  => 'html',
                'wordwrap'  => TRUE
            );
        }     
        $CI->email->initialize($config);   
        $CI->email->set_newline("\r\n");
        $CI->email->from($from_mail, $from_name);
        $CI->email->to($to);
        $CI->email->subject($subject);
        $CI->email->message($this->setEmailTemplate($template , $message));
        $CI->email->send();
    }
    
    function setEmailTemplate($template, $templateVariables)
    {
        $template = 'views/template/email/'.$template.".tpl";
        $data = "";
        $completePath=APPPATH.$template;
        if($fh = @fopen($completePath, 'r'))
        { 
            $data = @fread($fh, filesize($completePath));
            fclose($fh);
            if($templateVariables){
                foreach($templateVariables as $key=>$val)
                {
                    $data=str_replace('{{'.$key.'}}',$val,$data);
                }
                $data = str_replace('&apos;',"'",$data);
            }
        }
        return  $data;
    }
    
    function hashPassword($password)
    {
        return md5($password);
    }

    function getRandomString($length = 8) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $string = substr(str_shuffle($chars), 0, $length);
        return $string;
    }
}
