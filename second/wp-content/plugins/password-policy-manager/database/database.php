<?php
class MOPPM_DATABASE
{
    private $linkDetailsTable;
    function __construct()
    {
        global $wpdb;
        $this->userLoginInfoTable = $wpdb->base_prefix.'moppm_user_login_info';
        $this->Report_Table       = $wpdb->base_prefix.'moppm_user_report_table';
    }
    function plugin_activate()
    {
        add_site_option('moppm_activated_time', time());
        add_site_option('Moppm_enable_disable_ppm','on');
        global $wpdb;
        if ( ! get_site_option( 'moppm_dbversion' ) ) {
            update_site_option( 'moppm_dbversion', MOPPM_Constants::DB_VERSION );
            $this->generate_tables();
        } else {
            $current_db_version = get_site_option( 'moppm_dbversion' );
            if ( $current_db_version < MOPPM_Constants::DB_VERSION ) {

                update_site_option( 'moppm_dbversion', MOPPM_Constants::DB_VERSION );
                $this->generate_tables();
            }
        }
    }
   
    function generate_tables()
    {
        global $wpdb;
        $tableName = $this->userLoginInfoTable;
        if($wpdb->get_var("show tables like '$tableName'") != $tableName){
              $sql = "CREATE TABLE IF NOT EXISTS "  . $tableName . " (
            `session_id` mediumtext NOT NULL,  
             `Moppm_current_user_id` bigint NOT NULL ,
             `ts_created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
              PRIMARY KEY (`session_id`(100)));"; 
            dbDelta( $sql );
        }
        
            $tableName = $this->Report_Table;
            if($wpdb->get_var("show tables like '$tableName'") != $tableName) 
            {
                $sql = "CREATE TABLE " . $tableName . " (
                `id` int NOT NULL AUTO_INCREMENT, `user_email` mediumtext NOT NULL, `Login_time` mediumtext,
                `Logout_time` mediumtext, UNIQUE KEY id (id) );";
                dbDelta($sql);
            }   
       
    }
    function get_Report_list()
        {
           global $wpdb;
           $tableName = $this->Report_Table;
            return $wpdb->get_results("SELECT id,user_email,Login_time,Logout_time input FROM ".$tableName);
        }
    function insert_report_list($user_id,$email,$log_time,$log_out_time) 
    {
        global $wpdb;
        $sql = "INSERT INTO $this->Report_Table(id,user_email,Login_time,Logout_time) VALUES(%s,%s,%s,%s) ON DUPLICATE KEY UPDATE Login_time=%s";

        $query=$wpdb->prepare($sql,array($user_id,$email,$log_time,$log_out_time,$log_time));
        $wpdb->query( $query); 
            
    }
    function delete_report_list($user_id) 
    {
        global $wpdb;
        $query=$wpdb->prepare("DELETE FROM {$this->Report_Table} WHERE id = %s",array($user_id));
        $wpdb->query($query);
        return;  
    }
    function clear_report_list() 
    {
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->prefix}moppm_user_report_table");
        return;
    }

    function update_report_list($user_id,$log_out_time)
    { 
         global $wpdb;

         $sql1= "UPDATE $this->Report_Table SET Logout_time= %s  WHERE id = %s ";
         
         $query=$wpdb->prepare($sql1,array($log_out_time,$user_id));
         
         $wpdb->query($query);
    }


    function get_user_id_from_session($session_id)
    {
          global $wpdb;
          $sql="SELECT Moppm_current_user_id FROM $this->userLoginInfoTable WHERE session_id = '$session_id'";
          $results1 = $wpdb->get_results($sql);
          return $results1[0]->Moppm_current_user_id;
    }
    function insert_user_login_session( $session_id ,$user_id) 
    {
        global $wpdb;
        $sql = "INSERT INTO $this->userLoginInfoTable (session_id,Moppm_current_user_id) VALUES(%s,%s) ON DUPLICATE KEY UPDATE session_id=%s";
       
        $query=$wpdb->prepare($sql,array($session_id,$user_id,$session_id));
        $wpdb->query( $query);
       
        $sql = "DELETE FROM $this->userLoginInfoTable WHERE ts_created < DATE_ADD(NOW(),INTERVAL - 2 MINUTE);";
        $wpdb->query( $sql );
    }
    function delete_user_login_sessions($session_id ) {
        global $wpdb;

        $sql="DELETE FROM {$this->userLoginInfoTable} WHERE session_id=%s;";
        $query=$wpdb->prepare($sql,array($session_id));
        $wpdb->query($query);

        return;
    }
    
}
