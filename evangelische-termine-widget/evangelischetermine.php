<?php
/*
Plugin Name: Evangelische Termine
Plugin URI: http://www.cvjm-nuernberg.de/
Description: Dieses Plugin erstellt ein Sidebar-Widget, in welchem man Termine von Evangelische Termine anzeigen lassen kann
Author: Oliver Weber
Version: 0.1
Author URI: http://www.cvjm-nuernberg.de
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

/*
License:
==============================================================================
Copyright 2013 CVJM Nuernberg

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

The Losungen of the Herrnhuter Brüdergemeine are copyrighted. Owner of
copyright is the Evangelische Brüder-Unität – Herrnhuter Brüdergemeine.
The biblical texts from the Lutheran Bible, revised texts in 1984, revised
edition with a new spelling, subject to the copyright of the German Bible
Society, Stuttgart.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

Requirements:
==============================================================================
This plugin requires WordPress >= 2.8 and tested with PHP Interpreter >= 5.2.10
*/

class EvTermine_Widget extends WP_Widget {
  public function __construct() {
    $widget_ops = array(
      'classname' => 'widget_evtermine',
      'description' => 'Evangelische Termine darstellen'
    );
    parent::__construct('evtermine', 'Evanglische Termine', $widget_ops);
  }

  /**
   * This functions controls the output of the widget in the frontend
   */
  public function widget($args, $instance) {
    extract( $args );
    $title = '';
    if (!empty($instance['title'])) {
      $title = apply_filters( 'widget_title', $instance['title'] );
    }

    /*
    $showcopy = isset( $instance['showcopy'] ) ? $instance['showcopy'] : false;
    $showlink = isset( $instance['showlink'] ) ? $instance['showlink'] : false;
    */

    echo $args['before_widget'];

    /* Output title */
    if ( !empty($title) )
      echo $args['before_title'] . $title . $args['after_title'];

    $this->doOutput($instance);

    echo $args['after_widget'];
  }

  /**
   * Back-end widget form.
   *
   * @see WP_Widget::form()
   *
   * @param array $instance Previously saved values from database.
   */
  public function form( $instance ) {
    $default = array('title' => 'Aktuelle Termine', 'reqstr' => '', 'url' => 'www.evangelische-termine.de/Veranstalter/xml.php' );
    $instance = wp_parse_args( (array) $instance, $default);
    ?>
    <p>
    <label for="<?php echo $this->get_field_name( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
    <label for="<?php echo $this->get_field_name( 'url' ); ?>"><?php _e( 'URL:' ); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id( 'url' ); ?>" name="<?php echo $this->get_field_name( 'url' ); ?>"
    <label for="<?php echo $this->get_field_name( 'reqstr' ); ?>"><?php _e( 'Request Options:' ); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id( 'reqstr' ); ?>" name="<?php echo $this->get_field_name( 'reqstr' ); ?>"
    </p>
    <?php
  }

  /**
   * Sanitize widget form values as they are saved.
   *
   * @see WP_Widget::update()
   *
   * @param array $new_instance Values just sent to be saved.
   * @param array $old_instance Previously saved values from database.
   *
   * @return array Updated safe values to be saved.
   */
  public function update( $new_instance, $old_instance ) {
    $instance = $old_instance;
    $instance['title'] = ( !empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
    $instance['url'] = ( !empty( $new_instance['url'] ) ) ? strip_tags( $new_instance['url'] ) : '';
    $instance['reqstr'] = ( !empty( $new_instance['reqstr'] ) ) ? strip_tags( $new_instance['reqstr'] ) : '';

    return $instance;
  }

  /**
   * Get the XML string from the Evangilische Termine webpage
   */
  private function getTermine( $args ) {
    $xmlresp = '';
    if ( !empty( $args[ 'url' ]) ) {
      $geturl = $args[ 'url' ];
    } else {
      $geturl = 'www.evangelische-termine.de/Veranstalter/xml.php';
    }
	
	if (get_query_var('evterm_pageid')) {
	  if (!empty( $args[ 'reqstr' ] )) {
	    $args[ 'reqstr' ] .= '&pageID=' . get_query_var('evterm_pageid');
	  } else {
	    $args[ 'reqstr' ] = 'pageID=' . get_query_var('evterm_pageid');
	  }
    }

    if (!empty( $args[ 'reqstr' ] )) {
      $geturl .= '?' . $args[ 'reqstr' ];
    }

    if(function_exists('curl_init')) {
      /* If URL begins with 'http://' strip it off as cURL does not want that */
      if (strpos($geturl, 'http://') === 0)
      {
        $geturl = substr($geturl, 7);
      }
      $curlobj = curl_init();
      curl_setopt($curlobj, CURLOPT_URL, $geturl);
      curl_setopt($curlobj, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($curlobj, CURLOPT_CONNECTTIMEOUT, 5);

      $xmlresp = curl_exec($curlobj);
      $curlstatus = curl_getinfo ($curlobj);
      if ($curlstatus['http_code'] != '200') {
        echo '<p>Server returned respone ' . $curlstatus['http_code'] . '</p>';
      }
      curl_close($curlobj);
    } else {
      echo '<p>Please install cURL and simplexml for php</p>';
    }

    return $xmlresp;
  }
  
  private function parse_xml_args( $queryString )
  {
    $result = array();
    $entries = preg_split('/\|/', $queryString);
	foreach ($entries as $entry) {
	  list($key,$value) = preg_split('/=/', $entry);
	  $result[$key] = $value;
	}
	
	return $result;
  }
  
  private function getItemsPerPage( $queryString )
  {
    $itemsperpage = 1;
	$arg_array = $this->parse_xml_args( $queryString );
	if (array_key_exists('itemsPerPage', $arg_array)) {
	  $itemsperpage = intval($arg_array['itemsPerPage']);
	  if ($itemsperpage == 0)
	  {
		$itemsperpage = 1;
	  }
	}
	
	return $itemsperpage;
  }
  
  private function outputNavigation( $queryString, $maxentries, $itemsperpage )
  {
	echo '<div class="event_navigation">' . "\n";
    $arg_array = $this->parse_xml_args( $queryString );
	$page = 1;
	if (array_key_exists('pageID', $arg_array)) {
	  $page = intval($arg_array['pageID']);
	  if ($page == 0)
	  {
		$page = 1;
	  }
	}
	$maxpage = ceil($maxentries / $itemsperpage);
	
	if (array_key_exists('vid', $arg_array)) {
	  $vid = $arg_array['vid'];
	}
	
	$newArgs = '';
	foreach ($arg_array as $key => $value) {
	  if (strcmp($key, 'pageID') != 0) {
	    if (strlen($newArgs) != 0)
        {
	     $newArgs .= "&";
		}
		$newArgs .= $key . "=" . $value;
	  }
	}

	$nextpage = $page + 3;
	if ($nextpage > $maxpage) {
	  $nextpage = $maxpage;
	}
	
	$prevpage = $page - 3;
	if ($prevpage < 1)
	{
	  $prevpage = 1;
	}
	
	if (strlen($newArgs) != 0) {
	  $newArgs .= '&';
    }

	echo '<a href="javascript:reload_evtermine();" class="callajax" data-vid="'. $vid . '" ';
    echo 'data-count="' . $itemsperpage . '" data-query="' . $newArgs . 'pageID=1" data-filter="yes">' . "\n";
	echo '<img src="' . plugins_url('images/gostart_icon.png', __FILE__) . '" class="event_nav_icon" /></a>&nbsp;&nbsp;' . "\n";
	echo '<a href="javascript:reload_evtermine();" class="callajax" data-vid="'. $vid . '" ';
	echo 'data-count="' . $itemsperpage . '" data-query="' . $newArgs . 'pageID=' . $prevpage . '" data-filter="yes">' . "\n";
	echo '<img src="' . plugins_url('images/rw_icon.png', __FILE__) . '" class="event_nav_icon" /></a>&nbsp;&nbsp;' . "\n";
	for ($actpage = ($prevpage == 1) ? $prevpage : ($prevpage + 1); $actpage < $nextpage; $actpage++) {
	  if ($actpage != $page)
	  {
	    echo '<a href="javascript:reload_evtermine();" class="callajax" data-vid="'. $vid . '" ';
       echo 'data-count="' . $itemsperpage . '" data-query="' . $newArgs . 'pageID=' . $actpage . '" data-filter="yes">' . "\n";
	    echo $actpage . '</a>&nbsp;&nbsp;' . "\n";
	  } else {
	    echo $actpage . '&nbsp;&nbsp;' . "\n";
	  }
	}
    echo '<a href="javascript:reload_evtermine();" class="callajax" data-vid="'. $vid . '" ';
    echo 'data-count="' . $itemsperpage . '" data-query="' . $newArgs . 'pageID=' . $nextpage . '" data-filter="yes">' . "\n";
	echo '<img src="' . plugins_url('images/ff_icon.png', __FILE__) . '" class="event_nav_icon" /></a>&nbsp;&nbsp;' . "\n";
	echo '<a href="javascript:reload_evtermine();" class="callajax" data-vid="'. $vid . '" ';
	echo 'data-count="' . $itemsperpage . '" data-query="' . $newArgs . 'pageID=' . $maxpage . '" data-filter="yes">' . "\n";
	echo '<img src="' . plugins_url('images/goend_icon.png', __FILE__) . '" class="event_nav_icon" /></a>' . "\n" . "\n";
	echo '</div>' . "\n";
  }

  private function outputTermine( $xmlstr, $args ) {
    if (function_exists('simplexml_load_string') && strlen($xmlstr) != 0) {
      $xmlobj = new SimpleXMLElement($xmlstr);
      foreach($xmlobj->Export->Veranstaltung as $event) {
        echo '<p class="evtermine_container">' . "\n";
        echo '<span class="evtermine_date">'. $event->DATUM . '</span>&nbsp;&nbsp;' . "\n";
        echo '<a class="evtermine_title" href="#" rel="#ev_' . $event->_event_ID . '">' . $event->_event_TITLE . '</a><br>' . "\n";
        echo '<span class="evtermine_desc">';
        /* If there is no short description */
        $address = $event->_place_NAME . ', ' . $event->_place_STREET_NR;
        $addrlen = strlen($address);
        if (strlen($event->_event_SHORT_DESCRIPTION) > 0)
        {
          $desc = $event->_event_SHORT_DESCRIPTION;
        } else if (strlen($event->_event_LONG_DESCRIPTION) > 0) {
          $desc = $event->_event_LONG_DESCRIPTION;
        } else {
		  $desc = 'Keine weiteren Informationen.';
		}
        $desclen = strlen($desc);
        $maxlen = 120;
        if (($addrlen + $desclen) > $maxlen)
        {
          $desc = substr($desc, 0, $maxlen - $addrlen);
          $desccut = strrpos($desc, ' ');
          if ($desccut > 0)
          {
            $desc = substr($desc, 0, $desccut);
          }
          $desc .= '...';
        }
        echo $desc . ', &nbsp;' . $address;
        echo '</span></p>' . "\n";

        echo '<div class="simple_overlay" id="ev_' . $event->_event_ID . '">' . "\n";
        echo '<h2>' . $event->_event_TITLE . '</h2>' . "\n";
        echo '<p>' . $event->DATUM . '</p>' . "\n";
        if (strlen($event->_event_LONG_DESCRIPTION) > 0) {
          echo '<p class="preformatted">' . $event->_event_LONG_DESCRIPTION . '</p>' . "\n";
        } else {
          echo '<p>' . $event->_event_SHORT_DESCRIPTION . '</p>' . "\n";
        }
        echo '<p>' . $address . '</p>' . "\n";
        echo '</div>' . "\n";
      }
    } else {
      echo '<p>Keine Termine</p>\n';
    }
	$itemsperpage = $this->getItemsPerPage( $xmlobj->Export->meta->activeParams );
	$this->outputNavigation( $xmlobj->Export->meta->activeParams, $xmlobj->Export->meta->totalItems, $itemsperpage );
  }

  private function doOutput($args) {
    $xmlstr = $this->getTermine($args);
    $this->outputTermine($xmlstr, $args);
  }
}

/* Register the widget */
function EvEventInit() {
  register_widget('EvTermine_Widget');
}
add_action('widgets_init', 'EvEventInit');

?>
