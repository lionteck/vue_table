<?php

define("TOP_TEN_DAY",'SELECT DATE_FORMAT(ts_upd,"%d/%m/%Y") as d,a.date_id, c,titolo FROM (SELECT id,COUNT(id) as c,date_id,ts_upd,titolo FROM wp_order_items WHERE ts_upd>SUBDATE(NOW(), INTERVAL 1 MONTH) AND ts_upd<NOW() GROUP by date_id,DATE_FORMAT(ts_upd,"%Y%m%d") ORDER BY COUNT(id)  DESC LIMIT 5)a  ORDER BY c DESC');
define("TOP_TEN",'SELECT titolo, c as somma,commissioni FROM (SELECT id,COUNT(id) as c,date_id,SUM(commissioni) as commissioni,titolo FROM wp_order_items GROUP by date_id ORDER BY COUNT(id)  DESC LIMIT 10)a  ORDER BY c DESC');
define("TOP_TEN_INCASSO_COMMISSIONI",'SELECT titolo,location, c as somma,commissioni FROM (SELECT id,location,COUNT(id) as c,date_id,SUM(commissioni) as commissioni,titolo FROM wp_order_items GROUP by date_id ORDER BY commissioni  DESC LIMIT %numero%)a  ORDER BY commissioni DESC');
define("TOP_TEN_INCASSO",'SELECT titolo, c as somma,tot as totale FROM (SELECT id,COUNT(id) as c,date_id,SUM(commissioni+price+prev) as tot,titolo FROM wp_order_items GROUP by date_id ORDER BY tot  DESC LIMIT 10)a  ORDER BY totale DESC');
define("QUERIES_PARAMS",array('top_ten_incasso_commissioni'=>array('numero'=>10)));
define("QUERIES",array('top_ten_day'=>TOP_TEN_DAY,'top_ten'=>TOP_TEN,'top_ten_incasso_commissioni'=>TOP_TEN_INCASSO_COMMISSIONI,'top_ten_incasso'=>TOP_TEN_INCASSO));
