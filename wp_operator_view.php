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
	return returnBindPageVue(plugin_dir_url( __FILE__ ),plugin_dir_path( __FILE__ ),'operator',array('operator-viewer-table'),array('action' => 'get_order_detail_admin'));
}

add_shortcode("vendita_chart","vendita_chart");

function vendita_chart(){
	return returnBindPageVue(plugin_dir_url( __FILE__ ),plugin_dir_path( __FILE__ ),'operator-cart',array('operator-viewer-cart'),array('action' => 'get_order_detail_admin'));
}


add_shortcode("vendita_eventi","vendita_eventi");

function vendita_eventi(){
	return returnBindPageVue(plugin_dir_url( __FILE__ ),plugin_dir_path( __FILE__ ),'operator',array('operator-viewer-table'),array('action' => 'get_num_vendite'));
}


add_shortcode("table_static_query","table_static_query");

function table_static_query(){
	include(plugin_dir_path( __FILE__ ).'/wp_operator_config.php');
	$arr=array();
	$arr['params']='""';
	if(isset($_REQUEST['query']) && QUERIES_PARAMS[$_REQUEST['query']]){
		$params=QUERIES_PARAMS[$_REQUEST['query']];
		$params_bind="";
		foreach($params as $key=>$par){
			if(isset($_REQUEST[$key])){
				$params[$key]=$_REQUEST[$key];
				$params_bind.='<div style="float:left"><label>'.$key.'</label><BR/><input v-model="params.'.$key.'" v-if="params.'.$key.'!=null" type="text" /></div>';
			}
			else{
				$params_bind.='<div style="float:left"><label>'.$key.'</label><BR/><input v-model="params.'.$key.'" v-if="params.'.$key.'!=null" type="text" /></div>';
			}
		}
		$arr['params_bind']=$params_bind.'<button @click="loadTableQuery">search</button>';
		$arr['params']=json_encode($params);
		
	}
	if(isset($_REQUEST['query']) && isset(QUERIES[$_REQUEST['query']]))
		$arr['query']=$_REQUEST['query'];
	return returnBindPageVue(plugin_dir_url( __FILE__ ),plugin_dir_path( __FILE__ ),'operator',array('operator-viewer-table'),$arr);
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


add_action( 'wp_ajax_nopriv_table_query','table_query');
add_action( 'wp_ajax_table_query','table_query');

function table_query(){
	if( is_user_logged_in() ) {
		$user = wp_get_current_user();
		$roles = ( array ) $user->roles;
		$role=$roles[0];
		if($role=='administrator' ||  $role=='author'){
			
			include(plugin_dir_path( __FILE__ ).'/wp_operator_config.php');
			if(isset($_REQUEST['query']) && isset(QUERIES[$_REQUEST['query']]))
				$sql=QUERIES[$_REQUEST['query']];
			global $wpdb;
			$pars=$_REQUEST;
			foreach($pars as $key=>$par){
				if($par!='query')
					$sql=str_replace("%".$key."%",$par,$sql);
			}
			$details=$wpdb->get_results($sql);
			tableToVue($details);
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

 add_action( 'wp_ajax_nopriv_get_num_vendite','get_num_vendite');
 add_action( 'wp_ajax_get_num_vendite','get_num_vendite');


 function get_num_vendite(){
	if( is_user_logged_in() ) {
		$user = wp_get_current_user();
		$roles = ( array ) $user->roles;
		$role=$roles[0];
		if($role=='administrator' ||  $role=='author'){
			global $wpdb;
			$sql="SELECT date_id,titolo,ts_inizio,COUNT(date_id) as numero, SUM(commissioni) as totale FROM wp_order_items WHERE status='PRINTED' OR status='SISAL-PAID' GROUP BY date_id";
			$details=$wpdb->get_results($sql);
			tableToVue($details);
		}
	}
	die();
 
 }

 add_action( 'wp_ajax_nopriv_chart_vendite','chart_vendite');
 add_action( 'wp_ajax_chart_vendite','chart_vendite');


 function chart_vendite(){
	if( is_user_logged_in() ) {
		$user = wp_get_current_user();
		$roles = ( array ) $user->roles;
		$role=$roles[0];
		if($role=='administrator' ||  $role=='author'){
			global $wpdb;
 			$sql='SELECT DATE_FORMAT(ts_upd,"%d/%m/%Y") as d,date_id,COUNT(id) as c,titolo FROM wp_order_items WHERE ts_upd>SUBDATE(NOW(), INTERVAL 1 MONTH) AND ts_upd<NOW() GROUP by date_id,DATE_FORMAT(ts_upd,"%Y%m%d") ORDER BY date_id';
			$det=$wpdb->get_results($sql);
			tableToChartVue($det);
		}
	}
	die();
 }

 function tableToChartVue($details){
	$old_date=(new DateTime('now'))->setTime(0,0,0)->sub(new DateInterval('P1M'));
	$now=(new DateTime('now'))->getTimestamp();
	$det_conv=[];
	$old_d="";
	$dist_value=[];
	$dist_value_arr=[];
	foreach($details as $det){
		if($det_conv[$det->d]==null){
			$det_conv[$old_d]=[];
		}
		if($det_conv[$det->d][$det->date_id]==null){
			$det_conv[$det->d][$det->date_id]=[];
		}
		$dist_value[$det->date_id]=$det->titolo;
		
		$det_conv[$det->d][$det->date_id]=$det;

	}
	$data_chart=array();
	while($old_date->getTimestamp()<=$now){
		$date_day=$old_date->format("d/m/Y");
		$dist_value_arr[]=$date_day;
		if($det_conv[$date_day]!=null){
			foreach($dist_value as $key=>$dist_v){
				if($data_chart[$key]==null){
					$data_chart[$key]=[];
				}
				if($det_conv[$date_day][$key]!=null)
					$data_chart[$key][$date_day]=$det_conv[$date_day][$key]->c;
				else
					$data_chart[$key][$date_day]=0;
					//var_dump($det_conv[$date_day][$key]);
			}
			
			
		}
		else{
			foreach($dist_value as $key=>$dist_v){
				if($data_chart[$key]==null){
					$data_chart[$key]=[];
				}
				$data_chart[$key][$date_day]=0;
			}
			
		}
		/*foreach($dist_value as $key=>$dist_v){
			$dist_value_arr[]=$key;
		}*/
		$old_date=$old_date->add(new DateInterval('P1D'));
	}
	$dataset=[];
	$x=0;
	foreach($data_chart as $key=>$data_c){
		$dataset[]=[];
		$dataset[$x]['label']=$dist_value[$key];
		$dataset[$x]['data']=[];
		$dataset[$x]['backgroundColor']='rgba(0, 0, 0, 0)';
		foreach($data_c as $key2=>$dat){
			$dataset[$x]['data'][]=$dat;
		}
		$x++;
	}
	$obj=new StdClass();
	$obj->datasets=$dataset;
	$obj->labels=$dist_value_arr;
	echo json_encode($obj);
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

