<?php
/*
Plugin Name: WibStats
Plugin URI: http://www.stillbreathing.co.uk/wordpress/wibstats-statistics-for-wordpress-mu/
Description: A simple statistics plugin for Wordpress and WPMU, in use at http://wibsite.com (hence the name).
Version: 0.5.5
Author: Chris Taylor
Author URI: http://www.stillbreathing.co.uk
Donate URI: http://www.stillbreathing.co.uk/donate/
*/

global $current_blog;
global $wibstats_start;

// start the session if the id is not set
if ( session_id() == "" ) {
	session_start();
}

// add the wp_foot action
if ( function_exists( "add_action" ) ) {
	add_action( "admin_menu", "wibstats_timelimit" );

	add_action( "wp_footer", "wibstats" );
	add_action( "admin_menu", "wibstats_add_admin" );
	add_action( "admin_head", "wibstats_admin_head" );
	
	// for activation
	register_activation_hook( __FILE__, 'wibstats_activate' );
	
	// load the global settings
	wibstats_globals();
	
	// create the shortcode
	wibstats_setup_shortcodes();
}

// ============================================================================================================
// Admin display functions

// add the admin menu option
function wibstats_add_admin() {
	add_menu_page( "Blog statistics by WibStats", "Blog statistics", 1, "wibstats", "wibstats_reports" ); 
	add_submenu_page( "wibstats", "Visitors from search engines", "Searches", 1, "wibstats-searches", "wibstats_searches_report" ); 
	add_submenu_page( "wibstats", "Visitors from referring websites", "Referrers", 1, "wibstats-referrers", "wibstats_referrers_report" ); 
	add_submenu_page( "wibstats", "Direct visitors", "Direct visitors", 1, "wibstats-direct", "wibstats_direct_report" ); 
	add_submenu_page( "wibstats", "Pages viewed", "Pages", 1, "wibstats-pages", "wibstats_content_report" ); 
	add_submenu_page( "wibstats", "Visitor locations", "Locations", 1, "wibstats-locations", "wibstats_locations_report" ); 
	add_submenu_page( "wibstats", "Visit times", "Times", 1, "wibstats-times", "wibstats_times_report" ); 
	add_submenu_page( "wibstats", "Visitor environment", "Environment", 1, "wibstats-environment", "wibstats_environment_report" ); 
	add_submenu_page( "options-general.php", "Blog statistics settings", "Blog statistics settings", 10, "wibstats-options", "wibstats_options" ); 
	global $wibstats_mu;
	if ( $wibstats_mu ) {
		add_submenu_page( "wpmu-admin.php", "Blog statistics settings", "Blog statistics settings", 10, "wibstats-siteoptions", "wibstats_siteadmin_options" ); 
	}
}

// add the CSS and JavaScript for the reports
function wibstats_admin_head() {
	if ( isset( $_GET["page"] ) && substr( $_GET["page"], 0, 8 ) == "wibstats" ) {
	
	// get the gmaps api key
	$wibstats_gmap_key = wibstats_gmap_key();
	
	if ( $wibstats_gmap_key != "" && 
		( @$_GET["view"] == "" || 
		@$_GET["view"] == "session" || 
		@$_GET["view"] == "countries" || 
		@$_GET["view"] == "page" || 
		@$_GET["view"] == "term" || 
		@$_GET["view"] == "referrer" || 
		@$_GET["view"] == "map" )
		 ) {
		echo '
<script type="text/javascript" src="http://maps.google.com/maps?file=api&amp;sensor=false&amp;key=' . $wibstats_gmap_key . '"></script>
<script type="text/javascript">
jQuery( document ).ready( function () {
	if ( typeof window.initializeMap == "function" ) {
		initializeMap();
	}
} );
</script>
';
	}
	
	echo '
<style type="text/css">
#wibstats .wibstats_col1 {
clear: left;
float: left;
width: 48%;
}
#wibstats .wibstats_col2 {
clear: right;
float: right;
width: 48%;
}
#wibstats .wibstats_col31 {
clear: left;
float: left;
width: 32%;
}
#wibstats .wibstats_col32 {
float: left;
width: 32%;
margin-left: 1.5%;
}
#wibstats .wibstats_col33 {
clear: right;
float: right;
width: 32%;
}
#wibstats .wibstats_clear {
clear: both;
margin-top: 1em;
}
#wibstats td {
vertical-align: bottom;
}
#wibstats ul.inline li {
display: inline;
margin-right: 2em;
}
.wibstats_clear {
clear: both;
}
#wibstatsmenu {
float: right;
}
#wibstatsmenu li {
float: left;
margin-left: 1em;
text-align: center;
}
#wibstatsmenu li a {
text-decoration: none;
}
</style>';
	}
}
	

// show site admin options
function wibstats_siteadmin_options() {
	global $wibstats_mu;

	if ( $wibstats_mu && is_site_admin() ) {
	
		wibstats_report_header();

		// if saving options
		if ( count( $_POST ) > 0 ) {
			update_site_option( "wibstats_gmap_key", trim( @$_POST["wibstats_gmap_key"] ) );
			echo '
			<div class="updated"><p>' . __( "Site options updated", "wibstats" ) . '</p></div>
			';
		}
		
		$wibstats_gmap_key = get_site_option( "wibstats_gmap_key" );
		
		echo '<h2><a href="admin.php?page=wibstats">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; ' . __( "Change site admin options", "wibstats" ) . '</h2>
		<form action="wpmu-admin.php?page=wibstats-siteoptions" method="post">
		<p>' . __( 'For maps to be shown you must <a href="http://code.google.com/apis/maps/signup.html">sign up for a Google maps API key</a>.', "wibstats" ) . '</p>
		<p><label for="wibstats_gmap_key">' . __( "Google maps key", "wibstats" ) . '</label>
		<input type="text" name="wibstats_gmap_key" id="wibstats_gmap_key" value="' . $wibstats_gmap_key . '" /></p>
		<p><label for="save">' . __( "Save options", "wibstats" ) . '</label>
		<button type="submit" name="save" id="save" class="button">' . __( "Save options", "wibstats" ) .'</button></p>
		</form>
		';
		
		echo '<h2>' . __( "Choose a blog to view their statistics", "wibstats" ) . '</h2>
		<form action="admin.php?page=wibstats" method="post">
		<p><label for="blogid">' . __( "Choose blog", "wibstats" ) . '</label>
		<select name="blogid" id="blogid">
		';
		$blog_list = get_blog_list( 0, 'all' );
		foreach ( $blog_list AS $blog ) {
			echo '<option value="'.$blog['blog_id'].'"';
			if ( $blog['blog_id'] == $blogid ) {
				echo ' selected="selected"';
			}
			echo '>'.$blog['domain'].trim( $blog['path'], '/' ).' ( '.$blog['blog_id'].' )</option>
			';
		}
		echo '
		</select></p>
		<p><label for="go">' . __( "View statistics", "wibstats" ) . '</label>
		<button type="submit" name="go" id="go" class="button">' . __( "View statistics", "wibstats" ) .'</button></p>
		</form>
		';
		
		wibstats_report_footer();
	}
}

// show options
function wibstats_options() {

	wibstats_report_header();

	// if saving options
	global $wibstats_mu;
	if ( count( $_POST ) > 0 ) {
		update_option( "wibstats_time_offset", trim( @$_POST["wibstats_time_offset"] ) );
		if (!$wibsite_mu) {
			update_option( "wibstats_gmap_key", trim( @$_POST["wibstats_gmap_key"] ) );
		}
		echo '
		<div class="updated"><p>' . __( "Options updated", "wibstats" ) . '</p></div>
		';
	}
	
	$wibstats_time_offset = ( int )get_option( "wibstats_time_offset" );
	$wibstats_gmap_key = wibstats_gmap_key();
	
	echo '<h2><a href="admin.php?page=wibstats">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; ' . __( "Change options", "wibstats" ) . '</h2>
	<form action="options-general.php?page=wibstats-options" method="post">
	<p>' . __( 'Choose the correct time below, this will make sure you see the time of visitors relative to your time.', "wibstats" ) . '</p>
	<p><label for="wibstats_time_offset">' . __( "Your current time", "wibstats" ) . '</label>
	<select name="wibstats_time_offset" id="wibstats_time_offset">
	';
	
	for ( $i = -12; $i < 13; $i++ ) {
	
		echo '
	<option value="' . $i . '"';
	if ( ( int )$wibstats_time_offset == ( int )$i ) { echo ' selected="selected"'; }
	echo '>' . date( "F j, Y, g:i a", time() + ( $i * 60 * 60 ) ) . '</option>
		';
	
	}
	
	echo '
	</select></p>
	';
	if (!$wibstats_mu) {
	echo '
	<p>' . __( 'For maps to be shown you must <a href="http://code.google.com/apis/maps/signup.html">sign up for a Google maps API key</a>.', "wibstats" ) . '</p>
	<p><label for="wibstats_gmap_key">' . __( "Google maps key", "wibstats" ) . '</label>
	<input type="text" name="wibstats_gmap_key" id="wibstats_gmap_key" value="' . $wibstats_gmap_key . '" /></p>
	';
	}
	echo '
	<p><label for="save">' . __( "Save options", "wibstats" ) . '</label>
	<button type="submit" name="save" id="save" class="button">' . __( "Save options", "wibstats" ) .'</button></p>
	</form>
	';
	
	wibstats_report_footer();

}

function wibstats_gmap_key() {
	global $wibstats_mu;
	if ( $wibstats_mu ) {
		return get_site_option( "wibstats_gmap_key" );
	} else {
		return get_option( "wibstats_gmap_key" );
	}
}

// ============================================================================================================
// General visitor functions

// show a session report
function wibstats_session_report() {
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; ' . __( "Visitor session report", "wibstats" ) . '</h2>
	';
	
	// get the details for this visitor
	$sql = wibstats_sql( "session", 0, 0, "session=" . $_GET["session"] );
	$details = $wpdb->get_row( $sql );
	
	echo '
	<div class="wibstats_col1">
		<h4>' . __( "Location", "wibstats" ) . '</h4>
		<p>' . __( "Country", "wibstats" ) . ': ' . wibstats_country_icon( $details->country, $details->countrycode ) . ' ' . $details->country . '</p>
		<p>' . __( "City", "wibstats" ) . ': ' . $details->city . '</p>
		<h4>' . __( "Referrer", "wibstats" ) . '</h4>
		<p>' . __( "Referrer", "wibstats" ) . ': <a href="' . $details->referrer . '">' . $details->referrer_domain . '</a></p>
		<p>' . __( "Search terms", "wibstats" ) . ': <a href="admin.php?page=wibstats-searches&amp;view=term&amp;term=' . urlencode( $details->terms ) . '">' . $details->terms . '</a></p>
		<p>' . __( "Entry page", "wibstats" ) . ': <a href="admin.php?page=wibstats-pages&amp;view=page&amp;url=' . urlencode( $details->page ) . '">' . $details->title . '</a></p>
		<h4>' . __( "Visitor environment", "wibstats" ) . '</h4>
		<p>' . __( "Operating system", "wibstats" ) . ': ' . $details->platform . '</p>
		<p>' . __( "Browser", "wibstats" ) . ': ' . $details->browser . ' ' . $details->version . '</p>
		<p>' . __( "Screen size", "wibstats" ) . ': ' . $details->screensize . '</p>
		<p>' . __( "Color depth", "wibstats" ) . ': ' . $details->colordepth . 'bit</p>
	</div>
	';
	
	// get the gmaps api key
	$wibstats_gmap_key = wibstats_gmap_key();
	
	// if the key is set
	if ( $wibstats_gmap_key != "" && $details->latitude != "" && ( int )$details->latitude <> 0 && $details->longitude != "" && ( int )$details->latitude <> 0 ) {
		echo '
	<div class="wibstats_col2">
		<div id="map" style="height:300px;width:100%"></div>
		<script type="text/javascript">
			function initializeMap() {
			var map = new google.maps.Map2( document.getElementById( "map" ) );
			map.setCenter( new google.maps.LatLng( ' . $details->latitude . ', ' . $details->longitude . ' ), 9 );
			map.addControl( new GMapTypeControl() );
			map.addControl( new GSmallMapControl() );
			}
		</script>
		<p>Latitude:' . $details->latitude . ', Longitude: ' . $details->longitude . '</p>
	</div>
		';
	} else {
		echo '
	<div class="wibstats_col2">
		<p>' . __( "Sorry, the map cannot be shown for this visitor", "wibstats" ) . '</p>
	</div>
		';
	}
	
	// get the pages viewed in this session
	$sql = wibstats_sql( "session_pages", 0, 0, "session=" . $_GET["session"] );
	$pages = $wpdb->get_results( $sql );
	
	echo '
	<h3 class="wibstats_clear">' . __( "Pages viewed: ", "wibstats" ) . count( $pages ) . '</h3>
	';
	
	wibstats_table_header( array( "Page", "Time" ) );
	
	foreach( $pages as $page ) {
		echo '
		<tr>
			<td><a href="admin.php?page=wibstats-pages&amp;view=page&amp;url=' . urlencode( $page->page ) . '">' . wibstats_shorten( $page->title ) . '</a></td>
			<td>' . wibstats_date( $page->timestamp ) . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
	
	$sql = wibstats_sql( "ip_sessions", 50, 0, "ipaddress=" . $details->ipaddress . "&sessionid=" . $details->sessionid );
	$visits = $wpdb->get_results( $sql );
	$limiter = "";
	$total = wibstats_total_rows();
	if ( $total > 50 ){ $limiter = " " . __( "( last 50 shown )", "wibstats" ); }
	
	if ( $visits && is_array( $visits ) && count( $visits ) > 0 ) {
		echo '
		<h3>' . __( "Other visits by this IP address: ", "wibstats" ) . $total . $limiter . '</h3>
		';
		
		wibstats_table_header( array( "Referrer", "Search terms", "Time" ) );
		
		foreach( $visits as $visit )
		{
			echo '
			<tr>
				<td><a href="' . $visit->referrer . '">' . $visit->referrer_domain . '</a></td>
				<td><a href="admin.php?page=wibstats-searches&amp;view=term&amp;term=' . urlencode( $visit->terms ) . '">' . $visit->terms . '</a></td>
				<td><a href="admin.php?page=wibstats&amp;view=session' . $bloglink . '&amp;session=' . $visit->sessionid . '">' . wibstats_date( $visit->timestamp ) . '</a></td>
			</tr>
			';
		}
		
		wibstats_table_footer();
	}
}

// show small recent visitor map
function wibstats_small_recentvisitor_map( $num = 10 ) {
	echo '<h3>' . __( "Recent visitor map", "wibstats" ) . ' ( <a href="admin.php?page=wibstats-locations&view=map'.$bloglink.'">' . __( "more", "wibstats" ) . '</a> )</h3>';
	wibstats_recentvisitor_map( $num, false );
}

// show recent visitor map
function wibstats_recentvisitor_map( $num = 25, $headers = true ) {
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();	
	
	// get the gmaps api key
	$wibstats_gmap_key = wibstats_gmap_key();

	$sql = wibstats_sql( "recent_visitor_locations", $num );
	$visitors = $wpdb->get_results( $sql );
	
	if ( $headers ) {
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; <a href="admin.php?page=wibstats-locations'.$bloglink.'">' . __( "Visitor locations", "wibstats" ) . '</a> &raquo; ' . __( "Recent visitor map", "wibstats" ) . '</h2>
	';
	}
	
	if ( $visitors && is_array( $visitors ) && count( $visitors ) > 0 ) {
	
		// if the key is set
		if ( $wibstats_gmap_key != "" ) {
		
			echo '
		<div id="map" style="height:500px;width:100%"></div>
		<script type="text/javascript">
			function initializeMap() {
			var map = new google.maps.Map2( document.getElementById( "map" ) );
			map.setCenter( new google.maps.LatLng( 19.554270144063956,-9.4921875 ), 2 );
			map.addControl( new GMapTypeControl() );
			map.addControl( new GSmallMapControl() );
			';
			
			foreach( $visitors as $visitor ) {
				echo '
				
		var i' . $visitor->sessionid . ' = new GLatLng( ' . $visitor->latitude . ',' . $visitor->longitude .' );
		var m' . $visitor->sessionid . ' = new GMarker( i' . $visitor->sessionid . ' );
		GEvent.addListener( m' . $visitor->sessionid . ', "click", function() {
			m' . $visitor->sessionid . '.openInfoWindowHtml( \'<p>' . wibstats_country_icon( $visitor->country, $visitor->countrycode ) . $visitor->country . '<br />' . $visitor->city . '<br />' . wibstats_date( $visitor->timestamp ) . '<br /><a href="admin.php?page=wibstats&amp;view=session&amp;session=' . $visitor->sessionid . '">View details</a></p>\' );
		} );
		map.addOverlay( m' . $visitor->sessionid . ' );
				';
			}

			echo '
			}
		</script>
			';
		}
		
		wibstats_table_header( array( "Country", "City", "Time" ) );
		
		foreach( $visitors as $visitor ) {
			echo '
			<tr>
				<td>' . wibstats_country_icon( $visitor->country, $visitor->countrycode ) . $visitor->country . '</td>
				<td>' . $visitor->city . '</td>
				<td><a href="admin.php?page=wibstats&amp;view=session&amp;session=' . $visitor->sessionid . '">' . wibstats_date( $visitor->timestamp ) . '</a></td>
			</tr>
			';
		}
		
		wibstats_table_footer();
		
	} else {
	
		echo '
		<p>' . __( "No countries found", "wibstats" ) . '</p>
		';
	
	}
}

// show general visitors report
function wibstats_visitor_report( $totalvisitors, $totalpages ) {
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	// Totals
	
		// get total number of visitors
		$sql = wibstats_sql( "total_visitors" );
		$totalvisitors = $wpdb->get_var( $sql );
		
		// get total number of page views
		$sql = wibstats_sql( "total_pageviews" );
		$totalpageviews = $wpdb->get_var( $sql );
		
		// get total number of search visitors
		$sql = wibstats_sql( "total_search_visitors" );
		$totalsearchvisitors = $wpdb->get_var( $sql );
		
		// get total number of referrer visitors
		$sql = wibstats_sql( "total_referrer_visitors" );
		$totalreferrervisitors = $wpdb->get_var( $sql );
		
		// get total number of direct visitors
		$sql = wibstats_sql( "total_direct_visitors" );
		$totaldirectvisitors = $wpdb->get_var( $sql );

	// Last 28 days
		
		$start = time() - ( 60 * 60 * 24 * 28 );
		$start2 = time() - ( 60 * 60 * 24 * 28 * 2 );
		
		// get total number of visitors in the last 28 days
		$sql = wibstats_sql( "total_visitors_since", 0, 0, "since=".$start );
		$visitors28days = $wpdb->get_var( $sql );
		
		// get total number of search visitors in the last 28 days
		$sql = wibstats_sql( "total_search_visitors_since", 0, 0, "since=".$start );
		$totalsearchvisitors28days = $wpdb->get_var( $sql );
		
		// get total number of referrer visitors in the last 28 days
		$sql = wibstats_sql( "total_referrer_visitors_since", 0, 0, "since=".$start );
		$totalreferrervisitors28days = $wpdb->get_var( $sql );
		
		// get total number of direct visitors in the last 28 days
		$sql = wibstats_sql( "total_direct_visitors_since", 0, 0, "since=".$start );
		$totaldirectvisitors28days = $wpdb->get_var( $sql );
		
		// get total number of visitors in the previous 28 days
		$sql = wibstats_sql( "total_visitors_between", 0, 0, "start=".$start2."&end=".$start );
		$visitors28daysold = $wpdb->get_var( $sql );
		
		// get total number of page views in the last 28 days
		$sql = wibstats_sql( "total_pageviews_since", 0, 0, "since=".$start );
		$pages28days = $wpdb->get_var( $sql );
		
		// get total number of page views in the previous 28 days
		$sql = wibstats_sql( "total_pageviews_between", 0, 0, "start=".$start2."&end=".$start );
		$pages28daysold = $wpdb->get_var( $sql );
	
	// Last 7 days
	
		$start = time() - ( 60 * 60 * 24 * 7 );
		$start2 = time() - ( 60 * 60 * 24 * 7 * 2 );
		
		// get total number of visitors in the last 7 days
		$sql = wibstats_sql( "total_visitors_since", 0, 0, "since=".$start );
		$visitors7days = $wpdb->get_var( $sql );
		
		// get total number of search visitors in the last 7 days
		$sql = wibstats_sql( "total_search_visitors_since", 0, 0, "since=".$start );
		$totalsearchvisitors7days = $wpdb->get_var( $sql );
		
		// get total number of referrer visitors in the last 7 days
		$sql = wibstats_sql( "total_referrer_visitors_since", 0, 0, "since=".$start );
		$totalreferrervisitors7days = $wpdb->get_var( $sql );
		
		// get total number of direct visitors in the last 7 days
		$sql = wibstats_sql( "total_direct_visitors_since", 0, 0, "since=".$start );
		$totaldirectvisitors7days = $wpdb->get_var( $sql );
		
		// get total number of visitors in the previous 7 days
		$sql = wibstats_sql( "total_visitors_between", 0, 0, "start=".$start2."&end=".$start );
		$visitors7daysold = $wpdb->get_var( $sql );
		
		// get total number of page views in the last 7 days
		$sql = wibstats_sql( "total_pageviews_since", 0, 0, "since=".$start );
		$pages7days = $wpdb->get_var( $sql );
		
		// get total number of page views in the previous 7 days
		$sql = wibstats_sql( "total_pageviews_between", 0, 0, "start=".$start2."&end=".$start );
		$pages7daysold = $wpdb->get_var( $sql );
	
	// Last 24 hours
	
	$start = time() - ( 60 * 60 * 24 );
	$start2 = time() - ( 60 * 60 * 24 * 2 );
	
		// get total number of visitors in the last 24 hours
		$sql = wibstats_sql( "total_visitors_since", 0, 0, "since=".$start );
		$visitors24hours = $wpdb->get_var( $sql );
		
		// get total number of search visitors in the last 24 hours
		$sql = wibstats_sql( "total_search_visitors_since", 0, 0, "since=".$start );
		$totalsearchvisitors24hours = $wpdb->get_var( $sql );
		
		// get total number of referrer visitors in the last 24 hours
		$sql = wibstats_sql( "total_referrer_visitors_since", 0, 0, "since=".$start );
		$totalreferrervisitors24hours = $wpdb->get_var( $sql );
		
		// get total number of direct visitors in the last 24 hours
		$sql = wibstats_sql( "total_direct_visitors_since", 0, 0, "since=".$start );
		$totaldirectvisitors24hours = $wpdb->get_var( $sql );
		
		// get total number of visitors in the previous 24 hours
		$sql = wibstats_sql( "total_visitors_between", 0, 0, "start=".$start2."&end=".$start );
		$visitors24hoursold = $wpdb->get_var( $sql );
		
		// get total number of page views in the last 24 hours
		$sql = wibstats_sql( "total_pageviews_since", 0, 0, "since=".$start );
		$pages24hours = $wpdb->get_var( $sql );
		
		// get total number of page views in the previous 24 hours
		$sql = wibstats_sql( "total_pageviews_between", 0, 0, "start=".$start2."&end=".$start );
		$pages24hoursold = $wpdb->get_var( $sql );
	
	// visitors change
	$changevisitors28days = "";
	if ( $visitors28days > 0 && $visitors28daysold > 0 ) {
		$changevisitors28days = ' ' . wibstats_change( round( ( $visitors28days/$visitors28daysold )*100, 2 ) - 100 );
	}
	$changevisitors7days = "";
	if ( $visitors7days > 0 && $visitors7daysold > 0 ) {
		$changevisitors7days = ' ' . wibstats_change( round( ( $visitors7days/$visitors7daysold )*100, 2 ) - 100 );
	}
	$changevisitors24hours = "";
	if ( $visitors24hours > 0 && $visitors24hoursold > 0 ) {
		$changevisitors24hours = ' ' . wibstats_change( round( ( $visitors24hours/$visitors24hoursold )*100, 2 ) - 100 );
	}
	
	// page change
	$changepages28days = "";
	if ( $pages28days > 0 && $pages28daysold > 0 ) {
		$changepage28days = ' ' . wibstats_change( round( ( $pages28days/$pages28daysold )*100, 2 ) - 100 );
	}
	$changepages7days = "";
	if ( $pages7days > 0 && $pages7daysold > 0 ) {
		$changepages7days = ' ' . wibstats_change( round( ( $pages7days/$pages7daysold )*100, 2 ) - 100 );
	}
	$changepages24hours = "";
	if ( $pages24hours > 0 && $pages24hoursold > 0 ) {
		$changepages24hours = ' ' . wibstats_change( round( ( $pages24hours/$pages24hoursold )*100, 2 ) - 100 );
	}
	
	// pages per visit
	$totalpagesvisit = "";
	if ( $totalvisitors > 0 && $totalpageviews > 0 ) {
		$totalpagesvisit = round( ( $totalpageviews / $totalvisitors ), 2 );
	}
	$pagesvisit28days = "";
	if ( $visitors28days > 0 && $visitors28daysold > 0 && $pages28days > 0 && $pages28daysold > 0 ) {
		$pagesvisit28days = round( ( $pages28days / $visitors28days ), 2 );
		$pagesvisit28daysold = round( ( $pages28daysold / $visitors28daysold ), 2 );
		$changepagesvisit28days = ' ' . wibstats_change( round( ( $pagesvisit28days/$pagesvisit28daysold )*100, 2 ) - 100 );
	}
	$pagesvisit7days = "";
	if ( $visitors7days > 0 && $visitors7daysold > 0 && $pages7days > 0 && $pages7daysold > 0 ) {
		$pagesvisit7days = round( ( $pages7days / $visitors7days ), 2 );
		$pagesvisit7daysold = round( ( $pages7daysold / $visitors7daysold ), 2 );
		$changepagesvisit7days = ' ' . wibstats_change( round( ( $pagesvisit7days/$pagesvisit7daysold )*100, 2 ) - 100 );
	}
	$pagesvisit24hours = "";
	if ( $visitors24hours > 0 && $visitors24hoursold > 0 && $pages24hours > 0 && $pages24hoursold > 0 ) {
		$pagesvisit24hours = round( ( $pages24hours / $visitors24hours ), 2 );
		$pagesvisit24hoursold = round( ( $pages24hoursold / $visitors24hoursold ), 2 );
		$changepagesvisit24hours = ' ' . wibstats_change( round( ( $pagesvisit24hours/$pagesvisit24hoursold )*100, 2 ) - 100 );
	}
	
	// search percentages
	$searchpercent = "";
	if ( $totalsearchvisitors > 0 && $totalvisitors > 0 ) {
		$searchpercent = ' ( '  . ( round( ( $totalsearchvisitors/$totalvisitors )*100, 2 ) ) . '% )';
	}
	$searchpercent28days = "";
	if ( $totalsearchvisitors28days > 0 && $visitors28days > 0 ) {
		$searchpercent28days = ' ( '  . ( round( ( $totalsearchvisitors28days/$visitors28days )*100, 2 ) ) . '% )';
	}
	$searchpercent7days = "";
	if ( $totalsearchvisitors7days > 0 && $visitors7days > 0 ) {
		$searchpercent7days = ' ( '  . ( round( ( $totalsearchvisitors7days/$visitors7days )*100, 2 ) ) . '% )';
	}
	$searchpercent24hours = "";
	if ( $totalsearchvisitors24hours > 0 && $visitors24hours > 0 ) {
		$searchpercent24hours = ' ( '  . ( round( ( $totalsearchvisitors24hours/$visitors24hours )*100, 2 ) ) . '% )';
	}
	
	// referrer percentages
	$referrerpercent = "";
	if ( $totalreferrervisitors > 0 && $totalvisitors > 0 ) {
		$referrerpercent = ' ( '  . ( round( ( $totalreferrervisitors/$totalvisitors )*100, 2 ) ) . '% )';
	}
	$referrerpercent28days = "";
	if ( $totalreferrervisitors28days > 0 && $visitors28days > 0 ) {
		$referrerpercent28days = ' ( '  . ( round( ( $totalreferrervisitors28days/$visitors28days )*100, 2 ) ) . '% )';
	}
	$referrerpercent7days = "";
	if ( $totalreferrervisitors7days > 0 && $visitors7days > 0 ) {
		$referrerpercent7days = ' ( '  . ( round( ( $totalreferrervisitors7days/$visitors7days )*100, 2 ) ) . '% )';
	}
	$referrerpercent24hours = "";
	if ( $totalreferrervisitors24hours > 0 && $visitors24hours > 0 ) {
		$referrerpercent24hours = ' ( '  . ( round( ( $totalreferrervisitors24hours/$visitors24hours )*100, 2 ) ) . '% )';
	}
	
	// direct percentages
	$directpercent = "";
	if ( $totaldirectvisitors > 0 && $totalvisitors > 0 ) {
		$directpercent = ' ( '  . ( round( ( $totaldirectvisitors/$totalvisitors )*100, 2 ) ) . '% )';
	}
	$directpercent28days = "";
	if ( $totaldirectvisitors28days > 0 && $visitors28days > 0 ) {
		$directpercent28days = ' ( '  . ( round( ( $totaldirectvisitors28days/$visitors28days )*100, 2 ) ) . '% )';
	}
	$directpercent7days = "";
	if ( $totaldirectvisitors7days > 0 && $visitors7days > 0 ) {
		$directpercent7days = ' ( '  . ( round( ( $totaldirectvisitors7days/$visitors7days )*100, 2 ) ) . '% )';
	}
	$directpercent24hours = "";
	if ( $totaldirectvisitors24hours > 0 && $visitors24hours > 0 ) {
		$directpercent24hours = ' ( '  . ( round( ( $totaldirectvisitors24hours/$visitors24hours )*100, 2 ) ) . '% )';
	}
	
	// show the overal number of visitors
	echo '
	<h2>' . __( "Blog statistics", "wibstats" ) . '</h2>
	';
	
	wibstats_table_header( array( "", "Ever", "In the last 28 days", "In the last 7 days", "In the last 24 hours" ) );
	
	echo '
		<tr>
			<th>' . __( "Unique visitors", "wibstats" ) . '</th>
			<td>' . $totalvisitors . '</td>
			<td>' . $visitors28days . $changevisitors28days . '</td>
			<td>' . $visitors7days . $changevisitors7days . '</td>
			<td>' . $visitors24hours . $changevisitors24hours . '</td>
		</tr>
		<tr>
			<th>&raquo; ' . __( "From searches", "wibstats" ) . '</th>
			<td>' . $totalsearchvisitors . $searchpercent . '</td>
			<td>' . $totalsearchvisitors28days . $searchpercent28days . '</td>
			<td>' . $totalsearchvisitors7days . $searchpercent7days . '</td>
			<td>' . $totalsearchvisitors24hours . $searchpercent24hours . '</td>
		</tr>
		<tr>
			<th>&raquo; ' . __( "From referrers", "wibstats" ) . '</th>
			<td>' . $totalreferrervisitors . $referrerpercent . '</td>
			<td>' . $totalreferrervisitors28days . $referrerpercent28days . '</td>
			<td>' . $totalreferrervisitors7days . $referrerpercent7days . '</td>
			<td>' . $totalreferrervisitors24hours . $referrerpercent24hours . '</td>
		</tr>
		<tr>
			<th>&raquo; ' . __( "Direct visitors", "wibstats" ) . '</th>
			<td>' . $totaldirectvisitors . $directpercent . '</td>
			<td>' . $totaldirectvisitors28days . $directpercent28days . '</td>
			<td>' . $totaldirectvisitors7days . $directpercent7days . '</td>
			<td>' . $totaldirectvisitors24hours . $directpercent24hours . '</td>
		</tr>
		<tr>
			<th>' . __( "Page views", "wibstats" ) . '</th>
			<td>' . $totalpages . '</td>
			<td>' . $pages28days . $changepages28days . '</td>
			<td>' . $pages7days . $changepages7days . '</td>
			<td>' . $pages24hours . $changepages24hours . '</td>
		</tr>
		<tr>
			<th>' . __( "Average pages/visitor", "wibstats" ) . '</th>
			<td>' . $totalpagesvisit . '</td>
			<td>' . $pagesvisit28days . $changepagesvisit28days . '</td>
			<td>' . $pagesvisit7days . $changepagesvisit7days . '</td>
			<td>' . $pagesvisit24hours . $changepagesvisit24hours . '</td>
		</tr>
	';
	
	wibstats_table_footer();
}

// ============================================================================================================
// General reporting functions

// load the reports page
function wibstats_reports() {
	global $wibstats_mu;
	global $current_blog;
	
	// check wibstats is installed
	wibstats_check( true );
	
	if ( isset( $_POST["recreate"] ) && $_POST["recreate"] != "" ) {
		wibstats_createtables05( true );
		echo '
		<p class="updated fade">' . __( "Your statistics database table has been recreated. Your statistics should start working soon.", "wibstats" ) . '</p>
		';
	}
	
	wibstats_report_header();
	
	// check the Google maps API key has been entered
	$wibstats_gmap_key = wibstats_gmap_key();
	if ( ( ( $wibstats_mu && is_site_admin() ) || !$wibstats_mu ) && $wibstats_gmap_key == "" ) {
		echo '
		<div class="updated"><p>' . __( 'For maps to be shown you must <a href="http://code.google.com/apis/maps/signup.html">sign up for a Google maps API key</a>.', "wibstats" ) . '</p></div>
		';
	}	
	
	// create demo data
	if ( $_GET["view"] == "demodata" ) {
		wibstats_demodata();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_demodata()" );
	}
	else

	// show a session
	if ( $_GET["view"] == "session" && isset( $_GET["session"] ) && $_GET["session"] != "" ) {
		wibstats_session_report();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_session_report()" );
	}
	else
	{
		// show the dashboard
		wibstats_dashboard_report();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_dashboard_report()" );
	}
	
	wibstats_report_footer();
}

// show the wibstats dashboard report
function wibstats_dashboard_report()
{
	global $wpdb;
	global $wibstats_mu;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	// get total number of visitors ever	
	$sql = wibstats_sql( "total_visitors" );
	$totalvisitors = $wpdb->get_var( $sql );
	
	// get total number of pageviews ever
	$sql = wibstats_sql( "total_pageviews" );
	$totalpages = $wpdb->get_var( $sql );
	
	// if there have been visitors
	if ( $totalvisitors != "" && $totalvisitors != 0 && $totalpages != "" && $totalpages != 0 ) {
	
		// show general visitor report
		wibstats_visitor_report( $totalvisitors, $totalpages );
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_visitor_report()" );
		
		// show date range reports
		echo '
		<h3>' . __( "Date range reports", "wibstats" ) . '</h3>
		
		<ul class="inline">
			<li><a href="admin.php?page=wibstats#range24hours">' . __( "Last 24 hours", "wibstats" ) . '</a></li>
			<li><a href="admin.php?page=wibstats&amp;range=14days#range14days">' . __( "Last 14 days", "wibstats" ) . '</a></li>
			<li><a href="admin.php?page=wibstats&amp;range=12weeks#range12weeks">' . __( "Last 12 weeks", "wibstats" ) . '</a></li>
			<li><a href="admin.php?page=wibstats&amp;range=12months#range12months">' . __( "Last 12 months", "wibstats" ) . '</a></li>
		</ul>

		';
		
		// show 24 hour report
		if ( !isset( $_GET["range"] ) ) {
			
			wibstats_24hour_report();
			
			// do the stopwatch
			wibstats_stopwatch( "wibstats_24hour_report()" );
			
		}
		
		// show 14 day report
		if ( isset( $_GET["range"] ) && $_GET["range"] =="14days" ) {
			
			wibstats_14day_report();
			
			// do the stopwatch
			wibstats_stopwatch( "wibstats_14day_report()" );
			
		}
		
		// show 12 week report
		if ( isset( $_GET["range"] ) && $_GET["range"] =="12weeks" ) {
		
			wibstats_12week_report();
			
			// do the stopwatch
			wibstats_stopwatch( "wibstats_12week_report()" );
			
		}
		
		// show 12 month report
		if ( isset( $_GET["range"] ) && $_GET["range"] =="12months" ) {
		
			wibstats_12month_report();
			
			// do the stopwatch
			wibstats_stopwatch( "wibstats_12month_report()" );
			
		}
		
		// show the recent visitor location map report
		wibstats_small_recentvisitor_map();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_recentvisitor_map()" );

		// get new report
		wibstats_new_report();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_new_report()" );
		
	} else {
	
		echo '<h2>' . __( "Blog statistics", "wibstats" ) . '</h2>
		<p>' . __( "Sorry, it looks like you haven't had any visitors yet.<br /><br />If you think this is wrong, and you have had some visitors, please click the button below which will recreate the database table that stores your visitor information. This *may* completely remove all information for visitors you've already had ( depending on which part of the database is broken ).", "wibstats" ) . '</p>
		<form action="admin.php?page=wibstats" method="post">
		';

		if ( $wibstats_mu && is_site_admin() ) {
			echo '
			<p><label for="blogid">' . __( "Choose blog", "wibstats" ) . '</label>
			<select name="blogid" id="blogid">
			';
			$blog_list = get_blog_list( 0, 'all' );
			foreach ( $blog_list AS $blog ) {
				echo '<option value="'.$blog['blog_id'].'"';
				if ( $blog['blog_id'] == $blogid ) {
					echo ' selected="selected"';
				}
				echo '>'.$blog['domain']."/".trim( $blog['path'], '/' ).' ( '.$blog['blog_id'].' )</option>
				';
			}
			echo '
			</select></p>
			';
		}
		echo '
		<p><label for="recreate">' . __( "Recreate table", "wibstats" ) . '</label>
		<button class="button" type="submit" name="recreate" id="recreate" value="1">' . __( "Recreate statistics table", "wibstats" ) . '</button>
		<strong>' . __( "This *may* remove all visitor information stored for your blog", "wibstats" ) . '</strong</p>
		</form>
		';
	
	}
}

// to display above every report
function wibstats_report_header() {
	echo '
	<div id="wibstats" class="wrap">
	';
	wibstats_wp_plugin_standard_header( "GBP", "WibStats", "Chris Taylor", "chris@stillbreathing.co.uk", "http://wordpress.org/extend/plugins/wibstats-statistics-for-wordpress-mu/" );
}

// display the header of a data table
function wibstats_table_header( $headings ) {
	echo '
	<table class="widefat post fixed">
	<thead>
	<tr>
	';
	foreach( $headings as $heading ) {
		echo '<th>' . __( $heading, "wibstats" ) . '</th>
		';
	}
	echo '
	</tr>
	</thead>
	<tbody>
	';
}

function wibstats_table_footer() {
	echo '
	</tbody>
	</table>
	';
}

// to display below every report
function wibstats_report_footer() {
	echo '
	<div class="wibstats_clear"></div>
	<p style="padding-top:100px">My grateful thanks go to the clever chaps at <a href="http://www.hostip.info">hostip.info</a>, <a href="http://iplocationtools.com">iplocationtools.com</a>, <a href="http://ipmango.com/">ipmango.com</a>, <a href="http://geoip.pidgets.com">pidgets.com</a> and <a href="http://ipinfodb.com">ipinfodb.com</a> for their geolocation APIs - please visit and consider donating so they can keep their services running. If you really love this plugin consider visiting <a href="http://www.stillbreathing.co.uk/donate/">stillbreathing.co.uk</a> and donating enough for me to buy a beer. Thanks!</p>
	';
	wibstats_wp_plugin_standard_footer( "GBP", "WibStats", "Chris Taylor", "chris@stillbreathing.co.uk", "http://wordpress.org/extend/plugins/wibstats-statistics-for-wordpress-mu/" );
	echo '
	</div>';
}

// get the new items report
function wibstats_new_report() {
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();

	echo '
	<h3>' . __( "New in the last week", "wibstats" ) . '</h3>
	';
	
	// get DateTime one week ago
	$starttime = ( time() - 604800 );
	
	// get new search terms in the last week
	$sql = wibstats_sql( "new_search_terms", 25, 0, "since=".$starttime );
	$results = $wpdb->get_results( $sql );
	
	echo '
	<div class="wibstats_col1">
	<h4>' . __( "New search terms", "wibstats" ) . ' <a href="admin.php?page=wibstats-searches&amp;view=newterms'.$bloglink.'">...' . __( "more", "wibstats" ) . '</a></h4>
	';
	if ( $results && is_array( $results ) && count( $results ) > 0 ) {

		wibstats_table_header( array( "Search words", "First used" ) );
		
		foreach( $results as $term ) {
			echo '
			<tr>
				<td><a href="admin.php?page=wibstats-searches&amp;view=term&amp;term=' . urlencode( $term->terms ) . '">' . $term->terms . '</a></td>
				<td><a href="admin.php?page=wibstats&amp;view=session' . $bloglink . '&amp;session=' . $term->sessionid . '">' . wibstats_date( $term->earliest ) . '</a></td>
			</tr>
			';
		}
		
		wibstats_table_footer();
	
	} else {
	
		echo '
		<p>' . __( "No new searches in the last week", "wibstats" ) . '</p>
		';
	
	}
	
	echo '
	</div>
	';
	
	// get new locations in the last week
	$sql = wibstats_sql( "new_locations", 25 );
	$results = $wpdb->get_results( $sql );
	if ( $results && is_array( $results ) && count( $results ) > 0 )
	{
		$total = count( $results );
		$max = $total;
		for( $i = 0; $i < $total; $i++ ) {
			//$date = new DateTime( date( "Y-m-d", $results[$i]->earliest ) );
			$date = $results[$i]->earliest; 
			if ( $date < $starttime ) {
				$max = $i;
				break;
			}
		}
	}
	
	echo '
	<div class="wibstats_col2">
	<h4>' . __( "New visitor locations", "wibstats" ) . ' <a href="admin.php?page=wibstats-locations&amp;view=newlocations'.$bloglink.'">...' . __( "more", "wibstats" ) . '</a></h4>
	';
	if ( $max > 0 ) {
	
		wibstats_table_header( array( "Country", "City", "First visit" ) );
	
		for( $i = 0; $i < $max; $i++ ) {
			echo '
			<tr>
				<td>' . wibstats_country_icon( $results[$i]->country, $results[$i]->countrycode ) . $results[$i]->country . '</td>
				<td>' . $results[$i]->city . '</td>
				<td><a href="admin.php?page=wibstats&amp;view=session' . $bloglink . '&amp;session=' . $results[$i]->sessionid . '">' . wibstats_date( $results[$i]->earliest ) . '</a></td>
			</tr>
			';
		}
		
		wibstats_table_footer();
		
	} else {
		echo '
	<p>' . __( "No new locations in the last week", "wibstats" ) . '</p>
		';
	}
	echo '
	
	</div>
	';
}

// show 24 hour report
function wibstats_24hour_report( $filter = "", $value = "", $desc = "" ) {
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	$filter = $wpdb->escape( $filter );
	$value = $wpdb->escape( $value );

	if ( $filter == "" ) {
		$filter = "'1'";
		$value = "1";
	}
	
	if ( substr( $value, 0, 1 ) == "!" ) {
		$equals = "<>";
		$value = trim( $value, "!" );
	} else {
		$equals = "=";
	}

	// get the time offset
	$wibstats_time_offset = ( int )get_option( "wibstats_time_offset" );
	
	$start = time() - ( 60 * 60 * 23 );
	$thishour = $start;
	
	if ( $filter == "referrer" ) {
		$sql = wibstats_sql( "session_count_by_hour_referrer", 0, 0, "filter=".$filter."&equals=".$equals."&value=".$value."&start=".$start );
	} else {
		$sql = wibstats_sql( "session_count_by_hour", 0, 0, "filter=".$filter."&equals=".$equals."&value=".$value."&start=".$start );
	}
	$visitors = wibstats_rs_to_array( $wpdb->get_results( $sql ) );
	
	if ( $filter == "referrer" ) {
		$sql = wibstats_sql( "page_count_by_hour_referrer", 0, 0, "filter=".$filter."&equals=".$equals."&value=".$value."&start=".$start );
	} else {
		$sql = wibstats_sql( "page_count_by_hour", 0, 0, "filter=".$filter."&equals=".$equals."&value=".$value."&start=".$start );
	}
	$pages = wibstats_rs_to_array( $wpdb->get_results( $sql ) );
	
	$visitorsmax = $visitors["max"];
	$pagesmax = $pages["max"];
	
	$h = "";
	$v = "";
	$p = "";
	
	for( $i = 0; $i < 24; $i++ ) {
		$hour = date( "H", $thishour );
		$h .= '<th>' . $hour . '</th>
		';
		if ( $visitors[$hour] != "" ) {
			$v .= '<td><div style="background:#6F6F6F;width:10px;height:' . ( round( ( $visitors[$hour]/$visitorsmax )*100 ) ) . 'px"></div>' . $visitors[$hour] . '</td>';
		} else {
			$v .= '<td>0</td>';
		}
		if ( $pages[$hour] != "" )
		{
			$p .= '<td><div style="background:#6F6F6F;width:10px;height:' . ( round( ( $pages[$hour]/$pagesmax )*100 ) ) . 'px"></div>' . $pages[$hour] . '</td>';
		} else {
			$p .= '<td>0</td>';
		}
		$thishour = $thishour + ( 60 * 60 );
	}
	
	echo '
	<h4 id="range24hours">' . __( "Visitors in the last 24 hours" . $desc, "wibstats" ) . '</h4>
	<table class="widefat post fixed">
		<thead>
		<tr>
			<th style="width:100px"></th>
		';
	echo $h;
	echo '
		</tr>
		</thead>
		<tbody>
		<tr>
		<th style="width:100px">' . __( "Visitors", "wibstats" ) . '</th>
		';
	echo $v;
	echo '
		</tr>
		
		<tr>
		<th style="width:100px">' . __( "Page views", "wibstats" ) . '</th>
		';
	echo $p;
	echo '
		</tr>
		</tbody>
	</table>
	';
}

// show 14 day report
function wibstats_14day_report( $filter = "", $value = "", $desc = "" ) {
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	$filter = $wpdb->escape( $filter );
	$value = $wpdb->escape( $value );

	if ( $filter == "" ) {
		$filter = "'1'";
		$value = "1";
	}
	
	if ( substr( $value, 0, 1 ) == "!" ) {
		$equals = "<>";
		$value = trim( $value, "!" );
	} else {
		$equals = "=";
	}

	$start = time() - ( 60 * 60 * 24 * 13 );
	$thisday = $start;

	if ( $filter == "referrer" ) {
		$sql = wibstats_sql( "session_count_by_day_referrer", 0, 0, "filter=".$filter."&equals=".$equals."&value=".$value."&start=".$start );
	} else {
		$sql = wibstats_sql( "session_count_by_day", 0, 0, "filter=".$filter."&equals=".$equals."&value=".$value."&start=".$start );
	}
	$visitors = wibstats_rs_to_array( $wpdb->get_results( $sql ) );
	
	if ( $filter == "referrer" ) {
		$sql = wibstats_sql( "page_count_by_day_referrer", 0, 0, "filter=".$filter."&equals=".$equals."&value=".$value."&start=".$start );
	} else {
		$sql = wibstats_sql( "page_count_by_day", 0, 0, "filter=".$filter."&equals=".$equals."&value=".$value."&start=".$start );
	}
	$pages = wibstats_rs_to_array( $wpdb->get_results( $sql ) );
	
	$visitorsmax = $visitors["max"];
	$pagesmax = $pages["max"];
	
	$h = "";
	$v = "";
	$p = "";
	
	for( $i = 0; $i < 14; $i++ ) {
		$day = date( "j", $thisday );
		$h .= '<th>' . $day . '</th>
		';
		if ( $visitors[$day] != "" )
		{
			$v .= '<td><div style="background:#6F6F6F;width:10px;height:' . ( round( ( $visitors[$day]/$visitorsmax )*100 ) ) . 'px"></div>' . $visitors[$day] . '</td>';
		} else {
			$v .= '<td>0</td>';
		}
		if ( $pages[$day] != "" )
		{
			$p .= '<td><div style="background:#6F6F6F;width:10px;height:' . ( round( ( $pages[$day]/$pagesmax )*100 ) ) . 'px"></div>' . $pages[$day] . '</td>';
		} else {
			$p .= '<td>0</td>';
		}
		$thisday = $thisday + ( 60 * 60 * 24 );
	}
	
	echo '
	<h4 id="range14days">' . __( "Visitors in the last 14 days" . $desc, "wibstats" ) . '</h4>
	<table class="widefat post fixed">
		<thead>
		<tr>
			<th style="width:100px"></th>
		';
	echo $h;
	echo '
		</tr>
		</thead>
		<tbody>
		<tr>
		<th style="width:100px">' . __( "Visitors", "wibstats" ) . '</th>
		';
	echo $v;
	echo '
		</tr>
		
		<tr>
		<th style="width:100px">' . __( "Page views", "wibstats" ) . '</th>
		';
	echo $p;
	echo '
		</tr>
		</tbody>
	</table>
	';
}

// show 12 week report
function wibstats_12week_report( $filter = "", $value = "", $desc = "" ) {
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	$filter = $wpdb->escape( $filter );
	$value = $wpdb->escape( $value );

	if ( $filter == "" ) {
		$filter = "'1'";
		$value = "1";
	}
	
	if ( substr( $value, 0, 1 ) == "!" ) {
		$equals = "<>";
		$value = trim( $value, "!" );
	} else {
		$equals = "=";
	}

	// show 12 week report
	$start = time() - ( 60 * 60 * 24 * 7 * 11 );
	$thisweek = $start;
	
	if ( $filter == "referrer" ) {
		$sql = wibstats_sql( "session_count_by_week_referrer", 0, 0, "filter=".$filter."&equals=".$equals."&value=".$value."&start=".$start );
	} else {
		$sql = wibstats_sql( "session_count_by_week", 0, 0, "filter=".$filter."&equals=".$equals."&value=".$value."&start=".$start );
	}
	$visitors = wibstats_rs_to_array( $wpdb->get_results( $sql ) );
	
	if ( $filter == "referrer" ) {
		$sql = wibstats_sql( "page_count_by_week_referrer", 0, 0, "filter=".$filter."&equals=".$equals."&value=".$value."&start=".$start );
	} else {
		$sql = wibstats_sql( "page_count_by_week", 0, 0, "filter=".$filter."&equals=".$equals."&value=".$value."&start=".$start );
	}
	$pages = wibstats_rs_to_array( $wpdb->get_results( $sql ) );
	
	$visitorsmax = $visitors["max"];
	$pagesmax = $pages["max"];
	
	$h = "";
	$v = "";
	$p = "";
	
	$visitors["53"] = $visitors["0"];
	$pages["53"] = $pages["0"];
	
	for( $i = 0; $i < 12; $i++ ) {
		$week = date( "W", $thisweek );
		$week = ltrim( $week, "0" );
		$h .= '<th>' . __( "Wk", "wibstats" ) . ' ' . $week . '</th>
		';
		if ( $visitors[$week] != "" )
		{
			$v .= '<td><div style="background:#6F6F6F;width:10px;height:' . ( round( ( $visitors[$week]/$visitorsmax )*100 ) ) . 'px"></div>' . $visitors[$week] . '</td>';
		} else {
			$v .= '<td>0</td>';
		}
		if ( $pages[$week] != "" )
		{
			$p .= '<td><div style="background:#6F6F6F;width:10px;height:' . ( round( ( $pages[$week]/$pagesmax )*100 ) ) . 'px"></div>' . $pages[$week] . '</td>';
		} else {
			$p .= '<td>0</td>';
		}
		$thisweek = $thisweek + ( 60 * 60 * 24 * 7 );
	}
	
	echo '
	<h4 id="range12weeks">' . __( "Visitors in the last 12 weeks" . $desc, "wibstats" ) . '</h4>
	<table class="widefat post fixed">
		<thead>
		<tr>
			<th style="width:100px"></th>
		';
	echo $h;
	echo '
		</tr>
		</thead>
		<tbody>
		<tr>
		<th style="width:100px">' . __( "Visitors", "wibstats" ) . '</th>
		';
	echo $v;
	echo '
		</tr>
		
		<tr>
		<th style="width:100px">' . __( "Page views", "wibstats" ) . '</th>
		';
	echo $p;
	echo '
		</tr>
		</tbody>
	</table>
	';
}

// show 12 month report
function wibstats_12month_report( $filter = "", $value = "", $desc = "" ) {

	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	$filter = $wpdb->escape( $filter );
	$value = $wpdb->escape( $value );

	if ( $filter == "" ) {
		$filter = "'1'";
		$value = "1";
	}
	
	if ( substr( $value, 0, 1 ) == "!" ) {
		$equals = "<>";
		$value = trim( $value, "!" );
	} else {
		$equals = "=";
	}

	// show 12 month report
	$start = wibstats_addMonthToDate( wibstats_addYearToDate( time(), -1 ) );
	$thismonth = $start;
	
	if ( $filter == "referrer" ) {
		$sql = wibstats_sql( "session_count_by_month_referrer", 0, 0, "filter=".$filter."&equals=".$equals."&value=".$value."&start=".$start );
	} else {
		$sql = wibstats_sql( "session_count_by_month", 0, 0, "filter=".$filter."&equals=".$equals."&value=".$value."&start=".$start );
	}
	$visitors = wibstats_rs_to_array( $wpdb->get_results( $sql ) );
	
	if ( $filter == "referrer" ) {
		$sql = wibstats_sql( "page_count_by_month_referrer", 0, 0, "filter=".$filter."&equals=".$equals."&value=".$value."&start=".$start );
	} else {
		$sql = wibstats_sql( "page_count_by_month", 0, 0, "filter=".$filter."&equals=".$equals."&value=".$value."&start=".$start );
	}
	$pages = wibstats_rs_to_array( $wpdb->get_results( $sql ) );
	
	$visitorsmax = $visitors["max"];
	$pagesmax = $pages["max"];
	
	$h = "";
	$v = "";
	$p = "";
	
	for( $i = 0; $i < 12; $i++ ) {
		$month = date( "n", $thismonth );
		$h .= '<th>' . __( date( "M", $thismonth ), "wibstats" ) . '</th>
		';
		if ( $visitors[$month] != "" ) {
			$v .= '<td><div style="background:#6F6F6F;width:10px;height:' . ( round( ( $visitors[$month]/$visitorsmax )*100 ) ) . 'px"></div>' . $visitors[$month] . '</td>';
		} else {
			$v .= '<td>0</td>';
		}
		if ( $pages[$month] != "" ) {
			$p .= '<td><div style="background:#6F6F6F;width:10px;height:' . ( round( ( $pages[$month]/$pagesmax )*100 ) ) . 'px"></div>' . $pages[$month] . '</td>';
		} else {
			$p .= '<td>0</td>';
		}
		$thismonth = wibstats_addMonthToDate( $thismonth );
	}
	
	echo '
	<h4 id="range12months">' . __( "Visitors in the last 12 months" . $desc, "wibstats" ) . '</h4>
	<table class="widefat post fixed">
		<thead>
		<tr>
			<th style="width:100px"></th>
		';
	echo $h;
	echo '
		</tr>
		</thead>
		<tbody>
		<tr>
		<th style="width:100px">' . __( "Visitors", "wibstats" ) . '</th>
		';
	echo $v;
	echo '
		</tr>
		
		<tr>
		<th style="width:100px">' . __( "Page views", "wibstats" ) . '</th>
		';
	echo $p;
	echo '
		</tr>
		</tbody>
	</table>
	';
}

// show popular months report
function wibstats_popular_months_report( $filter = "", $value = "", $desc = "" ) {

	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	$filter = $wpdb->escape( $filter );
	$value = $wpdb->escape( $value );

	if ( $filter == "" ) {
		$filter = "'1'";
		$value = "1";
	}
	
	if ( substr( $value, 0, 1 ) == "!" ) {
		$equals = "<>";
		$value = trim( $value, "!" );
	} else {
		$equals = "=";
	}
	
	$sql = wibstats_sql( "popular_monthsofyear", 0, 0, "filter=".$filter."&equals=".$equals."&value=".$value );
	$visitors = wibstats_rs_to_array( $wpdb->get_results( $sql ) );
	$visitorsmax = $visitors["max"];
	$visitorstotal = $visitors["total"];
	
	$h = "";
	$v = "";
	
	for( $i = 1; $i < 13; $i++ ) {
		$h .= '<th>' . __( wibstats_month( $i ), "wibstats" ) . '</th>
		';
		if ( $visitors[$i] != "" ) {
			$v .= '<td><div style="background:#6F6F6F;width:10px;height:' . ( round( ( $visitors[$i]/$visitorsmax )*100 ) ) . 'px"></div><span title="' . __( "Visitors", "wibstats" ) . ': ' . $visitors[$i] . '">' . wibstats_percent( $visitorstotal, $visitors[$i] ) . '</span></td>';
		} else {
			$v .= '<td>0</td>';
		}
	}
	
	echo '
	<div class="wibstats_clear"></div>
	<h4 id="popularmonths">' . __( "Total visitors for each month of the year" . $desc, "wibstats" ) . '</h4>
	<table class="widefat post fixed">
		<thead>
		<tr>
			<th style="width:100px"></th>
		';
	echo $h;
	echo '
		</tr>
		</thead>
		<tbody>
		<tr>
		<th style="width:100px">' . __( "Visitors", "wibstats" ) . '</th>
		';
	echo $v;
	echo '
		</tr>
		</tbody>
	</table>
	';
}

// show popular weeks report
function wibstats_popular_weeks_report( $filter = "", $value = "", $desc = "" ) {

	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	$filter = $wpdb->escape( $filter );
	$value = $wpdb->escape( $value );

	if ( $filter == "" ) {
		$filter = "'1'";
		$value = "1";
	}
	
	if ( substr( $value, 0, 1 ) == "!" ) {
		$equals = "<>";
		$value = trim( $value, "!" );
	} else {
		$equals = "=";
	}
	
	$sql = wibstats_sql( "popular_weeksofyear", 0, 0, "filter=".$filter."&equals=".$equals."&value=".$value );
	$visitors = wibstats_rs_to_array( $wpdb->get_results( $sql ) );
	$visitorsmax = $visitors["max"];
	$visitorstotal = $visitors["total"];
	
	$h = "";
	$v = "";
	
	for( $i = 1; $i < 53; $i++ ) {
		$h .= '<th>' . $i . '</th>
		';
		if ( $visitors[$i] != "" ) {
			$v .= '<td><div style="background:#6F6F6F;width:10px;height:' . ( round( ( $visitors[$i]/$visitorsmax )*100 ) ) . 'px"></div><span title="' . __( "Visitors", "wibstats" ) . ': ' . $visitors[$i] . '">' . wibstats_percent( $visitorstotal, $visitors[$i] ) . '</span></td>';
		} else {
			$v .= '<td>0</td>';
		}
	}
	
	echo '
	<div class="wibstats_clear"></div>
	<h4 id="popularweeks">' . __( "Total visitors for each week of the year", "wibstats" ) . '</h4>
	<table class="widefat post fixed">
		<thead>
		<tr>
			<th style="width:100px"></th>
		';
	echo $h;
	echo '
		</tr>
		</thead>
		<tbody>
		<tr>
		<th style="width:100px">' . __( "Visitors", "wibstats" ) . '</th>
		';
	echo $v;
	echo '
		</tr>
		</tbody>
	</table>
	';
}

// show popular days of the month report
function wibstats_popular_monthdays_report( $filter = "", $value = "", $desc = "" ) {

	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	$filter = $wpdb->escape( $filter );
	$value = $wpdb->escape( $value );

	if ( $filter == "" ) {
		$filter = "'1'";
		$value = "1";
	}
	
	if ( substr( $value, 0, 1 ) == "!" ) {
		$equals = "<>";
		$value = trim( $value, "!" );
	} else {
		$equals = "=";
	}

	$sql = wibstats_sql( "popular_daysofmonth", 0, 0, "filter=".$filter."&equals=".$equals."&value=".$value );
	$visitors = wibstats_rs_to_array( $wpdb->get_results( $sql ) );
	$visitorsmax = $visitors["max"];
	$visitorstotal = $visitors["total"];
	
	$h = "";
	$v = "";
	
	for( $i = 1; $i < 32; $i++ ) {
		$h .= '<th>' . $i . '</th>
		';
		if ( $visitors[$i] != "" ) {
			$v .= '<td><div style="background:#6F6F6F;width:10px;height:' . ( round( ( $visitors[$i]/$visitorsmax )*100 ) ) . 'px"></div><span title="' . __( "Visitors", "wibstats" ) . ': ' . $visitors[$i] . '">' . wibstats_percent( $visitorstotal, $visitors[$i] ) . '</span></td>';
		} else {
			$v .= '<td>0</td>';
		}
	}
	
	echo '
	<div class="wibstats_clear"></div>
	<h4 id="range12months">' . __( "Total visitors for each day of the month", "wibstats" ) . '</h4>
	<table class="widefat post fixed">
		<thead>
		<tr>
			<th style="width:100px"></th>
		';
	echo $h;
	echo '
		</tr>
		</thead>
		<tbody>
		<tr>
		<th style="width:100px">' . __( "Visitors", "wibstats" ) . '</th>
		';
	echo $v;
	echo '
		</tr>
		</tbody>
	</table>
	';
}

// show popular days of the week report
function wibstats_popular_weekdays_report( $filter = "", $value = "", $desc = "" ) {

	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	$filter = $wpdb->escape( $filter );
	$value = $wpdb->escape( $value );

	if ( $filter == "" ) {
		$filter = "'1'";
		$value = "1";
	}
	
	if ( substr( $value, 0, 1 ) == "!" ) {
		$equals = "<>";
		$value = trim( $value, "!" );
	} else {
		$equals = "=";
	}

	$sql = wibstats_sql( "popular_daysofweek", 0, 0, "filter=".$filter."&equals=".$equals."&value=".$value );
	$visitors = wibstats_rs_to_array( $wpdb->get_results( $sql ) );
	$visitorsmax = $visitors["max"];
	$visitorstotal = $visitors["total"];
	
	$h = "";
	$v = "";
	
	for( $i = 1; $i < 8; $i++ ) {
		$h .= '<th>' . wibstats_dayofweek( $i ) . '</th>
		';
		if ( $visitors[$i] != "" ) {
			$v .= '<td><div style="background:#6F6F6F;width:10px;height:' . ( round( ( $visitors[$i]/$visitorsmax )*100 ) ) . 'px"></div><span title="' . __( "Visitors", "wibstats" ) . ': ' . $visitors[$i] . '">' . wibstats_percent( $visitorstotal, $visitors[$i] ) . '</span></td>';
		} else {
			$v .= '<td>0</td>';
		}
	}
	
	echo '
	<div class="wibstats_clear"></div>
	<h4 id="popularweekdays">' . __( "Total visitors for each day of the week", "wibstats" ) . '</h4>
	<table class="widefat post fixed">
		<thead>
		<tr>
			<th style="width:100px"></th>
		';
	echo $h;
	echo '
		</tr>
		</thead>
		<tbody>
		<tr>
		<th style="width:100px">' . __( "Visitors", "wibstats" ) . '</th>
		';
	echo $v;
	echo '
		</tr>
		</tbody>
	</table>
	';
}

// show popular hours of the day report
function wibstats_popular_hours_report( $filter = "", $value = "", $desc = "" ) {

	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	$filter = $wpdb->escape( $filter );
	$value = $wpdb->escape( $value );

	if ( $filter == "" ) {
		$filter = "'1'";
		$value = "1";
	}
	
	if ( substr( $value, 0, 1 ) == "!" ) {
		$equals = "<>";
		$value = trim( $value, "!" );
	} else {
		$equals = "=";
	}

	$sql = wibstats_sql( "popular_hoursofday", 0, 0, "filter=".$filter."&equals=".$equals."&value=".$value );
	$visitors = wibstats_rs_to_array( $wpdb->get_results( $sql ) );
	$visitorsmax = $visitors["max"];
	$visitorstotal = $visitors["total"];
	
	$h = "";
	$v = "";
	
	for( $i = 0; $i < 24; $i++ ) {
		$h .= '<th>' . $i . '</th>
		';
		if ( $visitors[$i] != "" ) {
			$v .= '<td><div style="background:#6F6F6F;width:10px;height:' . ( round( ( $visitors[$i]/$visitorsmax )*100 ) ) . 'px"></div><span title="' . __( "Visitors", "wibstats" ) . ': ' . $visitors[$i] . '">' . wibstats_percent( $visitorstotal, $visitors[$i] ) . '</span></td>';
		} else {
			$v .= '<td>0</td>';
		}
	}
	
	echo '
	<div class="wibstats_clear"></div>
	<h4 id="popularhours">' . __( "Total visitors for each hour of the day", "wibstats" ) . '</h4>
	<table class="widefat post fixed">
		<thead>
		<tr>
			<th style="width:100px"></th>
		';
	echo $h;
	echo '
		</tr>
		</thead>
		<tbody>
		<tr>
		<th style="width:100px">' . __( "Visitors", "wibstats" ) . '</th>
		';
	echo $v;
	echo '
		</tr>
		</tbody>
	</table>
	';
}

// show direct visitors report
function wibstats_direct_report() {
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	wibstats_report_header();
	
	// show search term detail reports
	if ( isset( $_GET["view"] ) && $_GET["view"] == "recentvisitors" ) {
		wibstats_recentdirectvisitors_report();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_recentdirectvisitors_report()" );
	}
	
	else if ( isset( $_GET["view"] ) && $_GET["view"] == "popularcountries" ) {
		wibstats_populardirectcountries_report();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_populardirectcountries_report()" );
	} else {
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; ' . __( "Direct visitors", "wibstats" ) . '</h2>
	';
	
	// show date range reports
	echo '
	<h3>' . __( "Date range reports", "wibstats" ) . '</h3>
	
	<ul class="inline">
		<li><a href="admin.php?page=wibstats-direct&amp;range=24hours#range24hours">' . __( "Last 24 hours", "wibstats" ) . '</a></li>
		<li><a href="admin.php?page=wibstats-direct&amp;range=14days#range14days">' . __( "Last 14 days", "wibstats" ) . '</a></li>
		<li><a href="admin.php?page=wibstats-direct&amp;#range12weeks">' . __( "Last 12 weeks", "wibstats" ) . '</a></li>
		<li><a href="admin.php?page=wibstats-direct&amp;range=12months#range12months">' . __( "Last 12 months", "wibstats" ) . '</a></li>
	</ul>

	';
	
	// show 24 hour report
	if ( isset( $_GET["range"] ) && $_GET["range"] =="24hours" ) {
		
		wibstats_24hour_report( "referrer", "", " " . __( "where the visitor was a direct visitor", "wibstats" ) );
		
	}
	
	// show 14 day report
	if ( isset( $_GET["range"] ) && $_GET["range"] =="14days" ) {
		
		wibstats_14day_report( "referrer", "", " " . __( "where the visitor was a direct visitor", "wibstats" ) );
		
	}
	
	// show 12 week report
	if ( !isset( $_GET["range"] ) ) {
	
		wibstats_12week_report( "referrer", "", " " . __( "where the visitor was a direct visitor", "wibstats" ) );
		
	}
	
	// show 12 month report
	if ( isset( $_GET["range"] ) && $_GET["range"] =="12months" ) {
		
		wibstats_12month_report( "referrer", "", " " . __( "where the visitor was a direct visitor", "wibstats" ) );
		
	}
	
	// get recent direct visitors
	$sql = wibstats_sql( "recent_direct_visitors", 10 );
	$recentdirects = $wpdb->get_results( $sql );

	echo '
	<div class="wibstats_col1">
	<h3>' . __( "Last 10 direct visitors", "wibstats" ) . ' <a href="admin.php?page=wibstats-direct&amp;view=recentvisitors'.$bloglink.'">...' . __( "more", "wibstats" ) . '</a></h3>
	';
	
	wibstats_table_header( array( "When", "Country", "City" ) );
	
	foreach ( $recentdirects as $direct ) {
		echo '
		<tr>
			<td><a href="admin.php?page=wibstats&amp;view=session' . $bloglink . '&amp;session=' . $direct->sessionid . '">' . wibstats_date( $direct->timestamp ) . '</a></td>
			<td>' . wibstats_country_icon( $direct->country, $direct->countrycode ) . $direct->country . '</td>
			<td>' . $direct->city . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
	
	echo '
	</div>
	';
	
	$sql = wibstats_sql( "popular_direct_countries", 10 );
	$populardirects = $wpdb->get_results( $sql );
	$sql = wibstats_sql( "total_direct_countries" );
	$totaldirects = $wpdb->get_var( $sql );
	
	echo '
	<div class="wibstats_col2">
	<h3>' . __( "10 most popular countries for direct visitors", "wibstats" ) . ' <a href="admin.php?page=wibstats-direct&amp;view=popularcountries'.$bloglink.'">...' . __( "more", "wibstats" ) . '</a></h3>
	';
	
	wibstats_table_header( array( "Country", "Visitors", "Percent" ) );
	
	foreach ( $populardirects as $direct ) {
		echo '
		<tr>
			<td>' . wibstats_country_icon( $direct->country, $direct->countrycode ) . $direct->country . '</td>
			<td>' . $direct->num . '</td>
			<td>' . wibstats_percent( $totaldirects, $direct->num ) . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
	
	echo '
	</div>
	';
	
	wibstats_popular_months_report( "p.referrer", "" );
	
	wibstats_popular_weekdays_report( "p.referrer", "" );
	
	wibstats_popular_hours_report( "p.referrer", "" );
	
	}
	
	wibstats_report_footer();
}

// show recent direct visitors report
function wibstats_recentdirectvisitors_report()
{
	global $wpdb;
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; <a href="admin.php?page=wibstats-direct'.$bloglink.'">' . __( "Direct visitors", "wibstats" ) . '</a> &raquo; ' . __( "Recent direct visitors", "wibstats" ) . '</h2>
	';
	
	$sql = wibstats_sql( "recent_direct_visitors", 100 );
	$recentdirects = $wpdb->get_results( $sql );
	
	wibstats_table_header( array( "When", "Country", "City" ) );
	
	foreach ( $recentdirects as $direct ) {
		echo '
		<tr>
			<td><a href="admin.php?page=wibstats&amp;view=session' . $bloglink . '&amp;session=' . $direct->sessionid . '">' . wibstats_date( $direct->timestamp ) . '</a></td>
			<td>' . wibstats_country_icon( $direct->country, $direct->countrycode ) . $direct->country . '</td>
			<td>' . $direct->city . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
	
}

// show popular countries for direct visitors report
function wibstats_populardirectcountries_report()
{
	global $wpdb;
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; <a href="admin.php?page=wibstats-direct'.$bloglink.'">' . __( "Direct visitors", "wibstats" ) . '</a> &raquo; ' . __( "Popular direct visitor countries", "wibstats" ) . '</h2>
	';
	
	$sql = wibstats_sql( "popular_direct_countries", 100 );
	$populardirects = $wpdb->get_results( $sql );
	$sql = wibstats_sql( "total_direct_countries" );
	$totaldirects = $wpdb->get_var( $sql );
	
	wibstats_table_header( array( "Country", "Visitors", "Percent" ) );
	
	foreach ( $populardirects as $direct ) {
		echo '
		<tr>
			<td>' . wibstats_country_icon( $direct->country, $direct->countrycode ) . $direct->country . '</td>
			<td>' . $direct->num . '</td>
			<td>' . wibstats_percent( $totaldirects, $direct->num ) . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
}

// ============================================================================================================
// Times functions

// show visitor times report
function wibstats_times_report()
{
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();

	wibstats_report_header();
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; ' . __( "Visit times", "wibstats" ) . '</h2>
	';
	
	wibstats_popular_months_report();
	
	wibstats_popular_weekdays_report();
	
	wibstats_popular_hours_report();
	
	wibstats_report_footer();
}

// ============================================================================================================
// Environment functions

// show visitor environment report
function wibstats_environment_report()
{
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();

	wibstats_report_header();
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; ' . __( "Visitor environment", "wibstats" ) . '</h2>
	';
	
	$sql = wibstats_sql( "popular_platforms", 10 );
	$popularplatforms = $wpdb->get_results( $sql );
	$sql = wibstats_sql( "total_platforms" );
	$totalplatforms = $wpdb->get_var( $sql );
	
	echo '
	<div class="wibstats_col31">
	<h3>' . __( "10 most popular platforms", "wibstats" ) . '</h3>
	';
	
	wibstats_table_header( array( "Platform", "Visitors", "Percent" ) );
	
	foreach ( $popularplatforms as $platform ) {
		echo '
		<tr>
			<td>' . $platform->platform . '</td>
			<td>' . $platform->num . '</td>
			<td>' . wibstats_percent( $totalplatforms, $platform->num ) . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
	
	echo '
	</div>
	';

	$sql = wibstats_sql( "popular_browsers", 10 );
	$popularbrowsers = $wpdb->get_results( $sql );
	$sql = wibstats_sql( "total_platforms" );
	$totalbrowsers = $wpdb->get_var( $sql );
	
	echo '
	<div class="wibstats_col32">
	<h3>' . __( "10 most popular browsers", "wibstats" ) . '</h3>
	';
	
	wibstats_table_header( array( "Browser", "Visitors", "Percent" ) );
	
	foreach ( $popularbrowsers as $browser ) {
		echo '
		<tr>
			<td>' . $browser->browser . '</td>
			<td>' . $browser->num . '</td>
			<td>' . wibstats_percent( $totalbrowsers, $browser->num ) . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
	
	echo '
	</div>
	';
	
	$sql = wibstats_sql( "popular_screen_sizes", 10 );
	$popularscreensizes = $wpdb->get_results( $sql );
	$sql = wibstats_sql( "total_platforms" );
	$totalscreensizes = $wpdb->get_var( $sql );
	
	echo '
	<div class="wibstats_col33">
	<h3>' . __( "10 most popular screen sizes", "wibstats" ) . '</h3>
	';
	
	wibstats_table_header( array( "Screen size", "Visitors", "Percent" ) );
	
	foreach ( $popularscreensizes as $screensize ) {
		echo '
		<tr>
			<td>' . $screensize->screensize . '</td>
			<td>' . $screensize->num . '</td>
			<td>' . wibstats_percent( $totalscreensizes, $screensize->num ) . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
	
	echo '
	</div>
	';
	
	wibstats_report_footer();
}

// ============================================================================================================
// Pages functions

// show content report
function wibstats_content_report()
{
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	wibstats_report_header();

	// show page reports
	if ( $_GET["view"] == "page" && isset( $_GET["url"] ) ) {
		wibstats_page_report();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_page_report()" );
	}
	else
	
	// show popular pages reports
	if ( $_GET["view"] == "popularpages" ) {
		wibstats_popularpages_report();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_popularpages_report()" );
	}
	else
	
	// show recent pages reports
	if ( $_GET["view"] == "recentpages" ) {
		wibstats_recentpages_report();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_recentpages_report()" );
	}
	else
	{
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; ' . __( "Pages viewed", "wibstats" ) . '</h2>
	';
	
	$sql = wibstats_sql( "recent_pages", 10 );
	$recentpages = $wpdb->get_results( $sql );
	
	echo '
	<div class="wibstats_col1">
	<h3>' . __( "Last 10 pages viewed", "wibstats" ) . ' <a href="admin.php?page=wibstats-pages&amp;view=recentpages'.$bloglink.'">...' . __( "more", "wibstats" ) . '</a></h3>
	';
	
	wibstats_table_header( array( "When", "Page", "Country", "City" ) );
	
	foreach ( $recentpages as $page ) {
		echo '
		<tr>
			<td><a href="admin.php?page=wibstats&amp;view=session' . $bloglink . '&amp;session=' . $page->sessionid . '">' . wibstats_date( $page->timestamp ) . '</a></td>
			<td><a href="admin.php?page=wibstats-pages&amp;view=page&amp;url=' . urlencode( $page->page ) . '">' . wibstats_shorten( $page->title ) . '</a></td>
			<td>' . wibstats_country_icon( $page->country, $page->countrycode ) . strip_tags( $page->country ) . '</td>
			<td>' . strip_tags( $page->city ) . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
	
	echo '
	</div>
	';

	$sql = wibstats_sql( "popular_pages", 10 );
	$popularpages = $wpdb->get_results( $sql );
	$sql = wibstats_sql( "total_pages" );
	$totalpages = $wpdb->get_var( $sql );
	
	echo '
	<div class="wibstats_col2">
	<h3>' . __( "10 most popular pages", "wibstats" ) . ' <a href="admin.php?page=wibstats-pages&amp;view=popularpages'.$bloglink.'">...' . __( "more", "wibstats" ) . '</a></h3>
	';
	
	wibstats_table_header( array( "Page", "Visitors", "Percent" ) );
	
	foreach ( $popularpages as $page ) {
		if ( $page->title == "" ) {
			$page->title = $page->page;
		}
		echo '
		<tr>
			<td><a href="admin.php?page=wibstats-pages&amp;view=page&amp;url=' . urlencode( $page->page ) . '">' . wibstats_shorten( $page->title ) . '</a></td>
			<td>' . $page->num . '</td>
			<td>' . wibstats_percent( $totalpages, $page->num ) . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
	
	echo '
	</div>
	';
	
	}
	
	wibstats_report_footer();
}

// show page report
function wibstats_page_report()
{
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; <a href="admin.php?page=wibstats-pages&amp;view=content'.$bloglink.'">' . __( "Pages viewed", "wibstats" ) . '</a> &raquo; ' . __( "Page:", "wibstats" ) . ' &#39;' . urldecode( $_GET["url"] ) . '&#39;</h2>
	';
	
	// show date range reports
	echo '
	<h3>' . __( "Date range reports", "wibstats" ) . '</h3>
	
	<ul class="inline">
		<li><a href="admin.php?page=wibstats-pages&amp;view=page&amp;url=' . $_GET["url"] . '&amp;range=24hours#range24hours">' . __( "Last 24 hours", "wibstats" ) . '</a></li>
		<li><a href="admin.php?page=wibstats-pages&amp;view=page&amp;url=' . $_GET["url"] . '&amp;range=14days#range14days">' . __( "Last 14 days", "wibstats" ) . '</a></li>
		<li><a href="admin.php?page=wibstats-pages&amp;view=page&amp;url=' . $_GET["url"] . '#range12weeks">' . __( "Last 12 weeks", "wibstats" ) . '</a></li>
		<li><a href="admin.php?page=wibstats-pages&amp;view=page&amp;url=' . $_GET["url"] . '&amp;range=12months#range12months">' . __( "Last 12 months", "wibstats" ) . '</a></li>
	</ul>

	';
	
	// show 24 hour report
	if ( isset( $_GET["range"] ) && $_GET["range"] =="24hours" ) {
		
		wibstats_24hour_report( "page", urldecode( $_GET["url"] ), " " . __( "where the page was", "wibstats" ) . ' &#39;<a href="' . $_GET["url"] . '">' . urldecode( $_GET["url"] ) . '</a>&#39;' );
		
	}
	
	// show 14 day report
	if ( isset( $_GET["range"] ) && $_GET["range"] =="14days" )
	{
		
		wibstats_14day_report( "page", urldecode( $_GET["url"] ), " " . __( "where the page was", "wibstats" ) . ' &#39;<a href="' . $_GET["url"] . '">' . urldecode( $_GET["url"] ) . '</a>&#39;' );
		
	}
	
	// show 12 week report
	if ( !isset( $_GET["range"] ) )
	{
	
		wibstats_12week_report( "page", urldecode( $_GET["url"] ), " " . __( "where the page was", "wibstats" ) . ' &#39;<a href="' . $_GET["url"] . '">' . urldecode( $_GET["url"] ) . '</a>&#39;' );
		
	}
	
	echo '
	<div class="wibstats_col1">
	<h3>' . __( "Search terms which found this page", "wibstats" ) . '</h3>
	';
	
	// get the terms used to find this page
	$sql = wibstats_sql( "page_terms", 0, 0, "page=" . urldecode( $_GET["url"] ) );
	$terms = $wpdb->get_results( $sql );
	
	if ( $terms && is_array( $terms ) && count( $terms ) > 0 )
	{
		
		wibstats_table_header( array( "Search terms", "Visitors" ) );
		
		foreach ( $terms as $term )
		{
			echo '
			<tr>
				<td><a href="admin.php?page=wibstats-pages&amp;view=term&amp;term=' . urlencode( $term->terms ) . '">' . $term->terms . '</a></td>
				<td>' . $term->visitors . '</td>
			</tr>
			';
		}
		
		wibstats_table_footer();
		
	} else {
		echo '
		<p>' . __( "No search terms found for this page", "wibstats" ) . '</p>
		';
	}
	
	echo '
	</div>

	<div class="wibstats_col2">
	<h3>' . __( "Countries visiting this page", "wibstats" ) . '</h3>
	';
	
	// get the gmaps api key
	$wibstats_gmap_key = wibstats_gmap_key();
	
	// get the countries visiting this page
	$sql = wibstats_sql( "page_countries", 0, 0, "page=" . urldecode( $_GET["url"] ) );
	$countries = $wpdb->get_results( $sql );
	
	if ( $countries && is_array( $countries ) && count( $countries ) > 0 )
	{
	
		// if the key is set
		if ( $wibstats_gmap_key != "" )
		{
		
			echo '
		<div id="map" style="height:400px;width:100%"></div>
		<script type="text/javascript">
			function initializeMap() {
			var map = new google.maps.Map2( document.getElementById( "map" ) );
			map.setCenter( new google.maps.LatLng( 19.554270144063956,-9.4921875 ), 1 );
			map.addControl( new GMapTypeControl() );
			map.addControl( new GSmallMapControl() );
			';
				foreach( $countries as $country )
				{
					echo '
					
			var i' . $country->countrycode . ' = new GLatLng( ' . $country->latitude . ',' . $country->longitude .' );
	    	var m' . $country->countrycode . ' = new GMarker( i' . $country->countrycode . ' );
			GEvent.addListener( m' . $country->countrycode . ', "click", function() {
				m' . $country->countrycode . '.openInfoWindowHtml( \'<p>' . wibstats_country_icon( $country->country, $country->countrycode ) . $country->country . '<br />' . __( "Visitors:", "wibstats" ) . ' ' . $country->visitors . '</p>\' );
			} );
			map.addOverlay( m' . $country->countrycode . ' );
					';
				}
				
			echo '
			}
		</script>
			';
		}
		
		wibstats_table_header( array( "Country", "Visitors" ) );
		
		foreach ( $countries as $country )
		{
			echo '
			<tr>
				<td>' . wibstats_country_icon( $country->country, $country->countrycode ) . $country->country . '</td>
				<td>' . $country->visitors . '</td>
			</tr>
			';
		}
		
		wibstats_table_footer();
		
	} else {
			
		echo '
		<p>' . __( "No countries found", "wibstats" ) . '</p>
		';
	
	}
	
	echo '
	</div>
	';
	
	wibstats_popular_months_report( "p.page", urldecode( $_GET["url"] ) );
	
	wibstats_popular_weekdays_report( "p.page", urldecode( $_GET["url"] ) );
	
	wibstats_popular_hours_report( "p.page", urldecode( $_GET["url"] ) );
}

// show popular pages report
function wibstats_popularpages_report()
{
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();

	$sql = wibstats_sql( "popular_pages", 100 );
	$popularpages = $wpdb->get_results( $sql );
	$sql = wibstats_sql( "total_pages" );
	$totalpages = $wpdb->get_var( $sql );
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; <a href="admin.php?page=wibstats-pages'.$bloglink.'">' . __( "Pages viewed", "wibstats" ) . '</a> &raquo; ' . __( "Most popular pages", "wibstats" ) . '</h2>
	';
	
	wibstats_table_header( array( "Page", "Visitors", "Percent" ) );
	
	foreach ( $popularpages as $page )
	{
		if ( $page->title == "" ) {
			$page->title = $page->page;
		}
		echo '
		<tr>
			<td><a href="admin.php?page=wibstats-pages&amp;view=page&amp;url=' . urlencode( $page->page ) . '">' . wibstats_shorten( $page->title ) . '</a></td>
			<td>' . $page->num . '</td>
			<td>' . wibstats_percent( $totalpages, $page->num ) . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
}

// show recent pages report
function wibstats_recentpages_report()
{
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; <a href="admin.php?page=wibstats-pages'.$bloglink.'">' . __( "Pages viewed", "wibstats" ) . '</a> &raquo; ' . __( "Recent page views", "wibstats" ) . '</h2>
	';

	$sql = wibstats_sql( "recent_pages", 100 );
	$recentpages = $wpdb->get_results( $sql );
	
	wibstats_table_header( array( "Page", "Country", "City", "When" ) );
	
	foreach ( $recentpages as $page )
	{
		if ( $page->title == "" ){
			$page->title = $page->page;
		}
		echo '
		<tr>
			<td><a href="admin.php?page=wibstats-pages&amp;view=page&amp;url=' . urlencode( $page->page ) . '">' . wibstats_shorten( $page->title ) . '</a></td>
			<td>' . wibstats_country_icon( $page->country, $page->countrycode ) . strip_tags( $page->country ) . '</td>
			<td>' . strip_tags( $page->city ) . '</td>
			<td><a href="admin.php?page=wibstats&amp;view=session' . $bloglink . '&amp;session=' . $page->sessionid . '">' . wibstats_date( $page->timestamp ) . '</a></td>
		</tr>
		';
	}
	
	wibstats_table_footer();
}

// ============================================================================================================
// Locations functions

// show locations report
function wibstats_locations_report()
{
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();

	wibstats_report_header();
	
	// show recent visitor map
	if ( @$_GET["view"] == "map" )
	{
		wibstats_recentvisitor_map();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_recentvisitor_map()" );
	}
	else
	
	// show countries map
	if ( @$_GET["view"] == "countries" )
	{
		wibstats_countries_map();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_countries_map()" );
	}
	else
	
	// show top 100 new visitor locations
	if ( $_GET["view"] == "newlocations" )
	{
		wibstats_newlocations_report();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_newlocations_report()" );
	}
	else
	
	// show visitor locations reports
	if ( $_GET["view"] == "locations" )
	{
		wibstats_locations_report();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_locations_report()" );
	}
	else
	
	// show popular countries reports
	if ( $_GET["view"] == "popularcountries" )
	{
		wibstats_popularcountries_report();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_popularcountries_report()" );
	}
	else
	
	// show popular cities reports
	if ( $_GET["view"] == "popularcities" )
	{
		wibstats_popularcities_report();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_popularcities_report()" );
	}
	else
	
	// show recent locations reports
	if ( $_GET["view"] == "recentlocations" )
	{
		wibstats_recentlocations_report();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_recentlocations_report()" );
	}
	else
	{
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; ' . __( "Visitor locations", "wibstats" ) . '</h2>
	<h3>' . __( "Visitor maps", "wibstats" ) . '</h3>
	<ul class="inline">
		<li><a href="admin.php?page=wibstats-locations&amp;view=countries'.$bloglink.'">' . __( "Visitors by country", "wibstats" ) . '</a></li>
		<li><a href="admin.php?page=wibstats-locations&amp;view=map'.$bloglink.'">' . __( "Last 50 visitors", "wibstats" ) . '</a></li>
	</ul>
	';
	
	$sql = wibstats_sql( "recent_locations", 25 );
	$recentlocations = $wpdb->get_results( $sql );
	
	echo '
	<div class="wibstats_col1">
	<h3>' . __( "Last 25 visitor locations", "wibstats" ) . ' <a href="admin.php?page=wibstats-locations&amp;view=recentlocations'.$bloglink.'">...' . __( "more", "wibstats" ) . '</a></h3>
	';
	
	wibstats_table_header( array( "When", "Country", "City" ) );
	
	foreach ( $recentlocations as $location )
	{
		echo '
		<tr>
			<td><a href="admin.php?page=wibstats&amp;view=session' . $bloglink . '&amp;session=' . $location->sessionid . '">' . wibstats_date( $location->timestamp ) . '</a></td>
			<td>' . wibstats_country_icon( $location->country, $location->countrycode ) . strip_tags( $location->country ) . '</td>
			<td>' . strip_tags( $location->city ) . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
	
	echo '
	</div>
	';
	
	$sql = wibstats_sql( "popular_countries", 10 );
	$popularcountries = $wpdb->get_results( $sql );
	$sql = wibstats_sql( "total_countries" );
	$totalcountries = $wpdb->get_var( $sql );
	
	echo '
	<div class="wibstats_col2">
	<h3>' . __( "10 most popular visitor countries" ) . ' <a href="admin.php?page=wibstats-locations&amp;view=popularcountries'.$bloglink.'">...' . __( "more", "wibstats" ) . '</a></h3>
	';
	
	wibstats_table_header( array( "Country", "Visitors", "Percent" ) );
	
	foreach ( $popularcountries as $country )
	{
		echo '
		<tr>
			<td>' . wibstats_country_icon( $country->country, $country->countrycode ) . strip_tags( $country->country ) . '</td>
			<td>' . $country->num . '</td>
			<td>' . wibstats_percent( $totalcountries, $country->num ) . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
	
	$sql = wibstats_sql( "popular_cities", 10 );
	$popularcities = $wpdb->get_results( $sql );
	$sql = wibstats_sql( "total_cities" );
	$totalcities = $wpdb->get_var( $sql );
	
	echo '
	<h3>' . __( "10 most popular visitor cities" ) . ' <a href="admin.php?page=wibstats-locations&amp;view=popularcities'.$bloglink.'">...' . __( "more", "wibstats" ) . '</a></h3>
	';
	
	wibstats_table_header( array( "City", "Visitors", "Percent" ) );
	
	foreach ( $popularcities as $city )
	{
		echo '
		<tr>
			<td>' . wibstats_country_icon( $city->country, $city->countrycode ) . strip_tags( $city->city ) . '</td>
			<td>' . $city->num . '</td>
			<td>' . wibstats_percent( $totalcities, $city->num ) . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
	
	echo '
	</div>
	';
	
	}
	
	wibstats_report_footer();
}

// show number of visitors per country map
function wibstats_countries_map()
{
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();

	// get the gmaps api key
	$wibstats_gmap_key = wibstats_gmap_key();

	$sql = wibstats_sql( "countries" );
	$countries = $wpdb->get_results( $sql );
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; <a href="admin.php?page=wibstats-locations'.$bloglink.'">' . __( "Visitor locations", "wibstats" ) . '</a> &raquo; ' . __( "Visitors by country", "wibstats" ) . '</h2>
	';
	
	if ( $countries && is_array( $countries ) && count( $countries ) > 0 )
	{
	
		// if the key is set
		if ( $wibstats_gmap_key != "" )
		{
		
			echo '
		<div id="map" style="height:500px;width:100%"></div>
		<script type="text/javascript">
			function initializeMap() {
			var map = new google.maps.Map2( document.getElementById( "map" ) );
			map.setCenter( new google.maps.LatLng( 19.554270144063956,-9.4921875 ), 2 );
			map.addControl( new GMapTypeControl() );
			map.addControl( new GSmallMapControl() );
			';
			
			foreach( $countries as $country )
			{
				echo '
				
		var i' . $country->countrycode . ' = new GLatLng( ' . $country->latitude . ',' . $country->longitude .' );
		var m' . $country->countrycode . ' = new GMarker( i' . $country->countrycode . ' );
		GEvent.addListener( m' . $country->countrycode . ', "click", function() {
			m' . $country->countrycode . '.openInfoWindowHtml( \'<p>' . wibstats_country_icon( $country->country, $country->countrycode ) . $country->country . '<br />' . __( "Visitors:", "wibstats" ) . ' ' . $country->visitors . '</p>\' );
		} );
		map.addOverlay( m' . $country->countrycode . ' );
				';
			}

			echo '
			}
		</script>
			';
		}
	
		wibstats_table_header( array( "Country", "Visitors" ) );
		
		foreach( $countries as $country )
		{
			echo '
			<tr>
				<td>' . wibstats_country_icon( $country->country, $country->countrycode ) . $country->country . '</td>
				<td>' . $country->visitors . '</td>
			</tr>
			';
		}
		
		wibstats_table_footer();
	
	} else {
	
		echo '
		<p>' . __( "No countries found", "wibstats" ) . '</p>
		';
	
	}
}

// show new visitor locations report
function wibstats_newlocations_report()
{
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();

	// get DateTime four week ago
	$starttime = date( "Y-m-d", ( time() - 2419200 ) );
	$start = new DateTime( $starttime );

	// get new locations in the 4 weeks
	$sql = wibstats_sql( "new_locations", 25 );
	$results = $wpdb->get_results( $sql );
	if ( $results && is_array( $results ) && count( $results ) > 0 )
	{
		$total = count( $results );
		$max = $total;
		for( $i = 0; $i < $total; $i++ )
		{
			$date = new DateTime( date( "Y-m-d", $results[$i]->earliest ) );
			if ( $date < $start )
			{
				$max = $i;
				break;
			}
		}
	}
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; ' . __( "New visitor locations in the last 4 weeks", "wibstats" ) . '</h2>
	';
	
	wibstats_table_header( array( "Country", "City", "First visit" ) );
	
	for( $i = 0; $i < $max; $i++ )
	{
		echo '
		<tr>
			<td>' . wibstats_country_icon( $results[$i]->country, $results[$i]->countrycode ) . $results[$i]->country . '</td>
			<td>' . $results[$i]->city . '</td>
			<td><a href="admin.php?page=wibstats&amp;view=session' . $bloglink . '&amp;session=' . $results[$i]->sessionid . '">' . wibstats_date( $results[$i]->earliest ) . '</a></td>
		</tr>
		';
	}
	
	wibstats_table_footer();
}

// show popular countries report
function wibstats_popularcountries_report()
{
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();

	$sql = wibstats_sql( "popular_countries", 100 );
	$popularcountries = $wpdb->get_results( $sql );
	$sql = wibstats_sql( "total_countries" );
	$totalcountries = $wpdb->get_var( $sql );

	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; <a href="admin.php?page=wibstats-locations'.$bloglink.'">' . __( "Visitor locations", "wibstats" ) . '</a> &raquo; ' . __( "Most popular visitor countries", "wibstats" ) . '</h2>
	';
	
	wibstats_table_header( array( "Country", "Visitors", "Percent" ) );
	
	foreach ( $popularcountries as $country )
	{
		echo '
		<tr>
			<td>' . wibstats_country_icon( $country->country, $country->countrycode ) . strip_tags( $country->country ) . '</td>
			<td>' . $country->num . '</td>
			<td>' . wibstats_percent( $totalcountries, $country->num ) . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
}

// show popular cities report
function wibstats_popularcities_report()
{
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();

	$sql = wibstats_sql( "popular_cities", 100 );
	$popularcities = $wpdb->get_results( $sql );
	$sql = wibstats_sql( "total_cities" );
	$totalcities = $wpdb->get_var( $sql );

	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; <a href="admin.php?page=wibstats-locations'.$bloglink.'">' . __( "Visitor locations", "wibstats" ) . '</a> &raquo; ' . __( "Most popular visitor cities", "wibstats" ) . '</h2>
	';
	
	wibstats_table_header( array( "City", "Visitors", "Percent" ) );
	
	foreach ( $popularcities as $city )
	{
		echo '
		<tr>
			<td>' . wibstats_country_icon( $city->country, $city->countrycode ) . strip_tags( $city->city ) . '</td>
			<td>' . $city->num . '</td>
			<td>' . wibstats_percent( $totalcities, $city->num ) . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
}

// show recent locations report
function wibstats_recentlocations_report()
{
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; <a href="admin.php?page=wibstats-locations'.$bloglink.'">' . __( "Visitor locations", "wibstats" ) . '</a> &raquo; ' . __( "Recent visitor locations", "wibstats" ) . '</h2>
	';

	$sql = wibstats_sql( "recent_locations", 100 );
	$recentlocations = $wpdb->get_results( $sql );
	echo '
	';
	
	wibstats_table_header( array( "When", "Country", "City" ) );
	
	foreach ( $recentlocations as $location )
	{
		echo '
		<tr>
			<td><a href="admin.php?page=wibstats&amp;view=session' . $bloglink . '&amp;session=' . $location->sessionid . '">' . wibstats_date( $location->timestamp ) . '</a></td>
			<td>' . wibstats_country_icon( $location->country, $location->countrycode ) . strip_tags( $location->country ) . '</td>
			<td>' . strip_tags( $location->city ) . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
}

// ============================================================================================================
// Referrer functions

// show referrers report
function wibstats_referrers_report()
{
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	wibstats_report_header();
	
	// show referrer reports
	if ( $_GET["view"] == "referrers" )
	{
		wibstats_referrers_report();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_referrers_report()" );
	}
	else
	
	// show referrer report
	if ( isset( $_GET["referrer"] ) )
	{
		wibstats_referrer_report();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_referrer_report()" );
	}
	else
	
	// show recent referrers reports
	if ( $_GET["view"] == "recentreferrers" )
	{
		wibstats_recentreferrers_report();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_recentreferrers_report()" );
	}
	else
	
	// show popular referrers reports
	if ( $_GET["view"] == "popularreferrers" )
	{
		wibstats_popularreferrers_report();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_popularreferrers_report()" );
	}
	else
	{
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; ' . __( "Referrers", "wibstats" ) . '</h2>
	';
	
	// show date range reports
	echo '
	<h3>' . __( "Date range reports", "wibstats" ) . '</h3>
	
	<ul class="inline">
		<li><a href="admin.php?page=wibstats-referrers&amp;range=24hours#range24hours">' . __( "Last 24 hours", "wibstats" ) . '</a></li>
		<li><a href="admin.php?page=wibstats-referrers&amp;range=14days#range14days">' . __( "Last 14 days", "wibstats" ) . '</a></li>
		<li><a href="admin.php?page=wibstats-referrers&amp;#range12weeks">' . __( "Last 12 weeks", "wibstats" ) . '</a></li>
		<li><a href="admin.php?page=wibstats-referrers&amp;range=12months#range12months">' . __( "Last 12 months", "wibstats" ) . '</a></li>
	</ul>

	';
	
	// show 24 hour report
	if ( isset( $_GET["range"] ) && $_GET["range"] =="24hours" )
	{
		
		wibstats_24hour_report( "referrer", "!", " " . __( "where the visitor came from a referring website", "wibstats" ) );
		
	}
	
	// show 14 day report
	if ( isset( $_GET["range"] ) && $_GET["range"] =="14days" )
	{
		
		wibstats_14day_report( "referrer", "!", " " . __( "where the visitor came from a referring website", "wibstats" ) );
		
	}
	
	// show 12 week report
	if ( !isset( $_GET["range"] ) )
	{
	
		wibstats_12week_report( "referrer", "!", " " . __( "where the visitor came from a referring website", "wibstats" ) );
		
	}
	
	// show 12 month report
	if ( isset( $_GET["range"] ) && $_GET["range"] =="12months" )
	{
		
		wibstats_12month_report( "referrer", "!", " " . __( "where the visitor came from a referring website", "wibstats" ) );
		
	}
	
	$sql = wibstats_sql( "recent_referrers", 10 );
	$recentreferrers = $wpdb->get_results( $sql );

	echo '
	<div class="wibstats_col1">
	<h3>' . __( "Last 10 referrers", "wibstats" ) . ' <a href="admin.php?page=wibstats-referrers&amp;view=recentreferrers'.$bloglink.'">...' . __( "more", "wibstats" ) . '</a></h3>
	';
	
	wibstats_table_header( array( "When", "Referrer" ) );
	
	foreach ( $recentreferrers as $referrer )
	{
		echo '
		<tr>
			<td><a href="admin.php?page=wibstats&amp;view=session' . $bloglink . '&amp;session=' . $referrer->sessionid . '">' . wibstats_date( $referrer->timestamp ) . '</a></td>
			<td><a href="admin.php?page=wibstats-referrers&amp;view=referrer&amp;referrer=' . urlencode( $referrer->referrer_domain ) . '">' . $referrer->referrer_domain . '</a></td>
		</tr>
		';
	}
	
	wibstats_table_footer();
	
	echo '
	</div>
	';
	
	$sql = wibstats_sql( "popular_referrers", 10 );
	$popularreferrers = $wpdb->get_results( $sql );
	$sql = wibstats_sql( "total_referrers" );
	$totalreferrers = $wpdb->get_var( $sql );
	
	echo '
	<div class="wibstats_col2">
	<h3>' . __( "10 most popular referrers", "wibstats" ) . ' <a href="admin.php?page=wibstats-referrers&amp;view=popularreferrers'.$bloglink.'">...' . __( "more", "wibstats" ) . '</a></h3>
	';
	
	wibstats_table_header( array( "Referrer", "Visitors sent", "Percent" ) );
	
	foreach ( $popularreferrers as $referrer )
	{
		echo '
		<tr>
			<td><a href="admin.php?page=wibstats-referrers&amp;view=referrer&amp;referrer=' . urlencode( $referrer->referrer_domain ) . '">' . $referrer->referrer_domain . '</a></td>
			<td>' . $referrer->num . '</td>
			<td>' . wibstats_percent( $totalreferrers, $referrer->num ) . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
	
	echo '
	</div>
	';
	
	}
	
	wibstats_report_footer();
}

// show referrer report
function wibstats_referrer_report()
{
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; <a href="admin.php?page=wibstats-referrers'.$bloglink.'">' . __( "Referrers", "wibstats" ) . '</a> &raquo; ' . __( "Referrer: ", "wibstats" ) . ' &#39;' . urldecode( $_GET["referrer"] ) . '&#39;</h2>
	';
	
	// show date range reports
	echo '
	<h3>' . __( "Date range reports", "wibstats" ) . '</h3>
	
	<ul class="inline">
		<li><a href="admin.php?page=wibstats-referrers&amp;referrer=' . $_GET["referrer"] . '&amp;range=24hours#range24hours">' . __( "Last 24 hours", "wibstats" ) . '</a></li>
		<li><a href="admin.php?page=wibstats-referrers&amp;referrer=' . $_GET["referrer"] . '&amp;range=14days#range14days">' . __( "Last 14 days", "wibstats" ) . '</a></li>
		<li><a href="admin.php?page=wibstats-referrers&amp;referrer=' . $_GET["referrer"] . '#range12weeks">' . __( "Last 12 weeks", "wibstats" ) . '</a></li>
		<li><a href="admin.php?page=wibstats-referrers&amp;referrer=' . $_GET["referrer"] . '&amp;range=24months#range12months">' . __( "Last 12 months", "wibstats" ) . '</a></li>
	</ul>

	';
	
	// show 24 hour report
	if ( isset( $_GET["range"] ) && $_GET["range"] =="24hours" )
	{
		
		wibstats_24hour_report( "referrer_domain", urldecode( $_GET["referrer"] ), " " . __( "where the referring domain was", "wibstats" ) . " &#39;" . urldecode( $_GET["referrer"] ) . "&#39;" );
		
	}
	
	// show 14 day report
	if ( isset( $_GET["range"] ) && $_GET["range"] =="14days" )
	{
		
		wibstats_14day_report( "referrer_domain", urldecode( $_GET["referrer"] ), " " . __( "where the referring domain was", "wibstats" ) . " &#39;" . urldecode( $_GET["referrer"] ) . "&#39;" );
		
	}
	
	// show 12 week report
	if ( !isset( $_GET["range"] ) )
	{
	
		wibstats_12week_report( "referrer_domain", urldecode( $_GET["referrer"] ), " " . __( "where the referring domain was", "wibstats" ) . " &#39;" . urldecode( $_GET["referrer"] ) . "&#39;" );
		
	}
	
	// show 12 month report
	if ( isset( $_GET["range"] ) && $_GET["range"] =="12month" )
	{
		
		wibstats_12month_report( "referrer_domain", urldecode( $_GET["referrer"] ), " " . __( "where the referring domain was", "wibstats" ) . " &#39;" . urldecode( $_GET["referrer"] ) . "&#39;" );
		
	}
	
	echo '
	<div class="wibstats_col1">
	<h3>' . __( "Top 25 pages visited from this referrer", "wibstats" ) . '</h3>
	';
	
	// get the pages visited for this referrer
	$sql = wibstats_sql( "referrer_pages_visited", 25, 0, "referrer_domain=".urldecode( $_GET["referrer"] ) );
	$pages = $wpdb->get_results( $sql );
	
	if ( $pages && is_array( $pages ) && count( $pages ) > 0 )
	{
		
		wibstats_table_header( array( "Page", "Visitors" ) );
		
		foreach ( $pages as $page )
		{
			echo '
			<tr>
				<td><a href="admin.php?page=wibstats-pages&amp;view=page&amp;url=' . urlencode( $page->page ) . '">' . $page->page . '</a></td>
				<td>' . $page->visitors . '</td>
			</tr>
			';
		}
		
		wibstats_table_footer();
		
	} else {
		echo '
		<p>' . __( "No pages found for this referrer", "wibstats" ) . '</p>
		';
	}
	
	echo '
	</div>

	<div class="wibstats_col2">
	<h3>' . __( "Top 25 referrer pages with referring links", "wibstats" ) . '</h3>
	';
	
	// get the pages visited for this referrer
	$sql = wibstats_sql( "referrer_link_pages", 25, 0, "referrer_domain=".urldecode( $_GET["referrer"] ) );
	$pages = $wpdb->get_results( $sql );
	
	if ( $pages && is_array( $pages ) && count( $pages ) > 0 )
	{
		
		wibstats_table_header( array( "Page", "Visitors" ) );
		
		foreach ( $pages as $page )
		{
			echo '
			<tr>
				<td><a href="' . $page->referrer . '">' . $page->referrer . '</a></td>
				<td>' . $page->visitors . '</td>
			</tr>
			';
		}
		
		wibstats_table_footer();
		
	} else {
		echo '
		<p>' . __( "No referring pages found on this referrer", "wibstats" ) . '</p>
		';
	}
	
	echo '
	</div>
	<div class="wibstats_clear">
	<h3>' . __( "Top 25 countries visiting for this referrer", "wibstats" ) . '</h3>
	';
	
	// get the gmaps api key
	$wibstats_gmap_key = wibstats_gmap_key();
	
	// get the countries visiting from this referrer
	$sql = wibstats_sql( "referrer_countries", 25, 0, "referrer_domain=".urldecode( $_GET["referrer"] ) );
	$countries = $wpdb->get_results( $sql );
	$sql = wibstats_sql( "referrer_total_countries", 0, 0, "referrer_domain=".urldecode( $_GET["referrer"] ) );
	$totalcountries = $wpdb->get_var( $sql );
	
	if ( $countries && is_array( $countries ) && count( $countries ) > 0 )
	{
	
		// if the key is set
		if ( $wibstats_gmap_key != "" )
		{
		
			echo '
		<div id="map" style="height:400px;width:100%"></div>
		<script type="text/javascript">
			function initializeMap() {
			var map = new google.maps.Map2( document.getElementById( "map" ) );
			map.setCenter( new google.maps.LatLng( 19.554270144063956,-9.4921875 ), 1 );
			map.addControl( new GMapTypeControl() );
			map.addControl( new GSmallMapControl() );
			';
			if ( $countries && is_array( $countries ) && count( $countries ) > 0 )
			{
				foreach( $countries as $country )
				{
					echo '
					
			var i' . $country->countrycode . ' = new GLatLng( ' . $country->latitude . ',' . $country->longitude .' );
			var m' . $country->countrycode . ' = new GMarker( i' . $country->countrycode . ' );
			GEvent.addListener( m' . $country->countrycode . ', "click", function() {
				m' . $country->countrycode . '.openInfoWindowHtml( \'<p>' . wibstats_country_icon( $country->country, $country->countrycode ) . $country->country . '<br />' . __( "Visitors:", "wibstats" ) . ' ' . $country->visitors . '</p>\' );
			} );
			map.addOverlay( m' . $country->countrycode . ' );
					';
				}
			}
			echo '
			}
		</script>
			';
		}
	
		wibstats_table_header( array( "Country", "Visitors", "Percent" ) );
		
		foreach ( $countries as $country )
		{
			echo '
			<tr>
				<td>' . wibstats_country_icon( $country->country, $country->countrycode ) . $country->country . '</td>
				<td>' . $country->visitors . '</td>
				<td>' . wibstats_percent( $totalcountries, $country->visitors ) . '</td>
			</tr>
			';
		}
		
		wibstats_table_footer();
		
	} else {
	
		echo '
		<p>' . __( "No countries found", "wibstats" ) . '</p>
		';
	
	}
	
	wibstats_popular_months_report( "p.referrer_domain", urldecode( $_GET["referrer"] ) );
	
	wibstats_popular_weekdays_report( "p.referrer_domain", urldecode( $_GET["referrer"] ) );
	
	wibstats_popular_hours_report( "p.referrer_domain", urldecode( $_GET["referrer"] ) );
}

// show recent referrers report
function wibstats_recentreferrers_report()
{
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; <a href="admin.php?page=wibstats-referrers'.$bloglink.'">' . __( "Referrers", "wibstats" ) . '</a> &raquo; ' . __( "Recent referrers", "wibstats" ) . '</h2>
	';

	$sql = wibstats_sql( "recent_referrers", 100 );
	$recentreferrers = $wpdb->get_results( $sql );
	
	echo '
	<h3>' . __( "Last 100 referrers", "wibstats" ) . '</h3>
	';
	
	wibstats_table_header( array( "When", "Referrer", "Country", "City" ) );
	
	foreach ( $recentreferrers as $referrer )
	{
		echo '
		<tr>
			<td><a href="admin.php?page=wibstats&amp;view=session' . $bloglink . '&amp;session=' . $referrer->sessionid . '">' . wibstats_date( $referrer->timestamp ) . '</a></td>
			<td><a href="admin.php?page=wibstats-referrers&amp;referrer=' . urlencode( $referrer->referrer_domain ) . '">' . $referrer->referrer_domain . '</a></td>
			<td>' . wibstats_country_icon( $referrer->country, $referrer->countrycode ) . strip_tags( $referrer->country ) . '</td>
			<td>' . strip_tags( $referrer->city ) . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
}

// show popular referrers report
function wibstats_popularreferrers_report()
{
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; <a href="admin.php?page=wibstats-referrers'.$bloglink.'">' . __( "Referrers", "wibstats" ) . '</a> &raquo; ' . __( "Popular referrers", "wibstats" ) . '</h2>
	';

	$sql = wibstats_sql( "popular_referrers", 100 );
	$popularreferrers = $wpdb->get_results( $sql );
	$sql = wibstats_sql( "total_referrers" );
	$totalreferrers = $wpdb->get_var( $sql );
	
	wibstats_table_header( array( "Referrer", "Visitors sent", "Percent" ) );
	
	foreach ( $popularreferrers as $referrer )
	{
		echo '
		<tr>
			<td><a href="admin.php?page=wibstats-referrers&amp;referrer=' . urlencode( $referrer->referrer_domain ) . '">' . $referrer->referrer_domain . '</a></td>
			<td>' . $referrer->num . '</td>
			<td>' . wibstats_percent( $totalreferrers, $referrer->num ) . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
}

// ============================================================================================================
// Search functions

// show searches report
function wibstats_searches_report()
{
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	wibstats_report_header();
	
	// show search term detail reports
	if ( isset( $_GET["term"] ) )
	{
		wibstats_searchterm_report();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_searchterm_report()" );
	}
	else 
	
	// show top 100 new search terms
	if ( $_GET["view"] == "newterms" )
	{
		wibstats_newterms_report();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_newterms_report()" );
	}
	else
	
	// show popular search terms reports
	if ( $_GET["view"] == "popularterms" )
	{
		wibstats_popularterms_report();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_popularterms_report()" );
	}
	else
	
	// show recent search terms reports
	if ( $_GET["view"] == "recentterms" )
	{
		wibstats_recentterms_report();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_recentterms_report()" );
	}
	else
	
	// show popular search domains reports
	if ( $_GET["view"] == "popularsearchdomains" )
	{
		wibstats_popularsearchdomains_report();
		
		// do the stopwatch
		wibstats_stopwatch( "wibstats_popularsearchdomains_report()" );
	}
	else
	{
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; ' . __( "Searches", "wibstats" ) . '</h2>
	';
	
	// show date range reports
	echo '
	<h3>' . __( "Date range reports", "wibstats" ) . '</h3>
	
	<ul class="inline">
		<li><a href="admin.php?page=wibstats-searches&amp;range=24hours#range24hours">' . __( "Last 24 hours", "wibstats" ) . '</a></li>
		<li><a href="admin.php?page=wibstats-searches&amp;range=14days#range14days">' . __( "Last 14 days", "wibstats" ) . '</a></li>
		<li><a href="admin.php?page=wibstats-searches&amp;#range12weeks">' . __( "Last 12 weeks", "wibstats" ) . '</a></li>
		<li><a href="admin.php?page=wibstats-searches&amp;range=12months#range12months">' . __( "Last 12 months", "wibstats" ) . '</a></li>
	</ul>

	';
	
	// show 24 hour report
	if ( isset( $_GET["range"] ) && $_GET["range"] =="24hours" )
	{
		
		wibstats_24hour_report( "terms", "!", " " . __( "where the visitor came from a search engine", "wibstats" ) );
		
	}
	
	// show 14 day report
	if ( isset( $_GET["range"] ) && $_GET["range"] =="14days" )
	{
		
		wibstats_14day_report( "terms", "!", " " . __( "where the visitor came from a search engine", "wibstats" ) );
		
	}
	
	// show 12 week report
	if ( !isset( $_GET["range"] ) )
	{
	
		wibstats_12week_report( "terms", "!", " " . __( "where the visitor came from a search engine", "wibstats" ) );
		
	}

	// show 12 month report
	if ( isset( $_GET["range"] ) && $_GET["range"] == "12months" )
	{
	
		wibstats_12month_report( "terms", "!", " " . __( "where the visitor came from a search engine", "wibstats" ) );
		
	}
	
	$sql = wibstats_sql( "recent_terms", 10 );
	$recentterms = $wpdb->get_results( $sql );
	
	echo '
	<div class="wibstats_col1">
	<h3>' . __( "Last 10 search words used", "wibstats" ) . ' <a href="admin.php?page=wibstats-searches&amp;view=recentterms'.$bloglink.'">...' . __( "more", "wibstats" ) . '</a></h3>
	';
	
	wibstats_table_header( array( "When", "Referrer", "Search words", "Country", "City" ) );
	
	foreach ( $recentterms as $term )
	{
		echo '
		<tr>
			<td><a href="admin.php?page=wibstats&amp;view=session' . $bloglink . '&amp;session=' . $term->sessionid . '">' . wibstats_date( $term->timestamp ) . '</a></td>
			<td><a href="' . $term->referrer . '">' . $term->referrer_domain . '</a></td>
			<td><a href="admin.php?page=wibstats-searches&amp;view=term&amp;term=' . urlencode( $term->terms ) . '">' . $term->terms . '</a></td>
			<td>' . wibstats_country_icon( $term->country, $term->countrycode ) . strip_tags( $term->country ) . '</td>
			<td>' . strip_tags( $term->city ) . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
	
	$sql = wibstats_sql( "popular_search_domains", 10 );
	$popularengines = $wpdb->get_results( $sql );
	$sql = wibstats_sql( "total_search_domains" );
	$totalpopularengines = $wpdb->get_var( $sql );
	
	echo '
	<h3>' . __( "Top 10 most popular search domains", "wibstats" ) . ' <a href="admin.php?page=wibstats-searches&amp;view=popularsearchdomains'.$bloglink.'">...' . __( "more", "wibstats" ) . '</a></h3>
	';
	
	wibstats_table_header( array( "Domain", "Times used", "Percent" ) );
	
	foreach ( $popularengines as $popularengine )
	{
		echo '
		<tr>
			<td><a href="http://' . $popularengine->referrer_domain . '">' . $popularengine->referrer_domain . '</a></td>
			<td>' . $popularengine->num . '</td>
			<td>' . wibstats_percent( $totalpopularengines, $popularengine->num ) . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
	
	echo '
	</div>
	';
	
	$sql = wibstats_sql( "popular_terms", 25 );
	$popularterms = $wpdb->get_results( $sql );
	$sql = wibstats_sql( "total_terms" );
	$totalterms = $wpdb->get_var( $sql );

	echo '
	<div class="wibstats_col2">
	<h3>' . __( "25 most popular search words", "wibstats" ) . ' <a href="admin.php?page=wibstats-searches&amp;view=popularterms'.$bloglink.'">...' . __( "more", "wibstats" ) . '</a></h3>
	';
	
	wibstats_table_header( array( "Search words", "Times used", "Percent" ) );
	
	foreach ( $popularterms as $term )
	{
		echo '
		<tr>
			<td><a href="admin.php?page=wibstats-searches&amp;view=term&amp;term=' . urlencode( $term->terms ) . '">' . $term->terms . '</a></td>
			<td>' . $term->num . '</td>
			<td>' . wibstats_percent( $totalterms, $term->num ) . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
	
	echo '
	</div>
	';
	
	wibstats_popular_months_report( "s.terms", "!" );
	
	wibstats_popular_weekdays_report( "s.terms", "!" );
	
	wibstats_popular_hours_report( "s.terms", "!" );
	
	}
	
	wibstats_report_footer();
}

// show new search terms report
function wibstats_newterms_report()
{
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();

	// get DateTime four week ago
	$starttime = ( time() - 2419200 );
	
	// get new search terms in the last week
	$sql = wibstats_sql( "new_search_terms", 0, 0, "since=".$starttime );
	$results = $wpdb->get_results( $sql );
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; ' . __( "New search terms in the last 4 weeks", "wibstats" ) . '</h2>
	';
	if ( $results && is_array( $results ) && count( $results ) > 0 )
	{
	
	wibstats_table_header( array( "Search words", "First used" ) );
	
	foreach( $results as $term )
	{
		echo '
		<tr>
			<td><a href="admin.php?page=wibstats-searches&amp;view=term&amp;term=' . urlencode( $term->terms ) . '">' . $term->terms . '</a></td>
			<td><a href="admin.php?page=wibstats&amp;view=session' . $bloglink . '&amp;session=' . $term->sessionid . '">' . wibstats_date( $term->earliest ) . '</a></td>
		</tr>
		';
	}
	
	wibstats_table_footer();
	
	} else {
	echo '
	<p>' . __( "No new search terms in the last 4 weeks.", "wibstats" ) . '</p>
	';
	}
}

// show search term report
function wibstats_searchterm_report()
{
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; <a href="admin.php?page=wibstats-searches'.$bloglink.'">' . __( "Searches", "wibstats" ) . '</a> &raquo; ' . __( "Search: ", "wibstats" ) . ' &#39;' . urldecode( $_GET["term"] ) . '&#39;</h2>
	';
	
	// show date range reports
	echo '
	<h3>' . __( "Date range reports", "wibstats" ) . '</h3>
	
	<ul class="inline">
		<li><a href="admin.php?page=wibstats-searches&amp;view=term&amp;term=' . $_GET["term"] . '&amp;range=24hours#range24hours">' . __( "Last 24 hours", "wibstats" ) . '</a></li>
		<li><a href="admin.php?page=wibstats-searches&amp;view=term&amp;term=' . $_GET["term"] . '&amp;range=14days#range14days">' . __( "Last 14 days", "wibstats" ) . '</a></li>
		<li><a href="admin.php?page=wibstats-searches&amp;view=term&amp;term=' . $_GET["term"] . '#range12weeks">' . __( "Last 12 weeks", "wibstats" ) . '</a></li>
		<li><a href="admin.php?page=wibstats-searches&amp;view=term&amp;term=' . $_GET["term"] . '&amp;range=12months#range12months">' . __( "Last 12 months", "wibstats" ) . '</a></li>
	</ul>

	';
	
	// show 24 hour report
	if ( isset( $_GET["range"] ) && $_GET["range"] =="24hours" )
	{
		
		wibstats_24hour_report( "terms", urldecode( $_GET["term"] ), " " . __( "where the search was", "wibstats" ) . " &#39;" . urldecode( $_GET["term"] ) . "&#39;" );
		
	}
	
	// show 14 day report
	if ( isset( $_GET["range"] ) && $_GET["range"] =="14days" )
	{
		
		wibstats_14day_report( "terms", urldecode( $_GET["term"] ), " " . __( "where the search was", "wibstats" ) . " &#39;" . urldecode( $_GET["term"] ) . "&#39;" );
		
	}
	
	// show 12 week report
	if ( !isset( $_GET["range"] ) )
	{
	
		wibstats_12week_report( "terms", urldecode( $_GET["term"] ), " " . __( "where the search was", "wibstats" ) . " &#39;" . urldecode( $_GET["term"] ) . "&#39;" );
		
	}
	
	// get the pages found for this term
	$sql = wibstats_sql( "term_pages", 0, 0, "term=" . urldecode( $_GET["term"] ) );
	$pages = $wpdb->get_results( $sql );
	echo '<h3>' . __( "Pages found for this search", "wibstats" ) . '</h3>';
	wibstats_table_header( array( "Title", "URL", "Visitors" ) );
	foreach ( $pages as $page )
	{
		echo '
		<tr>
			<td>' . $page->title . '</td>
			<td><a href="' . $page->page . '">' . $page->page . '</a></td>
			<td>' . $page->visitors . '</td>
		</tr>
		';
	}
	wibstats_table_footer();
	
	echo '
	<div class="wibstats_col1">
	<h3>' . __( "Search engines used for this search", "wibstats" ) . '</h3>
	';
	
	// get the search engines used for this term
	$sql = wibstats_sql( "term_search_engines", 0, 0, "term=" . urldecode( $_GET["term"] ) );
	$engines = $wpdb->get_results( $sql );
	
	if ( $engines && is_array( $engines ) && count( $engines ) > 0 )
	{
		wibstats_table_header( array( "Search engine", "Visitors" ) );
		
		foreach ( $engines as $engine )
		{
			echo '
			<tr>
				<td><a href="http://' . $engine->referrer_domain . '">' . $engine->referrer_domain . '</a></td>
				<td>' . $engine->visitors . '</td>
			</tr>
			';
		}
		
		wibstats_table_footer();
		
	} else {
		echo '
		<p>' . __( "No search engines found for this search", "wibstats" ) . '</p>
		';
	}
	
	echo '
	</div>

	<div class="wibstats_col2">
	<h3>' . __( "Countries visiting for this search", "wibstats" ) . '</h3>
	';
	
	// get the gmaps api key
	$wibstats_gmap_key = wibstats_gmap_key();
	
	// get the countries visiting for this term
	$sql = wibstats_sql( "term_countries", 0, 0, "term=" . urldecode( $_GET["term"] ) );
	$countries = $wpdb->get_results( $sql );
	
	if ( $countries && is_array( $countries ) && count( $countries ) > 0 )
	{
	
		// if the key is set
		if ( $wibstats_gmap_key != "" )
		{
		
			echo '
		<div id="map" style="height:400px;width:100%"></div>
		<script type="text/javascript">
			function initializeMap() {
			var map = new google.maps.Map2( document.getElementById( "map" ) );
			map.setCenter( new google.maps.LatLng( 19.554270144063956,-9.4921875 ), 1 );
			map.addControl( new GMapTypeControl() );
			map.addControl( new GSmallMapControl() );
			';
			if ( $countries && is_array( $countries ) && count( $countries ) > 0 )
			{
				foreach( $countries as $country )
				{
					echo '
					
			var i' . $country->countrycode . ' = new GLatLng( ' . $country->latitude . ',' . $country->longitude .' );
			var m' . $country->countrycode . ' = new GMarker( i' . $country->countrycode . ' );
			GEvent.addListener( m' . $country->countrycode . ', "click", function() {
				m' . $country->countrycode . '.openInfoWindowHtml( \'<p>' . wibstats_country_icon( $country->country, $country->countrycode ) . $country->country . '<br />' . __( "Visitors:", "wibstats" ) . ' ' . $country->visitors . '</p>\' );
			} );
			map.addOverlay( m' . $country->countrycode . ' );
					';
				}
			}
			echo '
			}
		</script>
			';
		}
	
		wibstats_table_header( array( "Country", "Visitors" ) );
		
		foreach ( $countries as $country )
		{
			echo '
			<tr>
				<td>' . wibstats_country_icon( $country->country, $country->countrycode ) . $country->country . '</td>
				<td>' . $country->visitors . '</td>
			</tr>
			';
		}
		
		wibstats_table_footer();
	
	} else {
	
		echo '
		<p>' . __( "No countries found", "wibstats" ) . '</p>
		';
	
	}
	
	echo '
	</div>
	';
	
	wibstats_popular_months_report( "p.terms", urldecode( $_GET["term"] ) );
	
	wibstats_popular_weekdays_report( "p.terms", urldecode( $_GET["term"] ) );
	
	wibstats_popular_hours_report( "p.terms", urldecode( $_GET["term"] ) );
}

// show popular search terms report
function wibstats_popularterms_report()
{
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; <a href="admin.php?page=wibstats-searches'.$bloglink.'">' . __( "Searches", "wibstats" ) . '</a> &raquo; ' . __( "Popular search words", "wibstats" ) . '</h2>
	';

	$sql = wibstats_sql( "popular_terms", 100 );
	$popularterms = $wpdb->get_results( $sql );
	$sql = wibstats_sql( "total_terms" );
	$totalterms = $wpdb->get_var( $sql );
	
	wibstats_table_header( array( "Search words", "Times used", "Percent" ) );
	
	foreach ( $popularterms as $term )
	{
		echo '
		<tr>
			<td><a href="admin.php?page=wibstats-searches&amp;view=term&amp;term=' . urlencode( $term->terms ) . '">' . $term->terms . '</a></td>
			<td>' . $term->num . '</td>
			<td>' . wibstats_percent( $totalterms, $term->num ) . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
}

// show recent search terms report
function wibstats_recentterms_report()
{
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; <a href="admin.php?page=wibstats-searches'.$bloglink.'">' . __( "Searches", "wibstats" ) . '</a> &raquo; ' . __( "Recent search words", "wibstats" ) . '</h2>
	';

	$sql = wibstats_sql( "recent_terms", 100 );
	$recentterms = $wpdb->get_results( $sql );

	wibstats_table_header( array( "Search words", "Referrer", "Country", "City", "Page", "When" ) );
	
	foreach ( $recentterms as $term )
	{
		if ( $term->title == "" ){
			$term->title = $term->page;
		}
		echo '
		<tr>
			<td><a href="admin.php?page=wibstats-searches&amp;view=term&amp;term=' . urlencode( $term->terms ) . '">' . $term->terms . '</a></td>
			<td><a href="' . $term->referrer . '">' . $term->referrer_domain . '</a></td>
			<td>' . wibstats_country_icon( $term->country, $term->countrycode ) . strip_tags( $term->country ) . '</td>
			<td>' . strip_tags( $term->city ) . '</td>
			<td><a href="admin.php?page=wibstats-pages&amp;view=page&amp;url=' . urlencode( $term->page ) . '">' . wibstats_shorten( $term->title ) . '</a></td>
			<td><a href="admin.php?page=wibstats&amp;view=session' . $bloglink . '&amp;session=' . $term->sessionid . '">' . wibstats_date( $term->timestamp ) . '</a></td>
		</tr>
		';
	}
	
	wibstats_table_footer();
}

// show search domains report
function wibstats_popularsearchdomains_report()
{
	global $wpdb;
	$blogid = wibstats_blog_id();
	$bloglink = wibstats_blog_link();
	
	echo '
	<h2><a href="admin.php?page=wibstats'.$bloglink.'">' . __( "Blog statistics", "wibstats" ) . '</a> &raquo; <a href="admin.php?page=wibstats-searches'.$bloglink.'">' . __( "Searches", "wibstats" ) . '</a> &raquo; ' . __( "Popular search domains", "wibstats" ) . '</h2>
	';

	$sql = wibstats_sql( "popular_search_domains", 100 );
	$popularsearchdomains = $wpdb->get_results( $sql );
	$sql = wibstats_sql( "total_search_domains" );
	$totalpopularengines = $wpdb->get_var( $sql );

	wibstats_table_header( array( "Search domain", "Times used", "Percent" ) );
	
	foreach ( $popularsearchdomains as $popularsearchdomain )
	{
		echo '
		<tr>
			<td><a href="http://' . $popularsearchdomain->referrer_domain . '">' . $popularsearchdomain->referrer_domain . '</a></td>
			<td>' . $popularsearchdomain->num . '</td>
			<td>' . wibstats_percent( $totalpopularengines, $popularsearchdomain->num ) . '</td>
		</tr>
		';
	}
	
	wibstats_table_footer();
}

// ============================================================================================================
// On-page code

// load the JavaScript which calls this page
function wibstats()
{
	global $current_user;
	global $wibstats_mu;
	$disable_logged_in_user = true;
	if ( $current_user->ID == 0 || !$disable_logged_in_user )
	{
		if ( $wibstats_mu )
		{
			$path = get_option( "siteurl" ) . "/wp-content/mu-plugins/";
		} else {
			$path = get_option( "siteurl" ) . "/wp-content/plugins/" . basename( dirname( __FILE__ ) ) . "/";
		}
		$js = '
// thanks to PPK ( http://www.quirksmode.org/js/detect.html ) for this fantastic code
var BrowserDetect = {
	init: function () {
		this.browser = this.searchString( this.dataBrowser ) || "An unknown browser";
		this.version = this.searchVersion( navigator.userAgent )
			|| this.searchVersion( navigator.appVersion )
			|| "an unknown version";
		this.OS = this.searchString( this.dataOS ) || "an unknown OS";
	},
	searchString: function ( data ) {
		for ( var i=0;i<data.length;i++ )	{
			var dataString = data[i].string;
			var dataProp = data[i].prop;
			this.versionSearchString = data[i].versionSearch || data[i].identity;
			if ( dataString ) {
				if ( dataString.indexOf( data[i].subString ) != -1 )
					return data[i].identity;
			}
			else if ( dataProp )
				return data[i].identity;
		}
	},
	searchVersion: function ( dataString ) {
		var index = dataString.indexOf( this.versionSearchString );
		if ( index == -1 ) return;
		return parseFloat( dataString.substring( index+this.versionSearchString.length+1 ) );
	},
	dataBrowser: [
		{
			string: navigator.userAgent,
			subString: "Chrome",
			identity: "Chrome"
		},
		{ 	string: navigator.userAgent,
			subString: "OmniWeb",
			versionSearch: "OmniWeb/",
			identity: "OmniWeb"
		},
		{
			string: navigator.vendor,
			subString: "Apple",
			identity: "Safari",
			versionSearch: "Version"
		},
		{
			prop: window.opera,
			identity: "Opera"
		},
		{
			string: navigator.vendor,
			subString: "iCab",
			identity: "iCab"
		},
		{
			string: navigator.vendor,
			subString: "KDE",
			identity: "Konqueror"
		},
		{
			string: navigator.userAgent,
			subString: "Firefox",
			identity: "Firefox"
		},
		{
			string: navigator.vendor,
			subString: "Camino",
			identity: "Camino"
		},
		{		// for newer Netscapes ( 6+ )
			string: navigator.userAgent,
			subString: "Netscape",
			identity: "Netscape"
		},
		{
			string: navigator.userAgent,
			subString: "MSIE",
			identity: "Explorer",
			versionSearch: "MSIE"
		},
		{
			string: navigator.userAgent,
			subString: "Gecko",
			identity: "Mozilla",
			versionSearch: "rv"
		},
		{ 		// for older Netscapes ( 4- )
			string: navigator.userAgent,
			subString: "Mozilla",
			identity: "Netscape",
			versionSearch: "Mozilla"
		}
	],
	dataOS : [
		{
			string: navigator.platform,
			subString: "Win",
			identity: "Windows"
		},
		{
			string: navigator.platform,
			subString: "Mac",
			identity: "Mac"
		},
		{
			string: navigator.userAgent,
			subString: "iPhone",
			identity: "iPhone/iPod"
	    },
		{
			string: navigator.platform,
			subString: "Linux",
			identity: "Linux"
		}
	]
};
BrowserDetect.init();
var page = escape( window.location.href );
var ref = escape( top.document.referrer );
var title = escape( document.title );
var color = window.screen.colorDepth; 
var res = window.screen.width + "x" + window.screen.height;
var browser = escape( BrowserDetect.browser );
var version = escape( BrowserDetect.version );
var platform = escape( BrowserDetect.OS );
if ( typeof( google ) != "undefined" && google && google.loader && google.loader.ClientLocation && typeof( google.loader.ClientLocation.address ) != "undefined" ) {
	var city = google.loader.ClientLocation.address.city;
	var country = google.loader.ClientLocation.address.country;
	var countrycode = google.loader.ClientLocation.address.country_code;
}
document.write( \'<\' + \'img src="' . $path . 'wibstats.php?color=\' + color + \'&res=\' + res + \'&browser=\' + browser + \'&version=\' + version + \'&platform=\' + platform + \'&referrer=\' + ref + \'&page=\' + page + \'&title=\' + title + \'&city=\' + city + \'&country=\' + country + \'&countrycode=\' + countrycode + \'" height="1" width="1" />\' );
		';
		print '<script type="text/javascript">';
		print $js;
		print '</script>';
	}
}

// if all the parameters are set, run the main WibStats function
if ( 
isset( $_GET["color"] ) &&
isset( $_GET["res"] ) &&
isset( $_GET["browser"] ) &&
isset( $_GET["version"] ) &&
isset( $_GET["platform"] ) &&
isset( $_GET["referrer"] )
 )
{

	$save = true;

	// include Wordpress
	if ( file_exists( "../../../wp-blog-header.php" ) ) {
		include_once( "../../../wp-blog-header.php" );
	} else if ( file_exists( "../../wp-blog-header.php" ) ) {
		include_once( "../../wp-blog-header.php" );
	} else {
		$save = false;
	}

	// if saving
	if ( $save ) {

		// get the globals
		wibstats_globals();
		
		// check the wibstats version
		wibstats_check( false );
		
		// save the visit
		wibstats_savevisit();
	
	} else {
	
		header( "HTTP/1.0 204 No Content" );
		exit();
	
	}
}

// ============================================================================================================
// Setup and upgrade functions

// activate Wibstats
function wibstats_activate()
{
	// create the tables
	wibstats_createtables05();
}

// check WibStats is installed and is the correct version
function wibstats_check( $display )
{

	// do the stopwatch
	wibstats_stopwatch( "wibstats_check()" );

	// get the version of Wibstats for the current blog
	$version = get_option( "wibstats_version" );
	if ( $version == "" ) $version = "0.1";

	// check for version 0.2
	if ( version_compare( $version, "0.2" ) == -1 )
	{
		// check the 0.2 statistics table exists
		if ( !wibstats_checktables02() )
		{

			// create the new tables
			wibstats_createtables05();
			
			// update the option
			update_option( "wibstats_version", "0.5" );
			$version = "0.5";
			
			if ( $display ) {
				echo '<p class="updated fade">' . __( "Installed WibStats version 0.5", "wibstats" ) . '</p>';
			}
			
		} else {

			// do the stopwatch
			wibstats_stopwatch( "wibstats_createtables()" );
			
			// check for version 0.2
			if ( wibstats_check02() )
			{
				echo '<p class="updated fade">' . __( "Checking for WibStats version 0.2", "wibstats" ) . '</p>';
				if ( $display ) {
					echo '<p class="updated fade">' . __( "Updated WibStats to version 0.2", "wibstats" ) . '</p>';
				}
				
				// update the option
				update_option( "wibstats_version", "0.2" );
				$version = "0.2";
				
			} else {
				if ( $display ) {
					echo '<p class="updated fade">' . __( "Could not update WibStats to version 0.2", "wibstats" ) . '</p>';
				}
			}
			
			// do the stopwatch
			wibstats_stopwatch( "wibstats_check02()" );
		}
	}

	// check for version 0.5
	if ( version_compare( $version, "0.5" ) == -1 )
	{
	
		// create the new tables
		wibstats_createtables05();
		
		// check for version 0.5
		if ( wibstats_check05() )
		{
			if ( $display ) {
				echo '<p class="updated fade">' . __( "Updated WibStats to version 0.5", "wibstats" ) . '</p>';
			}
			
			// update the option
			update_option( "wibstats_version", "0.5" );
			$version = "0.5";
			
		} else {
		
			if ( $display ) {
				echo '<p class="updated fade">' . __( "Could not update WibStats to version 0.5", "wibstats" ) . '</p>';
			}
			
		}
		// do the stopwatch
		wibstats_stopwatch( "wibstats_check05()" );
	}
}

// check version 0.2 is installed
function wibstats_check02()
{
	global $wpdb;
	global $current_blog;
	global $wibstats_mu;
	global $wibstats_table_sessions;
	global $wibstats_table_pages;

	// create the referrer_domain column and update the table
	$sql = "select count( referrer_domain ) from " . $wpdb->prefix . "wibstats;";
	if ( !$wpdb->get_var( $sql ) )
	{
		$wpdb->query( "alter table " . $wpdb->prefix . "wibstats add referrer_domain varchar( 255 ) not null default '';" );
	}
	// get referrers
	$referrers = $wpdb->get_results( "select distinct( referrer ) as referrer from " . $wpdb->prefix . "wibstats where referrer <> '' and referrer_domain = '' order by referrer;" );

	if ( $referrers && is_array( $referrers ) && count( $referrers ) > 0 )
	{
		// loop referrers
		foreach( $referrers as $referrer )
		{
			// save the referrer domain
			$domain = wibstats_get_domain( $referrer->referrer );
			$sql = "update " . $wpdb->prefix . "wibstats set referrer_domain= '" . $wpdb->escape( $domain ) . "' where referrer = '" . $wpdb->escape( $referrer->referrer ) . "';";
			$wpdb->query( $sql );
		}
	}
	return true;
}

// check version 0.5 is installed
function wibstats_check05()
{
	global $wpdb;
	global $current_blog;
	global $wibstats_mu;
	global $wibstats_table_sessions;
	global $wibstats_table_pages;
	// move the sessions
	$sql = "insert into " . $wibstats_table_sessions . "
	( timestamp, ipaddress, sessionid, colordepth, screensize, browser, version, platform, page, title, referrer, referrer_domain, terms , city, country, countrycode, latitude, longitude )
	select
	timestamp, ipaddress, sessionid, colordepth, screensize, browser, version, platform, page, title, referrer, referrer_domain, terms , city, country, countrycode, latitude, longitude
	from " . $wpdb->prefix . "wibstats
	group by sessionid
	order by timestamp desc;";
	$movesessions = $wpdb->query( $sql );
	// move the pages
	$sql = "insert into " . $wibstats_table_pages . "
	( timestamp, page, title, sessionid, referrer, referrer_domain, terms )
	select 
	timestamp, page, title, sessionid, referrer, referrer_domain, terms
	from " . $wpdb->prefix . "wibstats
	order by timestamp asc;";
	$movepages = $wpdb->query( $sql );
	// count up the totals
	$total1 = $wpdb->get_var( "select count( timestamp ) from " . $wpdb->prefix . "wibstats;" );
	$total2 = $wpdb->get_var( "select count( timestamp ) from " . $wibstats_table_pages . ";" );
	$total3 = $wpdb->get_var( "select count( timestamp ) from " . $wibstats_table_sessions . ";" );
	if ( $total1 == $total2 && $total3 > 0 )
	{
		return true;
	} else {
		return false;
	}
}

// check the statistics table for version 0.2 exist
function wibstats_checktables02()
{
	global $wpdb;
	
	$sql = "show tables like '" . $wpdb->prefix . "wibstats';";
	if ( $wpdb->get_var( $sql ) == $wpdb->prefix . "wibstats" )
	{
		return true;
	} else {
		return false;
	}
}

// check the statistics tables for version 0.5 exist
function wibstats_checktables05()
{
	global $wpdb;
	global $wibstats_table_sessions;
	global $wibstats_table_pages;
	
	$sql1 = "show tables like '" . $wibstats_table_sessions . "';";
	$sql2 = "show tables like '" . $wibstats_table_pages . "';";
	if ( $wpdb->get_var( $sql1 ) == $wibstats_table_sessions && $wpdb->get_var( $sql2 ) == $wibstats_table_pages )
	{
		return true;
	} else {
		return false;
	}
}

// create the tables for version 2
function wibstats_createtables02( $drop = false )
{
	global $wpdb;
	global $wibstats_table_pages;

	$blogid = wibstats_blog_id();
	
	require_once( ABSPATH . "wp-admin/includes/upgrade.php" );
	
	if ( $drop )
	{
		$sql = "DROP TABLE " . $wpdb->prefix . "wibstats";
		dbDelta( $sql );
	}
	
	// create the main stats table
	$sql = "CREATE TABLE " . $wpdb->prefix . "wibstats ( 
id mediumint( 9 ) NOT NULL AUTO_INCREMENT,
timestamp bigint( 11 ),
page VARCHAR( 255 ),
title varchar( 255 ),
ipaddress VARCHAR( 24 ),
sessionid VARCHAR( 24 ),
colordepth VARCHAR( 3 ),
screensize VARCHAR( 12 ),
browser VARCHAR( 50 ),
version VARCHAR( 12 ),
platform VARCHAR( 50 ),
referrer VARCHAR( 255 ),
referrer_domain VARCHAR( 255 ),
terms VARCHAR( 255 ),
city VARCHAR( 50 ),
country VARCHAR( 50 ),
countrycode VARCHAR( 3 ),
latitude FLOAT( 10,6 ),
longitude FLOAT( 10,6 ),
PRIMARY KEY  ( id ),
KEY `timestamp` ( `timestamp` ),
KEY `page` ( `page` ),
KEY `title` ( `title` ),
KEY `ipaddress` ( `ipaddress` ),
KEY `sessionid` ( `sessionid` ),
KEY `colordepth` ( `colordepth` ),
KEY `screensize` ( `screensize` ),
KEY `browser` ( `browser` ),
KEY `version` ( `version` ),
KEY `platform` ( `platform` ),
KEY `referrer` ( `referrer` ),
KEY `referrer_domain` ( `referrer_domain` ),
KEY `terms` ( `terms` ),
KEY `city` ( `city` ),
KEY `country` ( `country` ),
KEY `countrycode` ( `countrycode` ),
KEY `latitude` ( `latitude` ),
KEY `longitude` ( `longitude` )
 );";
	dbDelta( $sql );
}

// create the tables for version 5
function wibstats_createtables05( $drop = false )
{
	global $wpdb;
	global $wibstats_table_sessions;
	global $wibstats_table_pages;
	
	require_once( ABSPATH . "wp-admin/includes/upgrade.php" );
	
	if ( $drop )
	{
		$sql = "DROP TABLE " . $wibstats_table_sessions . ";";
		dbDelta( $sql );
		$sql = "DROP TABLE " . $wibstats_table_pages . ";";
		dbDelta( $sql );
	}
	
	// create the pages stats table
	$sql = "CREATE TABLE " . $wibstats_table_pages . " ( 
id mediumint( 9 ) NOT NULL AUTO_INCREMENT,
timestamp bigint( 11 ),
page VARCHAR( 255 ),
title varchar( 255 ),
sessionid VARCHAR( 50 ),
referrer VARCHAR( 255 ),
referrer_domain VARCHAR( 255 ),
terms VARCHAR( 255 ),
PRIMARY KEY  ( id ),
KEY `timestamp` ( `timestamp` ),
KEY `page` ( `page` ),
KEY `title` ( `title` ),
KEY `sessionid` ( `sessionid` ),
KEY `referrer` ( `referrer` ),
KEY `referrer_domain` ( `referrer_domain` ),
KEY `terms` ( `terms` )
 );";
	dbDelta( $sql );
	// create the pages table indexes
	$sql = "ALTER TABLE " . $wibstats_table_pages . " ADD INDEX `pages` ( `page` , `title` );";
	dbDelta( $sql );
	$sql = "ALTER TABLE " . $wibstats_table_pages . " ADD INDEX `sessionpages` ( `page` , `sessionid` );";
	dbDelta( $sql );
	// create the sessions stats table
	$sql = "CREATE TABLE " . $wibstats_table_sessions . " ( 
id mediumint( 9 ) NOT NULL AUTO_INCREMENT,
timestamp bigint( 11 ),
ipaddress VARCHAR( 24 ),
sessionid VARCHAR( 50 ),
colordepth VARCHAR( 3 ),
screensize VARCHAR( 12 ),
browser VARCHAR( 50 ),
version VARCHAR( 12 ),
platform VARCHAR( 50 ),
page VARCHAR( 255 ),
title varchar( 255 ),
referrer VARCHAR( 255 ),
referrer_domain VARCHAR( 255 ),
terms VARCHAR( 255 ),
city VARCHAR( 50 ),
country VARCHAR( 50 ),
countrycode VARCHAR( 3 ),
latitude FLOAT( 10,6 ),
longitude FLOAT( 10,6 ),
PRIMARY KEY  ( id ),
KEY `timestamp` ( `timestamp` ),
KEY `ipaddress` ( `ipaddress` ),
KEY `sessionid` ( `sessionid` ),
KEY `colordepth` ( `colordepth` ),
KEY `screensize` ( `screensize` ),
KEY `browser` ( `browser` ),
KEY `version` ( `version` ),
KEY `platform` ( `platform` ),
KEY `page` ( `page` ),
KEY `title` ( `title` ),
KEY `referrer` ( `referrer` ),
KEY `referrer_domain` ( `referrer_domain` ),
KEY `terms` ( `terms` ),
KEY `city` ( `city` ),
KEY `country` ( `country` ),
KEY `countrycode` ( `countrycode` ),
KEY `latitude` ( `latitude` ),
KEY `longitude` ( `longitude` )
 );";
	dbDelta( $sql );
}

// ============================================================================================================
// General functions

// set page timeout
function wibstats_timelimit()
{
	set_time_limit( 600 );
}

// get a percentage
function wibstats_percent( $total, $num )
{
	if ( ( int )$total > 0 && ( int )$num > 0 )
	{
		$o = ( $num / $total );
		return round( ( $o * 100 ), 2 ) . "%";
	} else {
		return "";
	}
}

// load the globals
function wibstats_globals()
{
	global $wpdb;
	global $current_blog;
	global $wibstats_mu;
	global $wibstats_table_sessions;
	global $wibstats_table_pages;
	global $wibstats_current_blog;

	if ( defined( 'VHOST' ) ) {
		global $current_site;
		$wibstats_mu = true;
		$wibstats_current_blog = $current_blog->blog_id;
		if ( $_GET["blogid"] != "" ) {
			$wibstats_current_blog = $_GET["blogid"];
		} else if ( $_POST["blogid"] != "" ) {
			$wibstats_current_blog = $_POST["blogid"];
		}
		$wibstats_table_sessions = $wpdb->base_prefix . $wibstats_current_blog . '_wibstats_sessions';
		$wibstats_table_pages = $wpdb->base_prefix . $wibstats_current_blog . '_wibstats_pages';
	} else {
		$wibstats_mu = false;
		$wibstats_table_sessions = $wpdb->prefix . 'wibstats_sessions';
		$wibstats_table_pages = $wpdb->prefix . 'wibstats_pages';
		$wibstats_current_blog = $current_blog->id;
	}
}

// setup shortcodes
function wibstats_setup_shortcodes()
{
	// [wibstats type="" limit="" cache=""]
	add_shortcode( 'wibstats', 'wibstats_shortcode_func' );
}
function wibstats_shortcode_func( $atts, $content="" ) {
	extract( shortcode_atts( array( 
		'report' => 'popularcountries',
		'size' => '10',
		'cache' => '900'
	 ), $atts ) );
	global $wpdb;
	$r = "";
	if ( ( int )$size > 100 ) { $size = 100; }
	if ( ( int )$size < 1 ) { $size = 10; }
	if ( $cache == "" || $cache == "false" ) { $cache = -1; }
	$sessionid = "";
	if ( $report == "session" ) { $sessionid = session_id(); }
	// if cache is 0
	if ( ( int )$cache == 0 ) {
		// don't cache
		$cachecontents == false;
	} else {
		// if cache is not -1
		if ( $cache <> -1 ) {
			// change minutes to seconds
			$cache = ( $cache * 60 );
		}
		// get the cache contents
		$cachecontents = wibstats_cache( "shortcode_" . $report . $sessionid . "_" . $size, null, $cache );
	}
	if ( $cachecontents !== false && $cachecontents != "" )
	{
		return $cachecontents;
	} else {
		switch ( $report ) {
			// popular countries
			case "popularcountries":
				$sql = wibstats_sql( "popular_countries", $size );
				$rows = $wpdb->get_results( $sql );
				$sql = wibstats_sql( "total_countries", $size );
				$total = $wpdb->get_var( $sql );
				$h = array( __( "Country", "wibstats" ), __( "Visitors", "wibstats" ) );
				$r = wibstats_rs_to_table( $rows, array( "country:countrycode:country", array( $total, "num" ) ) );
			break;
			// popular cities
			case "popularcities":
				$sql = wibstats_sql( "popular_cities", $size );
				$rows = $wpdb->get_results( $sql );
				$sql = wibstats_sql( "total_cities", $size );
				$total = $wpdb->get_var( $sql );
				$h = array( __( "City", "wibstats" ), __( "Country", "wibstats" ), __( "Visitors", "wibstats" ) );
				$r = wibstats_rs_to_table( $rows, array( "city", "country:countrycode:country", array( $total, "num" ) ) );
			break;
			// recent countries
			case "recentcountries":
				$sql = wibstats_sql( "recent_countries", $size );
				$rows = $wpdb->get_results( $sql );
				$h = array( __( "Country", "wibstats" ), __( "Time", "wibstats" ) );
				$r = wibstats_rs_to_table( $rows, array( "country:countrycode:country", "time:timestamp" ) );
			break;
			// recent cities
			case "recentcities":
				$sql = wibstats_sql( "recent_cities", $size );
				$rows = $wpdb->get_results( $sql );
				$h = array( __( "City", "wibstats" ), __( "Country", "wibstats" ), __( "Time", "wibstats" ) );
				$r = wibstats_rs_to_table( $rows, array( "city", "country:countrycode:country", "time:timestamp" ) );
			break;
			// popular browsers
			case "popularbrowsers":
				$sql = wibstats_sql( "popular_browsers", $size );
				$rows = $wpdb->get_results( $sql );
				$sql = wibstats_sql( "total_browsers", $size );
				$total = $wpdb->get_var( $sql );
				$h = array( __( "Browser", "wibstats" ), __( "Visitors", "wibstats" ) );
				$r = wibstats_rs_to_table( $rows, array( "browser", array( $total, "num" ) ) );
			break;
			// popular platforms
			case "popularplatforms":
				$sql = wibstats_sql( "popular_platforms", $size );
				$rows = $wpdb->get_results( $sql );
				$sql = wibstats_sql( "total_platforms", $size );
				$total = $wpdb->get_var( $sql );
				$h = array( __( "Platform", "wibstats" ), __( "Visitors", "wibstats" ) );
				$r = wibstats_rs_to_table( $rows, array( "platform", array( $total, "num" ) ) );
			break;
			// popular screen sizes
			case "popularscreensizes":
				$sql = wibstats_sql( "popular_screen_sizes", $size );
				$rows = $wpdb->get_results( $sql );
				$sql = wibstats_sql( "total_screen_sizes", $size );
				$total = $wpdb->get_var( $sql );
				$h = array( __( "Screen size", "wibstats" ), __( "Visitors", "wibstats" ) );
				$r = wibstats_rs_to_table( $rows, array( "screensize", array( $total, "num" ) ) );
			break;
			// popular search terms
			case "popularsearches":
				$sql = wibstats_sql( "popular_terms", $size );
				$rows = $wpdb->get_results( $sql );
				$sql = wibstats_sql( "total_terms", $size );
				$total = $wpdb->get_var( $sql );
				$h = array( __( "Search", "wibstats" ), __( "Visitors", "wibstats" ) );
				$r = wibstats_rs_to_table( $rows, array( "terms", array( $total, "num" ) ) );
			break;
			// recent search terms
			case "recentsearches":
				$sql = wibstats_sql( "recent_terms", $size );
				$rows = $wpdb->get_results( $sql );
				$h = array( __( "Search", "wibstats" ), __( "Country", "wibstats" ), __( "Time", "wibstats" ) );
				$r = wibstats_rs_to_table( $rows, array( "terms", "country:countrycode:country", "time:timestamp" ) );
			break;
			// popular days of the week
			case "populardays":
				$sql = wibstats_sql( "popular_daysofweek", 0, 0, "filter=1&equals==&value=1" );
				$rows = $wpdb->get_results( $sql );
				$total = wibstats_get_total( $rows, "num" );
				$h = array( __( "Day of week", "wibstats" ), __( "Visitors", "wibstats" ) );
				$r = wibstats_rs_to_table( $rows, array( "day:col", array( $total, "num" ) ) );
			break;
			// popular hours of the day
			case "popularhours":
				$sql = wibstats_sql( "popular_hoursofday", 0, 0, "filter=1&equals==&value=1" );
				$rows = $wpdb->get_results( $sql );
				$total = wibstats_get_total( $rows, "num" );
				$h = array( __( "Hour of day", "wibstats" ), __( "Visitors", "wibstats" ) );
				$r = wibstats_rs_to_table( $rows, array( "col", array( $total, "num" ) ) );
			break;
			// popular months of the year
			case "popularmonths":
				$sql = wibstats_sql( "popular_monthsofyear", 0, 0, "filter=1&equals==&value=1" );
				$rows = $wpdb->get_results( $sql );
				$total = wibstats_get_total( $rows, "num" );
				$h = array( __( "Month", "wibstats" ), __( "Visitors", "wibstats" ) );
				$r = wibstats_rs_to_table( $rows, array( "month:col", array( $total, "num" ) ) );
			break;
			// popular referrers
			case "popularreferrers":
				$sql = wibstats_sql( "popular_referrers", $size );
				$rows = $wpdb->get_results( $sql );
				$sql = wibstats_sql( "total_referrers", $size );
				$total = $wpdb->get_var( $sql );
				$h = array( __( "Referrer", "wibstats" ), __( "Visitors", "wibstats" ) );
				$r = wibstats_rs_to_table( $rows, array( "link:referrer_domain", array( $total, "num" ) ) );
			break;
			// recent referrers
			case "recentreferrers":
				$sql = wibstats_sql( "recent_referrers", $size );
				$rows = $wpdb->get_results( $sql );
				$h = array( __( "Referrer", "wibstats" ), __( "Time", "wibstats" ) );
				$r = wibstats_rs_to_table( $rows, array( "link:referrer_domain", "time:timestamp" ) );
			break;
			// session
			case "session":
				$sql = wibstats_sql( "session", 0, 0, "session=" . session_id() );
				$row = $wpdb->get_row( $sql );
				if ( $row->browser != "" )
				{
					$h = array( __( "Field", "wibstats" ), __( "Value", "wibstats" ) );
					$r = '';
					$r .= '<tr><td>' . __( "Country", "wibstats" ) . '</td><td>' . wibstats_country_icon( $row->country, $row->countrycode ). $row->country . '</td></tr>';
					$r .= '<tr><td>' . __( "City", "wibstats" ) . '</td><td>' . $row->city . '</td></tr>';
					if ( $row->referrer_domain != "" )
					{
					$r .= '<tr><td>' . __( "Referrer", "wibstats" ) . '</td><td><a href="http://' . $row->referrer_domain . '" rel="nofollow">' . $row->referrer_domain . '</a></td></tr>';
					}
					if ( $row->terms != "" )
					{
					$r .= '<tr><td>' . __( "Search words", "wibstats" ) . '</td><td>' . $row->terms . '</td></tr>';
					}
					$r .= '<tr><td>' . __( "Browser", "wibstats" ) . '</td><td>' . $row->browser . '</td></tr>';
					$r .= '<tr><td>' . __( "Platform", "wibstats" ) . '</td><td>' . $row->platform . '</td></tr>';
					$r .= '<tr><td>' . __( "Screen size", "wibstats" ) . '</td><td>' . $row->screensize . '</td></tr>';
				}
			break;
		}
		if ( $r != "" )
		{
			$o = "";
			$o .= '
			<div class="wibstats_report ' . $report . '">
			<table
			';
			if ( $content != "" )
			{
				$o .= ' summary="' . htmlspecialchars( $content ) . '"';
			}
			$o .= '>
			<thead>
			<tr>';
			foreach( $h as $heading )
			{
				$o .= '
				<th>' . $heading . '</th>
				';
			}
			$o .= '
			</tr>
			</thead>
			<tbody>
			';
			$o .= $r;
			$o .= '
			</tbody>
			</table>
			</div>
			';
			// if using the cache
			if ( ( int )$cache <> 0 ) {
				// save the cache and return it
				return wibstats_cache( "shortcode_" . $report . $sessionid . "_" . $size, str_replace( "\t", "", str_replace( "\r", "", str_replace( "\n", "", $o ) ) ), $cache );
			} else {
				// return the output
				return $o;
			}
		}
	}
}

// get the current blog ID
function wibstats_blog_id()
{
	global $wibstats_mu;
	if ( $wibstats_mu && is_site_admin() && isset( $_POST["blogid"] ) && $_POST["blogid"] != "" )
	{
		return ( int )$_POST["blogid"];
	}
	if ( $wibstats_mu && is_site_admin() && isset( $_GET["blogid"] ) && $_GET["blogid"] != "" )
	{
		return ( int )$_GET["blogid"];
	}
	global $current_blog;
	return $current_blog->ID;
}

// get the current blog domain
function wibstats_blog_domain()
{
	global $wibstats_mu;
	if ( $wibstats_mu && is_site_admin() && isset( $_POST["blogid"] ) && $_POST["blogid"] != "" )
	{
		$details = get_blog_details( ( int )$_POST["blogid"] );
		return $details->domain;
	}
	if ( $wibstats_mu && is_site_admin() && isset( $_GET["blogid"] ) && $_GET["blogid"] != "" )
	{
		$details = get_blog_details( ( int )$_GET["blogid"] );
		return $details->domain;
	}
	global $current_blog;
	return $current_blog->domain;
}

// get the current blog query string link
function wibstats_blog_link()
{
	global $wibstats_mu;
	if ( $wibstats_mu && is_site_admin() && isset( $_POST["blogid"] ) && $_POST["blogid"] != "" )
	{
		return "&amp;blogid=".( int )$_POST["blogid"];
	}
	if ( $wibstats_mu && is_site_admin() && isset( $_GET["blogid"] ) && $_GET["blogid"] != "" )
	{
		return "&amp;blogid=".( int )$_GET["blogid"];
	}
	return "";
}

// show a date
function wibstats_date( $time )
{
	// get the time offset
	$wibstats_time_offset = ( int )get_option( "wibstats_time_offset" );
	
	// get the start of today
	$today = mktime( 0, 0, 0, date( "n" ), date( "j" ), date( "Y" ) );
	if ( $today < $time )	{
		return date( "g:i a", $time + ( $wibstats_time_offset * 60 * 60 ) );
	} else if ( date( "Y", $time ) == date( "Y", $today ) ) {
		return date( "F j, g:i a", $time + ( $wibstats_time_offset * 60 * 60 ) );
	} else {
		return date( "F j, Y, g:i a", $time + ( $wibstats_time_offset * 60 * 60 ) );
	}
}

// show the name of the month
function wibstats_month( $i, $style="M" )
{
	return date( $style, strtotime( "2000-".$i."-1" ) );
}

// show the name of the day
function wibstats_dayofweek( $i )
{
	if ( $i == 2 ) {
		return __( "Monday", "wibstats" );
	} else if ( $i == 3 ) {
		return __( "Tuesday", "wibstats" );
	} else if ( $i == 4 ) {
		return __( "Wednesday", "wibstats" );
	} else if ( $i == 5 ) {
		return __( "Thursday", "wibstats" );
	} else if ( $i == 6 ) {
		return __( "Friday", "wibstats" );
	} else if ( $i == 7 ) {
		return __( "Saturday", "wibstats" );
	} else if ( $i == 1 ) {
		return __( "Sunday", "wibstats" );
	}
}

// from user contributed notes at http://www.php.net/manual/en/function.mktime.php
function wibstats_addYearToDate( $timeStamp, $totalYears=1 ){
	$thePHPDate = getdate( $timeStamp );
	$thePHPDate['year'] = $thePHPDate['year']+$totalYears;
	$timeStamp = mktime( $thePHPDate['hours'], $thePHPDate['minutes'], $thePHPDate['seconds'], $thePHPDate['mon'], $thePHPDate['mday'], $thePHPDate['year'] );
	return $timeStamp;
}
function wibstats_addMonthToDate( $timeStamp, $totalMonths=1 ){
	// You can add as many months as you want. mktime will accumulate to the next year.
	$thePHPDate = getdate( $timeStamp ); // Covert to Array    
	$thePHPDate['mon'] = $thePHPDate['mon']+$totalMonths; // Add to Month    
	$timeStamp = mktime( $thePHPDate['hours'], $thePHPDate['minutes'], $thePHPDate['seconds'], $thePHPDate['mon'], $thePHPDate['mday'], $thePHPDate['year'] ); // Convert back to timestamp
	return $timeStamp;
}
function wibstats_addDayToDate( $timeStamp, $totalDays=1 ){
	// You can add as many days as you want. mktime will accumulate to the next month / year.
	$thePHPDate = getdate( $timeStamp );
	$thePHPDate['mday'] = $thePHPDate['mday']+$totalDays;
	$timeStamp = mktime( $thePHPDate['hours'], $thePHPDate['minutes'], $thePHPDate['seconds'], $thePHPDate['mon'], $thePHPDate['mday'], $thePHPDate['year'] );
	return $timeStamp;
}
function wibstats_addHourToDate( $timeStamp, $totalHours=1 ){
	// You can add as many days as you want. mktime will accumulate to the next month / year.
	$thePHPDate = getdate( $timeStamp );
	$thePHPDate['mhour'] = $thePHPDate['mhour']+$totalHours;
	$timeStamp = mktime( $thePHPDate['mhour'], $thePHPDate['minutes'], $thePHPDate['seconds'], $thePHPDate['mon'], $thePHPDate['day'], $thePHPDate['year'] );
	return $timeStamp;
}

// get the total of a particular row in a recordset
function wibstats_get_total( $rs, $col )
{
	$t = 0;
	if ( $rs && is_array( $rs ) && count( $rs ) )
	{
		foreach ( $rs as $row )
		{
			$t = $t + $row->$col;
		}
	}
	return $t;
}

// convert a col/num recordset to an array
function wibstats_rs_to_array( $rs )
{
	$a = array();
	$a["max"] = 0;
	if ( $rs )
	{
		foreach( $rs as $row )
		{
			$a[$row->col] = $row->num;
			if ( $row->num > $a["max"] )
			{
				$a["max"] = $row->num;
			}
			$a["total"] = $a["total"] + $row->num;
		}
	}
	return $a;
}

// convert a recordset to table rows
function wibstats_rs_to_table( $rows, $fields )
{
	$root = get_option( "site_url" );
	$r = "";
	if ( $rows && is_array( $rows ) && count( $rows ) > 0 )
	{
		foreach ( $rows as $row )
		{
			$r .= '
		<tr>
		';
			foreach ( $fields as $field )
			{
				if ( is_array( $field ) )
				{
					$r .= '
			<td>' . wibstats_percent( $field[0], $row->$field[1] ) . '</td>
					';
				} else if ( substr( $field, 0, 5 ) == "time:" ) {
					$field = str_replace( "time:", "", $field );
					$time = wibstats_date( $row->$field );
					$r .= '
			<td>' . $time . '</td>
					';
				} else if ( substr( $field, 0, 4 ) == "day:" ) {
					$field = str_replace( "day:", "", $field );
					$day = wibstats_dayofweek( $row->$field );
					$r .= '
			<td>' . $day . '</td>
					';
				} else if ( substr( $field, 0, 6 ) == "month:" ) {
					$field = str_replace( "month:", "", $field );
					$month = wibstats_month( $row->$field, "F" );
					$r .= '
			<td>' . $month . '</td>
					';
				} else if ( substr( $field, 0, 5 ) == "link:" ) {
					$field = str_replace( "link:", "", $field );
					$link = $row->$field;
					$r .= '
			<td><a href="http://' . $link . '" rel="nofollow">' . $link . '</a></td>
					';
				} else if ( substr( $field, 0, 8 ) == "country:" ) {
					$parts = explode( ":", $field );
					$code = $row->$parts[1];
					$country = $row->$parts[2];
					$r .= '
			<td>' . wibstats_country_icon( $country, $code ). $country . '</td>
					';
				} else {
					$r .= '
			<td>' . $row->$field . '</td>
					';
				}
			}
			$r .= '
		</tr>
			';
		}
	}
	return $r;
}

// shorten long text
function wibstats_shorten( $str )
{
	if ( strlen( $str ) > 24 )
	{
		return '<span title="' . $str . '">' . substr( $str, 0, 3 ) . "..." . substr( $str, -19 ) . '</span>';
	} else {
		return $str;
	}
}

// get the domain from a URL
function wibstats_get_domain( $url )
{
	$parts = explode( "/", $url );
	return $parts[2];
}

// show the percentage change in visitors
function wibstats_change( $number )
{
	if ( $number < 0 )
	{
		return '<span style="color:#900">' . $number . '%</span>';
	} else {
		return '<span style="color:#090">+' . $number . '%</span>';
	}
}

// get the country icon
function wibstats_country_icon( $country, $code )
{
	if ( strlen( $code ) != 2 )
	{
		return "";
	} else {
		global $wibstats_mu;
		$code = strtolower( $code );
		$code = str_replace( "uk", "gb", $code );
		if ( $wibstats_mu )
		{
			return '<img src="' . get_option( "siteurl" ) . '/wp-content/mu-plugins/wibstats-includes/flags/' . $code . '.png" alt="' . $country . '" /> ';
		} else {
			return '<img src="' . get_option( "siteurl" ) . '/wp-content/plugins/' . basename( dirname( __FILE__ ) ) . '/wibstats-includes/flags/' . $code . '.png" alt="' . $country . '" /> ';
		}
	}
}

// do a stopwatch, to check performance of the reports
function wibstats_stopwatch( $desc )
{
	if ( isset( $_GET["stopwatch"] ) )
	{
		global $wibstats_start;
		if ( !$wibstats_start || $wibstats_start == "" || $wibstats_start == 0 )
		{
			$x = explode( ' ', microtime() );
			$wibstats_start = $x[1].substr( $x[0], 1 );
		}
		$x = explode( ' ', microtime() );
		$stop = $x[1].substr( $x[0], 1 );
		$bench = bcsub( $stop, $wibstats_start, 6 );
		print $desc . ": " . number_format( $bench, 3 ) . "<br />";
	}
}

// get the phrase searched for, from http://www.roscripts.com/snippets/show/74
function wibstats_get_search_phrase( $referer )
{
  $key_start = 0;
  $search_phrase = "";
  // used by dogpile, excite, webcrawler, metacrawler
  if ( strpos( $referer, '/search/web/' ) !== false ) $key_start = strpos( $referer, '/search/web/' ) + 12;
  // used by chubba             
  if ( strpos( $referer, 'arg=' ) !== false ) $key_start = strpos( $referer, 'arg=' ) + 4;
  // used by dmoz              
  if ( strpos( $referer, 'search=' ) !== false ) $key_start = strpos( $referer, 'query=' ) + 7;
  // used by looksmart              
  if ( strpos( $referer, 'qt=' ) !== false ) $key_start = strpos( $referer, 'qt=' ) + 3;
  // used by scrub the web          
  if ( strpos( $referer, 'keyword=' ) !== false ) $key_start = strpos( $referer, 'keyword=' ) + 8;
  // used by overture, hogsearch            
  if ( strpos( $referer, 'keywords=' ) !== false ) $key_start = strpos( $referer, 'keywords=' ) + 9;
  // used by mamma, lycos, kanoodle, snap, whatuseek              
  if ( strpos( $referer, 'query=' ) !== false ) $key_start = strpos( $referer, 'query=' ) + 6;
  // don't allow encrypted key words by aol            
  if ( strpos( $referer, 'encquery=' ) !== false ) $key_start = 0; 
  // used by ixquick              
  if ( strpos( $referer, '&query=' ) !== false ) $key_start = strpos( $referer, '&query=' ) + 7;
  // used by aol
  if ( strpos( $referer, 'qry=' ) !== false ) $key_start = strpos( $referer, 'qry=' ) + 4;
  // used by yahoo, hotbot
  if ( strpos( $referer, 'p=' ) !== false ) $key_start = strpos( $referer, 'p=' ) + 2;
  // used by google, msn, alta vista, ask jeeves, all the web, teoma, wisenut, search.com
  if ( strpos( $referer, 'q=' ) !==  false ) $key_start = strpos( $referer, 'q=' ) + 2;
  // if present, get the search phrase from the referer
  if ( $key_start > 0 ){    
    if ( strpos( $referer, '&', $key_start ) !== false ){
      $search_phrase = substr( $referer, $key_start, ( strpos( $referer, '&', $key_start ) - $key_start ) );
    } elseif ( strpos( $referer, '/search/web/' ) !== false ){
        if ( strpos( $referer, '/', $key_start ) !== false ){
          $search_phrase = urldecode( substr( $referer, $key_start, ( strpos( $referer, '/', $key_start ) - $key_start ) ) );
        } else {
          $search_phrase = urldecode( substr( $referer, $key_start ) );
        }
    } else {
      $search_phrase = substr( $referer, $key_start );
    } 
  } 
  $search_phrase = urldecode( $search_phrase );
  return $search_phrase;
}

// ============================================================================================================
// Caching functions

function wibstats_cache( $key, $data = null, $expire = 900 )
{
	// set the cache folder
	$folder = ABSPATH . "wp-content/" . wibstats_blog_id() . ".wibstats-cache";
	// check the cache older exists
	if ( !file_exists( $folder ) ) {
		mkdir( $folder );
	}
	
	$expiry = ( time() - $expire );
	$expiry_desc = " " . $expire . " seconds";
	// if expiry is -1
	if ( $expire == -1 ) {
		// set expiry time a forever
		$expiry = ( time() + 1 );
		$expiry_desc = "ever";
	}
	
	// if data is not provided
	if ( $data == null )
	{
		// return the cache file contents
		if ( file_exists( $folder . "/" . $key ) && ( filemtime( $folder . "/" . $key ) > $expiry ) )
		{
			$cache = file_get_contents( $folder . "/" . $key );
			$cache .= "\n<!-- Fetched from cache -->\n";
			return $cache;
		} else {
			return false;
		}
	// if saving data
	} else {
		$data .= "\n<!-- Cached on " . date( "F j, Y, g:i a", time() ) . " for" . $expiry_desc . " -->\n";
		file_put_contents( $folder . "/" . $key, $data );
		return $data;
	}
}



// ============================================================================================================
// Save a visit

// save the visit
function wibstats_savevisit()
{
	global $wpdb;
	global $current_blog;
	global $wibstats_mu;
	global $wibstats_table_sessions;
	global $wibstats_table_pages;
	global $wibstats_current_blog;
	
	$debug = isset($_GET["debug"]);
	
	// default to not saving the visit
	$savevisit = false;

	// get the IP
	if ( $_SERVER["HTTP_X_FORWARDED_FOR"] ){
		$iparray = split( ',', $_SERVER["HTTP_X_FORWARDED_FOR"] ); 
		$fullip = $iparray[0];
		$fullip = $_SERVER["HTTP_X_FORWARDED_FOR"];
	} else {
		$fullip = $_SERVER["REMOTE_ADDR"];
	}
	
	// get the IP number
	$iplong = ip2long( $fullip );
	$ipnumber = sprintf( "%u", $iplong );
		
	// get the start of today
	$startoftoday = mktime( 0, 0, 0, date( "n" ), date( "j" ), date( "Y" ) );
	
	// get the page
	$page = $_GET["page"];
	
	// check if the visitor has visited this page in this session and in the last 20 minutes
	$sql = $wpdb->prepare( "select count( id ) from " . $wibstats_table_pages . "
			where sessionid = %s
			and page = %s
			and timestamp > %d
			;",
			session_id(),
			$page,
			( time() - ( 20 * 60 ) ) );
	if ( $debug ){
		print $sql . "<br /><br />";
	}
	$visited = $wpdb->get_var( $sql );
	if ( $visited == "0" ) { $savevisit = true; }
	if ( $debug ){
		print "Save visit: " . $savevisit . "<br /><br />";
	}
	
	// if saving the visit
	if ( $savevisit )
	{
		$country = "";
		$city = "";
		$countrycode = "";
		
		// see if the city and country has already been provided
		if ( isset( $_GET["city"] ) && $_GET["city"] != "" ) $city = trim( $_GET["city"] );
		if ( $city == "undefined" ) { $city = ""; }
		if ( isset( $_GET["country"] ) && $_GET["country"] != "" ) $country = trim( $_GET["country"] );
		if ( $country == "undefined" ) { $country = ""; }
		if ( isset( $_GET["countrycode"] ) && $_GET["countrycode"] != "" ) $countrycode = trim( $_GET["countrycode"] );
		if ( $countrycode == "undefined" ) { $countrycode = ""; }
		
		// get the session details
		$sql = $wpdb->prepare( "select id, country, city from " . $wibstats_table_sessions . "
							where ipaddress = %s
							and sessionid = %s
							;",
							$fullip,
							session_id() );
		$session = $wpdb->get_row( $sql );
		if ( $debug ){
			print $sql . "<br /><br />";
			print_r( $session );
		}
		
		// if the city, country and country code have not been provided
		if ( $country == "" && $city == "" && $countrycode == "" )
		{			
			// if there is no city and country saved
			if ( $session->country == "" && $session->city == "" )
			{

				// get the geographical data from an API
				$apis = array( 
						array( "url"=>"http://api.hostip.info/get_html.php?position=true&ip=%","name"=>"hostip.info" ),
						array( "url"=>"http://iplocationtools.com/ip_query.php?ip=%","name"=>"iplocationtools.com" ),
						array( "url"=>"http://www.ipmango.com/api.php?ip=%", "name"=>"ipmango.com" ),
						array( "url"=>"http://geoip.pidgets.com/?ip=%&format=xml", "name"=>"pidgets.com" ),
						array( "url"=>"http://ipinfodb.com/ip_query.php?ip=%", "name"=>"ipinfodb.com")
						 );
				
				// select a random api
				$api = rand( 0, count( $apis )-1 );

				// get the response
				$apipath = str_replace( "%", $fullip, $apis[$api]["url"] );
				$response = wp_remote_fopen( $apipath );
				
				// run the function
				
				// hostip
				if ( $apis[$api]["name"] == "hostip.info" )
				{	
					$parts = explode( "\n", $response );
					foreach( $parts as $part )
					{
						if ( substr( $part, 0, 5 ) == "City:" )
						{
							$city = trim( str_replace( "City:", "", $part ) );
						}
						if ( substr( $part, 0, 8 ) == "Country:" )
						{
							$city = trim( str_replace( "Country:", "", $part ) );
						}
						if ( substr( $part, 0, 10 ) == "Longitude:" )
						{
							$city = trim( str_replace( "Longitude:", "", $part ) );
						}
						if ( substr( $part, 0, 9 ) == "Latitude:" )
						{
							$city = trim( str_replace( "Latitude:", "", $part ) );
						}
					}
				}
				
				// iplocationtools
				if ( $apis[$api]["name"] == "iplocationtools.com" )
				{	
					$xml = @simplexml_load_string( $response );
					$country = $xml->CountryName;
					$countrycode = $xml->CountryCode;
					$city = $xml->City;
					$lat = $xml->Latitude;
					$long = $xml->Longitude;
				}
				
				// ipmango
				if ( $apis[$api]["name"] == "ipmango.com" )
				{	
					$xml = @simplexml_load_string( $response );
					$country = $xml->countryname;
					$countrycode = $xml->countrycode;
					$city = $xml->city;
					$lat = $xml->latitude;
					$long = $xml->longitude;
				}
				
				// pidgets
				if ( $apis[$api]["name"] == "pidgets.com" )
				{	
					$xml = @simplexml_load_string( $response );
					$country = $xml->country_name;
					$countrycode = $xml->country_code;
					$city = $xml->city;
					$lat = $xml->latitude;
					$long = $xml->longitude;
				}
				
				// ipinfodb
				if ( $apis[$api]["name"] == "ipinfodb.com" )
				{
					$xml = @simplexml_load_string( $response );
					$country = $xml->CountryName;
					$countrycode = $xml->CountryCode;
					$city = $xml->City;
					$lat = $xml->Latitude;
					$long = $xml->Longitude;
				}
			
			} else {
			
				// get the session country and city
				$country = $session->country;
				$city = $session->city;
			
			}
		}
		
		// replace empty values
		if ( $country == "" ) $country = "Unknown";
		if ( $city == "" ) $city = "Unknown";
		if ( $countrycode == "" ) $countrycode = "unk";
		
		// get the parameters
		$colordepth = $_GET["color"];
		$screensize = $_GET["res"];
		$browser = $_GET["browser"];
		$version = $_GET["version"];
		$platform = $_GET["platform"];
		$referrer = $_GET["referrer"];
		$title = $_GET["title"];
		
		// get the search terms
		$terms = wibstats_get_search_phrase( $referrer );
		
		// get the referrer domain
		$domain = wibstats_get_domain( $referrer );
		
		// if the session does not exist
		if ( $session->id == "" )
		{
			// save the session
			$sql = $wpdb->prepare( "insert into " . $wibstats_table_sessions . "
				( timestamp, ipaddress, sessionid,
				colordepth, screensize, browser,
				version, platform, 
				city, country, countrycode,
				page, title,
				referrer, referrer_domain, terms,
				latitude, longitude )
				values
				( %d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s );",
				time(), $fullip, session_id(), $colordepth, $screensize, $browser,
				$version, $platform, $city, $country, $countrycode, $page, $title, $referrer, $domain, $terms, $lat, $long );
			$wpdb->query( $sql );
			if ( $debug ){
				print $sql . "<br /><br />";
			}
		}
		
		// add the visit
		$sql = $wpdb->prepare( "insert into " . $wibstats_table_pages . "
				( timestamp, page, title, sessionid,
				referrer, referrer_domain, terms )
				values
				( %d, %s, %s, %s, %s, %s, %s );",
				time(), $page, $title, session_id(), $referrer, $domain, $terms );
		$wpdb->query( $sql );
		if ( $debug ){
			print $sql . "<br /><br />";
		}
	}

	if ( !$debug ){
		// output a 1px square transparent GIF
		header("Content-type: image/gif");
		header("Server: WibStats");
		$im = imageCreate(1, 1);
		$backgroundColor = imageColorAllocate($im, 255, 255, 255);
		imageFilledRectangle($im, 0, 0, $width - 1 , $height - 1, $backgroundColor);
		imageColorTransparent($im, $backgroundColor);
		imageInterlace($im);
		imageGif($im);
		imageDestroy($im);
	}
	exit();
}

// ============================================================================================================
// Demo data

// create demo data
function wibstats_demodata()
{
	wibstats_report_header();

	echo '<h2>' . __( "Create demo data", "wibstats" ) . '</h2>
	<p>' . __( "Are you sure you want to do this? It will delete ALL your current statistics.", "wibstats" ) . '<p>
	';
	
	if ( @$_POST["wibstats_create_demodata"] == "" )
	{
	
		echo '
		<form action="admin.php?page=wibstats-demodata" method="post">
		<p><button type="submit" name="wibstats_create_demodata" value="1" class="button">' . __( "Yes, create demo data", "wibstats" ) . '</button><p>
		</p>
		<p><a href="admin.php?page=wibstats">' . __( "No, return to the main report", "wibstats" ) . '</a><p>
		';
	
	} else {
	
		echo '<p>' . __( "Creating demo data...", "wibstats" ) . '</p>';
		
		wibstats_create_demodata();
	
	}
	
	wibstats_report_footer();
}

// create demo data
function wibstats_create_demodata()
{
	global $wpdb;
	global $wibstats_table_sessions;
	global $wibstats_table_pages;
	
	// delete pages data
	$sql = "delete from " . $wibstats_table_pages . ";";
	$wpdb->query( $sql );
	
	// reset identity
	$sql = "ALTER TABLE " . $wibstats_table_pages . " AUTO_INCREMENT = 0";
	$wpdb->query( $sql );

	// delete sessions data
	$sql = "delete from " . $wibstats_table_sessions . ";";
	$wpdb->query( $sql );
	
	// reset identity
	$sql = "ALTER TABLE " . $wibstats_table_sessions . " AUTO_INCREMENT = 0";
	$wpdb->query( $sql );
	
	// do the stopwatch
	wibstats_stopwatch( "Delete old stats" );

	// insert 100 random sessions
	for( $i = 0; $i < 100; $i++ )
	{
		$fullip = "IP" . $i;
		$session = $i;
		$colordepths = array( "16", "24", "32" );
		$colordepth = $colordepths[array_rand( $colordepths )];
		$screensizes = array( "800x600", "1024x768", "1184x960", "1200x1024", "1600x1200", "1200x900" );
		$screensize = $screensizes[array_rand( $screensizes )];
		$browsers = array( "Chrome","Safari","iCab","Internet Explorer","Firefox","KHTML","Opera" );
		$browser = $browsers[array_rand( $browsers )];
		$version = rand( 1,10 );
		$platforms = array( "Windows", "MacPPC", "MacIntel", "Linux" );
		$platform = $platforms[array_rand( $platforms )];
		$city = "City" . rand( 1,250 );
		$country = "Country" . rand( 1,50 );
		$countrycode = str_replace( "Country", "C", $country );
		$lat = round( ( -85+lcg_value()*( abs( 85-( -85 ) ) ) ), 6 );
		$long = round( ( -180+lcg_value()*( abs( 180-( -180 ) ) ) ), 6 );
		$sql = $wpdb->prepare( "insert into " . $wibstats_table_sessions . "
				( timestamp, ipaddress, sessionid,
				colordepth, screensize, browser,
				version, platform, 
				city, country, countrycode,
				latitude, longitude )
				values
				( %d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s );",
				0, $fullip, $session, $colordepth, $screensize, $browser,
				$version, $platform, $city, $country, $countrycode, $lat, $long );
		$wpdb->query( $sql );
	}
	// update 70 sessions to have one of 50 random referrers
	$sql = "update " . $wibstats_table_sessions . " set referrer = round( ( rand()*50 ), 0 ), referrer_domain = round( ( rand()*50 ), 0 ) where id < 71;";
	$wpdb->query( $sql );
	
	// update 50 referral sessions to have one of 100 random search terms
	$sql = "update " . $wibstats_table_sessions . " set terms = round( ( rand()*100 ), 0 ) where id < 51;";
	$wpdb->query( $sql );
	
	// do the stopwatch
	wibstats_stopwatch( "Insert 100 random sessions" );
	// duplicate the data 5 times
	for ( $i = 0; $i < 5; $i++ )
	{
		$sql = "insert into " . $wibstats_table_sessions . "
				( timestamp, ipaddress, sessionid,
				colordepth, screensize, browser,
				version, platform, 
				city, country, countrycode,
				latitude, longitude )
				select
				timestamp, ipaddress, sessionid,
				colordepth, screensize, browser,
				version, platform, 
				city, country, countrycode,
				latitude, longitude
				from " . $wibstats_table_sessions . ";";
		$wpdb->query( $sql );
	}
	// do the stopwatch
	wibstats_stopwatch( "Duplicate random sessions 5 times" );
	
	// get session IDs
	$ids = $wpdb->get_results( "select id from " . $wibstats_table_sessions . ";" );
	
	echo '<p>' . __( "Sessions added:", "wibstats" ) . ' ' . count( $ids ) . '</p>';
	
	// update the time to be random for each row
	$seconds = ( 60 * 60 * 24 * 365 );
	$start = time() - $seconds;
	$sql = "update " . $wibstats_table_sessions . " join ( select id from " . $wibstats_table_sessions . " ) w2 set timestamp = " . $start . " + ( rand()*" . $seconds . " );";
	$wpdb->query( $sql );
	
	// do the stopwatch
	wibstats_stopwatch( "Randomise session timestamps" );

	// insert 50 random pages
	for( $i = 0; $i < 50; $i++ )
	{
		$page = "page-" . rand( 1, 50 );
		$title = str_replace( "page-", "Page ", $page );
		$referrer = "referrer-" . rand( 1, 100 );
		$domain = $referrer;
		$term = array( "" );
		for( $z = 0; $z < 50; $z++ )
		{
			$term[] = "";
			$term[] = "search " . $z;
		}
		$terms = $term[array_rand( $term )];
		$sql = $wpdb->prepare( "insert into " . $wibstats_table_pages . "
				( timestamp, page, title, sessionid,
				referrer, referrer_domain, terms )
				values
				( %d, %s, %s, %s, %s, %s, %s );",
				0, $page, $title, "", $referrer, $domain, $terms );
		$wpdb->query( $sql );
	}
	
	// do the stopwatch
	wibstats_stopwatch( "Insert 50 random page requests" );
	
	// duplicate the data 5 times
	for ( $i = 0; $i < 5; $i++ )
	{
		$sql = "insert into " . $wibstats_table_pages . "
				( timestamp, page, title, sessionid,
				referrer, referrer_domain, terms )
				select
				timestamp, page, title, sessionid,
				referrer, referrer_domain, terms
				from " . $wibstats_table_pages . ";";
		$wpdb->query( $sql );
	}
	
	// do the stopwatch
	wibstats_stopwatch( "Duplicate random page requests 10 times" );
	
	// get page IDs
	$ids = $wpdb->get_results( "select id from " . $wibstats_table_pages . ";" );
	
	echo '<p>' . __( "Pages added:", "wibstats" ) . ' ' . count( $ids ) . '</p>';
	
	// update the session to be random for each row
	$seconds = ( 60 * 60 * 24 * 365 );
	$start = time() - $seconds;
	$sql = "update " . $wibstats_table_pages . " join ( select id from " . $wibstats_table_pages . "  ) w2 set sessionid = round( ( rand()*100 ), 0 );";
	$wpdb->query( $sql );
	
	// do the stopwatch
	wibstats_stopwatch( "Randomise page request sessions" );
	
	// update the time to be random for each row
	$seconds = ( 60 * 60 * 24 * 365 );
	$start = time() - $seconds;
	$sql = "update " . $wibstats_table_pages . " join ( select id from " . $wibstats_table_pages . " ) w2 set timestamp = " . $start . " + ( rand()*" . $seconds . " );";
	$wpdb->query( $sql );
	
	// do the stopwatch
	wibstats_stopwatch( "Randomise page request timestamps" );
}

// ============================================================================================================
// SQL statements

// get the total number of rows found for a query
function wibstats_total_rows()
{
	global $wpdb;
	return $wpdb->get_var( "SELECT FOUND_ROWS();" );
}

// get the SQL string for a report
function wibstats_sql( $report, $num = 0, $start = 0, $params = "" )
{
	global $wpdb;
	global $current_blog;
	global $wibstats_mu;
	global $wibstats_table_sessions;
	global $wibstats_table_pages;
	global $wibstats_current_blog;
	
	// extract parameters
	parse_str( $params, $parameters );
	
	switch ( $report ) {
	
		// total number of visitors ever
		case "total_visitors":
		$sql = "select count( distinct( sessionid ) ) as num from " . $wibstats_table_sessions . "";
		break;
		
		// total number of visitors since a timestamp
		case "total_visitors_since":
		$sql = "select count( distinct( sessionid ) ) as num from " . $wibstats_table_sessions . " where timestamp > " . ( int )$parameters["since"] . "";
		break;

		// total number of visitors between two dates
		case "total_visitors_between":
		$sql = "select count( distinct( sessionid ) ) as num from " . $wibstats_table_sessions . " where timestamp >= " . ( int )$parameters["start"] . " and timestamp < " . ( int )$parameters["end"] . "";
		break;
	
		// total number of pageviews ever
		case "total_pageviews":
		$sql = "select count( id ) as num from " . $wibstats_table_pages . "";
		break;
		
		// total number of pageviews since a timestamp
		case "total_pageviews_since":
		$sql = "select count( id ) as num from " . $wibstats_table_pages . " where timestamp > " . ( int )$parameters["since"] . "";
		break;
		
		// total number of pageviews between two dates
		case "total_pageviews_between":
		$sql = "select count( id ) as num from " . $wibstats_table_pages . " where timestamp >= " . ( int )$parameters["start"] . " and timestamp < " . ( int )$parameters["end"] . "";
		break;
		
		// total number of search visitors
		case "total_search_visitors":
		$sql = "select count( distinct( sessionid ) ) as num from " . $wibstats_table_sessions . " where terms <> ''";
		break;
		
		// total number of search visitors since a timestamp
		case "total_search_visitors_since":
		$sql = "select count( distinct( sessionid ) ) as num from " . $wibstats_table_sessions . " where timestamp > " . ( int )$parameters["since"] . " and terms <> ''";
		break;
		
		// total number of search visitors between two dates
		case "total_search_visitors_between":
		$sql = "select count( distinct( sessionid ) ) as num from " . $wibstats_table_sessions . " where timestamp >= " . ( int )$parameters["start"] . " and timestamp < " . ( int )$parameters["end"] . " and terms <> ''";
		break;
		
		// total number of referrer visitors
		case "total_referrer_visitors":
		$sql = "select count( distinct( sessionid ) ) as num from " . $wibstats_table_sessions . " where terms = '' and referrer <> ''";
		break;
		
		// total number of referrer visitors since a timestamp
		case "total_referrer_visitors_since":
		$sql = "select count( distinct( sessionid ) ) as num from " . $wibstats_table_sessions . " where timestamp > " . ( int )$parameters["since"] . " and terms = '' and referrer <> ''";
		break;
		
		// total number of referrer visitors between two dates
		case "total_referrer_visitors_between":
		$sql = "select count( distinct( sessionid ) ) as num from " . $wibstats_table_sessions . " where timestamp >= " . ( int )$parameters["start"] . " and timestamp < " . ( int )$parameters["end"] . " and terms = '' and referrer <> ''";
		break;
		
		// total number of direct visitors
		case "total_direct_visitors":
		$sql = "select count( distinct( sessionid ) ) as num from " . $wibstats_table_sessions . " where referrer = ''";
		break;
	
		// total number of direct visitors since a timestamp
		case "total_direct_visitors_since":
		$sql = "select count( distinct( sessionid ) ) as num from " . $wibstats_table_sessions . " where timestamp > " . ( int )$parameters["since"] . " and referrer = ''";
		break;
		
		// total number of direct visitors between two dates
		case "total_direct_visitors_between":
		$sql = "select count( distinct( sessionid ) ) as num from " . $wibstats_table_sessions . " where timestamp >= " . ( int )$parameters["start"] . " and timestamp < " . ( int )$parameters["end"] . " and referrer = ''";
		break;
		
		// popular months of the year
		case "popular_monthsofyear":
		$sql = "select count( distinct( s.id ) ) as num, month( FROM_UNIXTIME( s.timestamp ) ) as col
				from " . $wibstats_table_sessions . " s
				left outer join " . $wibstats_table_pages . " p on p.sessionid = s.sessionid
				where " . stripslashes( stripslashes( $wpdb->escape( $parameters["filter"] ) ) ) . " " . stripslashes( stripslashes( $wpdb->escape( $parameters["equals"] ) ) ) . " '" . stripslashes( stripslashes( $wpdb->escape( $parameters["value"] ) ) ) . "'
				group by month( FROM_UNIXTIME( s.timestamp ) )
				order by month( FROM_UNIXTIME( s.timestamp ) )";
		break;
		
		// popular weeks of the year
		case "popular_weeksofyear":
		$sql = "select count( distinct( id ) ) as num, weekofyear( FROM_UNIXTIME( s.timestamp ) ) as col
				from " . $wibstats_table_sessions . " s
				left outer join " . $wibstats_table_pages . " p on p.sessionid = s.sessionid
				where " . stripslashes( stripslashes( $wpdb->escape( $parameters["filter"] ) ) ) . " " . stripslashes( stripslashes( $wpdb->escape( $parameters["equals"] ) ) ) . " '" . stripslashes( stripslashes( $wpdb->escape( $parameters["value"] ) ) ) . "'
				group by weekofyear( FROM_UNIXTIME( s.timestamp ) )
				order by weekofyear( FROM_UNIXTIME( s.timestamp ) )";
		break;
		
		// popular days of the month
		case "popular_daysofmonth":
		$sql = "select count( distinct( s.id ) ) as num, day( FROM_UNIXTIME( s.timestamp ) ) as col
				from " . $wibstats_table_sessions . " s
				left outer join " . $wibstats_table_pages . " p on p.sessionid = s.sessionid
				where " . stripslashes( stripslashes( $wpdb->escape( $parameters["filter"] ) ) ) . " " . stripslashes( stripslashes( $wpdb->escape( $parameters["equals"] ) ) ) . " '" . stripslashes( stripslashes( $wpdb->escape( $parameters["value"] ) ) ) . "'
				group by day( FROM_UNIXTIME( s.timestamp ) )
				order by day( FROM_UNIXTIME( s.timestamp ) )";
		break;
		
		// popular days of the week
		case "popular_daysofweek":
		$sql = "select count( distinct( s.id ) ) as num, dayofweek( FROM_UNIXTIME( s.timestamp ) ) as col
				from " . $wibstats_table_sessions . " s
				left outer join " . $wibstats_table_pages . " p on p.sessionid = s.sessionid
				where " . stripslashes( stripslashes( $wpdb->escape( $parameters["filter"] ) ) ) . " " . stripslashes( stripslashes( $wpdb->escape( $parameters["equals"] ) ) ) . " '" . stripslashes( stripslashes( $wpdb->escape( $parameters["value"] ) ) ) . "'
				group by dayofweek( FROM_UNIXTIME( s.timestamp ) )
				order by dayofweek( FROM_UNIXTIME( s.timestamp ) )";
		break;
		
		// popular hours of the day
		case "popular_hoursofday":
		$sql = "select count( distinct( s.id ) ) as num, hour( FROM_UNIXTIME( s.timestamp ) ) as col
				from " . $wibstats_table_sessions . " s
				left outer join " . $wibstats_table_pages . " p on p.sessionid = s.sessionid
				where " . stripslashes( stripslashes( $wpdb->escape( $parameters["filter"] ) ) ) . " " . stripslashes( stripslashes( $wpdb->escape( $parameters["equals"] ) ) ) . " '" . stripslashes( stripslashes( $wpdb->escape( $parameters["value"] ) ) ) . "'
				group by hour( FROM_UNIXTIME( s.timestamp ) )
				order by hour( FROM_UNIXTIME( s.timestamp ) )";
		break;
		
		// recent referrers
		case "recent_referrers":
		$sql = "select referrer, referrer_domain, timestamp, sessionid
				from " . $wibstats_table_sessions . "
				where referrer <> ''
				and terms = ''
				and referrer_domain <> '" . wibstats_blog_domain() . "'
				order by timestamp desc";
		break;
		
		// popular referrers
		case "popular_referrers":
		$sql = "select referrer_domain, count( id ) as num
				from " . $wibstats_table_sessions . "
				where referrer_domain <> ''
				and terms = ''
				and referrer_domain <> '" . wibstats_blog_domain() . "'
				group by referrer_domain
				order by count( id ) desc";
		break;
		
		// total referrers
		case "total_referrers":
		$sql = "select count( id ) as num
				from " . $wibstats_table_sessions . "
				where referrer_domain <> ''
				and terms = ''
				and referrer_domain <> '" . wibstats_blog_domain() . "'";
		break;
		
		// pages visited by a referrer
		case "referrer_pages_visited":
		$sql = "select page, count( page ) as visitors
				from " . $wibstats_table_pages . "
				where referrer_domain = '" . $wpdb->escape( $parameters["referrer_domain"] ) . "'
				group by referrer_domain
				order by count( page ) desc";
		break;
		
		// referrer pages with referral links
		case "referrer_link_pages";
		$sql = "select referrer, count( referrer ) as visitors
				from " . $wibstats_table_pages . "
				where referrer_domain = '" . $wpdb->escape( $parameters["referrer_domain"] ) . "'
				group by referrer
				order by count( referrer ) desc";
		break;
		
		// countries visiting from this referrer
		case "referrer_countries":
		$sql = "select country, countrycode, avg( latitude ) as latitude, avg( longitude ) as longitude, count( country ) as visitors
				from " . $wibstats_table_sessions . "
				where referrer_domain = '" . $wpdb->escape( $parameters["referrer_domain"] ) . "'
				and latitude <> 0
				and longitude <> 0
				and country <> ''
				and countrycode <> ''
				group by country
				order by count( country ) desc";
		break;
		
		// total countries visiting from this referrer
		case "referrer_total_countries":
		$sql = "select count( country ) as visitors
				from " . $wibstats_table_sessions . "
				where referrer_domain = '" . $wpdb->escape( $parameters["referrer_domain"] ) . "'
				and latitude <> 0
				and longitude <> 0
				and country <> ''
				and countrycode <> ''";
		break;
		
		// recent search terms
		case "recent_terms":
		$sql = "select terms, referrer, referrer_domain, country, countrycode, city, timestamp, sessionid
				from " . $wibstats_table_sessions . "
				where terms <> ''
				order by timestamp desc";
		break;
		
		// popular search domains
		case "popular_search_domains":
		$sql = "select count( referrer_domain ) as num, referrer_domain
				from " . $wibstats_table_sessions . "
				where terms <> ''
				and referrer_domain <> ''
				group by referrer_domain
				order by count( referrer_domain ) desc";
		break;
		
		// total search domains
		case "total_search_domains":
		$sql = "select count( referrer_domain ) as num
				from " . $wibstats_table_sessions . "
				where terms <> ''
				and referrer_domain <> ''";
		break;
		
		// recent visitors with a location
		case "recent_visitor_locations":
		$sql = "select country, countrycode, city, sessionid, latitude, longitude, timestamp
				from " . $wibstats_table_sessions . "
				where country <> ''
				and countrycode <> ''
				and latitude <> 0
				and longitude <> 0
				order by timestamp desc";
		break;
		
		// visitors by country
		case "countries":
		$sql = "select count( country ) as visitors, country, countrycode, avg( latitude ) as latitude, avg( longitude ) as longitude
				from " . $wibstats_table_sessions . "
				where country <> ''
				and latitude <> 0
				and longitude <> 0
				group by country, countrycode
				order by count( country ) desc";
		break;
		
		// new search terms
		case "new_search_terms":
		$sql = "select terms, min( timestamp ) as earliest, sessionid, referrer, referrer_domain
				from " . $wibstats_table_sessions . "
				where terms <> ''
				and ( '" . $wpdb->escape( $parameters["since"] ) . "' = ''
				or timestamp > " . $wpdb->escape( ( int )$parameters["since"] ) . " )
				group by terms
				order by min( timestamp ) desc";
		break;
		
		// popular search terms
		case "popular_terms":
		$sql = "select terms, count( id ) as num
				from " . $wibstats_table_sessions . "
				where terms <> ''
				group by terms
				order by count( id ) desc";
		break;
		
		// total search terms
		case "total_terms":
		$sql = "select count( id ) as num
				from " . $wibstats_table_sessions . "
				where terms <> ''";
		break;
		
		// search engines used for a term
		case "term_search_engines":
		$sql = "select referrer_domain, count( referrer_domain ) as visitors
				from " . $wibstats_table_sessions . "
				where terms = '" . $wpdb->escape( $parameters["term"] ) . "'
				group by referrer_domain
				order by count( referrer_domain ) desc";
		break;
		
		// pages found for a term
		case "term_pages":
		$sql = "select title, page, count( page ) as visitors
				from " . $wibstats_table_sessions . "
				where terms = '" . $wpdb->escape( $parameters["term"] ) . "'
				group by page
				order by count( page ) desc";
		break;
		
		// countries visiting for a search term
		case "term_countries":
		$sql = "select country, countrycode, avg( latitude ) as latitude, avg( longitude ) as longitude, count( country ) as visitors
				from " . $wibstats_table_sessions . "
				where terms = '" . $wpdb->escape( $parameters["term"] ) . "'
				and latitude <> 0
				and longitude <> 0
				and country <> ''
				and countrycode <> ''
				group by country
				order by count( country ) desc";
		break;
		
		// new locations
		case "new_locations":
		$sql = "select s.country, s.countrycode, s.city, min( s.timestamp ) as earliest, e.sessionid, e.referrer
				from " . $wibstats_table_sessions . " s
				inner join " . $wibstats_table_sessions . " e on ( e.country = s.country or e.city = s.city ) and e.timestamp = s.timestamp
				where s.country <> ''
				group by s.country, s.city
				order by min( s.timestamp ) desc";
		break;
		
		// recent locations
		case "recent_locations":
		$sql = "select country, countrycode, city, timestamp, sessionid
				from " . $wibstats_table_sessions . "
				where country <> ''
				and countrycode <> ''
				group by sessionid
				order by timestamp desc";
		break;
		
		// recent countries
		case "recent_countries":
		$sql = "select country, countrycode, timestamp, sessionid
				from " . $wibstats_table_sessions . "
				where country <> ''
				and countrycode <> ''
				group by sessionid
				order by timestamp desc";
		break;
		
		// recent cities
		case "recent_cities":
		$sql = "select country, countrycode, city, timestamp, sessionid
				from " . $wibstats_table_sessions . "
				where country <> ''
				and countrycode <> ''
				and city <> ''
				group by sessionid
				order by timestamp desc";
		break;
		
		// popular countries
		case "popular_countries":
		$sql = "select country, countrycode, count( distinct( sessionid ) ) as num
				from " . $wibstats_table_sessions . "
				where country <> ''
				and countrycode <> ''
				group by country
				order by count( distinct( sessionid ) ) desc";
		break;
		
		// total countries
		case "total_countries":
		$sql = "select count( distinct( sessionid ) )
				from " . $wibstats_table_sessions . "
				where country <> ''
				and countrycode <> ''";
		break;
		
		// popular cities
		case "popular_cities":
		$sql = "select country, countrycode, city, count( distinct( sessionid ) ) as num
				from " . $wibstats_table_sessions . "
				where country <> ''
				and countrycode <> ''
				and city <> ''
				group by country, city
				order by count( distinct( sessionid ) ) desc";
		break;
		
		// total cities
		case "total_cities":
		$sql = "select count( distinct( sessionid ) )
				from " . $wibstats_table_sessions . "
				where country <> ''
				and countrycode <> ''
				and city <> ''";
		break;
		
		// recent pages
		case "recent_pages":
		$sql = "select p.page, p.title, s.country, s.countrycode, s.city, p.timestamp, s.sessionid
				from " . $wibstats_table_pages . " p
				inner join " . $wibstats_table_sessions . " s on s.sessionid = p.sessionid
				order by p.timestamp desc";
		break;
		
		// popular pages
		case "popular_pages":
		$sql = "select count( page ) as num, page, title
				from " . $wibstats_table_pages . "
				where page <> ''
				group by page, title
				order by count( page ) desc";
		break;

		// total pages
		case "total_pages":
		$sql = "select count( page ) as num
				from " . $wibstats_table_pages . "
				where page <> ''";
		break;
		
		// session count by hour
		case "session_count_by_hour":	
		$sql = "select timestamp, hour( FROM_UNIXTIME( timestamp ) ) as col, count( id ) as num
				from " . $wibstats_table_sessions . " where 
				timestamp >= " . $wpdb->escape( $parameters["start"] ) . "
				and " . stripslashes( stripslashes( $wpdb->escape( $parameters["filter"] ) ) ) . " " . stripslashes( stripslashes( $wpdb->escape( $parameters["equals"] ) ) ) . " '" . stripslashes( stripslashes( $wpdb->escape( $parameters["value"] ) ) ) . "'
				group by hour( FROM_UNIXTIME( timestamp ) ), day( FROM_UNIXTIME( timestamp ) ), month( FROM_UNIXTIME( timestamp ) ), year( FROM_UNIXTIME( timestamp ) )
				order by year( FROM_UNIXTIME( timestamp ) ), month( FROM_UNIXTIME( timestamp ) ), day( FROM_UNIXTIME( timestamp ) ), hour( FROM_UNIXTIME( timestamp ) )";
				
		break;	
		
		// session count by hour for referrers
		case "session_count_by_hour_referrer":	
		$sql = "select timestamp, hour( FROM_UNIXTIME( timestamp ) ) as col, count( id ) as num
				from " . $wibstats_table_sessions . " where 
				timestamp >= " . $wpdb->escape( $parameters["start"] ) . "
				and " . stripslashes( stripslashes( $wpdb->escape( $parameters["filter"] ) ) ) . " " . stripslashes( stripslashes( $wpdb->escape( $parameters["equals"] ) ) ) . " '" . stripslashes( stripslashes( $wpdb->escape( $parameters["value"] ) ) ) . "'
				and terms = ''
				group by hour( FROM_UNIXTIME( timestamp ) ), day( FROM_UNIXTIME( timestamp ) ), month( FROM_UNIXTIME( timestamp ) ), year( FROM_UNIXTIME( timestamp ) )
				order by year( FROM_UNIXTIME( timestamp ) ), month( FROM_UNIXTIME( timestamp ) ), day( FROM_UNIXTIME( timestamp ) ), hour( FROM_UNIXTIME( timestamp ) )";
				
		break;
		
		// page count by hour
		case "page_count_by_hour":
		$sql = "select timestamp, hour( FROM_UNIXTIME( timestamp ) ) as col, count( id ) as num
				from " . $wibstats_table_pages . " where 
				timestamp >= " . $wpdb->escape( $parameters["start"] ) . "
				and " . stripslashes( stripslashes( $wpdb->escape( $parameters["filter"] ) ) ) . " " . stripslashes( stripslashes( $wpdb->escape( $parameters["equals"] ) ) ) . " '" . stripslashes( stripslashes( $wpdb->escape( $parameters["value"] ) ) ) . "'
				group by hour( FROM_UNIXTIME( timestamp ) ), day( FROM_UNIXTIME( timestamp ) ), month( FROM_UNIXTIME( timestamp ) ), year( FROM_UNIXTIME( timestamp ) )
				order by year( FROM_UNIXTIME( timestamp ) ), month( FROM_UNIXTIME( timestamp ) ), day( FROM_UNIXTIME( timestamp ) ), hour( FROM_UNIXTIME( timestamp ) )";
				
		break;	
		
		// page count by hour for referrers
		case "page_count_by_hour_referrers":
		$sql = "select timestamp, hour( FROM_UNIXTIME( timestamp ) ) as col, count( id ) as num
				from " . $wibstats_table_pages . " where 
				timestamp >= " . $wpdb->escape( $parameters["start"] ) . "
				and " . stripslashes( stripslashes( $wpdb->escape( $parameters["filter"] ) ) ) . " " . stripslashes( stripslashes( $wpdb->escape( $parameters["equals"] ) ) ) . " '" . stripslashes( stripslashes( $wpdb->escape( $parameters["value"] ) ) ) . "'
				and terms = ''
				group by hour( FROM_UNIXTIME( timestamp ) ), day( FROM_UNIXTIME( timestamp ) ), month( FROM_UNIXTIME( timestamp ) ), year( FROM_UNIXTIME( timestamp ) )
				order by year( FROM_UNIXTIME( timestamp ) ), month( FROM_UNIXTIME( timestamp ) ), day( FROM_UNIXTIME( timestamp ) ), hour( FROM_UNIXTIME( timestamp ) )";
				
		break;	
		
		// session count by day
		case "session_count_by_day":	
		$sql = "select timestamp, day( FROM_UNIXTIME( timestamp ) ) as col, count( id ) as num
				from " . $wibstats_table_sessions . " where 
				timestamp >= " . $wpdb->escape( $parameters["start"] ) . "
				and " . stripslashes( stripslashes( $wpdb->escape( $parameters["filter"] ) ) ) . " " . stripslashes( stripslashes( $wpdb->escape( $parameters["equals"] ) ) ) . " '" . stripslashes( stripslashes( $wpdb->escape( $parameters["value"] ) ) ) . "'
				group by day( FROM_UNIXTIME( timestamp ) ), month( FROM_UNIXTIME( timestamp ) ), year( FROM_UNIXTIME( timestamp ) )
				order by year( FROM_UNIXTIME( timestamp ) ), month( FROM_UNIXTIME( timestamp ) ), day( FROM_UNIXTIME( timestamp ) )";
				
		break;	
		
		// session count by day for referrers
		case "session_count_by_day_referrer":	
		$sql = "select timestamp, day( FROM_UNIXTIME( timestamp ) ) as col, count( id ) as num
				from " . $wibstats_table_sessions . " where 
				timestamp >= " . $wpdb->escape( $parameters["start"] ) . "
				and " . stripslashes( stripslashes( $wpdb->escape( $parameters["filter"] ) ) ) . " " . stripslashes( stripslashes( $wpdb->escape( $parameters["equals"] ) ) ) . " '" . stripslashes( stripslashes( $wpdb->escape( $parameters["value"] ) ) ) . "'
				and terms = ''
				group by day( FROM_UNIXTIME( timestamp ) ), month( FROM_UNIXTIME( timestamp ) ), year( FROM_UNIXTIME( timestamp ) )
				order by year( FROM_UNIXTIME( timestamp ) ), month( FROM_UNIXTIME( timestamp ) ), day( FROM_UNIXTIME( timestamp ) )";
				
		break;	
		
		// page count by day
		case "page_count_by_day":
		$sql = "select timestamp, day( FROM_UNIXTIME( timestamp ) ) as col, count( id ) as num
				from " . $wibstats_table_pages . " where 
				timestamp >= " . $wpdb->escape( $parameters["start"] ) . "
				and " . stripslashes( stripslashes( $wpdb->escape( $parameters["filter"] ) ) ) . " " . stripslashes( stripslashes( $wpdb->escape( $parameters["equals"] ) ) ) . " '" . stripslashes( stripslashes( $wpdb->escape( $parameters["value"] ) ) ) . "'
				group by day( FROM_UNIXTIME( timestamp ) ), month( FROM_UNIXTIME( timestamp ) ), year( FROM_UNIXTIME( timestamp ) )
				order by year( FROM_UNIXTIME( timestamp ) ), month( FROM_UNIXTIME( timestamp ) ), day( FROM_UNIXTIME( timestamp ) )";

		break;
		
		// page count by day for referrers
		case "page_count_by_day_referrer":
		$sql = "select timestamp, day( FROM_UNIXTIME( timestamp ) ) as col, count( id ) as num
				from " . $wibstats_table_pages . " where 
				timestamp >= " . $wpdb->escape( $parameters["start"] ) . "
				and " . stripslashes( stripslashes( $wpdb->escape( $parameters["filter"] ) ) ) . " " . stripslashes( stripslashes( $wpdb->escape( $parameters["equals"] ) ) ) . " '" . stripslashes( stripslashes( $wpdb->escape( $parameters["value"] ) ) ) . "'
				and terms = ''
				group by day( FROM_UNIXTIME( timestamp ) ), month( FROM_UNIXTIME( timestamp ) ), year( FROM_UNIXTIME( timestamp ) )
				order by year( FROM_UNIXTIME( timestamp ) ), month( FROM_UNIXTIME( timestamp ) ), day( FROM_UNIXTIME( timestamp ) )";
				
		break;
		
		// session count by week
		case "session_count_by_week":
		$sql = "select timestamp, week( FROM_UNIXTIME( timestamp ) ) as col, count( id ) as num
				from " . $wibstats_table_sessions . " where 
				timestamp >= " . $wpdb->escape( $parameters["start"] ) . "
				and " . stripslashes( stripslashes( $wpdb->escape( $parameters["filter"] ) ) ) . " " . stripslashes( stripslashes( $wpdb->escape( $parameters["equals"] ) ) ) . " '" . stripslashes( stripslashes( $wpdb->escape( $parameters["value"] ) ) ) . "'
				group by week( FROM_UNIXTIME( timestamp ) ), year( FROM_UNIXTIME( timestamp ) )
				order by year( FROM_UNIXTIME( timestamp ) ), week( FROM_UNIXTIME( timestamp ) )";

		break;	
		
		// session count by week for referrers
		case "session_count_by_week_referrer":
		$sql = "select timestamp, week( FROM_UNIXTIME( timestamp ) ) as col, count( id ) as num
				from " . $wibstats_table_sessions . " where 
				timestamp >= " . $wpdb->escape( $parameters["start"] ) . "
				and " . stripslashes( stripslashes( $wpdb->escape( $parameters["filter"] ) ) ) . " " . stripslashes( stripslashes( $wpdb->escape( $parameters["equals"] ) ) ) . " '" . stripslashes( stripslashes( $wpdb->escape( $parameters["value"] ) ) ) . "'
				and terms = ''
				group by week( FROM_UNIXTIME( timestamp ) ), year( FROM_UNIXTIME( timestamp ) )
				order by year( FROM_UNIXTIME( timestamp ) ), week( FROM_UNIXTIME( timestamp ) )";

		break;	
		
		// page count by week
		case "page_count_by_week":
		$sql = "select timestamp, week( FROM_UNIXTIME( timestamp ) ) as col, count( id ) as num
				from " . $wibstats_table_pages . " where 
				timestamp >= " . $wpdb->escape( $parameters["start"] ) . "
				and " . stripslashes( stripslashes( $wpdb->escape( $parameters["filter"] ) ) ) . " " . stripslashes( stripslashes( $wpdb->escape( $parameters["equals"] ) ) ) . " '" . stripslashes( stripslashes( $wpdb->escape( $parameters["value"] ) ) ) . "'
				group by week( FROM_UNIXTIME( timestamp ) ), year( FROM_UNIXTIME( timestamp ) )
				order by year( FROM_UNIXTIME( timestamp ) ), week( FROM_UNIXTIME( timestamp ) )";
				
		break;
		
		// page count by week for referrers
		case "page_count_by_week_referrer":
		$sql = "select timestamp, week( FROM_UNIXTIME( timestamp ) ) as col, count( id ) as num
				from " . $wibstats_table_pages . " where 
				timestamp >= " . $wpdb->escape( $parameters["start"] ) . "
				and " . stripslashes( stripslashes( $wpdb->escape( $parameters["filter"] ) ) ) . " " . stripslashes( stripslashes( $wpdb->escape( $parameters["equals"] ) ) ) . " '" . stripslashes( stripslashes( $wpdb->escape( $parameters["value"] ) ) ) . "'
				and terms = ''
				group by week( FROM_UNIXTIME( timestamp ) ), year( FROM_UNIXTIME( timestamp ) )
				order by year( FROM_UNIXTIME( timestamp ) ), week( FROM_UNIXTIME( timestamp ) )";
				
		break;
		
		// session count by month
		case "session_count_by_month":
		$sql = "select timestamp, month( FROM_UNIXTIME( timestamp ) ) as col, count( id ) as num
				from " . $wibstats_table_sessions . " where 
				timestamp >= " . $wpdb->escape( $parameters["start"] ) . "
				and " . stripslashes( stripslashes( $wpdb->escape( $parameters["filter"] ) ) ) . " " . stripslashes( stripslashes( $wpdb->escape( $parameters["equals"] ) ) ) . " '" . stripslashes( stripslashes( $wpdb->escape( $parameters["value"] ) ) ) . "'
				group by month( FROM_UNIXTIME( timestamp ) ), year( FROM_UNIXTIME( timestamp ) )
				order by year( FROM_UNIXTIME( timestamp ) ), month( FROM_UNIXTIME( timestamp ) )";
				
		break;	
		
		// session count by month for referrers
		case "session_count_by_month_referrer":
		$sql = "select timestamp, month( FROM_UNIXTIME( timestamp ) ) as col, count( id ) as num
				from " . $wibstats_table_sessions . " where 
				timestamp >= " . $wpdb->escape( $parameters["start"] ) . "
				and " . stripslashes( stripslashes( $wpdb->escape( $parameters["filter"] ) ) ) . " " . stripslashes( stripslashes( $wpdb->escape( $parameters["equals"] ) ) ) . " '" . stripslashes( stripslashes( $wpdb->escape( $parameters["value"] ) ) ) . "'
				and terms = ''
				group by month( FROM_UNIXTIME( timestamp ) ), year( FROM_UNIXTIME( timestamp ) )
				order by year( FROM_UNIXTIME( timestamp ) ), month( FROM_UNIXTIME( timestamp ) )";
				
		break;	
		
		// page count by month
		case "page_count_by_month":
		$sql = "select timestamp, month( FROM_UNIXTIME( timestamp ) ) as col, count( id ) as num
				from " . $wibstats_table_pages . " where 
				timestamp >= " . $wpdb->escape( $parameters["start"] ) . "
				and " . stripslashes( stripslashes( $wpdb->escape( $parameters["filter"] ) ) ) . " " . stripslashes( stripslashes( $wpdb->escape( $parameters["equals"] ) ) ) . " '" . stripslashes( stripslashes( $wpdb->escape( $parameters["value"] ) ) ) . "'
				group by month( FROM_UNIXTIME( timestamp ) ), year( FROM_UNIXTIME( timestamp ) )
				order by year( FROM_UNIXTIME( timestamp ) ), month( FROM_UNIXTIME( timestamp ) )";

		break;
		
		// page count by month for referrers
		case "page_count_by_month_referrer":
		$sql = "select timestamp, month( FROM_UNIXTIME( timestamp ) ) as col, count( id ) as num
				from " . $wibstats_table_pages . " where 
				timestamp >= " . $wpdb->escape( $parameters["start"] ) . "
				and " . stripslashes( stripslashes( $wpdb->escape( $parameters["filter"] ) ) ) . " " . stripslashes( stripslashes( $wpdb->escape( $parameters["equals"] ) ) ) . " '" . stripslashes( stripslashes( $wpdb->escape( $parameters["value"] ) ) ) . "'
				and terms = ''
				group by month( FROM_UNIXTIME( timestamp ) ), year( FROM_UNIXTIME( timestamp ) )
				order by year( FROM_UNIXTIME( timestamp ) ), month( FROM_UNIXTIME( timestamp ) )";

		break;
		
		// session
		case "session":
		$sql = "select country, countrycode, city, 
				latitude, longitude, referrer, referrer_domain, 
				terms, timestamp,
				colordepth, screensize, browser, version, platform,
				ipaddress, sessionid
				from " . $wibstats_table_sessions . "
				where sessionid = '" . $wpdb->escape( $parameters["session"] ) . "'
				order by timestamp asc";
		break;
		
		// pages viewed in a session
		case "session_pages":
		$sql = "select page, title, timestamp
				from " . $wibstats_table_pages . "
				where sessionid = '" . $wpdb->escape( $parameters["session"] ) . "'
				order by timestamp asc";
		break;
		
		// sessions by an IP address
		case "ip_sessions":
		$sql = "select SQL_CALC_FOUND_ROWS referrer, referrer_domain, terms, timestamp, sessionid
				from " . $wibstats_table_sessions . "
				where ipaddress = '" . $wpdb->escape( $parameters["ipaddress"] ) . "'
				and sessionid <> '" . $wpdb->escape( $parameters["sessionid"] ) . "'
				group by sessionid
				order by timestamp desc";
		break;
		
		// recent direct visitors
		case "recent_direct_visitors":
		$sql = "select country, countrycode, city, timestamp, sessionid
				from " . $wibstats_table_sessions . "
				where referrer = ''
				group by sessionid
				order by timestamp desc";
		break;
		
		// popular countries for direct visitors
		case "popular_direct_countries":
		$sql = "select country, countrycode, count( id ) as num
				from " . $wibstats_table_sessions . "
				where referrer_domain = ''
				and country <> ''
				and countrycode <> ''
				group by country
				order by count( id ) desc";
		break;
		
		// total countries for direct visitors
		case "total_direct_countries":
		$sql = "select count( id ) as num
				from " . $wibstats_table_sessions . "
				where referrer_domain = ''
				and country <> ''
				and countrycode <> ''";
		break;
		
		// search terms used to find a page
		case "page_terms":
		$sql = "select terms, count( terms ) as visitors
				from " . $wibstats_table_pages . "
				where page = '" . $wpdb->escape( $parameters["url"] ) . "'
				and terms <> ''
				group by terms
				order by count( terms ) desc";
		break;
		
		// countries visiting a page
		case "page_countries":
		$sql = "select s.country, s.countrycode, avg( s.latitude ) as latitude, avg( s.longitude ) as longitude, count( s.country ) as visitors
				from " . $wibstats_table_sessions . " s
				inner join " . $wibstats_table_pages . " p on p.sessionid = s.sessionid
				where p.page = '" . $wpdb->escape( $parameters["url"] ) . "'
				and s.country <> ''
				and s.countrycode <> ''
				and s.longitude <> 0
				and s.latitude <> 0
				group by s.country";
		break;
		
		// popular platforms
		case "popular_platforms":
		$sql = "select count( distinct( sessionid ) ) as num, platform
				from " . $wibstats_table_sessions . "
				where platform <> ''
				group by platform
				order by count( distinct( sessionid ) ) desc";
		break;
		
		// total platforms
		case "total_platforms":
		$sql = "select count( distinct( sessionid ) ) as total
				from " . $wibstats_table_sessions . "
				where platform <> ''";
		break;
		
		// popular browsers
		case "popular_browsers":
		$sql = "select count( distinct( sessionid ) ) as num, browser
				from " . $wibstats_table_sessions . "
				where browser <> ''
				group by browser
				order by count( distinct( sessionid ) ) desc";
		break;
		
		// total browsers
		case "total_browsers":
		$sql = "select count( distinct( sessionid ) ) as total
				from " . $wibstats_table_sessions . "
				where browser <> ''";
		break;
		
		// popular screen sizes
		case "popular_screen_sizes":
		$sql = "select count( distinct( sessionid ) ) as num, screensize
				from " . $wibstats_table_sessions . "
				where screensize <> ''
				group by screensize
				order by count( distinct( sessionid ) ) desc";
		break;
		
		// total screen sizes
		case "total_screen_sizes":
		$sql = "select count( distinct( sessionid ) ) as total
				from " . $wibstats_table_sessions . "
				where screensize <> ''";
		break;
		
	}
	
	$num = ( int )$num;
	if ( $num > 0 )
	{
		$start = ( int )$start;
		$sql .= "
				limit " . $start . ", " . $num;
	}
	
	$sql .= ";";
	
	// Print the SQL for debugging
	//print $sql;
	
	return $sql;
}

// a standard header for your plugins, offers a PayPal donate button and link to a support page
function wibstats_wp_plugin_standard_header( $currency = "", $plugin_name = "", $author_name = "", $paypal_address = "", $bugs_page ) {
	$r = "";
	$option = get_option( $plugin_name . " header" );
	if ( $_GET[ "header" ] != "" || $_GET["thankyou"] == "true" ) {
		update_option( $plugin_name . " header", "hide" );
		$option = "hide";
	}
	if ( $_GET["thankyou"] == "true" ) {
		$r .= '<div class="updated"><p>' . __( "Thank you for donating" ) . '</p></div>';
	}
	if ( $currency != "" && $plugin_name != "" && $_GET[ "header" ] != "hide" && $option != "hide" )
	{
		$r .= '<div class="updated">';
		$pageURL = 'http';
		if ( $_SERVER["HTTPS"] == "on" ) { $pageURL .= "s"; }
		$pageURL .= "://";
		if ( $_SERVER["SERVER_PORT"] != "80" ) {
			$pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		}
		if ( strpos( $pageURL, "?") === false ) {
			$pageURL .= "?";
		} else {
			$pageURL .= "&";
		}
		$pageURL = htmlspecialchars( $pageURL );
		if ( $bugs_page != "" ) {
			$r .= '<p>' . sprintf ( __( 'To report bugs please visit <a href="%s">%s</a>.' ), $bugs_page, $bugs_page ) . '</p>';
		}
		if ( $paypal_address != "" && is_email( $paypal_address ) ) {
			$r .= '
			<form id="wp_plugin_standard_header_donate_form" action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_donations" />
			<input type="hidden" name="item_name" value="Donation: ' . $plugin_name . '" />
			<input type="hidden" name="business" value="' . $paypal_address . '" />
			<input type="hidden" name="no_note" value="1" />
			<input type="hidden" name="no_shipping" value="1" />
			<input type="hidden" name="rm" value="1" />
			<input type="hidden" name="currency_code" value="' . $currency . '">
			<input type="hidden" name="return" value="' . $pageURL . 'thankyou=true" />
			<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHosted" />
			<p>';
			if ( $author_name != "" ) {
				$r .= sprintf( __( 'If you found %1$s useful please consider donating to help %2$s to continue writing free Wordpress plugins.' ), $plugin_name, $author_name );
			} else {
				$r .= sprintf( __( 'If you found %s useful please consider donating.' ), $plugin_name );
			}
			$r .= '
			<p><input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="" /></p>
			</form>
			';
		}
		$r .= '<p><a href="' . $pageURL . 'header=hide" class="button">' . __( "Hide this" ) . '</a></p>';
		$r .= '</div>';
	}
	print $r;
}
function wibstats_wp_plugin_standard_footer( $currency = "", $plugin_name = "", $author_name = "", $paypal_address = "", $bugs_page ) {
	$r = "";
	if ( $currency != "" && $plugin_name != "" )
	{
		$r .= '<form id="wp_plugin_standard_footer_donate_form" action="https://www.paypal.com/cgi-bin/webscr" method="post" style="clear:both;padding-top:50px;"><p>';
		if ( $bugs_page != "" ) {
			$r .= sprintf ( __( '<a href="%s">Bugs</a>' ), $bugs_page );
		}
		if ( $paypal_address != "" && is_email( $paypal_address ) ) {
			$r .= '
			<input type="hidden" name="cmd" value="_donations" />
			<input type="hidden" name="item_name" value="Donation: ' . $plugin_name . '" />
			<input type="hidden" name="business" value="' . $paypal_address . '" />
			<input type="hidden" name="no_note" value="1" />
			<input type="hidden" name="no_shipping" value="1" />
			<input type="hidden" name="rm" value="1" />
			<input type="hidden" name="currency_code" value="' . $currency . '">
			<input type="hidden" name="return" value="' . $pageURL . 'thankyou=true" />
			<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHosted" />
			<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="' . __( "Donate" ) . ' ' . $plugin_name . '" />
			';
		}
		$r .= '</p></form>';
	}
	print $r;
}

require_once( "plugin-register.class.php" );
$register = new Plugin_Register();
$register->file = __FILE__;
$register->slug = "wibstats";
$register->name = "WibStats";
$register->version = "0.5.5";
$register->developer = "Chris Taylor";
$register->homepage = "http://www.stillbreathing.co.uk";
$register->Plugin_Register();
?>