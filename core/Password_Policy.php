<?php

namespace ICT\Core;

/* * ***************************************************************
 * Copyright © 2026 ICT Innovations Pakistan All Rights Reserved   *
 * Developed By: Nasir Iqbal                                       *
 * Website : http://www.ictinnovations.com/                        *
 * Mail : support@ictinnovations.com                                 *
 * *************************************************************** */

use ICT\Core\Session;
use ICT\Core\CoreException;
use ICT\Core\Account;
use ICT\Core\Api\ContactApi;
use ICT\Core\Api\TransmissionApi;
use ICT\Core\Api\ProgramApi;
use ICT\Core\Message\Template;

class Password_Policy
{
    private static $table = 'password_policy';
    private static $Usertable = 'usr';


    private static $fields = array(
        'password_policy_id',
        'min_length',
        'min_uppercase',
        'min_lowercase',
        'min_numbers',
        'special_character',
        'passwd_exp_limit',
        'passwd_email_notify',
        'failed_attempts',
        'passwd_history',
        'sessiontime'

    );



    /** @var integer */
    public $password_policy_id = NULL;

    /** @var integer */
    public $min_length = NULL;

    /** @var string */
    public $min_uppercase = NULL;

    /** @var string */
    public $min_lowercase = NULL;

    /** @var string */
    public $special_character = NULL;

    /** @var integer */
    public $min_numbers = NULL;

    /** @var integer */
    public $passwd_exp_limit = NULL;

    /** @var integer */
    public $passwd_email_notify = NULL;

    /** @var integer */
    public $failed_attempts = NULL;

    /** @var integer */
    public $passwd_history = NULL;

    /** @var integer */
    public $sessiontime = NULL;


    public  function getPolicy()
    {
        $query = "SELECT * FROM " . self::$table;
        $result = DB::query(self::$table, $query);
        $data = $result->fetch_assoc();
        if ($data) {
            $this->password_policy_id = $data['password_policy_id'];
            $this->min_length = $data['min_length'];
            $this->min_uppercase = $data['min_uppercase'];
            $this->min_lowercase = $data['min_lowercase'];
            $this->special_character = $data['special_character'];
            $this->min_numbers = $data['min_numbers'];
            $this->passwd_exp_limit = $data['passwd_exp_limit'];
            $this->passwd_email_notify = $data['passwd_email_notify'];
            $this->failed_attempts = $data['failed_attempts'];
            $this->passwd_history = $data['passwd_history'];
            $this->sessiontime = $data['sessiontime'];
        }
    }

    public function save()
    {
        $data = array(

            'password_policy_id' => $this->password_policy_id,
            'min_length' => $this->min_length,
            'min_uppercase' => $this->min_uppercase,
            'min_lowercase' => $this->min_lowercase,
            'min_numbers' => $this->min_numbers,
            'special_character' => $this->special_character,
            'passwd_exp_limit' => $this->passwd_exp_limit,
            'passwd_email_notify' => $this->passwd_email_notify,
            'failed_attempts' => $this->failed_attempts,
            'passwd_history' => $this->passwd_history,
            'sessiontime' => $this->sessiontime

        );

        if ($this->password_policy_id) {
            $result =  DB::update(self::$table, $data, 'password_policy_id');
        } else {
            $result = DB::update(self::$table, $data, false);
        }
        return $result;
    }

    public function check_passwd($user_id = NULL, $password)
    {

        if ($user_id) {
            $this->getPolicy();
            $passwd_history_limit = $this->passwd_history;
            $query = "SELECT passwd FROM password_history WHERE usr_id = $user_id ORDER BY password_history_id DESC LIMIT $passwd_history_limit";
            $result = DB::query('password_history', $query);
            $passwords = array();
            while ($row = mysqli_fetch_assoc($result)) {
                $passwords[] = $row['passwd'];
            }
            foreach ($passwords as $passwordz) {
                if ($passwordz == md5($password)) {
                    throw new CoreException(412, 'Password matched');
                }
            }
            $data = array(
                'passwd' => md5($password),
                'usr_id' => $user_id,
            );
            $result = DB::update('password_history', $data);
            return $result;
        }
    }

    public function password_exp_limit()
    {
        $this->getPolicy();
        $passwdExpLimit = $this->passwd_exp_limit;
        $passwdEmailNotify = $this->passwd_email_notify;
        $listUser = User::search();
        $notifyUser = [];
        $currentTime = time();
        foreach ($listUser as $user) {
            $pastTime = max($user['last_updated'], $user['date_created']);
            $days = floor(($currentTime - $pastTime) / (60 * 60 * 24));
            $daysRemaining = $passwdExpLimit - $days;
            $query = "UPDATE " . self::$Usertable . " SET pass_exp_in = $days WHERE usr_id = {$user['user_id']}";
            $results = DB::query(self::$Usertable, $query);
            $Tenant = new Tenant($user['tenant_id']);
            if (isset($Tenant->permissions) && in_array('password_expiry', $Tenant->permissions) && $user['is_admin'] != 1) {
                if ($passwdEmailNotify == $daysRemaining) {
                    $notifyUser[] = array(
                        'email' => $user['email'],
                        'user_id' => $user['user_id'],
                        'email_send' => $user['email_send']
                    );
                }
            }
        }
        foreach ($notifyUser as $sendUser) {
            if ($sendUser['email_send'] !== '1') {
                $this->passwd_expired($sendUser['email'], $sendUser['user_id']);
            }
        }

        return $results;
    }

    public function passwd_expired($useremail, $user_id)
    {
        $query = "UPDATE " . self::$Usertable . " SET email_send = '1' WHERE usr_id = {$user_id}";
        DB::query(self::$Usertable, $query);
        $oSession = Session::get_instance();
        $oSession->user = new User($user_id);
        $templatePath = "/usr/ictcore/core/Program/Emailtofax/data/pass_expiry.tpl.php";
        $oTemplate = Template::construct_from_file($templatePath);
        $oTemplate->save();
        $template_id = $oTemplate->template_id;
        $programData = array(
            'name' => 'sendemail',
            'template_id' => $template_id,
        );
        $oProgram = new ProgramApi();
        $program_id = $oProgram->create('sendemail', $programData);
        $contactData = array('email' => $useremail);
        $oContact = new ContactApi();
        $contact_id = $oContact->create($contactData);
        $oAccount = new Account();
        $accountData = array(
            'created_by' => $user_id
        );
        $account = $oAccount->search($accountData);
        $transmissionData = array(
            'title'        =>  'passwd_notify',
            'origin'       =>  'sendemail',
            'service_flag' =>  '8',
            'program_id'   =>  $program_id,
            'contact_id'   =>  $contact_id,
            'program_data' => array(
                'program_id'   =>  $program_id,
                'type'        => 'sendemail',
                'template_id' => $template_id
            ),
            'transmission_data' => array(
                'direction'    =>  'outbound',
                'contact_id'   =>  $contact_id,
                'account_id'   =>  $account[0]['account_id'],
            ),
        );
        $oTransmission = new TransmissionApi();
        $transmission_id = $oTransmission->create($transmissionData);
        $transmission_send = $oTransmission->send($transmission_id);
        return true;
    }
}
