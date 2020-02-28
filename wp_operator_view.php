<?php
/*
Plugin Name: Operator View
Plugin URI: https://operator_view.com/
Description: plugin operator view
Version: 1.0.0
Author: Lionteck
Author URI: https://lionteck.com/wordpress-plugins/
License: GPLv2 or later
Text Domain: operator view
*/

add_shortcode("titoli_emessi","titoli_emessi");

function titoli_emessi(){
	return returnBindPageVue(plugin_dir_url( __FILE__ ),plugin_dir_path( __FILE__ ),'operator',array('operator-viewer-table'),array());
}



add_shortcode("titoli_emessi_prova","titoli_emessi_prova");

function titoli_emessi_prova(){
	return returnBindPageVue(plugin_dir_url( __FILE__ ),plugin_dir_path( __FILE__ ),'operator-prova',array('operator-viewer-table-prova'),array());
}



add_action( 'wp_ajax_nopriv_get_barcode_sisal_admin','get_barcode_sisal_admin');
add_action( 'wp_ajax_get_barcode_sisal_admin','get_barcode_sisal_admin');
function get_barcode_sisal_admin(){
	if( is_user_logged_in() ) {
		 $user = wp_get_current_user();
		 $roles = ( array ) $user->roles;
		 $role=$roles[0];
		 if($role=='administrator' ||  $role=='author'){
			 $ch = curl_init();  
			global $wpdb;
			$date_id=$_REQUEST['date_id'];
			$sql="SELECT * FROM wp_orders,wp_order_items WHERE date_id=$date_id AND wp_orders.id=wp_order_items.oid AND status='SISAL'";
			$pnr=$wpdb->get_results($sql);
			$pnrs=array();
			foreach($pnr as $p){
				array_push($pnrs,$p->pnr);
			}
			
			if(count($pnrs)!=0){
				$pnrs2=json_encode($pnrs);
				//echo $call;
				//var_dump($certs);
				curl_setopt_array($ch, array(
					CURLOPT_URL => GATEWAY_URL.'/5/get_barcode/1',
					CURLOPT_SSLKEY => GAT_KEY,
					CURLOPT_CAINFO => GAT_INFO,
					CURLOPT_CUSTOMREQUEST => 'POST',
					CURLOPT_SSLCERT=> GAT_CERT,
					CURLOPT_FOLLOWLOCATION => 1,
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_SSL_VERIFYHOST => 0,
					CURLOPT_SSL_VERIFYPEER => 0,
					 CURLOPT_POSTFIELDS => $pnrs2, 
					CURLOPT_HTTPHEADER => array(
								  'Content-Type: application/json'
						)
					)
				);								
				$result = gestione_response_curl($ch);
				$res_j=json_decode($result);
				

				foreach($res_j as $item){
					
					$sql="UPDATE wp_order_items SET barcode='".$item->barcode."', status='SISAL-PAID' WHERE pnr='".$item->pnr."'";
					log_gateway(true,$sql);
					$wpdb->query($sql);
				}
				
				$sql="UPDATE wp_order_items,wp_orders SET status='SISAL-EXPIRED' WHERE date_id=$date_id AND wp_orders.id=wp_order_items.oid AND ts_expire<NOW() AND status='SISAL'";
				$wpdb->query($sql);
			
				echo $result;
				die();
			}
			echo json_encode($pnrs);
		 }
	}
	 
	 die();
 }

 add_action( 'wp_ajax_nopriv_get_order_detail_admin','get_order_detail_admin');
 add_action( 'wp_ajax_get_order_detail_admin','get_order_detail_admin');

 
 function get_order_detail_admin(){
	if( is_user_logged_in() ) {
		$user = wp_get_current_user();
		$roles = ( array ) $user->roles;
		$role=$roles[0];
		if($role=='administrator' ||  $role=='author'){
			$date_id=$_REQUEST['date_id'];
			global $wpdb;
			$sql="CALL get_event_all($date_id)";
			$details=$wpdb->get_results($sql);
			tableToVue($details);
		}
	}
	die();
 
 }

 function tableToVue($details){
	$table=new stdClass();
	$table->table_headers=[];
    $table->table_datas=[];
    $table->values=[];
	$x=0;
	foreach($details as $detail){
        $table->table_datas[]=[];
        $table->values[]=[];
		foreach($detail as $key=>$d){
			if($x==0){
                $table->table_headers[]=$key;
                $table->values[$key]=[];
            }
            $table->values[$key][$d]=true;
			$table->table_datas[$x][$key]=$d;
		}
		$x++;
	}
	echo json_encode($table);
 }

