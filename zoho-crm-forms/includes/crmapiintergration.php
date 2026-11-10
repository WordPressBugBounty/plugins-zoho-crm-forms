<?php

if (!class_exists("crmformsZohoApi")) {

    class zcfaccountApi {

        public $zohocrmurl;

        public function __construct() {
            $configSetting = get_option("zcf_crmformswpbuilder_settings");
            $zcrm_integ_domain_name         = get_option("zcrm_integ_domain_name");
            if($zcrm_integ_domain_name !=''){
                $domain_setup       = "https://www.zohoapis.".$zcrm_integ_domain_name;
            }else{
                $domain_setup       = "https://www.zohoapis.com";
            }

            $this->apiurl = $domain_setup;
            $this->authtoken = $configSetting['authtoken'];

        }
        public function zcfGetModuleFields($module, $methodname, $authkey, $param = "", $recordId = "") {

          require_once( ZCF_BASE_DIR_URI . "includes/crmoauthentication.php");
          zcfcheckAccessToken();
          $this->authtoken = base64_decode(base64_decode(base64_decode($SettingsConfig['authtoken'])));

                $zfformbaseurl = $this->apiurl . "/crm/v2/settings/layouts?module=" . $module;
                $args = array(
                'timeout' => '5',
                'redirection' => '5',
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(
                        'authorization' => 'Zoho-oauthtoken ' . $authkey
                    ),
                'cookies' => array()
                );
               $zfformsresponse =  wp_remote_get( $zfformbaseurl, $args );
               $result_array = json_decode(wp_remote_retrieve_body($zfformsresponse),true);
               global $wpdb;
         $wpdb->query("
                    CREATE TABLE IF NOT EXISTS `zcf_zohocrm_moduleLists` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                          `modulename` varchar(255) NOT NULL,
                          `Layoutname` varchar(255) NOT NULL,
                          `layoutID` varchar(255) NOT NULL,
                           PRIMARY KEY ( id )
                        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8
               ");
        $delete_moduleList = $wpdb->query($wpdb->prepare("DELETE FROM zcf_zohocrm_moduleLists WHERE modulename = %s",
        $module));              
        foreach($result_array['layouts'] as $value_array){
                $layoutname = $value_array['name'];
                $layoutId   = $value_array['id'];
                $wpdb->insert('zcf_zohocrm_moduleLists', array('modulename' => $module, 'Layoutname' => $layoutname, 'layoutID' => $layoutId));
               }

            return $result_array;
        }

        public function zcfFormDatainsert($modulename, $methodname, $authkey, $xmlData = "", $extraParams = "") {
                require_once( ZCF_BASE_DIR_URI . "includes/crmoauthentication.php");
                zcfcheckAccessToken();
                $SettingsConfig = get_option("zcf_crmformswpbuilder_settings");


                $this->authtoken = base64_decode(base64_decode(base64_decode($SettingsConfig['authtoken'])));

                $zfformbaseurl = $this->apiurl . "/crm/v2/" . $modulename;
                $args = array(
                'body' => $xmlData,
                'timeout' => '5',
                'redirection' => '5',
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(
                        'authorization' => 'Zoho-oauthtoken ' . $this->authtoken
                    ),
                'cookies' => array()
                );

               $zfformsresponse =  wp_remote_post( $zfformbaseurl, $args );

               // Transport failure (WP_Error): log it and stop.
               if (is_wp_error($zfformsresponse)) {
                    $this->zcf_SubmitLog(array('code' => 'HTTP_REQUEST_FAILED', 'message' => $zfformsresponse->get_error_message()), $xmlData);
                    return array();
               }

               $httpcode = (int) wp_remote_retrieve_response_code($zfformsresponse);
               $responseresultjson = json_decode(wp_remote_retrieve_body($zfformsresponse), true);
               if (!is_array($responseresultjson)) {
                    $responseresultjson = array();
               }
               if ($httpcode < 200 || $httpcode >= 300) {
                    $responseresultjson['http_status'] = $httpcode;
               }
               $this->zcf_SubmitLog($responseresultjson, $xmlData);

               // Upload attachments only after the CRM confirms the record was created.
               $recordid = $this->zcfGetCreatedRecordId($httpcode, $responseresultjson);
               if ($recordid !== '' && $extraParams && is_array($extraParams)) {
                foreach ($extraParams as $field => $path) {
                        $this->zcfInsertattachment($recordid, $authkey, $path, $modulename);
                }
               }
               return $responseresultjson;

        }

        // Returns the new record ID when the response is a 2xx with data[0].code === "SUCCESS"
        // and a non-empty details.id; otherwise returns an empty string.
        private function zcfGetCreatedRecordId($httpcode, $response) {
            if ($httpcode < 200 || $httpcode >= 300) {
                return '';
            }
            if (!isset($response['data'][0]) || !is_array($response['data'][0])) {
                return '';
            }
            $first = $response['data'][0];
            if (!isset($first['code']) || $first['code'] !== 'SUCCESS') {
                return '';
            }
            if (!isset($first['details']['id']) || !is_scalar($first['details']['id'])) {
                return '';
            }
            $id = (string) $first['details']['id'];
            return preg_match('/^[0-9]+$/', $id) ? $id : '';
        }

        public function zcf_SubmitLog($responseresultjson,$fetchData=""){
            global $wpdb;
            if (isset($responseresultjson['data'][0]) && is_array($responseresultjson['data'][0])) {
                $first = $responseresultjson['data'][0];
            } elseif (is_array($responseresultjson) && !empty($responseresultjson)) {
                $first = $responseresultjson;
            } else {
                $first = array('code' => 'INVALID_RESPONSE');
            }
            $status = (isset($first['code']) && is_scalar($first['code'])) ? (string) $first['code'] : 'INVALID_RESPONSE';
            $desc = serialize($first);
            $joinstring = $desc."".$fetchData;
            $wpdb->insert( 'zcf_submitlogs' , array( 'crmsubmitlogStatus' => $status,'crmsubmitlogDescribtion'=>$joinstring) );
        }
        public function zcfInsertattachment($id, $authkey, $path='', $modulename="") {
            if (!is_scalar($id) || !preg_match('/^[0-9]+$/', (string) $id)) {
                return false;
            }

          $url = $this->apiurl ."/crm/v2/$modulename/$id/Attachments";
          $path = '@' . $path;
          $post = array("file" => $path);
          $args = array(
              'body'        => $post,
              'timeout' => '5',
              'redirection' => '5',
              'httpversion' => '1.0',
              'blocking' => true,
              'cookies' => array(),
              'accept'        => 'application/json',
              'content-type'  => 'application/binary',
              'headers' => array(
                      'authorization' => 'Zoho-oauthtoken ' . $this->authtoken
              ),
            );
          $result = wp_remote_post(  $url, $args );
        }


        public function zcfGetRecords($modulename, $methodname, $authkey, $selectColumns = "", $xmlData = "", $extraParams = "") {
            $postContent = "scope=crmapi";
            $postContent .= "&authtoken={$authkey}"; //Give your authtoken
            if ($selectColumns == "") {
                $postContent .= "&selectColumns=All";
            } else {
                $postContent .= "&selectColumns={$modulename}( {$selectColumns} )";
            }

            if ($extraParams != "") {
                $postContent .= $extraParams;
            }
        }
        public function zcfGetModules($TFA_authtoken) {
            global $wpdb;
            $zfformbaseurl = $this->apiurl . "/crm/v2/settings/modules";
                $args = array(
                'timeout' => '5',
                'redirection' => '5',
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(
                        'authorization' => 'Zoho-oauthtoken ' . $TFA_authtoken
                    ),
                'cookies' => array()
                );
               $zfformsresponse =  wp_remote_get( $zfformbaseurl, $args );
               $responsedata = json_decode(wp_remote_retrieve_body($zfformsresponse),true);
                if( $responsedata !=''  && $responsedata['modules'][0] !=''){
                 $desc = serialize($responsedata['modules'][0]);
               }elseif($responsedata !=''  && $responsedata['modules'][0] ==''){
                  $desc = serialize($responsedata);
               }
               $wpdb->insert( 'zcf_submitlogs' , array( 'crmsubmitlogStatus' => "moduleSyc",'crmsubmitlogDescribtion'=>$desc) );
                $listModule = array();
                $configcreated_time = date("Y-m-d H:i:s");
                foreach ($responsedata['modules'] as $key => $value) {
                    $api_supported = $value['api_supported'];
                    $plural_label = $value['singular_label'];
                    $api_name = $value['api_name'];
                    $module_name = $value['module_name'];
                    $moduleid = $value['id'];
                    $business_card_field_limit = $value['business_card_field_limit'];
                    $resultarray = $wpdb->get_results($wpdb->prepare("select *from zcf_zohocrm_list_module where api_name=%s and module_name=%s and module_id =%s", $api_name, $module_name, $moduleid));
                    if (count($resultarray) == 0) {
                        $fields = $wpdb->insert('zcf_zohocrm_list_module', array('api_supported' => "$api_supported", 'plural_label' => "$plural_label", 'api_name' => "$api_name", 'module_name' => "$module_name", 'module_id' => "$moduleid", 'business_card_field_limit' => "$business_card_field_limit", 'modifydate' => $configcreated_time));
                    } else {
                        $fields = $wpdb->update('zcf_zohocrm_list_module', array('api_supported' => "$api_supported", 'plural_label' => "$plural_label", 'api_name' => "$api_name", 'module_name' => "$module_name", 'module_id' => "$moduleid", 'business_card_field_limit' => "$business_card_field_limit", 'modifydate' => $configcreated_time), array('api_name' => "$api_name", 'module_id' => "$moduleid"));
                    }
                }

            return true;
        }
        public function zcfgetAssignmentRule($TFA_authtoken, $modulename) {
            global $wpdb;
            $zfformbaseurl = $this->apiurl . "/crm/v2/settings/assignment_rules/" . $modulename;
            $args = array(
                'timeout' => '5',
                'redirection' => '5',
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(
                        'authorization' => 'Zoho-oauthtoken ' . $TFA_authtoken
                    ),
                'cookies' => array()
                );
               $zfformsresponse =  wp_remote_get( $zfformbaseurl, $args );
               $responsedata = json_decode(wp_remote_retrieve_body($zfformsresponse),true);
                $configcreated_time = date("Y-m-d H:i:s");

            if(!empty($responsedata['assignment_rules'])){
              foreach ($responsedata['assignment_rules'] as $key => $value) {
                    $modulename = $value['module'];
                    $assignmentrrule_name = $value['name'];
                    $assignmentrule_ID = $value['id'];
                    $resultarray = $wpdb->get_results($wpdb->prepare("select *from zcf_zohocrm_assignmentrule where assignmentrule_ID =%s", $assignmentrule_ID));
                    if (count($resultarray) == 0) {
                        $fields = $wpdb->insert('zcf_zohocrm_assignmentrule', array('modulename' => "$modulename", 'assignmentrule_ID' => "$assignmentrule_ID", 'assignmentrrule_name' => "$assignmentrrule_name"));
                    } else {
                        $fields = $wpdb->update('zcf_zohocrm_assignmentrule', array('modulename' => "$modulename", 'assignmentrule_ID' => "$assignmentrule_ID", 'assignmentrrule_name' => "$assignmentrrule_name"), array('assignmentrule_ID' => "$assignmentrule_ID"));
                    }
                }
            }

            return true;
        }

        public function zcfGetUserRecord($modulename, $methodname, $authkey, $selectColumns = "", $xmlData = "", $extraParams = "") {
                $zfformbaseurl = $this->apiurl . "/crm/v2/users?type=AllUsers";
                $args = array(
                'body' => $xmlData,
                'timeout' => '5',
                'redirection' => '5',
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(
                        'authorization' => 'Zoho-oauthtoken ' . $authkey
                    ),
                'cookies' => array()
                );
               $zfformsresponse =  wp_remote_get( $zfformbaseurl, $args );
               $result_array = json_decode(wp_remote_retrieve_body($zfformsresponse),true);
            return $result_array;
        }

    }

}
?>
