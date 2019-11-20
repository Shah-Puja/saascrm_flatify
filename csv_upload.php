<?php

/* * *********************************************************************************************************************************
  MODULE FOR UPLOADING THE CSV FILE TO MEDIA FOLDER AND INSERT THE DATA IN TEMP TABLE WHICH IS JSON ENCODED

  The controller takes the data from the csv file uploaded by the user.
  It will create the info folder under media/csv/lead_import if it does not exists already.
  The csv file will be saved in that folder with the file name 'csv_import_current datetime'
  It will then present the mapping interface to the user where the csv field are taken from the csv file and the dropdown is hard coded
  which contains the data from the database.

 * ********************************************************************************************************************************** */

class Csv_upload extends MX_Controller {

    var $page_name = 'csv_upload';
    var $parent_controller = 'csv_upload';
    public $getFormFieldsLabel = array();
    public $page_h1 = "CSV Upload";

    function __construct() {
        parent::__construct();
        $this->load->module('login');
        $this->load->library('CSVReader');
        $this->load->helper('common');
        $this->load->helper('user_fields');
        $this->all_users = get_all_users();
        $this->historyComments = $this->config->item('historyCommentFormat');
        $this->getFormFieldsLabel = get_fields_label($this->page_name);
        if(!(isset($_SERVER['HTTP_EXACRM_USER_AGENT']) && $_SERVER['HTTP_EXACRM_USER_AGENT'] == 'exacrm_app')){   
            if (!$this->login->_is_logged_in()) {
                if ($this->input->is_ajax_request()) {
                    $ajaxSessionTimeout = AJAX_SESSION_CHECK;
                    echo $ajaxSessionTimeout;
                    die;
                    exit;
                }        
                $_SESSION['LastUrl'] = current_url();
                redirect('login');
            }
        }else{
            if (!$this->login->_app_is_logged_in()) {
                redirect('login/app_login');
            }
        }
    }

    function index() {
        redirect('adminx');
    }

    /**
     * company function.
     * 
     * To get the mode of csv from the module, ie. company,contact,lead or user
     * @access public
     */
    function company() {
        $data = array();
        $data['mode'] = $this->uri->segment(3);
        $data['parent_li'] = $data['mode'];
        $_SESSION['file_name'] = "";
        $this->_display('upload_form', $data);
    }

    /**
     * contact function.
     * 
     * To get the mode of csv from the module, ie. company,contact,lead or user
     * @access public
     */
    function contact() {
        $data = array();
        $data['mode'] = $this->uri->segment(3);
        $data['parent_li'] = $data['mode'];
        $_SESSION['file_name'] = "";
        $this->_display('upload_form', $data);
    }

    /**
     * lead function.
     * 
     * To get the mode of csv from the module, ie. company,contact,lead or user
     * @access public
     */
    function lead() {
        $data = array();
        $data['mode'] = $this->uri->segment(3);
        $data['parent_li'] = $data['mode'];
        $_SESSION['file_name'] = "";
        $this->_display('upload_form', $data);
    }

    /**
     * users function.
     * 
     * To get the mode of csv from the module, ie. company,contact,lead or user
     * @access public
     */
    function users() {
        $data = array();
        if (isset($_SESSION['upload_error']) && $_SESSION['upload_error'] != '') {
            $data['error'] = $_SESSION['upload_error'];
            unset($_SESSION['upload_error']);
        }
        $data['mode'] = $this->uri->segment(3);
        $data['parent_li'] = $data['mode'];
        $_SESSION['file_name'] = "";
        $this->_display('upload_form', $data);
    }

    /**
     * pipeline function.
     * 
     * To get the mode of csv from the module, ie. company,contact,lead ,user or pipeline
     * @access public
     */
    function pipeline() {
        $data = array();
        $data['mode'] = $this->uri->segment(3);
        $data['parent_li'] = $data['mode'];
        $_SESSION['file_name'] = "";
        $this->_display('upload_form', $data);
    }

    /**
     * product function.
     * 
     * To get the mode of csv from the module, ie. company,contact,lead ,user, pipeline or product
     * @access public
     */
    function product() {
        $data = array();
        $data['mode'] = $this->uri->segment(3);
        $data['parent_li'] = $data['mode'];
        $_SESSION['file_name'] = "";
        $this->_display('upload_form', $data);
    }

    /**
     * upload_file function.
     * 
     * To upload the file and redirecting it to the mapping interface
     * @access public
     */
    function upload_file() {//Upload File 
        $data = array();
        $mode = $this->input->post('mode');
        if ($mode == "") {
            redirect('adminx');
        }
        $data['mode'] = isset($mode) ? $mode : '';
        if (isset($_FILES['file']['name']) && $_FILES['file']['name'] != '') {
            $all_users = $this->all_users;
            $filename = $_FILES['file']['name'];
            $ext = substr($filename, strrpos($filename, '.') + 1);
            if ($ext == 'csv') {
                $this->load->library('File_Uploader');
                if (isset($_FILES['file']['name'])) {
                    $file_name = 'csv_import_' . date('Y-m-d_H-i-s', time());
                    $params = array(
                        "file_name" => $file_name,
                        "field_name" => "file"
                    );
                    $this->file_uploader->initialize($params);
                    if (!file_exists(ROOTBASEPATH . 'media/csv/bulk_import/')) {
                        mkdir(ROOTBASEPATH . 'media/csv/bulk_import/', 0777, true);
                    }
                    $path = ROOTBASEPATH . 'media/csv/bulk_import/';
                    $config = array(
                        "upload_path" => $path,
                        "allowed_types" => 'csv',
                        "overwrite" => TRUE
                    );
                    $this->file_uploader->upload_file($config);
                    $file_path = $path . $file_name . '.csv';
                    if (is_file($file_path) && $this->file_uploader->upload_status == 1) {
                        @chmod($file_path, 0777);
                    }
                }
                $errors = array();
                if (empty($this->bulk_csv_path)) {
                    $errors[] = 'Please Upload a CSV file only ';
                }
                if (count($errors) > 0) {
                    $this->errors = implode(' ', $errors);
                }
                $params = array(
                    'separator' => ',',
                    'enclosure' => '"',
                );
                $this->csvreader->initialize($params);
                $csv_file = $file_path;
                if (!is_file($csv_file)) {
                    return 0;
                    exit('CSV file doest not exists: ' . $csv_file);
                }
                $csvData = $this->csvreader->parse_file_new($csv_file);                    //trim the data in foreach 

                if (isset($csvData) && is_array($csvData) && count($csvData) > 0) {
                    $user = $_SESSION['user_id'];
                    $email = $_SESSION['logged_in']['email_id'];
                    $csv_queue = array(
                        'file_name' => $file_name,
                        'header' => '',
                        'created_date' => date('Y-m-d H:i:s'),
                        'created_by' => $user,
                        'is_parsed' => 0,
                        'is_sent' => 0,
                        'contact_email' => $email,
                        'status' => 0
                    );
                    $csv_queue['is_test_data'] = $_SESSION['setTestEnv'];
                    $this->db->insert('csv_queue', $csv_queue);
                    $file_id = $this->db->insert_id();
                    $i = 0;
                    foreach ($csvData as $csvdatakey => $csvdatavalue) {
                        
                        if ($csvdatakey === '') {
                            continue;
                        }

                        $json[$i] = array(
                            'data' => base64_encode(serialize(array_values($csvdatavalue))),
                            'status' => 0,
                            'created_date' => date('Y-m-d H:i:s'),
                            'created_by' => $user,
                            'file_id' => $file_id
                        );
                        $i++;
                    }

                    if (isset($json) && is_array($json) && count($json) > 0) {
                        $this->db->insert_batch('csv_temp', $json);
                        $this->mapping($csvData, $file_name, $mode);
                    } else {
                        $data['parent_li'] = $mode;
                        $data['error'] = "All rows empty. Please check csv file";
                        $_SESSION['upload_error'] = $data['error'];
                        if ($this->input->post('save_and_next') == 'save_and_next') {
                            redirect('/bulk_import/csv_upload/' . $mode . '/onboarding');
                        } else {
                            redirect('bulk_import/csv_upload/' . $mode);
                        }
                    }
                } else {
                    $data['mode'] = isset($mode) ? $mode : '';
                    $data['parent_li'] = $mode;
                    if ($mode == "company") {
                        $data['error'] = "Company name is required. Please fill all the fields.";
                    } else if ($mode == "contact") {
                        $data['error'] = "Contact's first name is required. Please fill all the fields.";
                    } else if ($mode == "lead") {
                        $data['error'] = "Lead name is required. Please fill all the fields.";
                    } else if ($mode == "users") {
                        $data['error'] = "User's first name is required. Please fill all the fields.";
                    } else if ($mode == "pipeline") {
                        $data['error'] = "Pipeline name,Associated entity and Entity type is required. Please fill all the fields.";
                    } else if ($mode == "product") {
                        $data['error'] = $this->getFormFieldsLabel['product'] . " is required. Please fill all the fields.";
                    } else {
                        $data['error'] = "No rows found. Please check csv file";
                    }
                    $_SESSION['upload_error'] = $data['error'];
                    if ($this->input->post('save_and_next') == 'save_and_next') {
                        redirect('/bulk_import/csv_upload/' . $mode . '/onboarding');
                    } else {
                        redirect('bulk_import/csv_upload/' . $mode);
                    }
                }
            } else {
                $data['mode'] = isset($mode) ? $mode : '';
                $data['parent_li'] = $mode;
                $data['error'] = "Please ensure that the file is .csv file";
                $_SESSION['upload_error'] = $data['error'];
                if ($this->input->post('save_and_next') == 'save_and_next') {
                    redirect('/bulk_import/csv_upload/' . $mode . '/onboarding');
                } else {
                    redirect('bulk_import/csv_upload/' . $mode);
                }
            }
        } else {
            $_SESSION['upload_error'] = 'Please ensure that the file is .csv file';
            if ($this->input->post('save_and_next') == 'save_and_next') {
                redirect('/bulk_import/csv_upload/' . $mode . '/onboarding');
            } else {
                redirect('bulk_import/csv_upload/' . $mode);
            }
        }
    }

    /**
     * mapping function.
     * 
     * To map the Crm fields with Csv labels
     * @access public
     */
    function mapping($csvData, $file_name, $mode) {//mapping interface
        $qid = $this->db->query("SELECT mapname FROM csv_queue");
        if ($qid->num_rows() > 0) {
            foreach ($qid->result_array() as $res) {
                $mapname[] = $res['mapname'];
            }
        }
        $mapname = array_filter($mapname);
        $data['mapname'] = $mapname;
        $params = array(
            'separator' => ',',
            'enclosure' => '"',
        );
        $this->csvreader->initialize($params);
        foreach ($csvData[0] as $nkey => $nval) {
            if ($nkey == '') {
                continue;
            }
            $field[] = $nkey;
        }
        $data['fieldcsv'] = $field;
        $getFormFieldsLabel = $this->getFormFieldsLabel;
        //Dropdown filelds hardcoded here for Mapping with Csv headers
        $field_company = array(
            'none' => 'None',
            'company_id' => 'Company Id [For companies already present in the system]',
            'company_name' => $getFormFieldsLabel['company_name'],
            'company_type' => $getFormFieldsLabel['company_type'],
            'industry' => $getFormFieldsLabel['industry'],
            'employees' => $getFormFieldsLabel['employees'],
            'annual_income' => $getFormFieldsLabel['annual_revenue'],
            'comments' => $getFormFieldsLabel['desc'],
            'source' => $getFormFieldsLabel['source'],
            'source_description' => $getFormFieldsLabel['source_desc'],
            'contact_first_name' => $getFormFieldsLabel['first_name'],
            'contact_last_name' => $getFormFieldsLabel['last_name'],
            'contact_email' => "Contact " . $getFormFieldsLabel['email'],
            'contact_phone' => "Contact " . $getFormFieldsLabel['phone'],
            'contact_type' => $getFormFieldsLabel['contact_type'],
            'contact_position' => "Contact " . $getFormFieldsLabel['position'],
            'responsible_person' => $getFormFieldsLabel['user_responsible'],
            'company_email_1' => $getFormFieldsLabel['email'] . '1',
            'company_email_type_1' => $getFormFieldsLabel['email'] . '1 Type',
            'company_phone_1' => $getFormFieldsLabel['phone'] . '1',
            'company_phone_type_1' => $getFormFieldsLabel['phone'] . '1 Type',
            'company_site_1' => $getFormFieldsLabel['web_address'] . '1',
            'company_site_type_1' => $getFormFieldsLabel['web_address'] . '1 Type',
            'company_messenger_1' => $getFormFieldsLabel['messenger'] . '1',
            'company_messenger_type_1' => $getFormFieldsLabel['messenger'] . '1 Type',
            'company_email_2' => $getFormFieldsLabel['email'] . '2',
            'company_email_type_2' => $getFormFieldsLabel['email'] . '2 Type',
            'company_phone_2' => $getFormFieldsLabel['phone'] . '2',
            'company_phone_type_2' => $getFormFieldsLabel['phone'] . '2 Type',
            'company_site_2' => $getFormFieldsLabel['web_address'] . '2',
            'company_site_type_2' => $getFormFieldsLabel['web_address'] . '2 Type',
            'company_messenger_2' => $getFormFieldsLabel['messenger'] . '2',
            'company_messenger_type_2' => $getFormFieldsLabel['messenger'] . '2 Type',
            'company_email_3' => $getFormFieldsLabel['email'] . '3',
            'company_email_type_3' => $getFormFieldsLabel['email'] . '3 Type',
            'company_phone_3' => $getFormFieldsLabel['phone'] . '3',
            'company_phone_type_3' => $getFormFieldsLabel['phone'] . '3 Type',
            'company_site_3' => $getFormFieldsLabel['web_address'] . '3',
            'company_site_type_3' => $getFormFieldsLabel['web_address'] . '3 Type',
            'company_messenger_3' => $getFormFieldsLabel['messenger'] . '3',
            'company_messenger_type_3' => $getFormFieldsLabel['messenger'] . '3 Type',
            'company_email_4' => $getFormFieldsLabel['email'] . '4',
            'company_email_type_4' => $getFormFieldsLabel['email'] . '4 Type',
            'company_phone_4' => $getFormFieldsLabel['phone'] . '4',
            'company_phone_type_4' => $getFormFieldsLabel['phone'] . '4 Type',
            'company_site_4' => $getFormFieldsLabel['web_address'] . '4',
            'company_site_type_4' => $getFormFieldsLabel['web_address'] . '4 Type',
            'company_messenger_4' => $getFormFieldsLabel['messenger'] . '4',
            'company_messenger_type_4' => $getFormFieldsLabel['messenger'] . '4 Type',
            'company_email_5' => $getFormFieldsLabel['email'] . '5',
            'company_email_type_5' => $getFormFieldsLabel['email'] . '5 Type',
            'company_phone_5' => $getFormFieldsLabel['phone'] . '5',
            'company_phone_type_5' => $getFormFieldsLabel['phone'] . '5 Type',
            'company_site_5' => $getFormFieldsLabel['web_address'] . '5',
            'company_site_type_5' => $getFormFieldsLabel['web_address'] . '5 Type',
            'company_messenger_5' => $getFormFieldsLabel['messenger'] . '5',
            'company_messenger_type_5' => $getFormFieldsLabel['messenger'] . '5 Type',
            'tag_name' => 'Tag',
            'company_notes' => 'Notes'
        );
        $field_contact = array(
            'none' => 'None',
            'contact_title' => $getFormFieldsLabel['title'],
            'contact_firstname' => $getFormFieldsLabel['first_name'],
            'contact_lastname' => $getFormFieldsLabel['last_name'],
            'contact_middlename' => $getFormFieldsLabel['middle_name'],
            'contact_position' => $getFormFieldsLabel['position'],
            'contact_type' => $getFormFieldsLabel['contact_type'],
            'responsible_person' => $getFormFieldsLabel['user_responsible'],
            'source' => $getFormFieldsLabel['source'],
            'source_description' => $getFormFieldsLabel['source_desc'],
            'description' => $getFormFieldsLabel['desc'],
            'company_name' => $getFormFieldsLabel['company_name'],
            'company_type' => $getFormFieldsLabel['company_type'],
            'industry' => $getFormFieldsLabel['industry'],
            'employees' => $getFormFieldsLabel['employees'],
            'annual_income' => $getFormFieldsLabel['annual_revenue'],
            'contact_email_1' => $getFormFieldsLabel['email'] . '1',
            'contact_email_type_1' => $getFormFieldsLabel['email'] . '1 Type',
            'contact_phone_1' => $getFormFieldsLabel['phone'] . '1',
            'contact_phone_type_1' => $getFormFieldsLabel['phone'] . '1 Type',
            'contact_site_1' => $getFormFieldsLabel['web_address'] . '1',
            'contact_site_type_1' => $getFormFieldsLabel['web_address'] . '1 Type',
            'contact_messenger_1' => $getFormFieldsLabel['messenger'] . '1',
            'contact_messenger_type_1' => $getFormFieldsLabel['messenger'] . '1 Type',
            'contact_email_2' => $getFormFieldsLabel['email'] . '2',
            'contact_email_type_2' => $getFormFieldsLabel['email'] . '2 Type',
            'contact_phone_2' => $getFormFieldsLabel['phone'] . '2',
            'contact_phone_type_2' => $getFormFieldsLabel['phone'] . '2 Type',
            'contact_site_2' => $getFormFieldsLabel['web_address'] . '2',
            'contact_site_type_2' => $getFormFieldsLabel['web_address'] . '2 Type',
            'contact_messenger_2' => $getFormFieldsLabel['messenger'] . '2',
            'contact_messenger_type_2' => $getFormFieldsLabel['messenger'] . '2 Type',
            'contact_email_3' => $getFormFieldsLabel['email'] . '3',
            'contact_email_type_3' => $getFormFieldsLabel['email'] . '3 Type',
            'contact_phone_3' => $getFormFieldsLabel['phone'] . '3',
            'contact_phone_type_3' => $getFormFieldsLabel['phone'] . '3 Type',
            'contact_site_3' => $getFormFieldsLabel['web_address'] . '3',
            'contact_site_type_3' => $getFormFieldsLabel['web_address'] . '3 Type',
            'contact_messenger_3' => $getFormFieldsLabel['messenger'] . '3',
            'contact_messenger_type_3' => $getFormFieldsLabel['messenger'] . '3 Type',
            'contact_email_4' => $getFormFieldsLabel['email'] . '4',
            'contact_email_type_4' => $getFormFieldsLabel['email'] . '4 Type',
            'contact_phone_4' => $getFormFieldsLabel['phone'] . '4',
            'contact_phone_type_4' => $getFormFieldsLabel['phone'] . '4 Type',
            'contact_site_4' => $getFormFieldsLabel['web_address'] . '4',
            'contact_site_type_4' => $getFormFieldsLabel['web_address'] . '4 Type',
            'contact_messenger_4' => $getFormFieldsLabel['messenger'] . '4',
            'contact_messenger_type_4' => $getFormFieldsLabel['messenger'] . '4 Type',
            'contact_email_5' => $getFormFieldsLabel['email'] . '5',
            'contact_email_type_5' => $getFormFieldsLabel['email'] . '5 Type',
            'contact_phone_5' => $getFormFieldsLabel['phone'] . '5',
            'contact_phone_type_5' => $getFormFieldsLabel['phone'] . '5 Type',
            'contact_site_5' => $getFormFieldsLabel['web_address'] . '5',
            'contact_site_type_5' => $getFormFieldsLabel['web_address'] . '5 Type',
            'contact_messenger_5' => $getFormFieldsLabel['messenger'] . '5',
            'contact_messenger_type_5' => $getFormFieldsLabel['messenger'] . '5 Type',
            'tag_name' => 'Tag'
        );

        $field_lead = array(
            'none' => 'None',
            'lead_name' => $getFormFieldsLabel['lead_name'],
            'status' => $getFormFieldsLabel['status'],
            'responsible_person' => $getFormFieldsLabel['user_responsible'],
            'lead_description' => $getFormFieldsLabel['desc'],
            'lead_source' => $getFormFieldsLabel['source'],
            'lead_source_description' => $getFormFieldsLabel['source_desc'],
            'lead_contact_firstname' => $getFormFieldsLabel['first_name'],
            'lead_contact_lastname' => $getFormFieldsLabel['last_name'],
            'lead_contact_middlename' => $getFormFieldsLabel['middle_name'],
            'lead_contact_position' => $getFormFieldsLabel['position'],
            'lead_email_1' => $getFormFieldsLabel['email'] . '1',
            'lead_email_type_1' => $getFormFieldsLabel['email'] . '1 Type',
            'lead_phone_1' => $getFormFieldsLabel['phone'] . '1',
            'lead_phone_type_1' => $getFormFieldsLabel['phone'] . '1 Type',
            'lead_site_1' => $getFormFieldsLabel['web_address'] . '1',
            'lead_site_type_1' => $getFormFieldsLabel['web_address'] . '1 Type',
            'lead_messenger_1' => $getFormFieldsLabel['messenger'] . '1',
            'lead_messenger_type_1' => $getFormFieldsLabel['messenger'] . '1 Type',
            'lead_email_2' => $getFormFieldsLabel['email'] . '2',
            'lead_email_type_2' => $getFormFieldsLabel['email'] . '2 Type',
            'lead_phone_2' => $getFormFieldsLabel['phone'] . '2',
            'lead_phone_type_2' => $getFormFieldsLabel['phone'] . '2 Type',
            'lead_site_2' => $getFormFieldsLabel['web_address'] . '2',
            'lead_site_type_2' => $getFormFieldsLabel['web_address'] . '2 Type',
            'lead_messenger_2' => $getFormFieldsLabel['messenger'] . '2',
            'lead_messenger_type_2' => $getFormFieldsLabel['messenger'] . '2 Type',
            'lead_email_3' => $getFormFieldsLabel['email'] . '3',
            'lead_email_type_3' => $getFormFieldsLabel['email'] . '3 Type',
            'lead_phone_3' => $getFormFieldsLabel['phone'] . '3',
            'lead_phone_type_3' => $getFormFieldsLabel['phone'] . '3 Type',
            'lead_site_3' => $getFormFieldsLabel['web_address'] . '3',
            'lead_site_type_3' => $getFormFieldsLabel['web_address'] . '3 Type',
            'lead_messenger_3' => $getFormFieldsLabel['messenger'] . '3',
            'lead_messenger_type_3' => $getFormFieldsLabel['messenger'] . '3 Type',
            'lead_email_4' => $getFormFieldsLabel['email'] . '4',
            'lead_email_type_4' => $getFormFieldsLabel['email'] . '4 Type',
            'lead_phone_4' => $getFormFieldsLabel['phone'] . '4',
            'lead_phone_type_4' => $getFormFieldsLabel['phone'] . '4 Type',
            'lead_site_4' => $getFormFieldsLabel['web_address'] . '4',
            'lead_site_type_4' => $getFormFieldsLabel['web_address'] . '4 Type',
            'lead_messenger_4' => $getFormFieldsLabel['messenger'] . '4',
            'lead_messenger_type_4' => $getFormFieldsLabel['messenger'] . '4 Type',
            'lead_email_5' => $getFormFieldsLabel['email'] . '5',
            'lead_email_type_5' => $getFormFieldsLabel['email'] . '5 Type',
            'lead_phone_5' => $getFormFieldsLabel['phone'] . '5',
            'lead_phone_type_5' => $getFormFieldsLabel['phone'] . '5 Type',
            'lead_site_5' => $getFormFieldsLabel['web_address'] . '5',
            'lead_site_type_5' => $getFormFieldsLabel['web_address'] . '5 Type',
            'lead_messenger_5' => $getFormFieldsLabel['messenger'] . '5',
            'lead_messenger_type_5' => $getFormFieldsLabel['messenger'] . '5 Type',
            'tag_name' => 'Tag'
        );

        $field_address = array(
            'is_primary_1' => 'Address - Is Primary1 (Yes/No)',
            'address_type_1' => 'Address Type1',
            'street_address_1' => 'Street Address1',
            'city_1' => 'City1',
            'state_1' => 'State1',
            'post_code_1' => 'Post Code1',
            'country_1' => 'Country1',
            'is_primary_2' => 'Address - Is Primary2 (Yes/No)',
            'address_type_2' => 'Address Type2',
            'street_address_2' => 'Street Address2',
            'city_2' => 'City2',
            'state_2' => 'State2',
            'post_code_2' => 'Post Code2',
            'country_2' => 'Country2',
            'is_primary_3' => 'Address - Is Primary3 (Yes/No)',
            'address_type_3' => 'Address Type3',
            'street_address_3' => 'Street Address3',
            'city_3' => 'City3',
            'state_3' => 'State3',
            'post_code_3' => 'Post Code3',
            'country_3' => 'Country3',
            'is_primary_4' => 'Address - Is Primary4 (Yes/No)',
            'address_type_4' => 'Address Type4',
            'street_address_4' => 'Street Address4',
            'city_4' => 'City4',
            'state_4' => 'State4',
            'post_code_4' => 'Post Code4',
            'country_4' => 'Country4',
            'is_primary_5' => 'Address - Is Primary5 (Yes/No)',
            'address_type_5' => 'Address Type5',
            'street_address_5' => 'Street Address5',
            'city_5' => 'City5',
            'state_5' => 'State5',
            'post_code_5' => 'Post Code5',
            'country_5' => 'Country5',
        );

        $field_users = array(
            'none' => 'None',
            'users_firstname' => $getFormFieldsLabel['first_name'],
            'users_lastname' => $getFormFieldsLabel['last_name'],
            'nick_name' => $getFormFieldsLabel['nick_name'],
            'user_email' => $getFormFieldsLabel['email'],
            'username' => $getFormFieldsLabel['username'],
            'password' => $getFormFieldsLabel['password'],
            'users_alternate_email' => $getFormFieldsLabel['alternate_email'],
            'skype_id' => $getFormFieldsLabel['skype_id'],
            'work_phone' => $getFormFieldsLabel['work_phone'],
            'home_phone' => $getFormFieldsLabel['home_phone'],
            'mobile_number' => $getFormFieldsLabel['mobile_number'],
            'fax' => $getFormFieldsLabel['fax'],
            'job_title' => $getFormFieldsLabel['job_title'],
            'department' => $getFormFieldsLabel['department'],
            'gender' => $getFormFieldsLabel['gender'],
            'birth_date' => $getFormFieldsLabel['birth_date'],
            'profile' => $getFormFieldsLabel['profile'],
        );
        $field_pipeline = array(
            'none' => 'None',
            'deal_name' => $getFormFieldsLabel['pipeline_name'],
            'deal_stage' => $getFormFieldsLabel['pipeline_stage'],
            'probability' => $getFormFieldsLabel['probability'],
            'income' => $getFormFieldsLabel['income'],
            'deal_type' => $getFormFieldsLabel['pipeline_type'],
            'associated_entity' => $getFormFieldsLabel['associated_entity'],
            'entity_state' => 'Entity State',
            'entity_type' => 'Entity Type',
            'deal_start_date' => $getFormFieldsLabel['pipeline_start_date'],
            'deal_complete_date' => $getFormFieldsLabel['exp_sale_date'],
            'comment' => $getFormFieldsLabel['comment'],
            'pdt_name' => $getFormFieldsLabel['product'],
            'pdt_code' => $getFormFieldsLabel['product_code'],
            'pdt_price' => $getFormFieldsLabel['unit_price'],
            'pdt_qty' => $getFormFieldsLabel['product_quantity'],
            'pdt_uom' => $getFormFieldsLabel['unit_of_measurement'],
            'tag_name' => 'Tag'
        );
        $field_product = array(
            'none' => 'None',
            'name' => $getFormFieldsLabel['name'],
            'description' => $getFormFieldsLabel['desc'],
            'category' => $getFormFieldsLabel['category'],
            'status' => $getFormFieldsLabel['status'],
            'prod_code' => $getFormFieldsLabel['product_code'],
            'price' => $getFormFieldsLabel['unit_price'],
            'unit_of_measurement' => $getFormFieldsLabel['unit_of_measurement']
        );


        if ($mode == "company") {
            $data['field_company'] = $field_company;
            $userfields_items = fetch_userfield_item_att('company'); //custom fields
        } else if ($mode == "contact") {
            $data['field_contact'] = $field_contact;
            $userfields_items = fetch_userfield_item_att('contact'); //custom fields
        } else if ($mode == "lead") {
            $data['field_lead'] = $field_lead;
            $userfields_items = fetch_userfield_item_att('lead'); //custom fields
        } else if ($mode == "users") {
            $data['field_users'] = $field_users;
            $userfields_items = fetch_userfield_item_att('users'); //custom fields
        } else if ($mode == "pipeline") {
            $data['field_pipeline'] = $field_pipeline;
            $userfields_items = fetch_userfield_item_att('pipeline'); //custom fields
        } else if ($mode == "product") {
            $data['field_product'] = $field_product;
            $userfields_items = fetch_userfield_item_att('products'); //custom fields
        }

        /* custom fields - EAV module */
        if (!empty($userfields_items)) {
            foreach ($userfields_items as $key1 => $res1) {
                if (!isset($frm_val[$res1['item_name']]) || empty($frm_val[$res1['item_name']])) {
                    $frm_val[$res1['item_name']] = '';
                }
                $data[$res1['item_name']] = $frm_val[$res1['item_name']];
                $userfields_items[$key1]['value'] = $frm_val[$res1['item_name']];
            }
        }

        $data['userfields_items'] = $userfields_items;

        /* custom fields - EAV module */
        $data['field_address'] = $mode != 'pipeline' && $mode != 'product' ? $field_address : '';
        $data['file_name'] = $file_name;
        $data['parent_li'] = $mode;
        $data['mode'] = $mode;
        $this->_display('mapping', $data);
    }

    /**
     * ins_temp function.
     * 
     * To insert the data into temporary table
     * @access public
     */
    function ins_temp() {//inserts it into temporary table 
        if ($this->input->post() && count($this->input->post()) > 0) {
            $mode = $this->input->post('mode');
            $csv_headers = $this->input->post('csv_headers');
            $mapname = $this->input->post('smap');
            $loadmap = $this->input->post('mapname');
            if ($mapname == '' && $loadmap != '') {
                $mapname = $loadmap;
            }
            unset($_POST['smap']);
            unset($_POST['csv_headers']);
            $order = array();
            $data = array();
            $file_name = $this->input->post('file_name');
            unset($_POST['file_name']);
            unset($_POST['mapname']);

            $_SESSION['file_name'] = $file_name;
            $i = 0;
            foreach ($this->input->post() as $key) {
                if ($key != "lead" && $key != "company" && $key != "contact" && $key != "users" && $key != "pipeline" && $key != "product" && $key != "none") {
                    $order[$key] = $this->input->post($key);
                }
                if ($key == "none") {
                    $order[$key . $i] = $this->input->post($key);
                    $i++;
                }
            }

            $order = base64_encode(serialize($order));
            $this->db->set('header', $order);
            $this->db->set('csv_header', $csv_headers);
            $this->db->set('mapname', $mapname);
            $this->db->set('status', 1);
            $this->db->where('file_name', $file_name);
            $this->db->update('csv_queue');
            $file_id_query = $this->db->query('SELECT * FROM csv_queue WHERE file_name="' . $file_name . '"');
            if ($file_id_query->num_rows() > 0) {
                $file_id_array = $file_id_query->row_array();
            }

            if (isset($file_id_array) && is_array($file_id_array) && count($file_id_array) > 0) {
                $data['file_id'] = $file_id_array['id'];
                $data['header'] = unserialize(base64_decode($file_id_array['header']));

                $file_temp_query = $this->db->query('SELECT * FROM csv_temp WHERE file_id="' . $file_id_array['id'] . '"');
                foreach ($file_temp_query->result_array() as $file_temp_array) {
                    $temp_array[] = unserialize(base64_decode($file_temp_array['data']));
                }

                $this->validate_csv_data($data['header'], $temp_array, $mode, $file_name);
            }
        } else {
            $this->_display('upload_form', $data);
        }
    }

    function valid_email($email) {
        return match_valid_email($email);
    }

    function unique_email($email) {
        $email_query = $this->db->query("Select email from " . TBL_USERS . " WHERE status = 1 AND email LIKE '" . $email . "'");
        if ($email_query->num_rows() > 0) {
            return false;
        } else {
            return true;
        }
    }

    function unique_username($username) {
        $username_query = $this->db->query("Select username from " . TBL_USERS . " WHERE status = 1 AND username LIKE '" . $username . "'");
        if ($username_query->num_rows() > 0) {
            return false;
        } else {
            return true;
        }
    }

    function clean_array_values($csvdatav){
        return preg_replace('/[^(\x20-\x7F)]*/', '', $csvdatav); //utf cleaning
    }
    function insert_csv_data($mode = '') {
        $created_by = "";
        $getUserQry = $this->db->query("SELECT user_permission FROM user WHERE id=" . $_SESSION['user_id'] . "");
        $getUserRes = $getUserQry->row_array();
        $this->getUserpermission = $getUserRes['user_permission'];
        if ($this->getUserpermission == "Exa Admin" OR $this->getUserpermission == "Super Admin") {
            $userQry = $this->db->query("SELECT id FROM user WHERE firstname = 'System'");
            $userQryRes = $userQry->row_array();
            $getUserID = $userQryRes['id'];
            $created_by = isset($getUserID) ? $getUserID : "";
        } else {
            $created_by = $_SESSION['user_id'];
        }
        $final_array = array();
        $email_data = array();
        $phone_data = array();
        $messenger_data = array();
        $site_data = array();
        $address_data = array();
        $db_data = array();
        if ($mode == "") {
            $mode = $this->input->post('mode') ? $this->input->post('mode') : '';
        } else {
            $mode = $mode;
        }

        $file_name = $_SESSION['file_name'];
        $file_id_query = $this->db->query('SELECT * FROM csv_queue WHERE file_name="' . $file_name . '"');
        if ($file_id_query->num_rows() > 0) {
            $file_id_array = $file_id_query->row_array();
        }

        if (isset($file_id_array) && is_array($file_id_array) && count($file_id_array) > 0) {
            $data['file_id'] = $file_id_array['id'];
            $data['header'] = unserialize(base64_decode($file_id_array['header']));

            $file_temp_query = $this->db->query('SELECT * FROM csv_temp WHERE file_id="' . $file_id_array['id'] . '"');
            foreach ($file_temp_query->result_array() as $file_temp_array) {
                $temp_array[] = unserialize(base64_decode($file_temp_array['data']));
            }
            foreach ($temp_array as $temp_val) {
                $final_array[] = @array_combine(array_keys($data['header']), array_values($temp_val));
            }
        }

        $insert_count = 0;
        if (isset($final_array) && $final_array != "") {
            foreach ($final_array as $u_key => $data) {
                $data = array_map( array($this,'clean_array_values'), $data);
                //Get ID of sales person else insert new user, if not exist
                if (isset($data['responsible_person']) && $data['responsible_person'] != '') {
                    $responsible_query = $this->db->query("SELECT id FROM " . TBL_USERS . " WHERE CONCAT(firstname,' ',lastname) LIKE '" . trim($this->db->escape_like_str($data['responsible_person'])) . "' AND status=1 AND is_epcrm_user!=1");
                    if ($responsible_query->num_rows() > 0) {
                        $responsible_person_result = $responsible_query->row_array();
                        $responsible_person = $responsible_person_result['id'];
                    }else {
                        $responsible_person = 0;
                        $sales_person_name = @explode(' ',trim($data['responsible_person']), 2);
                        $user_data = array(
                            'firstname' => $sales_person_name[0],
                            'lastname' => $sales_person_name[1],
                            'username' => strtolower($sales_person_name[0]).'.'.strtolower($sales_person_name[1]),
                            'position' => 'Sales Person',
                            'start_date' => date('Y-m-d H:i:s'),
                            'email' => '',
                            'user_permission' => "Staff",
                            'status' => 1,
                            'ip' => $this->get_real_ip_addr(),
                            'created_date' => date('Y-m-d H:i:s'),
                            'created_by' => '',
                            'allow_login' => 0
                        );
                        $this->db->insert(TBL_USERS, $user_data);
                        $responsible_person = $this->db->insert_id();
                        insert_history($responsible_person, TBL_USERS, 'id', 'new-user-inserted');
                    }
                } 

                switch ($mode) {
                    case 'company':
                        if (isset($data['company_name']) && $data['company_name'] != "" && !empty($data['company_name'])) {
                            //Insert new company
                            $primary_data = array(
                                "company_name" => $data['company_name'],
                                "responsible_person" => isset($responsible_person) ? $responsible_person : '',
                                "company_type" => isset($data['company_type']) ? $data['company_type'] : '',
                                "industry" => isset($data['industry']) ? $data['industry'] : '',
                                "employees" => isset($data['employees']) ? $data['employees'] : '',
                                "annual_income" => isset($data['annual_income']) ? $data['annual_income'] : '',
                                "comments" => isset($data['comments']) ? $data['comments'] : '',
                                "source" => isset($data['source']) ? $data['source'] : '',
                                "source_description" => isset($data['source_description']) ? $data['source_description'] : '',
                                "created_from" => "csv import",
                                'status' => 1,
                                'created_by' => $created_by,
                                'created_date' => date('Y-m-d H:i:s')
                            );
                            $primary_data['is_test_data'] = $_SESSION['setTestEnv'];
                            $this->db->insert(TBL_COMPANY, $primary_data);
                            $company_id = $this->db->insert_id();
                            $historyData = $this->historyComments['company']['1'];
                            insert_history($company_id, TBL_COMPANY, 'id', $historyData);

                            //Insert new contact
                            if ((isset($data['contact_first_name']) && $data['contact_first_name'] != '') || (isset($data['contact_last_name']) && $data['contact_last_name'] != "")) {
                                $contact_first_name = isset($data['contact_first_name']) && $data['contact_first_name'] != '' ? $data['contact_first_name'] : "";
                                $contact_last_name = isset($data['contact_last_name']) && $data['contact_last_name'] != '' ? $data['contact_last_name'] : "";
                                //Get ID of contact type else insert new, if not exists
                                if (isset($data['contact_type']) && $data['contact_type'] != "") {
                                    $contact_type_arr = $this->db->query("SELECT id FROM " . TBL_CONTACT_TYPE . " WHERE contact_type_name LIKE '" . trim($this->db->escape_like_str($data['contact_type'])) . "' and status=1 ");
                                    if ($contact_type_arr->num_rows() > 0) {
                                        $contact_type_arr_result = $contact_type_arr->row_array();
                                        $contact_type = $contact_type_arr_result['id'];
                                    } else {
                                        $contact_type = 0;
                                        $primary_contact_type_data = array(
                                            "contact_type_name" => $data['contact_type'],
                                            "status" => '1',
                                            'created_by' => $_SESSION['user_id'],
                                            'created_date' => date('Y-m-d H:i:s')
                                        );
                                        $primary_contact_type_data['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_CONTACT_TYPE, $primary_contact_type_data);
                                        $contact_type = $this->db->insert_id();
                                        $historyData = $this->historyComments['contact_type']['1'];
                                        insert_history($contact_type, TBL_CONTACT_TYPE, 'id', $historyData);
                                    }
                                }
                                $contact_data = array(
                                    'firstname' => $contact_first_name,
                                    'lastname' => $contact_last_name,
                                    'company_id' => $company_id,
                                    "responsible_person" => isset($responsible_person) ? $responsible_person : '',
                                    'position' => isset($data['contact_position']) && $data['contact_position'] != '' ? $data['contact_position'] : '',
                                    'contact_type' => isset($contact_type) && $contact_type != '' ? $contact_type : '',
                                    'created_from' => 'csv import',
                                    'status' => 1,
                                    'responsible_person' => isset($responsible_person) ? $responsible_person : '',
                                    'created_by' => $created_by,
                                    'created_date' => date('Y-m-d H:i:s'),
                                );
                                $contact_data['is_test_data'] = $_SESSION['setTestEnv'];
                                $this->db->insert(TBL_CONTACT, $contact_data);
                                $contact_id = $this->db->insert_id();
                                $historyData = $this->historyComments['contact']['1'];
                                insert_history($contact_id, TBL_CONTACT, 'id', $historyData);

                                //Insert contact's email
                                if (isset($data['contact_email']) && $data['contact_email'] != "") {
                                    $contact_email_data = array(
                                        'entity_type' => 2,
                                        'entity_status' => 1,
                                        'entity_id' => $contact_id,
                                        'number_type' => 'email',
                                        'number_category' => 0, //by default work 
                                        'number' => isset($data['contact_email']) && $data['contact_email'] != '' ? trim($data['contact_email']) : '',
                                        'status' => 1,
                                        'created_by' => $created_by,
                                        'created_date' => date('Y-m-d H:i:s')
                                    );
                                    $contact_email_data['enhanced_search_number'] = $contact_email_data['number'];
                                    $contact_email_data['is_test_data'] = $_SESSION['setTestEnv'];
                                    $this->db->insert(TBL_PHONE_NUMBERS, $contact_email_data);
                                    $con_email_id = $this->db->insert_id();
                                    $historyData = $this->historyComments['email']['1'];
                                    insert_history($con_email_id, TBL_PHONE_NUMBERS, 'id', $historyData);
                                }
                                //Insert contact's phone
                                if (isset($data['contact_phone']) && $data['contact_phone'] != "") {
                                    $contact_phone_data = array(
                                        'entity_type' => 2,
                                        'entity_status' => 1,
                                        'entity_id' => $contact_id,
                                        'number_type' => 'phone',
                                        'number_category' => 4, //by default work 
                                        'number' => isset($data['contact_phone']) && $data['contact_phone'] != '' ? trim($data['contact_phone']) : '',
                                        'status' => 1,
                                        'created_by' => $created_by,
                                        'created_date' => date('Y-m-d H:i:s')
                                    );
                                    $contact_phone_data['enhanced_search_number'] = $contact_phone_data['number'];
                                    $contact_phone_data['is_test_data'] = $_SESSION['setTestEnv'];
                                    $this->db->insert(TBL_PHONE_NUMBERS, $contact_phone_data);
                                    $con_phone_id = $this->db->insert_id();
                                    $historyData = $this->historyComments['phone']['1'];
                                    insert_history($con_phone_id, TBL_PHONE_NUMBERS, 'id', $historyData);
                                }
                            }

                            //Insert company's email
                            for ($i = 1; $i <= 5; $i++) {
                                $complete_email = (isset($data['company_email_' . $i]) && $data['company_email_' . $i] != "") ? trim($data['company_email_' . $i]) : '';
                                if ($complete_email == '') {
                                    continue;
                                } else if ($complete_email != "") {
                                    $valid_email = $this->valid_email($complete_email);
                                    if ($valid_email == false) {
                                        $complete_email = "";
                                    } else {
                                        $number_category_type = "";
                                        $number_arr = $this->config->item('number_type');
                                        $number_arr = array_map('strtolower', $number_arr['Email']);
                                        $number_category_type = isset($data['company_email_type_' . $i]) && $data['company_email_type_' . $i] != '' ? array_search(strtolower($data['company_email_type_' . $i]), $number_arr) : 0;

                                        $email_data[$i] = array(
                                            'entity_type' => 1,
                                            'entity_status' => 1,
                                            'entity_id' => $company_id,
                                            'number_type' => 'email',
                                            'number_category' => $number_category_type,
                                            'number' => isset($data['company_email_' . $i]) ? trim($data['company_email_' . $i]) : '',
                                            'status' => 1,
                                            'created_by' => $created_by,
                                            'created_date' => date('Y-m-d H:i:s')
                                        );
                                        if (isset($email_data[$i]) && $email_data[$i] != "") {
                                            $email_data[$i]['enhanced_search_number'] = $email_data[$i]['number'];
                                            $email_data[$i]['is_test_data'] = $_SESSION['setTestEnv'];
                                            $this->db->insert(TBL_PHONE_NUMBERS, $email_data[$i]);
                                            $email_id = $this->db->insert_id();
                                            $historyData = $this->historyComments['email']['1'];
                                            insert_history($email_id, TBL_PHONE_NUMBERS, 'id', $historyData);
                                        }
                                    }
                                }
                            }

                            //Insert company's phone
                            for ($i = 1; $i <= 5; $i++) {
                                $complete_number = (isset($data['company_phone_' . $i]) && $data['company_phone_' . $i] != "") ? $data['company_phone_' . $i] : '';
                                if ($complete_number == '') {
                                    continue;
                                } else if ($complete_number != '') {
                                    $number_category_type = "";
                                    $number_arr = $this->config->item('number_type');
                                    $number_arr = array_map('strtolower', $number_arr['Phone']);
                                    $number_category_type = isset($data['company_phone_type_' . $i]) && $data['company_phone_type_' . $i] != '' ? (array_search(strtolower($data['company_phone_type_' . $i]), $number_arr)) : 4;

                                    $phone_data[$i] = array(
                                        'entity_type' => 1,
                                        'entity_status' => 1,
                                        'entity_id' => $company_id,
                                        'number_type' => 'phone',
                                        'number_category' => $number_category_type,
                                        'number' => isset($data['company_phone_' . $i]) ? trim($data['company_phone_' . $i]) : '',
                                        'status' => 1,
                                        'created_by' => $created_by,
                                        'created_date' => date('Y-m-d H:i:s')
                                    );
                                    if (isset($phone_data[$i]) && $phone_data[$i] != "") {
                                        $phone_data[$i]['enhanced_search_number'] = get_enhanced_search_number($phone_data[$i]['number'], $phone_data[$i]['number_type']);
                                        $phone_data[$i]['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_PHONE_NUMBERS, $phone_data[$i]);
                                        $phone_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['phone']['1'];
                                        insert_history($phone_id, TBL_PHONE_NUMBERS, 'id', $historyData);
                                    }
                                }
                            }

                            //Insert company's messenger
                            for ($i = 1; $i <= 5; $i++) {
                                $complete_Messenger = (isset($data['company_messenger_' . $i]) && $data['company_messenger_' . $i] != "") ? $data['company_messenger_' . $i] : '';
                                if ($complete_Messenger == '') {
                                    continue;
                                } else if ($complete_Messenger != '') {
                                    $number_category_type = "";
                                    $number_arr = $this->config->item('number_type');
                                    $number_arr = array_map('strtolower', $number_arr['Messenger']);
                                    $number_category_type = isset($data['company_messenger_type_' . $i]) && $data['company_messenger_type_' . $i] != '' ? (array_search(strtolower($data['company_messenger_type_' . $i]), $number_arr)) : 4;

                                    $messenger_data[$i] = array(
                                        'entity_type' => 1,
                                        'entity_status' => 1,
                                        'entity_id' => $company_id,
                                        'number_type' => 'messenger',
                                        'number_category' => $number_category_type,
                                        'number' => isset($data['company_messenger_' . $i]) ? trim($data['company_messenger_' . $i]) : '',
                                        'status' => 1,
                                        'created_by' => $created_by,
                                        'created_date' => date('Y-m-d H:i:s')
                                    );
                                    if (isset($messenger_data[$i]) && $messenger_data[$i] != "") {
                                        $messenger_data[$i]['enhanced_search_number'] = $messenger_data[$i]['number'];
                                        $messenger_data[$i]['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_PHONE_NUMBERS, $messenger_data[$i]);
                                        $messenger_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['messenger']['1'];
                                        insert_history($messenger_id, TBL_PHONE_NUMBERS, 'id', $historyData);
                                    }
                                }
                            }

                            //Insert company's site
                            for ($i = 1; $i <= 5; $i++) {
                                $complete_site = (isset($data['company_site_' . $i]) && $data['company_site_' . $i] != "") ? $data['company_site_' . $i] : '';
                                if ($complete_site == '') {
                                    continue;
                                } else if ($complete_site != '') {
                                    $number_category_type = "";
                                    $number_arr = $this->config->item('number_type');
                                    $number_arr = array_map('strtolower', $number_arr['Site']);
                                    $number_category_type = isset($data['company_site_type_' . $i]) && $data['company_site_type_' . $i] != '' ? (array_search(strtolower($data['company_site_type_' . $i]), $number_arr)) : 5;

                                    $site_data[$i] = array(
                                        'entity_type' => 1,
                                        'entity_status' => 1,
                                        'entity_id' => $company_id,
                                        'number_type' => 'site',
                                        'number_category' => $number_category_type,
                                        'number' => isset($data['company_site_' . $i]) ? trim($data['company_site_' . $i]) : '',
                                        'status' => 1,
                                        'created_by' => $created_by,
                                        'created_date' => date('Y-m-d H:i:s')
                                    );
                                    if (isset($site_data[$i]) && $site_data[$i] != "") {
                                        $site_data[$i]['enhanced_search_number'] = $site_data[$i]['number'];
                                        $site_data[$i]['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_PHONE_NUMBERS, $site_data[$i]);
                                        $site_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['site']['1'];
                                        insert_history($site_id, TBL_PHONE_NUMBERS, 'id', $historyData);
                                    }
                                }
                            }

                            //Insert company's address
                            for ($i = 1; $i <= 5; $i++) {
                                if ((isset($data['street_address_' . $i]) && $data['street_address_' . $i] != "") || (isset($data['city_' . $i]) && $data['city_' . $i] != "") || (isset($data['state_' . $i]) && $data['state_' . $i] != "") || (isset($data['post_code_' . $i]) && $data['post_code_' . $i] != "") || (isset($data['country_' . $i]) && $data['country_' . $i] != "")) {
                                    $address = isset($data['street_address_' . $i]) ? $data['street_address_' . $i] : '';
                                    $address = preg_replace('/\s+/', ' ', $address);
                                    if (isset($data['country_' . $i]) && $data['country_' . $i] != '') {
                                        $country_name = check_country($data['country_' . $i]);
                                    }

                                    $address_data[$i] = array(
                                        'entity_id' => $company_id,
                                        'entity_type' => 1,
                                        'is_primary' => (isset($data['is_primary_' . $i]) && strtolower($data['is_primary_' . $i]) == 'yes') ? '1' : '0',
                                        'address_type' => (isset($data['address_type_' . $i]) && strtolower($data['address_type_' . $i]) == 'home') ? '0' : '1',
                                        'street_address' => $address,
                                        'city' => isset($data['city_' . $i]) ? $data['city_' . $i] : '',
                                        'state' => isset($data['state_' . $i]) ? $data['state_' . $i] : '',
                                        'post_code' => isset($data['post_code_' . $i]) ? $data['post_code_' . $i] : '',
                                        'country' => isset($country_name) && $country_name != '' ? $country_name : '',
                                        'status' => 1,
                                        'created_by' => $created_by
                                    );
                                }
                            }

                            if (count($address_data) > 0) {
                                foreach ($address_data as &$array) {
                                    $query = $this->db->get_where(TBL_ADDRESS, $array);
                                    $count = $query->num_rows();
                                    if ($count === 0) {
                                        $array['entity_status'] = 1;
                                        $array['created_date'] = date('Y-m-d H:i:s');
                                        $array['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_ADDRESS, $array);
                                        $address_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['address']['1'];
                                        insert_history($address_id, TBL_ADDRESS, 'id', $historyData);
                                    }
                                }
                            }

                            /* Associate tags for Company */
                            if (isset($data['tag_name']) && $data['tag_name'] != '') {
                                $all_tags = $data['tag_name'];
                                $all_tags = @explode(',', $all_tags);
                                foreach ($all_tags as $key => $tag_name) {
                                    $tag_query = $this->db->query("SELECT id FROM " . TBL_TAGS . " WHERE belongs_to = 'Company' AND title LIKE '" . trim($this->db->escape_like_str($tag_name)) . "' and status=1 ");
                                    if ($tag_query->num_rows() > 0) {
                                        $tag_result = $tag_query->row_array();
                                        $tag_id = $tag_result['id'];
                                        //Insert into tag map table with tag id
                                        $tag_map_data = array(
                                            'entity_id' => $company_id,
                                            'entity_type' => '1',
                                            'entity_status' => '1',
                                            'tag_id' => $tag_id,
                                            'status' => 1,
                                            'created_date' => date('Y-m-d H:i:s'),
                                            'created_by' => $created_by
                                        );
                                        $tag_map_data['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_TAG_MAP, $tag_map_data);
                                        $tag_map_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['tag_map']['1'];
                                        insert_history($tag_map_id, TBL_TAG_MAP, 'id', $historyData);
                                    } else {
                                        //Create first new tag and then map into tag map table
                                        $tag_data = array(
                                            'title' => $tag_name,
                                            'belongs_to' => 'Company',
                                            'status' => 1,
                                            'created_date' => date('Y-m-d H:i:s'),
                                            'created_by' => $created_by
                                        );
                                        $tag_data['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_TAGS, $tag_data);
                                        $new_tag_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['tag']['1'];
                                        insert_history($new_tag_id, TBL_TAGS, 'id', $historyData);

                                        //map into tag map table with the new tag id
                                        $tag_map_data = array(
                                            'entity_id' => $company_id,
                                            'entity_type' => '1',
                                            'entity_status' => '1',
                                            'tag_id' => $new_tag_id,
                                            'status' => 1,
                                            'created_date' => date('Y-m-d H:i:s'),
                                            'created_by' => $created_by
                                        );
                                        $tag_map_data['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_TAG_MAP, $tag_map_data);
                                        $tag_map_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['tag_map']['1'];
                                        insert_history($tag_map_id, TBL_TAG_MAP, 'id', $historyData);
                                    }
                                }
                            }
                            /* Associate tags for company ends here */  
                            //
                            //Update config variable
                            if ($this->config->item('onboarding_company') == '0') {
                                $config_update_data = array('config_value' => '1', 'modified_by' => $created_by, 'modified_date' => date('Y-m-d H:i:s'));
                                $this->db->where('config_key', 'onboarding_company');
                                $this->db->update(TBL_CRM_CONFIG, $config_update_data);
                            }

                            //Fetch custom fields of current module
                            $entity_id = $company_id;
                            $userfields_items_arr = fetch_userfield_item_att('company');
                            $insert_count++;
                        } else {
                            $count = $u_key + 1;
                            $data['count'] = $count;
                            $data['error'] = 'Company name is required on row ' . $count . '';  //$commaList = implode(', ', $non_inserted_data);
                            $non_inserted_data[] = $data;
                        }
                        break;
                    case 'contact':
                        if ((isset($data['contact_firstname']) && isset($data['contact_lastname'])) && (($data['contact_firstname'] == "" && $data['contact_lastname'] != "") || ($data['contact_firstname'] != "" && $data['contact_lastname'] != "") || ($data['contact_firstname'] != "" && $data['contact_lastname'] == ""))) {
                            
                            //Get ID of contact type or insert new, if not exists
                            if (isset($data['contact_type']) && $data['contact_type'] != "") {
                                $contact_type_arr = $this->db->query("SELECT id FROM " . TBL_CONTACT_TYPE . " WHERE contact_type_name LIKE '" . trim($this->db->escape_like_str($data['contact_type'])) . "' and status=1 ");
                                if ($contact_type_arr->num_rows() > 0) {
                                    $contact_type_arr_result = $contact_type_arr->row_array();
                                    $contact_type = $contact_type_arr_result['id'];
                                } else {
                                    $contact_type = 0;
                                    $primary_contact_type_data = array(
                                        "contact_type_name" => $data['contact_type'],
                                        "status" => '1',
                                        'created_by' => $_SESSION['user_id'],
                                        'created_date' => date('Y-m-d H:i:s')
                                    );
                                    $primary_contact_type_data['is_test_data'] = $_SESSION['setTestEnv'];
                                    $this->db->insert(TBL_CONTACT_TYPE, $primary_contact_type_data);
                                    $contact_type = $this->db->insert_id();
                                    $historyData = $this->historyComments['contact_type']['1'];
                                    insert_history($contact_type, TBL_CONTACT_TYPE, 'id', $historyData);
                                }
                            }
                            
                            //Insert new contact
                            $contact_fname = (isset($data['contact_firstname']) && $data['contact_firstname'] != '')?$data['contact_firstname']:'';
                            $contact_lname = (isset($data['contact_lastname']) && $data['contact_lastname'] != '')?$data['contact_lastname']:'';
                            $contact_firstname = ($contact_fname != '')?$contact_fname : $contact_lname;
                            $contact_lastname = ($contact_fname != '')?$contact_lname : '';
                            
                            $primary_data = array(
                                'title' => isset($data['contact_title']) ? $data['contact_title'] : '',
                                'firstname' => isset($contact_firstname) ? $contact_firstname : '',
                                'lastname' => isset($contact_lastname) ? $contact_lastname : '',
                                'middlename' => isset($data['contact_middlename']) ? $data['contact_middlename'] : '',
                                'position' => isset($data['contact_position']) ? $data['contact_position'] : '',
                                'responsible_person' => isset($responsible_person) ? $responsible_person : '',
                                'contact_type' => isset($contact_type) ? $contact_type : '',
                                'source' => isset($data['source']) ? $data['source'] : '',
                                'description' => isset($data['description']) ? $data['description'] : '',
                                'source_description' => isset($data['source_description']) ? $data['source_description'] : '',
                                "created_from" => "csv import",
                                //'is_public' => ($data['available'] == 'yes') ? 1 : 0,
                                'status' => 1,
                                'created_by' => $created_by,
                                'created_date' => date('Y-m-d H:i:s')
                            );
                            $primary_data['is_test_data'] = $_SESSION['setTestEnv'];
                            $this->db->insert(TBL_CONTACT, $primary_data);
                            $contact_id = $this->db->insert_id();
                            $historyData = $this->historyComments['contact']['1'];
                            insert_history($contact_id, TBL_CONTACT, 'id', $historyData);

                            //Check company already present else Insert new company if not present
                            if (isset($data['company_name']) && $data['company_name'] != '') {
                                $contact_company = array();
                                $company_data = array();
                                $company_query = $this->db->query("SELECT id FROM " . TBL_COMPANY . " WHERE company_name LIKE '" . trim($this->db->escape_like_str($data['company_name'])) . "' and status=1 ");
                                if ($company_query->num_rows() > 0) {
                                    $company_result = $company_query->row_array();
                                    $company_id = $company_result['id'];
                                } else {
                                    $company_data = array(
                                        'company_name' => $data['company_name'],
                                        'company_type' => isset($data['company_type']) && $data['company_type'] != '' ? $data['company_type'] : '',
                                        'industry' => isset($data['industry']) && $data['industry'] != '' ? $data['industry'] : '',
                                        'employees' => isset($data['employees']) && $data['employees'] != '' ? $data['employees'] : '',
                                        'annual_income' => isset($data['annual_income']) && $data['annual_income'] != '' ? $data['annual_income'] : '',
                                        'status' => 1,
                                        'responsible_person' => isset($responsible_person) ? $responsible_person : '',
                                        'created_from' => 'csv import',
                                        'created_by' => $created_by,
                                        'created_date' => date('Y-m-d H:i:s')
                                    );
                                    $company_data['is_test_data'] = $_SESSION['setTestEnv'];
                                    $this->db->insert(TBL_COMPANY, $company_data);
                                    $company_id = $this->db->insert_id();
                                    $historyData = $this->historyComments['company']['1'];
                                    insert_history($company_id, TBL_COMPANY, 'id', $historyData);
                                }
                                //Map contact with this company
                                $contact_company = array('company_id' => isset($company_id) && $company_id != '' && $company_id != '0' ? $company_id : '0');
                                $this->db->where('id', $contact_id);
                                $this->db->update(TBL_CONTACT, $contact_company);
                                $historyData = $this->historyComments['contact']['3'];
                                insert_history($contact_id, TBL_CONTACT, 'id', $historyData);
                            }

                            //Insert email
                            for ($i = 1; $i <= 5; $i++) {
                                $complete_email = (isset($data['contact_email_' . $i]) && $data['contact_email_' . $i] != "") ? trim($data['contact_email_' . $i]) : '';
                                if ($complete_email == "") {
                                    continue;
                                } else if ($complete_email != '') {
                                    $valid_email = $this->valid_email($complete_email);
                                    if ($valid_email == false) {
                                        $complete_email = "";
                                    } else {
                                        $number_category_type = "";
                                        $number_arr = $this->config->item('number_type');
                                        $number_arr = array_map('strtolower', $number_arr['Email']);
                                        $number_category_type = isset($data['contact_email_type_' . $i]) && $data['contact_email_type_' . $i] != '' ? array_search(strtolower($data['contact_email_type_' . $i]), $number_arr) : 0;

                                        $email_data[$i] = array(
                                            'entity_type' => 2,
                                            'entity_status' => 1,
                                            'entity_id' => $contact_id,
                                            'number_type' => 'email',
                                            'number_category' => $number_category_type,
                                            'number' => isset($data['contact_email_' . $i]) ? trim($data['contact_email_' . $i]) : '',
                                            'status' => 1,
                                            'created_by' => $created_by,
                                            'created_date' => date('Y-m-d H:i:s')
                                        );
                                        if (isset($email_data[$i]) && $email_data[$i] != "") {
                                            $email_data[$i]['enhanced_search_number'] = $email_data[$i]['number'];
                                            $email_data[$i]['is_test_data'] = $_SESSION['setTestEnv'];
                                            $this->db->insert(TBL_PHONE_NUMBERS, $email_data[$i]);
                                            $email_id = $this->db->insert_id();
                                            $historyData = $this->historyComments['email']['1'];
                                            insert_history($email_id, TBL_PHONE_NUMBERS, 'id', $historyData);
                                        }
                                    }
                                }
                            }

                            //Insert phone
                            for ($i = 1; $i <= 5; $i++) {
                                $complete_number = (isset($data['contact_phone_' . $i]) && $data['contact_phone_' . $i] != "") ? $data['contact_phone_' . $i] : '';
                                if ($complete_number == "") {
                                    continue;
                                } else if ($complete_number != '') {
                                    $number_category_type = "";
                                    $number_arr = $this->config->item('number_type');
                                    $number_arr = array_map('strtolower', $number_arr['Phone']);
                                    $number_category_type = isset($data['contact_phone_type_' . $i]) && $data['contact_phone_type_' . $i] != '' ? array_search(strtolower($data['contact_phone_type_' . $i]), $number_arr) : 4;

                                    $phone_data[$i] = array(
                                        'entity_type' => 2,
                                        'entity_status' => 1,
                                        'entity_id' => $contact_id,
                                        'number_type' => 'phone',
                                        'number_category' => $number_category_type,
                                        'number' => isset($data['contact_phone_' . $i]) ? trim($data['contact_phone_' . $i]) : '',
                                        'status' => 1,
                                        'created_by' => $created_by,
                                        'created_date' => date('Y-m-d H:i:s')
                                    );
                                    if (isset($phone_data[$i]) && $phone_data[$i] != "") {
                                        $phone_data[$i]['enhanced_search_number'] = get_enhanced_search_number($phone_data[$i]['number'], $phone_data[$i]['number_type']);
                                        $phone_data[$i]['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_PHONE_NUMBERS, $phone_data[$i]);
                                        $phone_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['phone']['1'];
                                        insert_history($phone_id, TBL_PHONE_NUMBERS, 'id', $historyData);
                                    }
                                }
                            }

                            //Insert messenger
                            for ($i = 1; $i <= 5; $i++) {
                                $complete_Messenger = (isset($data['contact_messenger_' . $i]) && $data['contact_messenger_' . $i] != "") ? $data['contact_messenger_' . $i] : '';
                                if ($complete_Messenger == "") {
                                    continue;
                                } else if ($complete_Messenger != '') {
                                    $number_category_type = "";
                                    $number_arr = $this->config->item('number_type');
                                    $number_arr = array_map('strtolower', $number_arr['Messenger']);
                                    $number_category_type = isset($data['contact_messenger_type_' . $i]) && $data['contact_messenger_type_' . $i] != '' ? array_search(strtolower($data['contact_messenger_type_' . $i]), $number_arr) : 4;

                                    $messenger_data[$i] = array(
                                        'entity_type' => 2,
                                        'entity_status' => 1,
                                        'entity_id' => $contact_id,
                                        'number_type' => 'messenger',
                                        'number_category' => $number_category_type,
                                        'number' => isset($data['contact_messenger_' . $i]) ? trim($data['contact_messenger_' . $i]) : '',
                                        'status' => 1,
                                        'created_by' => $created_by,
                                        'created_date' => date('Y-m-d H:i:s')
                                    );
                                    if (isset($messenger_data[$i]) && $messenger_data[$i] != "") {
                                        $messenger_data[$i]['enhanced_search_number'] = $messenger_data[$i]['number'];
                                        $messenger_data[$i]['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_PHONE_NUMBERS, $messenger_data[$i]);
                                        $messenger_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['messenger']['1'];
                                        insert_history($messenger_id, TBL_PHONE_NUMBERS, 'id', $historyData);
                                    }
                                }
                            }

                            //Insert site
                            for ($i = 1; $i <= 5; $i++) {
                                $complete_site = (isset($data['contact_site_' . $i]) && $data['contact_site_' . $i] != "") ? $data['contact_site_' . $i] : '';
                                if ($complete_site == "") {
                                    continue;
                                } else if ($complete_site != '') {
                                    $number_category_type = "";
                                    $number_arr = $this->config->item('number_type');
                                    $number_arr = array_map('strtolower', $number_arr['Site']);
                                    $number_category_type = isset($data['contact_site_type_' . $i]) && $data['contact_site_type_' . $i] != '' ? array_search(strtolower($data['contact_site_type_' . $i]), $number_arr) : 5;

                                    $site_data[$i] = array(
                                        'entity_type' => 2,
                                        'entity_status' => 1,
                                        'entity_id' => $contact_id,
                                        'number_type' => 'site',
                                        'number_category' => $number_category_type,
                                        'number' => isset($data['contact_site_' . $i]) ? trim($data['contact_site_' . $i]) : '',
                                        'status' => 1,
                                        'created_by' => $created_by,
                                        'created_date' => date('Y-m-d H:i:s')
                                    );
                                    if (isset($site_data[$i]) && $site_data[$i] != "") {
                                        $site_data[$i]['enhanced_search_number'] = $site_data[$i]['number'];
                                        $site_data[$i]['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_PHONE_NUMBERS, $site_data[$i]);
                                        $site_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['site']['1'];
                                        insert_history($site_id, TBL_PHONE_NUMBERS, 'id', $historyData);
                                    }
                                }
                            }

                            //Insert address
                            for ($i = 1; $i <= 5; $i++) {
                                $country_name = '';
                                if ((isset($data['street_address_' . $i]) && $data['street_address_' . $i] != "") || (isset($data['city_' . $i]) && $data['city_' . $i] != "") || (isset($data['state_' . $i]) && $data['state_' . $i] != "") || (isset($data['post_code_' . $i]) && $data['post_code_' . $i] != "") || (isset($data['country_' . $i]) && $data['country_' . $i] != "")) {
                                    if ($data['country_' . $i] != '') {
                                        $country_name = check_country($data['country_' . $i]);
                                    }
                                    $address_data[$i] = array(
                                        'entity_id' => $contact_id,
                                        'entity_type' => 2,
                                        'is_primary' => (isset($data['is_primary_' . $i]) && strtolower($data['is_primary_' . $i]) == 'yes') ? '1' : '0',
                                        'address_type' => (isset($data['address_type_' . $i]) && strtolower($data['address_type_' . $i]) == 'home') ? '0' : '1',
                                        'street_address' => isset($data['street_address_' . $i]) ? $data['street_address_' . $i] : '',
                                        'city' => isset($data['city_' . $i]) ? $data['city_' . $i] : '',
                                        'state' => isset($data['state_' . $i]) ? $data['state_' . $i] : '',
                                        'post_code' => isset($data['post_code_' . $i]) ? $data['post_code_' . $i] : '',
                                        'country' => isset($country_name) && $country_name != '' ? $country_name : '',
                                        'status' => 1,
                                        'created_by' => $created_by
                                    );
                                }
                            }

                            if (count($address_data) > 0) {
                                foreach ($address_data as &$array) {
                                    $query = $this->db->get_where(TBL_ADDRESS, $array);
                                    $count = $query->num_rows();
                                    if ($count === 0) {
                                        $array['entity_status'] = 1;
                                        $array['created_date'] = date('Y-m-d H:i:s');
                                        $array['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_ADDRESS, $array);
                                        $address_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['address']['1'];
                                        insert_history($address_id, TBL_ADDRESS, 'id', $historyData);
                                    }
                                    
                                }
                            }
                            
                            /* Associate tags for Contact */
                            if (isset($data['tag_name']) && $data['tag_name'] != '') {
                                $all_tags = $data['tag_name'];
                                $all_tags = @explode(',', $all_tags);
                                foreach ($all_tags as $key => $tag_name) {
                                    $tag_query = $this->db->query("SELECT id FROM " . TBL_TAGS . " WHERE belongs_to = 'Contact' AND title LIKE '" . trim($this->db->escape_like_str($tag_name)) . "' and status=1 ");
                                    if ($tag_query->num_rows() > 0) {
                                        $tag_result = $tag_query->row_array();
                                        $tag_id = $tag_result['id'];
                                        //Insert into tag map table with tag id
                                        $tag_map_data = array(
                                            'entity_id' => $contact_id,
                                            'entity_type' => '2',
                                            'entity_status' => '1',
                                            'tag_id' => $tag_id,
                                            'status' => 1,
                                            'created_date' => date('Y-m-d H:i:s'),
                                            'created_by' => $created_by
                                        );
                                        $tag_map_data['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_TAG_MAP, $tag_map_data);
                                        $tag_map_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['tag_map']['1'];
                                        insert_history($tag_map_id, TBL_TAG_MAP, 'id', $historyData);
                                    } else {
                                        //Create first new tag and then map into tag map table
                                        $tag_data = array(
                                            'title' => $tag_name,
                                            'belongs_to' => 'Contact',
                                            'status' => 1,
                                            'created_date' => date('Y-m-d H:i:s'),
                                            'created_by' => $created_by
                                        );
                                        $tag_data['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_TAGS, $tag_data);
                                        $new_tag_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['tag']['1'];
                                        insert_history($new_tag_id, TBL_TAGS, 'id', $historyData);

                                        //map into tag map table with the new tag id
                                        $tag_map_data = array(
                                            'entity_id' => $contact_id,
                                            'entity_type' => '2',
                                            'entity_status' => '1',
                                            'tag_id' => $new_tag_id,
                                            'status' => 1,
                                            'created_date' => date('Y-m-d H:i:s'),
                                            'created_by' => $created_by
                                        );
                                        $tag_map_data['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_TAG_MAP, $tag_map_data);
                                        $tag_map_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['tag_map']['1'];
                                        insert_history($tag_map_id, TBL_TAG_MAP, 'id', $historyData);
                                    }
                                }
                            }
                            /* Associate tags for contact ends here */

                            //Update config variable
                            if ($this->config->item('onboarding_contact') == '0') {
                                $config_update_data = array('config_value' => '1', 'modified_by' => $created_by, 'modified_date' => date('Y-m-d H:i:s'));
                                $this->db->where('config_key', 'onboarding_contact');
                                $this->db->update(TBL_CRM_CONFIG, $config_update_data);
                            }

                            //Get custom fields of current module
                            $entity_id = $contact_id;
                            $userfields_items_arr = fetch_userfield_item_att('contact');
                            $insert_count++;
                            
                        } else if (isset($data['contact_firstname']) && ($data['contact_firstname'] == "" || $data['contact_firstname'] == false)) {
                            $count = $u_key + 1;
                            $data['count'] = $count;
                            $data['error'] = 'Contact First name is required on row ' . $count . '';
                            $non_inserted_data[] = $data;
                        }
                        break;
                    case 'lead':
                        if (isset($data['lead_name']) && !empty($data['lead_name'])) {
                            if (isset($data['status']) && $data['status'] != '') {
                                $lead_config_status = $this->config->item('lead_status');
                                foreach ($lead_config_status as $nkey => $nval) {
                                    if (trim(strtolower($data['status'])) == trim(strtolower($nval))) {
                                        $lead_status = $nkey;
                                    }
                                }
                            }

                            $lead_data = array(
                                'lead_name' => isset($data['lead_name']) ? $data['lead_name'] : '',
                                'lead_status' => isset($lead_status) ? $lead_status : 0,
                                'description' => isset($data['lead_description']) ? $data['lead_description'] : '',
                                'currency' => isset($data['currency']) ? $data['currency'] : '',
                                'source' => isset($data['lead_source']) ? $data['lead_source'] : '',
                                'source_description' => isset($data['lead_source_description']) ? $data['lead_source_description'] : '',
                                "created_from" => "csv import",
                                'responsible_person' => isset($responsible_person) ? $responsible_person : '',
                                'lastname' => isset($data['lead_contact_lastname']) ? $data['lead_contact_lastname'] : '',
                                'firstname' => isset($data['lead_contact_firstname']) ? $data['lead_contact_firstname'] : '',
                                'middlename' => isset($data['lead_contact_middlename']) ? $data['lead_contact_middlename'] : '',
                                'position' => isset($data['lead_contact_position']) ? $data['lead_contact_position'] : '',
                                'created_by' => $created_by,
                                'created_date' => isset($data['created_date']) && $data['created_date'] != '' ? date(DATE_FORMAT_BACKEND, strtotime($data['created_date'])) : date('Y-m-d H:i:s'),
                                'status' => 1
                            );
                            $lead_data['is_test_data'] = $_SESSION['setTestEnv'];
                            $this->db->insert(TBL_LEAD, $lead_data);
                            $lead_id = $this->db->insert_id();
                            $historyData = $this->historyComments['lead']['1'];
                            insert_history($lead_id, TBL_LEAD, 'id', $historyData);

                            for ($i = 1; $i <= 5; $i++) {
                                $complete_email = (isset($data['lead_email_' . $i]) && $data['lead_email_' . $i] != "") ? trim($data['lead_email_' . $i]) : '';
                                if ($complete_email == "") {
                                    continue;
                                } else if ($complete_email != '') {
                                    $valid_email = $this->valid_email($complete_email);
                                    if ($valid_email == false) {
                                        $complete_email = "";
                                    } else {
                                        $number_category_type = "";
                                        $number_arr = $this->config->item('number_type');
                                        $number_arr = array_map('strtolower', $number_arr['Email']);
                                        $number_category_type = isset($data['lead_email_type_' . $i]) && $data['lead_email_type_' . $i] != '' ? array_search(strtolower($data['lead_email_type_' . $i]), $number_arr) : 0;

                                        $email_data[$i] = array(
                                            'entity_type' => 3,
                                            'entity_status' => 1,
                                            'entity_id' => $lead_id,
                                            'number_type' => 'email',
                                            'number_category' => $number_category_type,
                                            'number' => isset($data['lead_email_' . $i]) ? trim($data['lead_email_' . $i]) : '',
                                            'status' => 1,
                                            'created_by' => $created_by,
                                            'created_date' => isset($data['created_date']) && $data['created_date'] != '' ? date(DATE_FORMAT_BACKEND, strtotime($data['created_date'])) : date('Y-m-d H:i:s')
                                        );
                                        if (isset($email_data[$i]) && $email_data[$i] != "") {
                                            $email_data[$i]['enhanced_search_number'] = $email_data[$i]['number'];
                                            $email_data[$i]['is_test_data'] = $_SESSION['setTestEnv'];
                                            $this->db->insert(TBL_PHONE_NUMBERS, $email_data[$i]);
                                            $email_id = $this->db->insert_id();
                                            $historyData = $this->historyComments['email']['1'];
                                            insert_history($email_id, TBL_PHONE_NUMBERS, 'id', $historyData);
                                        }
                                    }
                                }
                            }

                            for ($i = 1; $i <= 5; $i++) {
                                $complete_number = (isset($data['lead_phone_' . $i]) && $data['lead_phone_' . $i] != "") ? $data['lead_phone_' . $i] : '';
                                if ($complete_number == "") {
                                    continue;
                                } else if ($complete_number != '') {
                                    $number_category_type = "";
                                    $number_arr = $this->config->item('number_type');
                                    $number_arr = array_map('strtolower', $number_arr['Phone']);
                                    $number_category_type = isset($data['lead_phone_type_' . $i]) && $data['lead_phone_type_' . $i] != '' ? array_search(strtolower($data['lead_phone_type_' . $i]), $number_arr) : 4;

                                    $phone_data[$i] = array(
                                        'entity_type' => 3,
                                        'entity_status' => 1,
                                        'entity_id' => $lead_id,
                                        'number_type' => 'phone',
                                        'number_category' => $number_category_type,
                                        'number' => isset($data['lead_phone_' . $i]) ? trim($data['lead_phone_' . $i]) : '',
                                        'status' => 1,
                                        'created_by' => $created_by,
                                        'created_date' => isset($data['created_date']) && $data['created_date'] != '' ? date(DATE_FORMAT_BACKEND, strtotime($data['created_date'])) : date('Y-m-d H:i:s')
                                    );
                                    if (isset($phone_data[$i]) && $phone_data[$i] != "") {
                                        $phone_data[$i]['enhanced_search_number'] = get_enhanced_search_number($phone_data[$i]['number'], $phone_data[$i]['number_type']);
                                        $phone_data[$i]['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_PHONE_NUMBERS, $phone_data[$i]);
                                        $phone_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['phone']['1'];
                                        insert_history($phone_id, TBL_PHONE_NUMBERS, 'id', $historyData);
                                    }
                                }
                            }

                            for ($i = 1; $i <= 5; $i++) {
                                $complete_Messenger = (isset($data['lead_messenger_' . $i]) && $data['lead_messenger_' . $i] != "") ? $data['lead_messenger_' . $i] : '';
                                if ($complete_Messenger == "") {
                                    continue;
                                } else if ($complete_Messenger != '') {
                                    $number_category_type = "";
                                    $number_arr = $this->config->item('number_type');
                                    $number_arr = array_map('strtolower', $number_arr['Messenger']);
                                    $number_category_type = isset($data['lead_messenger_type_' . $i]) && $data['lead_messenger_type_' . $i] != '' ? array_search(strtolower($data['lead_messenger_type_' . $i]), $number_arr) : 4;

                                    $messenger_data[$i] = array(
                                        'entity_type' => 3,
                                        'entity_status' => 1,
                                        'entity_id' => $lead_id,
                                        'number_type' => 'messenger',
                                        'number_category' => $number_category_type,
                                        'number' => isset($data['lead_messenger_' . $i]) ? trim($data['lead_messenger_' . $i]) : '',
                                        'status' => 1,
                                        'created_by' => $created_by,
                                        'created_date' => isset($data['created_date']) && $data['created_date'] != '' ? date(DATE_FORMAT_BACKEND, strtotime($data['created_date'])) : date('Y-m-d H:i:s')
                                    );
                                    if (isset($messenger_data[$i]) && $messenger_data[$i] != "") {
                                        $messenger_data[$i]['enhanced_search_number'] = $messenger_data[$i]['number'];
                                        $messenger_data[$i]['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_PHONE_NUMBERS, $messenger_data[$i]);
                                        $messenger_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['messenger']['1'];
                                        insert_history($messenger_id, TBL_PHONE_NUMBERS, 'id', $historyData);
                                    }
                                }
                            }


                            for ($i = 1; $i <= 5; $i++) {
                                $complete_site = (isset($data['lead_site_' . $i]) && $data['lead_site_' . $i] != "") ? $data['lead_site_' . $i] : '';
                                if ($complete_site == "") {
                                    continue;
                                } else if ($complete_site != '') {
                                    $number_category_type = "";
                                    $number_arr = $this->config->item('number_type');
                                    $number_arr = array_map('strtolower', $number_arr['Site']);
                                    $number_category_type = isset($data['lead_site_type_' . $i]) && $data['lead_site_type_' . $i] != '' ? array_search(strtolower($data['lead_site_type_' . $i]), $number_arr) : 5;

                                    $site_data[$i] = array(
                                        'entity_type' => 3,
                                        'entity_status' => 1,
                                        'entity_id' => $lead_id,
                                        'number_type' => 'site',
                                        'number_category' => $number_category_type,
                                        'number' => isset($data['lead_site_' . $i]) ? trim($data['lead_site_' . $i]) : '',
                                        'status' => 1,
                                        'created_by' => $created_by,
                                        'created_date' => isset($data['created_date']) && $data['created_date'] != '' ? date(DATE_FORMAT_BACKEND, strtotime($data['created_date'])) : date('Y-m-d H:i:s')
                                    );
                                    if (isset($site_data[$i]) && $site_data[$i] != "") {
                                        $site_data[$i]['enhanced_search_number'] = $site_data[$i]['number'];
                                        $site_data[$i]['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_PHONE_NUMBERS, $site_data[$i]);
                                        $site_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['site']['1'];
                                        insert_history($site_id, TBL_PHONE_NUMBERS, 'id', $historyData);
                                    }
                                }
                            }

                            for ($i = 1; $i <= 5; $i++) {
                                $country_name = '';
                                if ((isset($data['street_address_' . $i]) && $data['street_address_' . $i] != "") || (isset($data['city_' . $i]) && $data['city_' . $i] != "") || (isset($data['state_' . $i]) && $data['state_' . $i] != "") || (isset($data['post_code_' . $i]) && $data['post_code_' . $i] != "") || (isset($data['country_' . $i]) && $data['country_' . $i] != "")) {
                                    if ($data['country_' . $i] != '') {
                                        $country_name = check_country($data['country_' . $i]);
                                    }

                                    $address_data[$i] = array(
                                        'entity_id' => $lead_id,
                                        'entity_type' => 3,
                                        'is_primary' => (isset($data['is_primary_' . $i]) && strtolower($data['is_primary_' . $i]) == 'yes') ? '1' : '0',
                                        'address_type' => (isset($data['address_type_' . $i]) && strtolower($data['address_type_' . $i]) == 'home') ? '0' : '1',
                                        'street_address' => isset($data['street_address_' . $i]) ? $data['street_address_' . $i] : '',
                                        'city' => isset($data['city_' . $i]) ? $data['city_' . $i] : '',
                                        'state' => isset($data['state_' . $i]) ? $data['state_' . $i] : '',
                                        'post_code' => isset($data['post_code_' . $i]) ? $data['post_code_' . $i] : '',
                                        'country' => isset($country_name) && $country_name != '' ? $country_name : '',
                                        'status' => 1,
                                        'created_by' => $created_by
                                    );
                                }
                            }

                            if (count($address_data) > 0) {
                                foreach ($address_data as &$array) {
                                    $query = $this->db->get_where(TBL_ADDRESS, $array);
                                    $count = $query->num_rows();
                                    if ($count === 0) {
                                        $array['entity_status'] = 1;
                                        $array['created_date'] = date('Y-m-d H:i:s');
                                        $array['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_ADDRESS, $array);
                                        $address_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['address']['1'];
                                        insert_history($address_id, TBL_ADDRESS, 'id', $historyData);
                                    }
                                }
                            }

                            /* Associate tags for Lead */
                            if (isset($data['tag_name']) && $data['tag_name'] != '') {
                                $all_tags = $data['tag_name'];
                                $all_tags = @explode(',', $all_tags);
                                foreach ($all_tags as $key => $tag_name) {
                                    $tag_query = $this->db->query("SELECT id FROM " . TBL_TAGS . " WHERE belongs_to = 'Lead' AND title LIKE '" . trim($this->db->escape_like_str($tag_name)) . "' and status=1 ");
                                    if ($tag_query->num_rows() > 0) {
                                        $tag_result = $tag_query->row_array();
                                        $tag_id = $tag_result['id'];
                                        //Insert into tag map table with tag id
                                        $tag_map_data = array(
                                            'entity_id' => $lead_id,
                                            'entity_type' => '3',
                                            'entity_status' => '1',
                                            'tag_id' => $tag_id,
                                            'status' => 1,
                                            'created_date' => date('Y-m-d H:i:s'),
                                            'created_by' => $created_by
                                        );
                                        $tag_map_data['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_TAG_MAP, $tag_map_data);
                                        $tag_map_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['tag_map']['1'];
                                        insert_history($tag_map_id, TBL_TAG_MAP, 'id', $historyData);
                                    } else {
                                        //Create first new tag and then map into tag map table
                                        $tag_data = array(
                                            'title' => $tag_name,
                                            'belongs_to' => 'Lead',
                                            'status' => 1,
                                            'created_date' => date('Y-m-d H:i:s'),
                                            'created_by' => $created_by
                                        );
                                        $tag_data['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_TAGS, $tag_data);
                                        $new_tag_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['tag']['1'];
                                        insert_history($new_tag_id, TBL_TAGS, 'id', $historyData);

                                        //map into tag map table with the new tag id
                                        $tag_map_data = array(
                                            'entity_id' => $lead_id,
                                            'entity_type' => '3',
                                            'entity_status' => '1',
                                            'tag_id' => $new_tag_id,
                                            'status' => 1,
                                            'created_date' => date('Y-m-d H:i:s'),
                                            'created_by' => $created_by
                                        );
                                        $tag_map_data['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_TAG_MAP, $tag_map_data);
                                        $tag_map_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['tag_map']['1'];
                                        insert_history($tag_map_id, TBL_TAG_MAP, 'id', $historyData);
                                    }
                                }
                            }
                            /* Associate tags for lead ends here */

                            $entity_id = $lead_id;
                            $userfields_items_arr = fetch_userfield_item_att('lead');
                            $insert_count++;
                        } else {
                            $count = $u_key + 1;
                            $data['count'] = $count;
                            $data['error'] = 'Lead name is required on row ' . $count . '';
                            $non_inserted_data[] = $data;
                        }
                        break;
                    case 'users':
                        $user_email = '';

                        if ((isset($data['users_firstname']) && $data['users_firstname'] != "" ) && (isset($data['users_lastname']) && $data['users_lastname'] != "" ) && (isset($data['user_email']) && $data['user_email'] != "") && (isset($data['profile']) && $data['profile'] != "")) {

                            $data['error'] = "";

                            if (isset($data['username']) && $data['username'] != "") {
                                $unique_username = $this->unique_username(trim($data['username']));
                                if ($unique_username == false) {
                                    $count = $u_key + 1;
                                    $data['error'].=(($data['error'] != "") ? ", " : "") . 'Username is not unique on row ' . $count . '';
                                    $data['count'] = $count;
                                    $non_inserted_data[] = $data;
                                    continue;
                                }
                            }
                            if (isset($data['user_email']) && $data['user_email'] != "") {
                                $unique_email = $this->unique_email(trim($data['user_email']));
                                $valid_email = $this->valid_email(trim($data['user_email']));
                                if ($valid_email == false) {
                                    $count = $u_key + 1;
                                    $data['error'].=(($data['error'] != "") ? ", " : "") . 'User Email is not valid on row ' . $count . '';
                                    $data['count'] = $count;
                                    $non_inserted_data[] = $data;
                                    continue;
                                } else if ($unique_email == false) {
                                    $count = $u_key + 1;
                                    $data['error'].=(($data['error'] != "") ? ", " : "") . 'User Email is not unique on row ' . $count . '';
                                    $data['count'] = $count;
                                    $non_inserted_data[] = $data;
                                    continue;
                                } else {
                                    $user_email = trim($data['user_email']);
                                }
                            }


                            $departments = '';
                            if (isset($data['department']) && $data['department'] != '') {
                                $department_arr = $this->get_active_departments();
                                foreach ($department_arr as $nkey => $nval) {
                                    if (strtolower($data['department']) == strtolower($nval['department_name'])) {
                                        $departments = $nval['id'];
                                    }
                                }
                            }

                            $gender_arr = isset($data['gender']) ? $data['gender'] : '';
                            switch (strtolower($gender_arr)) {
                                case 'male':
                                    $gender = 2;
                                    break;

                                case 'female':
                                    $gender = 1;
                                    break;

                                default:
                                    $gender = '';
                                    break;
                            }

                            $user_data = array(
                                'firstname' => isset($data['users_firstname']) ? $data['users_firstname'] : "",
                                'lastname' => isset($data['users_lastname']) ? $data['users_lastname'] : "",
                                'nick_name' => (isset($data['nick_name']) && $data['nick_name'] != "") ? $data['nick_name'] : "",
                                'email' => isset($user_email) ? $user_email : "",
                                'username' => (isset($data['username']) && $data['username'] != "") ? trim($data['username']) : $user_email,
                                'password' => isset($data['password']) ? md5($data['password']) : "",
                                'start_date' => date('Y-m-d H:i:s'),
                                'alternate_email' => isset($data['users_alternate_email']) ? trim($data['users_alternate_email']) : "",
                                'skype_id' => isset($data['skype_id']) ? $data['skype_id'] : "",
                                'workphone' => isset($data['work_phone']) ? $data['work_phone'] : "",
                                'homephone' => isset($data['home_phone']) ? $data['home_phone'] : "",
                                'mobile' => isset($data['mobile_number']) ? $data['mobile_number'] : "",
                                'fax' => isset($data['fax']) ? $data['fax'] : "",
                                'position' => isset($data['job_title']) ? $data['job_title'] : "Sales Person",
                                'birth_date' => (isset($data['birth_date']) && $data['birth_date'] != "" ) ? date(DATE_FORMAT_BACKEND, strtotime($data['birth_date'])) : "",
                                'status' => 1,
                                'ip' => $this->get_real_ip_addr(),
                                'created_date' => date('Y-m-d H:i:s'),
                                'created_by' => $created_by,
                                'created_from' => 'csv import',
                                'department_id' => isset($departments) && $departments != '' ? $departments : 3,
                                'user_permission' => isset($data['profile']) ? $data['profile'] : '',
                                'gender' => $gender,
                            );

                            $user_data['is_test_data'] = $_SESSION['setTestEnv'];
                            $this->db->insert(TBL_USERS, $user_data);
                            $uid = $this->db->insert_id();
                            insert_history($uid, TBL_USERS, 'id', 'new-user-inserted');
                            $entity_id = $uid;

                            //Update config variable
                            if ($this->config->item('onboarding_staff') == '0') {
                                $config_update_data = array('config_value' => '1', 'modified_by' => $created_by, 'modified_date' => date('Y-m-d H:i:s'));
                                $this->db->where('config_key', 'onboarding_staff');
                                $this->db->update(TBL_CRM_CONFIG, $config_update_data);
                            }

                            for ($i = 1; $i <= 5; $i++) {
                                $country_name = '';
                                if ((isset($data['street_address_' . $i]) && $data['street_address_' . $i] != "") || (isset($data['city_' . $i]) && $data['city_' . $i] != "") || (isset($data['state_' . $i]) && $data['state_' . $i] != "") || (isset($data['post_code_' . $i]) && $data['post_code_' . $i] != "") || (isset($data['country_' . $i]) && $data['country_' . $i] != "")) {
                                    if ($data['country_' . $i] != '') {
                                        $country_name = check_country($data['country_' . $i]);
                                    }
                                    $address_data[$i] = array(
                                        'entity_id' => $entity_id,
                                        'entity_type' => 6,
                                        'is_primary' => (isset($data['is_primary_' . $i]) && strtolower($data['is_primary_' . $i]) == 'yes') ? '1' : '0',
                                        'address_type' => (isset($data['address_type_' . $i]) && strtolower($data['address_type_' . $i]) == 'home') ? '0' : '1',
                                        'street_address' => isset($data['street_address_' . $i]) ? $data['street_address_' . $i] : '',
                                        'city' => isset($data['city_' . $i]) ? $data['city_' . $i] : '',
                                        'state' => isset($data['state_' . $i]) ? $data['state_' . $i] : '',
                                        'post_code' => isset($data['post_code_' . $i]) ? $data['post_code_' . $i] : '',
                                        'country' => isset($country_name) && $country_name != '' ? $country_name : '',
                                        'status' => 1,
                                        'created_by' => $created_by
                                    );
                                }
                            }

                            if (count($address_data) > 0) {
                                foreach ($address_data as &$array) {
                                    $query = $this->db->get_where(TBL_ADDRESS, $array);
                                    $count = $query->num_rows();
                                    if ($count === 0) {
                                        $array['created_date'] = date('Y-m-d H:i:s');
                                        $array['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_ADDRESS, $array);
                                        $address_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['address']['1'];
                                        insert_history($address_id, TBL_ADDRESS, 'id', $historyData);
                                    }
                                }
                            }

                            $userfields_items_arr = fetch_userfield_item_att('users');
                            $insert_count++;
                        } else {
                            $data['error'] = "";
                            $count = $u_key + 1;
                            if (isset($data['users_firstname']) && $data['users_firstname'] == "") {
                                $data['error'].= (($data['error'] != "") ? ", " : "") . 'First name is required on row ' . $count . '';
                            } if (isset($data['users_lastname']) && $data['users_lastname'] == "") {
                                $data['error'].=(($data['error'] != "") ? ", " : "") . 'Last name is required on row ' . $count . '';
                            } if (isset($data['user_email']) && $data['user_email'] == "") {
                                $data['error'].=(($data['error'] != "") ? ", " : "") . 'Email is required on row ' . $count . '';
                            } if (isset($data['password']) && $data['password'] == "") {
                                $data['error'].=(($data['error'] != "") ? ", " : "") . 'Password is required on row ' . $count . '';
                            } if (isset($data['department']) && $data['department'] == "") {
                                $data['error'].=(($data['error'] != "") ? ", " : "") . 'Department is required on row ' . $count . '';
                            }if (isset($data['profile']) && $data['profile'] == "") {
                                $data['error'].=(($data['error'] != "") ? ", " : "") . 'Profile is required on row ' . $count . '';
                            }
                            if (isset($data['username']) && $data['username'] != "") {
                                $unique_username = $this->unique_username(trim($data['username']));
                                if ($unique_username == false) {
                                    $data['error'].=(($data['error'] != "") ? ", " : "") . 'Username is not unique on row ' . $count . '';
                                }
                            }
                            if (isset($data['user_email']) && $data['user_email'] != "") {
                                $unique_email = $this->unique_email(trim($data['user_email']));
                                $valid_email = $this->valid_email(trim($data['user_email']));
                                if ($valid_email == false) {
                                    $data['error'].=(($data['error'] != "") ? ", " : "") . 'User Email is not valid on row ' . $count . '';
                                } else if ($unique_email == false) {
                                    $data['error'].=(($data['error'] != "") ? ", " : "") . 'User Email is not unique on row ' . $count . '';
                                } else {
                                    $user_email = trim($data['user_email']);
                                }
                            } else {
                                $data['error'].="";
                            }

                            $data['count'] = $count;
                            $non_inserted_data[] = $data;
                        }
                        break;
                    case 'pipeline':
                        if (isset($data['deal_name']) && $data['deal_name'] != "" && !empty($data['deal_name']) && isset($data['entity_type']) && $data['entity_type'] != "" && !empty($data['entity_type']) && isset($data['associated_entity']) && $data['associated_entity'] != "" && !empty($data['associated_entity'])) {
                            $entity_id = '';
                            $entity_type = '';
                            $entity_name = '';
                            if (isset($data['entity_type']) && $data['entity_type'] != '') {
                                //check company already present else Insert new company if not present
                                if (strtolower($data['entity_type']) == 'company') {
                                    $company_data = array();
                                    $company_query = $this->db->query("SELECT id FROM " . TBL_COMPANY . " WHERE company_name LIKE '" . trim($this->db->escape_like_str($data['associated_entity'])) . "' and status=1 ");
                                    if ($company_query->num_rows() > 0) {
                                        $company_result = $company_query->row_array();
                                        $company_id = $company_result['id'];
                                    } else {
                                        $company_data = array(
                                            'company_name' => $data['associated_entity'],
                                            'status' => 1,
                                            'created_from' => 'csv import',
                                            'created_by' => $created_by,
                                            'created_date' => date('Y-m-d H:i:s')
                                        );
                                        $company_data['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_COMPANY, $company_data);
                                        $company_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['company']['1'];
                                        insert_history($company_id, TBL_COMPANY, 'id', $historyData);

                                        $array_address = array(
                                            'entity_type' => '1',
                                            'is_primary' => '',
                                            'is_head_office' => '',
                                            'address_type' => '1',
                                            'street_address' => '',
                                            'city' => '',
                                            'state' => isset($data['entity_state']) ? $data['entity_state'] : '',
                                            'country' => 'Australia',
                                            'post_code' => '',
                                            'status' => '1',
                                            'entity_id' => $company_id,
                                            'created_by' => $_SESSION['user_id'],
                                            'created_date' => date('Y-m-d H:i:s'),
                                            'is_test_data' => $_SESSION['setTestEnv']
                                        );
                                        $this->db->insert(TBL_ADDRESS, $array_address);
                                        $address_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['address']['1'];
                                        insert_history($address_id, TBL_ADDRESS, 'id', $historyData);
                                    }
                                    $entity_id = $company_id;
                                    $entity_type = '1';
                                    $entity_name = $data['associated_entity'];
                                }
                                //check contact already present else Insert new contact if not present
                                if (strtolower($data['entity_type']) == 'contact') {
                                    $contact_data = array();
                                    $contact_name = explode(' ', $data['associated_entity']);
                                    $first_name = isset($contact_name[0]) && $contact_name[0] != '' ? $contact_name[0] : '';
                                    $last_name = isset($contact_name[1]) && $contact_name[1] != '' ? $contact_name[1] : '';

                                    $contact_query = $this->db->query("SELECT id FROM " . TBL_CONTACT . " WHERE CONCAT(firstname,' ',lastname) LIKE '" . trim($this->db->escape_like_str($data['associated_entity'])) . "' AND status=1 ");
                                    if ($contact_query->num_rows() > 0) {
                                        $contact_result = $contact_query->row_array();
                                        $contact_id = $contact_result['id'];
                                    } else {
                                        $contact_data = array(
                                            'firstname' => $first_name,
                                            'lastname' => $last_name,
                                            'status' => 1,
                                            'created_from' => 'csv import',
                                            'created_by' => $created_by,
                                            'created_date' => date('Y-m-d H:i:s')
                                        );
                                        $contact_data['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_CONTACT, $contact_data);
                                        $contact_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['contact']['1'];
                                        insert_history($contact_id, TBL_CONTACT, 'id', $historyData);
                                    }
                                    $entity_id = $contact_id;
                                    $entity_type = '2';
                                    $entity_name = $data['associated_entity'];
                                }
                                //check lead already present else Insert new lead if lead present
                                if (strtolower($data['entity_type']) == 'lead') {
                                    $lead_data = array();
                                    $lead_query = $this->db->query("SELECT id FROM " . TBL_LEAD . " WHERE lead_name LIKE '" . trim($this->db->escape_like_str($data['associated_entity'])) . "' and status=1 ");
                                    if ($lead_query->num_rows() > 0) {
                                        $lead_result = $lead_query->row_array();
                                        $lead_id = $lead_result['id'];
                                    } else {
                                        $lead_data = array(
                                            'lead_name' => $data['associated_entity'],
                                            'status' => 1,
                                            'created_from' => 'csv import',
                                            'created_by' => $created_by,
                                            'created_date' => date('Y-m-d H:i:s')
                                        );
                                        $lead_data['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_LEAD, $lead_data);
                                        $lead_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['lead']['1'];
                                        insert_history($lead_id, TBL_LEAD, 'id', $historyData);
                                    }
                                    $entity_id = $lead_id;
                                    $entity_type = '3';
                                    $entity_name = $data['associated_entity'];
                                }
                            }

                            $deal_stage = '';
                            $stages = $this->config->item('deal_stage');
                            if (isset($data['deal_stage']) && $data['deal_stage'] != '') {
                                foreach ($stages as $deal => $stage) {
                                    if (strtolower($data['deal_stage']) == strtolower($stage)) {
                                        $deal_stage = $deal;
                                    }
                                }
                            }
                            $deal_type = '';
                            $o_types = $this->config->item('deal_type');
                            if (isset($data['deal_type']) && $data['deal_type'] != '') {
                                foreach ($o_types as $pipe_val => $pipe_type) {
                                    if (strtolower($data['deal_type']) == strtolower($pipe_type)) {
                                        $deal_type = $pipe_val;
                                    }
                                }
                            }
                            $probability = '';
                            $probability_value = ''; 
                            if (isset($data['probability']) && $data['probability'] != '') {
                                $prob_query = $this->db->query('SELECT pvalue FROM ' . TBL_PROBABILITY . ' WHERE status = 1 AND pname = "' . trim($data['probability']) . '" ');
                                $pro_val = $prob_query->row_array();
                                if ($prob_query->num_rows() > 0) {
                                    $probability = $pro_val['pvalue'];
                                    $probability_value = $probability . "% - " . trim($data['probability']);
                                }
                            }else{
                                $prob_query = $this->db->query('SELECT MIN(pvalue) as pvalue,pname FROM ' . TBL_PROBABILITY . ' WHERE status = 1');
                                $pro_val = $prob_query->row_array();
                                if ($prob_query->num_rows() > 0) {
                                    $probability = $pro_val['pvalue'];
                                    $probability_value = $probability . "% - " . trim($pro_val['pname']);
                                }
                            }
                            $primary_data = array(
                                "deal_name" => isset($data['deal_name']) && $data['deal_name'] != '' ? $data['deal_name'] : '',
                                "deal_stage" => isset($deal_stage) && $deal_stage != '' ? $deal_stage : '',
                                "deal_type" => isset($deal_type) && $deal_type != '' ? $deal_type : '',
                                "probability" => isset($probability) && $probability != '' ? $probability : '',
                                "income" => isset($data['income']) && $data['income'] != '' ? $data['income'] : '',
                                "entity_id" => isset($entity_id) && $entity_id != '' ? $entity_id : '',
                                "entity_type" => isset($entity_type) && $entity_type != '' ? $entity_type : '',
                                "entity_status" => '1',
                                "status" => 1,
                                "created_from" => "csv import",
                                "deal_start_date" => isset($data['deal_start_date']) && $data['deal_start_date'] != '' ? date(DATE_FORMAT_BACKEND, strtotime($data['deal_start_date'])) : '',
                                "deal_complete_date" => isset($data['deal_complete_date']) && $data['deal_complete_date'] != '' ? date(DATE_FORMAT_BACKEND, strtotime($data['deal_complete_date'])) : '',
                                "comment" => isset($data['comment']) && $data['comment'] != '' ? $data['comment'] : '',
                                "created_by" => $_SESSION['user_id'],
                                "created_date" => date('Y-m-d H:i:s')
                            );
                            $data['is_test_data'] = $_SESSION['setTestEnv'];
                            $this->db->insert(TBL_PIPELINE, $primary_data);
                            $deal_id = $this->db->insert_id();
                            $historyData = $this->historyComments['pipeline']['1'];
                            insert_history($deal_id, TBL_PIPELINE, 'id', $historyData);

                            if (isset($data['pdt_name']) && $data['pdt_name'] != '') {
                                
                                        $pdtName = trim($data['pdt_name']);
                                        $product_query = $this->db->query("SELECT id FROM " . TBL_PRODUCT . " WHERE name LIKE '" . trim($pdtName) . "' and status=1 ");
                                        if ($product_query->num_rows() > 0) {
                                            $product_result = $product_query->row_array();
                                            $product_id = $product_result['id'];
                                        } else {
                                            $product_data = array(
                                                'name' => $pdtName,
                                                'prod_code' => $pdtName,
                                                'description' => '',
                                                'price' => '',
                                                'unit_of_measurement' => isset($data['pdt_uom']) ? $data['pdt_uom'] : '',
                                                'category_id' => '1',
                                                'status' => '1',
                                                'created_from' => 'csv import',
                                                'created_date' => date('Y-m-d H:i:s'),
                                                'created_by' => $_SESSION['user_id']
                                            );
                                            $product_data['is_test_data'] = $_SESSION['setTestEnv'];
                                            $this->db->insert(TBL_PRODUCT, $product_data);
                                            $product_id = $this->db->insert_id();
                                            $historyData = $this->historyComments['product']['1'];
                                            insert_history($product_id, TBL_PRODUCT, 'id', $historyData);
                                        }
                                        $product_total = (isset($data['pdt_price']) && isset($data['pdt_qty'])) ? $data['pdt_price'] * $data['pdt_qty'] : '';
                                        $productData = array(
                                            'entity_id' => $deal_id,
                                            'entity_type' => '5',
                                            'product_id' => $product_id,
                                            'product_price' => '',
                                            'product_quantity' => isset($data['pdt_price']) ? $data['pdt_qty'] : '',
                                            'product_total' => $product_total,
                                            'created_by' => $_SESSION['user_id'],
                                            'created_date' => date('Y-m-d H:i:s'),
                                            'status' => '1'
                                        );
                                        $productData['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_PRODUCT_MAP, $productData);
                                        $pdt_map_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['product_map']['1'];
                                        insert_history($pdt_map_id, TBL_PRODUCT_MAP, 'id', $historyData);
                                   
                                
                            }
                            /* Add Sales Activity for pipeline  */
                            $probability_value = isset($probability_value) && $probability_value != '' ? $probability_value : '-';
                            $deal_name = isset($data['deal_name']) && $data['deal_name'] != '' ? $data['deal_name'] : '';
                            $activity_subject = $entity_name . ' ' . $deal_name . ' ' . 'added activity';

                            $product_total = isset($product_total) && $product_total!='' ? '$ '. number_format($product_total) : '';
                            $data['pdt_name'] = isset($data['pdt_name']) ? $data['pdt_name'] : '';
                            $activity_text = "<b>Potential Sale Value : </b>" . $product_total . " ||";
                            $activity_text.= "<b>Product : </b>" . $data['pdt_name'] . " ||";
                            $activity_text.= "<b>Probability : </b>" . $probability_value;
                            // If sale is lost or won then activity status is complete else create incomplete activity 
                            $activity_status = 0;
                            if (isset($deal_stage) && $deal_stage == '6' || $deal_stage == '4') {
                                $activity_status = 1;
                            }
                            $data_activity = array(
                                'activity_start_date' => date('Y-m-d H:i:s'),
                                'activity_subject' => trim($activity_subject),
                                'activity_category' => 2,
                                'activity_type' => 6,
                                'entity_type' => isset($entity_type) && $entity_type != '' ? $entity_type : '',
                                'entity_status' => '1',
                                'entity_id' => isset($entity_id) && $entity_id != '' ? $entity_id : '',
                                'pipeline_id' => $deal_id,
                                'activity_text' => $activity_text,
                                'activity_status' => $activity_status,
                                'status' => 1,
                                'created_from' => 'csv import',
                                'created_date' => date('Y-m-d H:i:s'),
                                'created_by' => $_SESSION['user_id']
                            );
                            $this->db->insert(TBL_ACTIVITIES, $data_activity);
                            $act_id = $this->db->insert_id();
                            $historyData = $this->historyComments['activity']['1'];
                            insert_history($act_id, TBL_ACTIVITIES, 'id', $historyData);
                            /* Add Sales Activity for pipeline ends here */


                            /* Associate tags for Pipeline */
                            if (isset($data['tag_name']) && $data['tag_name'] != '') {
                                $all_tags = $data['tag_name'];
                                $all_tags = @explode(',', $all_tags);
                                foreach ($all_tags as $key => $tag_name) {
                                    $tag_query = $this->db->query("SELECT id FROM " . TBL_TAGS . " WHERE belongs_to = 'Pipeline' AND title LIKE '" . trim($this->db->escape_like_str($tag_name)) . "' and status=1 ");
                                    if ($tag_query->num_rows() > 0) {
                                        $tag_result = $tag_query->row_array();
                                        $tag_id = $tag_result['id'];
                                        //Insert into tag map table with tag id
                                        $tag_map_data = array(
                                            'entity_id' => $deal_id,
                                            'entity_type' => '5',
                                            'entity_status' => '1',
                                            'tag_id' => $tag_id,
                                            'status' => 1,
                                            'created_date' => date('Y-m-d H:i:s'),
                                            'created_by' => $created_by
                                        );
                                        $tag_map_data['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_TAG_MAP, $tag_map_data);
                                        $tag_map_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['tag_map']['1'];
                                        insert_history($tag_map_id, TBL_TAG_MAP, 'id', $historyData);
                                    } else {
                                        //Create first new tag and then map into tag map table
                                        $tag_data = array(
                                            'title' => $tag_name,
                                            'belongs_to' => 'Pipeline',
                                            'status' => 1,
                                            'created_date' => date('Y-m-d H:i:s'),
                                            'created_by' => $created_by
                                        );
                                        $tag_data['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_TAGS, $tag_data);
                                        $new_tag_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['tag']['1'];
                                        insert_history($new_tag_id, TBL_TAGS, 'id', $historyData);

                                        //map into tag map table with the new tag id
                                        $tag_map_data = array(
                                            'entity_id' => $deal_id,
                                            'entity_type' => '5',
                                            'entity_status' => '1',
                                            'tag_id' => $new_tag_id,
                                            'status' => 1,
                                            'created_date' => date('Y-m-d H:i:s'),
                                            'created_by' => $created_by
                                        );
                                        $tag_map_data['is_test_data'] = $_SESSION['setTestEnv'];
                                        $this->db->insert(TBL_TAG_MAP, $tag_map_data);
                                        $tag_map_id = $this->db->insert_id();
                                        $historyData = $this->historyComments['tag_map']['1'];
                                        insert_history($tag_map_id, TBL_TAG_MAP, 'id', $historyData);
                                    }
                                }
                            }
                            /* Associate tags for Pipeline ends here */

                            $entity_id = $deal_id;
                            $userfields_items_arr = fetch_userfield_item_att('pipeline');
                            $insert_count++;
                        } else {
                            $count = $u_key + 1;
                            $data['count'] = $count;
                            if ($data['deal_name'] == '' && $data['entity_type'] == '' && $data['associated_entity'] == '') {
                                $pipe_field = 'Pipeline Name,Associated Entity and Entity Type';
                            } else if ($data['deal_name'] == '' && $data['entity_type'] == '') {
                                $pipe_field = 'Pipeline Name and Entity Type';
                            } else if ($data['deal_name'] == '' && $data['associated_entity'] == '') {
                                $pipe_field = 'Pipeline Name and Associated Entity';
                            } else if ($data['entity_type'] == '' && $data['associated_entity'] == '') {
                                $pipe_field = 'Associated Entity And Entity Type';
                            } else if ($data['deal_name'] == '') {
                                $pipe_field = 'Pipeline Name';
                            } else if ($data['entity_type'] == '') {
                                $pipe_field = 'Entity Type';
                            } else if ($data['associated_entity'] == '') {
                                $pipe_field = 'Associated Entity';
                            }
                            $data['error'] = $pipe_field . ' is required on row ' . $count . '';
                            $non_inserted_data[] = $data;
                        }
                        break;
                    case 'product':
                        if (isset($data['name']) && $data['name'] != "" && !empty($data['name'])) {
                            $category = '';
                            if ($data['category'] != '') {
                                $cat_query = $this->db->query('SELECT id FROM ' . TBL_PRODUCT_CATEGORY . ' WHERE status = 1 AND name = "' . trim($data['category']) . '" ');
                                if ($cat_query->num_rows() > 0) {
                                    $cat_id = $cat_query->row_array();
                                    $category = $cat_id['id'];
                                } else {
                                    $product_cat_data = array(
                                        'name' => $data['category'],
                                        'status' => 1,
                                        'created_date' => date('Y-m-d H:i:s'),
                                        'created_by' => $_SESSION['user_id'],
                                        'created_from' => 'csv import'
                                    );
                                    $product_cat_data['is_test_data'] = $_SESSION['setTestEnv'];
                                    $this->db->insert(TBL_PRODUCT_CATEGORY, $product_cat_data);
                                    $category = $this->db->insert_id();
                                    $historyData = $this->historyComments['product_category']['1'];
                                    insert_history($category, TBL_PRODUCT_CATEGORY, 'id', $historyData);
                                }
                            }

                            $primary_data = array(
                                "name" => isset($data['name']) && $data['name'] != '' ? $data['name'] : '',
                                "description" => isset($data['description']) && $data['description'] != '' ? $data['description'] : '',
                                "category_id" => isset($category) && $category != '' ? $category : '1',
                                "status" => isset($data['status']) && $data['status'] != '' && strtolower($data['status']) == 'in active' ? '0' : '1',
                                "prod_code" => isset($data['prod_code']) && $data['prod_code'] != '' ? $data['prod_code'] : '',
                                "price" => isset($data['price']) && $data['price'] != '' ? $data['price'] : '',
                                "unit_of_measurement" => isset($data['unit_of_measurement']) && $data['unit_of_measurement'] != '' ? $data['unit_of_measurement'] : '',
                                "created_by" => $_SESSION['user_id'],
                                "created_date" => date('Y-m-d H:i:s'),
                                "created_from" => "csv import"
                            );
                            $data['is_test_data'] = $_SESSION['setTestEnv'];
                            $this->db->insert(TBL_PRODUCT, $primary_data);
                            $product_id = $this->db->insert_id();
                            $historyData = $this->historyComments['product']['1'];
                            insert_history($product_id, TBL_PRODUCT, 'id', $historyData);

                            //On-boarding process Update config variable
                            if ($this->config->item('onboarding_product') == '0') {
                                $config_update_data = array('config_value' => '1', 'modified_by' => $_SESSION['user_id'], 'modified_date' => date('Y-m-d H:i:s'));
                                $this->db->where('config_key', 'onboarding_product');
                                $this->db->update(TBL_CRM_CONFIG, $config_update_data);
                            }

                            $entity_id = $product_id;
                            $userfields_items_arr = fetch_userfield_item_att('products');
                            $insert_count++;
                        } else {
                            $count = $u_key + 1;
                            $data['count'] = $count;
                            $data['error'] = $this->getFormFieldsLabel['product'] . ' name is required on row ' . $count . '';
                            $non_inserted_data[] = $data;
                        }
                        break;
                }

                //Insert custom fields
                if (isset($userfields_items_arr) && $userfields_items_arr != "") {
                    foreach ($userfields_items_arr as $userfields_item_key => $userfields_item_val) {
                        if (!empty(array_filter($userfields_item_val['options']))) {
                            foreach ($userfields_item_val['options'] as $userfields_item_vals) {
                                if (isset($data[$userfields_item_val['item_name']])) {
                                    if (isset($userfields_item_vals['user_field_options']) && (strtolower($userfields_item_vals['user_field_options']) == strtolower($data[$userfields_item_val['item_name']]))) {
                                        $db_data['eav_item_id'] = $userfields_item_key;
                                        $db_data['value'] = isset($userfields_item_vals['id']) ? $userfields_item_vals['id'] : "";
                                        $db_data['table_row_id'] = $entity_id;
                                    }
                                }
                            }
                        } else {
                            $db_data['eav_item_id'] = $userfields_item_key;
                            $db_data['value'] = isset($data[$userfields_item_val['item_name']]) ? $data[$userfields_item_val['item_name']] : "";
                            $db_data['table_row_id'] = $entity_id;
                        }
                        $db_data['created_by'] = $created_by;
                        $db_data['created_date'] = date("Y-m-d H:i:s");
                        if ((isset($entity_id) && $entity_id != "") && (isset($db_data['value']) && $db_data['value'] != "")) {
                            //$db_data['is_test_data'] = $_SESSION['setTestEnv'];
                            $this->db->insert('eav_item_attributes', $db_data);
                            $eav_id = $this->db->insert_id();
                            insert_history($eav_id, 'eav_item_attributes', 'id', 'eav field inserted');
                        }
                    }
                }
            }
        }


        $data['non_inserted_data'] = isset($non_inserted_data) ? $non_inserted_data : "";
        $data['mode'] = $mode;
        $data['parent_li'] = $mode;
        $data['insert_count'] = $insert_count;
        $data['total_csv_rec_count'] = count($final_array);
        $this->_display('upload_success', $data);
    }

    /**
     * validate_csv_data function.
     * 
     * To validate the data from csv
     * @access public
     * @params 
     *      $compdata : unserialized data of csv headers
     *      $temp_array : unserialized data of csv records
     *      $mode : mode of csv
     *      $file_name : name of csv
     */
    function validate_csv_data($compdata, $temp_array, $mode, $file_name) {
        $onboarding = '';
        if ($this->uri->segment(5) == 'onboarding') {
            $onboarding = '/onboarding';
        }
        foreach ($temp_array as $temp_val) {
            $final_array[] = @array_combine(array_keys($compdata), array_values($temp_val));
        }

        if (count(array_filter($final_array)) == 0) {
            $data['error'] = 'Please fill all the fields.';
            $data['mode'] = $mode;
            $data['onboarding'] = $onboarding;
            $data['parent_li'] = $mode;
            $this->_display('error', $data);
        } else {
            $count = 0;
            foreach ($final_array as $data) {
                switch ($mode) {
                    case 'company':
                        $this->errors = $this->_verify_form('company', $data, $count);
                        if ($this->errors != '') {
                            $errors[] = $this->errors;
                            $data['count'] = $count;
                        }
                        break;
                    case 'contact':
                        $this->errors = $this->_verify_form('contact', $data, $count);
                        if ($this->errors != '') {
                            $errors[] = $this->errors;
                            $data['count'] = $count;
                        }
                        break;
                    case 'lead':
                        $this->errors = $this->_verify_form('lead', $data, $count);
                        if ($this->errors != '') {
                            $errors[] = $this->errors;
                            $data['count'] = $count;
                        }
                        break;
                    case 'users':
                        $this->errors = $this->_verify_form('users', $data, $count);
                        if ($this->errors != '') {
                            $errors[] = $this->errors;
                            $data['count'] = $count;
                        }
                        break;
                    case 'pipeline':
                        $this->errors = $this->_verify_form('pipeline', $data, $count);
                        if ($this->errors != '') {
                            $errors[] = $this->errors;
                            $data['count'] = $count;
                        }
                        break;
                    case 'product':
                        $this->errors = $this->_verify_form('product', $data, $count);
                        if ($this->errors != '') {
                            $errors[] = $this->errors;
                            $data['count'] = $count;
                        }
                        break;
                }
                $count++;
            }  //end of final_data foreach

            if (isset($errors) && is_array($errors) && !empty(array_filter($errors))) {
                $data['file_name'] = $file_name;
                $data['errors'] = $errors;
                $data['parent_li'] = $mode;
                $data['mode'] = $mode;
                $this->_display('validating_form', $data);
            } else {

                redirect('bulk_import/csv_upload/insert_csv_data/' . $mode . $onboarding);
            }
        }
    }

    /**
     * get_real_ip_addr function.
     * 
     * To get ip address
     * @access public
     */
    function get_real_ip_addr() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $real_ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $real_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $real_ip = $_SERVER['REMOTE_ADDR'];
        }
        return $real_ip;
    }

    /**
     * get_profile function.
     * 
     * To get mapname from csv_queue
     * @access public
     */
    function get_profile() {
        $selectval = $this->input->post('selectval');
        $selectval = trim($this->db->escape_like_str($selectval));
        $csv = $this->input->post('hiddenval');
        $csv_new = unserialize(base64_decode($csv));
        $qid = $this->db->query('SELECT header, csv_header FROM csv_queue WHERE mapname LIKE"' . $selectval . '"');
        foreach ($qid->result_array() as $value) {
            $old_header = unserialize(base64_decode($value['header']));
            $csv_old = unserialize(base64_decode($value['csv_header']));
        }
        $new_header = array();
        foreach ($csv_old as $keyo => $valo) {
            foreach ($csv_new as $keyn => $valn) {
                if ($valo == $valn) {
                    $new_header[$keyn] = $old_header[$keyo];
                }
            }
        }
        echo json_encode($new_header);
    }

    /**
     * validate_mapping function.
     * 
     * To validate the mapping fields
     * @access public
     */
    function validate_mapping() {
        if ($this->input->post() && count($this->input->post()) > 0) {
            $error_message = '';
            $msg = array();
            $company = array();
            $contact = array();
            $pipeline = array();
            $product = array();
            foreach ($this->input->post('fields') as $field) {
                switch ($field) {
                    case 'company_id':
                        $company['company_id'] = $field;
                        break;
                    case 'company_name':
                        $company['company_name'] = $field;
                        break;
                    case 'contact_name':
                        $company['contact_name'] = $field;
                        break;
                    case 'company_email_1':
                    case 'company_phone_1':
                    case 'company_site_1':
                    case 'company_messenger_1':
                    case 'company_email_2':
                    case 'company_phone_2':
                    case 'company_site_2':
                    case 'company_messenger_2':
                    case 'company_email_3':
                    case 'company_phone_3':
                    case 'company_site_3':
                    case 'company_messenger_3':
                    case 'company_email_4':
                    case 'company_phone_4':
                    case 'company_site_4':
                    case 'company_messenger_4':
                    case 'company_email_5':
                    case 'company_phone_5':
                    case 'company_site_5':
                    case 'company_messenger_5':
                        $company['numbers'][] = $field;
                        break;

                    case 'contact_firstname':
                        $contact['contact_firstname'] = $field;
                        break;
                    case 'contact_lastname':
                        $contact['contact_lastname'] = $field;
                        break;
                    case 'contact_email_1':
                    case 'contact_phone_1':
                    case 'contact_site_1':
                    case 'contact_messenger_1':
                    case 'contact_email_2':
                    case 'contact_phone_2':
                    case 'contact_site_2':
                    case 'contact_messenger_2':
                    case 'contact_email_3':
                    case 'contact_phone_3':
                    case 'contact_site_3':
                    case 'contact_messenger_3':
                    case 'contact_email_4':
                    case 'contact_phone_4':
                    case 'contact_site_4':
                    case 'contact_messenger_4':
                    case 'contact_email_5':
                    case 'contact_phone_5':
                    case 'contact_site_5':
                    case 'contact_messenger_5':
                        $contact['numbers'][] = $field;
                        break;


                    case 'street_address':
                        $address['address_line'][] = $field;
                        break;
                    case 'city':
                        $address['city'] = $field;
                        break;
                    case 'state':
                        $address['state'] = $field;
                        break;
                    case 'post_code':
                        $address['post_code'] = $field;
                        break;
                    case 'country':
                        $address['country'] = $field;
                        break;
                    case 'pipeline_id':
                        $pipeline['pipeline_id'] = $field;
                        break;
                    case 'deal_name':
                        $pipeline['deal_name'] = $field;
                        break;
                    case 'deal_stage':
                        $pipeline['deal_stage'] = $field;
                        break;
                    case 'probability':
                        $pipeline['probability'] = $field;
                        break;
                    case 'deal_type':
                        $pipeline['deal_type'] = $field;
                        break;
                    case 'associated_entity':
                        $pipeline['associated_entity'] = $field;
                        break;
                    case 'deal_start_date':
                        $pipeline['deal_start_date'] = $field;
                        break;
                    case 'deal_complete_date':
                        $pipeline['deal_complete_date'] = $field;
                        break;
                    case 'comment':
                        $pipeline['comment'] = $field;
                        break;
                    case 'product_id':
                        $product['product_id'] = $field;
                        break;
                    case 'name':
                        $product['name'] = $field;
                        break;
                    case 'description':
                        $product['description'] = $field;
                        break;
                    case 'category':
                        $product['category'] = $field;
                        break;
                    case 'status':
                        $product['status'] = $field;
                        break;
                    case 'price':
                        $product['price'] = $field;
                        break;
                    case 'prod_code':
                        $product['prod_code'] = $field;
                        break;
                    case 'unit_of_measurement':
                        $product['unit_of_measurement'] = $field;
                        break;
                }
            }

            $address_flag = 0;
            $contact_flag = 0;
            $numbers_flag = 0;
            if (!(isset($company) && is_array($company)) && count($company) > 0) {
                $error_message.='<li>Incomplete mapped data for company , kindly provide the company name (company id in case the company is already present in the system).</li>';
            } else if (!(isset($contact) && is_array($contact)) && count($contact) > 0) {
                $error_message.='<li>Incomplete mapped data for contact , kindly provide the contact first and last name (contact id in case the contact is already present in the system).</li>';
            } else if (!(isset($pipeline) && is_array($pipeline)) && count($pipeline) > 0) {
                $error_message.='<li>Incomplete mapped data for pipeline , kindly provide the pipeline name,associated entity and entity type.</li>';
            } else if (!(isset($product) && is_array($product)) && count($product) > 0) {
                $error_message.='<li>Incomplete mapped data for ' . $this->getFormFieldsLabel['product'] . ', kindly provide the ' . $this->getFormFieldsLabel['product'] . ' name .</li>';
            }

            if ($error_message == '') {
                $msg['success'] = 'success';
                echo json_encode($msg);
                exit;
            } else {
                $error_message .= '<li>Please ensure your mapping/csv is correct.</li>';
                $msg['error'] = $error_message;
                echo json_encode($msg);
            }
        } else {
            $error_message = '<li>No mapping found.</li>';
            $msg['error'] = $error_message;
            echo json_encode($msg);
        }
    }

    /**
     * export_to_csv function.
     * 
     * To download/export the sample files
     * @access public
     */
    function export_to_csv($mode, $file_type) {
        header("Content-type: text/csv");
        if ($mode == "lead") {
            if ($file_type == "detailed") {
                header("Content-Disposition: attachment; filename=lead_csv.csv");
                readfile('media/csv/lead/lead_csv.csv');
            } else if ($file_type == "quick_unassigned_lead") {
                header("Content-Disposition: attachment; filename=quick_unassigned_lead.csv");
                readfile('media/csv/lead/quick_unassigned_lead.csv');
            } else if ($file_type == "quick_assigned_lead") {
                header("Content-Disposition: attachment; filename=quick_assigned_lead.csv");
                readfile('media/csv/lead/quick_assigned_lead.csv');
            }
        } else if ($mode == "company") {
            if ($file_type == "detailed") {
                header("Content-Disposition: attachment; filename=company_csv.csv");
                readfile('media/csv/company/company_csv.csv');
            } else {
                header("Content-Disposition: attachment; filename=quick_company_csv.csv");
                readfile('media/csv/company/quick_company_csv.csv');
            }
        } else if ($mode == "contact") {
            if ($file_type == "detailed") {
                header("Content-Disposition: attachment; filename=contact_csv.csv");
                readfile('media/csv/contact/contact_csv.csv');
            } else {
                header("Content-Disposition: attachment; filename=quick_contact_csv.csv");
                readfile('media/csv/contact/quick_contact_csv.csv');
            }
        } else if ($mode == "users") {
            if ($file_type == "detailed") {
                header("Content-Disposition: attachment; filename=users_csv.csv");
                readfile('media/csv/users/users_csv.csv');
            } else {
                header("Content-Disposition: attachment; filename=quick_users_csv.csv");
                readfile('media/csv/users/quick_users_csv.csv');
            }
        } else if ($mode == "pipeline") {
            if ($file_type == "detailed") {
                header("Content-Disposition: attachment; filename=pipeline_csv.csv");
                readfile('media/csv/pipeline/pipeline_csv.csv');
            } else {
                header("Content-Disposition: attachment; filename=quick_pipeline_csv.csv");
                readfile('media/csv/pipeline/quick_pipeline_csv.csv');
            }
        } else if ($mode == "product") {
            if ($file_type == "detailed") {
                header("Content-Disposition: attachment; filename=product_csv.csv");
                readfile('media/csv/product/product_csv.csv');
            } else {
                header("Content-Disposition: attachment; filename=quick_product_csv.csv");
                readfile('media/csv/product/quick_product_csv.csv');
            }
        }
        exit;
    }

    /**
     * get_active_departments function.
     * 
     * To get active departments for users csv
     * @access public
     */
    function get_active_departments() {

        $sql = 'SELECT id,department_name FROM ' . TBL_DEPARTMENTS . ' WHERE status=1';
        $res_obj = $this->db->query($sql);
        $result = $res_obj->result_array();
        return $result;
    }

    /**
     * _verify_form function.
     * 
     * Validation performed
     * @access public
     * @return validation_errors
     */
    function _verify_form($mode = '', $data = array(), $count) {
        $errors = '';
        $this->load->library('Form_validation');
        $this->load->helper('form');

        switch ($mode) {
            case 'company':
                $counter = $count + 1;
                if (isset($data['company_name']) && $data['company_name'] == "") {
                    $errors.="<li><strong>Company name</strong> is required on row " . $counter . ".</li>";
                }
                for ($i = 1; $i <= 5; $i++) {
                    $complete_email = isset($data['company_email_' . $i]) ? trim($data['company_email_' . $i]) : '';
                    if (isset($complete_email) && $complete_email != "") {
                        $valid_email = $this->valid_email($complete_email);
                        if ($valid_email == false) {
                            $errors.="<li><strong>Company Email</strong> is invalid on row " . $counter . ".</li>";
                        }
                    }
                }
                break;
            case 'contact':
                $counter = $count + 1;
                if ($data['contact_firstname'] == "" && $data['contact_lastname'] == ""){
                    $errors.="<li><strong>Contact first name</strong> is required on row " . $counter . ".</li>";
                }
                for ($i = 1; $i <= 5; $i++) {
                    $complete_email = isset($data['contact_email_' . $i]) ? trim($data['contact_email_' . $i]) : '';
                    if (isset($complete_email) && $complete_email != "") {
                        $valid_email = $this->valid_email($complete_email);
                        if ($valid_email == false) {
                            $errors.="<li><strong>Contact Email</strong> is invalid on row " . $counter . ".</li>";
                        }
                    }
                }
                break;
            case 'lead':
                $counter = $count + 1;
                if (isset($data['lead_name']) && $data['lead_name'] == "") {
                    $errors.="<li><strong>Lead name</strong> is required on row " . $counter . ".</li>";
                }
                for ($i = 1; $i <= 5; $i++) {
                    $complete_email = isset($data['lead_email_' . $i]) ? trim($data['lead_email_' . $i]) : '';
                    if (isset($complete_email) && $complete_email != "") {
                        $valid_email = $this->valid_email($complete_email);
                        if ($valid_email == false) {
                            $errors.="<li><strong>Lead Email</strong> is invalid on row " . $counter . ".</li>";
                        }
                    }
                }
                break;
            case 'users':
                $counter = $count + 1;
                if (isset($data['users_firstname']) && $data['users_firstname'] == "") {
                    $errors.="<li><strong>User's first name</strong> is required on row " . $counter . ".</li>";
                }
                if (isset($data['users_lastname']) && $data['users_lastname'] == "") {
                    $errors.="<li><strong>User's last name</strong> is required on row " . $counter . ".</li>";
                }
                if (isset($data['user_email']) && $data['user_email'] == "") {
                    $errors.="<li><strong>Email</strong> is required on row " . $counter . ".</li>";
                }
                if (isset($data['password']) && $data['password'] == "") {
                    $errors.="<li><strong>Password</strong> is required on row " . $counter . ".</li>";
                }
                if (isset($data['profile']) && $data['profile'] == "") {
                    $errors.="<li><strong>Profile</strong> is required on row " . $counter . ".</li>";
                }

                $username = isset($data['username']) ? trim($data['username']) : "";
                if (isset($username) && $username != "") {
                    $unique_username = $this->unique_username($username);
                    if ($unique_username == false) {
                        $errors.="<li><strong>User Name</strong> is not unique on row " . $counter . ".</li>";
                    }
                }

                $complete_email = isset($data['user_email']) ? trim($data['user_email']) : '';
                if (isset($complete_email) && $complete_email != "") {
                    $valid_email = $this->valid_email($complete_email);
                    $unique_email = $this->unique_email($complete_email);
                    if ($valid_email == false) {
                        $errors.="<li><strong>User Email</strong> is invalid on row " . $counter . ".</li>";
                    }
                    if ($unique_email == false) {
                        $errors.="<li><strong>User Email</strong> is not unique on row " . $counter . ".</li>";
                    }
                }
                break;

            case 'pipeline':
                $counter = $count + 1;
                if (isset($data['deal_name']) && $data['deal_name'] == "") {
                    $errors.="<li><strong> Pipeline Name </strong> is required on row " . $counter . ".</li>";
                }
                if (isset($data['associated_entity']) && $data['associated_entity'] == "") {
                    $errors.="<li><strong>Associated Entity</strong> is required on row " . $counter . ".</li>";
                }
                if (isset($data['entity_type']) && $data['entity_type'] == "") {
                    $errors.="<li><strong>Entity Type</strong> is required on row " . $counter . ".</li>";
                }

                break;

            case 'product':
                $counter = $count + 1;
                if (isset($data['name']) && $data['name'] == "") {
                    $errors.="<li><strong>" . $this->getFormFieldsLabel['product'] . " name</strong> is required on row " . $counter . ".</li>";
                }

                break;
        }
        return $errors;
    }

    /**
     * _display function.
     * 
     * Displays the View With Pre Defined Templates
     * @access public
     * @return void
     */
    function _display($view_name, $data) {
        $data = strip_slashes($data);
        if ($data['parent_li'] == "lead") {
            $data['page_title'] = 'Leads';
        } else if ($data['parent_li'] == "company") {
            $data['page_title'] = 'Companies';
        } else if ($data['parent_li'] == "contact") {
            $data['page_title'] = 'Contacts';
        } else if ($data['parent_li'] == "users") {
            $data['page_title'] = 'Users';
        } else {
            $data['page_title'] = 'Upload CSV';
        }
        if ($this->uri->segment(5) == "onboarding" || $this->uri->segment(4) == 'onboarding') {
            $data['sub_title'] = $_SESSION['org_name'];
            $data['page_title'] = 'exaCRM Account Personalization';
        }
        $data['child_li'] = 'csv';
        $data['getFormFieldsLabel'] = $this->getFormFieldsLabel;
        $this->load->view('adminheader', $data);
        if(!(isset($_SERVER['HTTP_EXACRM_USER_AGENT']) && $_SERVER['HTTP_EXACRM_USER_AGENT'] == 'exacrm_app')){
            $this->load->view('crm_header_top_view', $data);
            $this->load->view('crm_header_view', $data);
        }
        $this->load->view($view_name, $data);
        $this->load->view('adminfooter', $data);
    }

}
