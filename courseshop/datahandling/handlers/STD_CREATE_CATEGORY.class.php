<?php 

/**
* STD_CREATE_CATEGORY is a standard shop product action handler that creates a category for the customer
* and enrols the customer as course creator (category manager) inside.
*
*/
include_once $CFG->dirroot.'/blocks/courseshop/datahandling/shophandler.class.php';

class shop_handler_STD_CREATE_CATEGORY extends shop_handler{
	
	function __construct($label){
		parent::__construct($label);
	}
    
    function produce_prepay(&$data){
        global $CFG;

		// If Customer already has account in incoming data we have nothing to do
		if (!is_null($data->user)){
            $productiondata->public = get_string('knownaccount', 'block_courseshop', $data->user->username);
            $productiondata->private = get_string('knownaccount', 'block_courseshop', $data->user->username);
            $productiondata->salesadmin = get_string('knownaccount', 'block_courseshop', $data->user->username);
			courseshop_trace("[{$data->transactionid}] Pre Pay Process : Known account {$data->user->username} at process entry.");
            return $productiondata;
		}
		
		// In this case we can have a early Customer that never confirmed a product or a brand new Customer comming in.
		// The Customer might match with an existing user... 
		// TODO : If a collision is to be detected, a question should be asked to the customer.

        // Create Moodle User but no assignation
        $newuser->username = courseshop_generate_username($data->customer->firstname, $data->customer->lastname);
        $password = generate_password(8);
        $newuser->city = $data->customer->city;
        $newuser->country = $data->customer->country;
        $newuser->firstname = $data->customer->firstname;
        $newuser->lastname = $data->customer->lastname;
        $newuser->email = $data->customer->email;

        $newuser->auth = 'manual';
        $newuser->confirmed = 1;
        $newuser->lastip = getremoteaddr();
        $newuser->timemodified = time();
        $newuser->mnethostid = $CFG->mnet_localhost_id;

		// Check we really got it in user table. Create it in other cases.
        if (!$data->user = get_record('user', 'username', $newuser->username)){
            if ($newuserid = insert_record('user', $newuser)) {
                $data->user = get_complete_user_data('username', $newuser->username);
                if(!empty($CFG->{'auth_'.$newuser->auth.'_forcechangepassword'})){
                    set_user_preference('auth_forcepasswordchange', 1, $data->user->id);
                }
                update_internal_user_password($data->user, $password);

                // bind customer record to Moodle userid
                $customer->id = $data->userid;
                $customer->hasaccount = $data->user->id;
                update_record('courseshop_customer', $customer);
				courseshop_trace("[{$data->transactionid}] Pre Pay Process : New user created {$newuser->username} with ID $newuserid.");
            }            

            $productiondata->public = get_string('productiondata_public', 'STD_CREATE_CATEGORY', '', $CFG->dirroot.'/blocks/courseshop/datahandling/handlers/lang/');
            $a->username = $newuser->username;
            $a->password = $password;
            $productiondata->private = get_string('productiondata_private', 'STD_CREATE_CATEGORY', $a, $CFG->dirroot.'/blocks/courseshop/datahandling/handlers/lang/');
            $productiondata->salesadmin = get_string('productiondata_sales', 'STD_CREATE_CATEGORY', $newuser->username, $CFG->dirroot.'/blocks/courseshop/datahandling/handlers/lang/');
        } else {
            $productiondata->public = get_string('knownaccount', 'block_courseshop', $newuser->username);
            $productiondata->private = get_string('knownaccount', 'block_courseshop', $newuser->username);
            $productiondata->salesadmin = get_string('kownaccount', 'block_courseshop', $newuser->username);
 			courseshop_trace("[{$data->transactionid}] Pre Pay Process : Known account {$data->user->username}.");
        }

        return $productiondata;
    }

    function produce_postpay(&$data){
        global $CFG;
        
        if (!isset($data->actionparams['parentcategory'])){
        	courseshop_trace("[{$data->transactionid}] Post Pay Process : Missing action data (parentcategory) for STD_CREATE_CATEGORY standard handler");
        	return array();
        }
        
        $catparent = $data->actionparams['parentcategory'];

        $now = time();
        $secsduration = @$data->actionparams['duration'] * DAYSECS;
        $upto = ($secsduration) ? $now + $secsduration : 0 ;

        $cat->name = generate_catname($data->user);

		if ($catid = courseshop_fast_make_category($catname, $description, $catparent)){
        
	        if (!$role = get_record('role', 'shortname', 'categoryowner')){ // non standard specific role when selling parts of managmeent delegation
		        $role = get_record('role', 'shortname', 'coursecreator'); // fall back for standard implementations
		    }
	        if (!role_assign($role->id, $data->user->id, 0, $context->id, $now, $upto, false, 'manual', time())){
		        $productiondata->public = get_string('productiondata_failure_public', 'STD_CREATE_CATEGORY', 'Code : COURSECREATOR ROLE ASSIGN', $CFG->dirroot.'/blocks/courseshop/datahandling/handlers/lang/');
		        $productiondata->private = get_string('productiondata_failure_private', 'STD_CREATE_CATEGORY', $data, $CFG->dirroot.'/blocks/courseshop/datahandling/handlers/lang/');
		        $productiondata->salesadmin = get_string('productiondata_failure_sales', 'STD_CREATE_CATEGORY', $data, $CFG->dirroot.'/blocks/courseshop/datahandling/handlers/lang/');
	        	courseshop_trace("[{$data->transactionid}] Post Pay Process : STD_CREATE_CATEGORY failed to assign course creator...");
		        return $productiondata;
	        }

	        $productiondata->public = get_string('productiondata_assign_public', 'STD_CREATE_CATEGORY', '', $CFG->dirroot.'/blocks/courseshop/datahandling/handlers/lang/');
	        $productiondata->private = get_string('productiondata_assign_private', 'STD_CREATE_CATEGORY', $course->id, $CFG->dirroot.'/blocks/courseshop/datahandling/handlers/lang/');
	        $productiondata->salesadmin = get_string('productiondata_assign_sales', 'STD_CREATE_CATEGORY', $course->id, $CFG->dirroot.'/blocks/courseshop/datahandling/handlers/lang/');
	
	        // record the bill is soldout
	        set_field('courseshop_bill', 'status', 'SOLDOUT', 'id', $data->id);
	    } else {
	        $productiondata->public = get_string('productiondata_failure_public', 'STD_CREATE_CATEGORY', 'Code : CATEGORY CREATION', $CFG->dirroot.'/blocks/courseshop/datahandling/handlers/lang/');
	        $productiondata->private = get_string('productiondata_failure_private', 'STD_CREATE_CATEGORY', $data, $CFG->dirroot.'/blocks/courseshop/datahandling/handlers/lang/');
	        $productiondata->salesadmin = get_string('productiondata_failure_sales', 'STD_CREATE_CATEGORY', $data, $CFG->dirroot.'/blocks/courseshop/datahandling/handlers/lang/');
	        courseshop_trace("[{$data->transactionid}] Post Pay Process : STD_CREATE_CATEGORY failed to create catgory.");
	        return $productiondata;
	    }

        courseshop_trace("[{$data->transactionid}] Post Pay Process : STD_CREATE_CATEGORY Complete.");
        return $productiondata;
    } 
}


?>