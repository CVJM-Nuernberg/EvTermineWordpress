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

    $this->doOutput(array('reqstr' => 'vid=3'));

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
    if ( !empty( $args[ 'url' ]) )
    {
      $geturl = $args[ 'url ' ];
    }
    else
    {
      $geturl = 'www.evangelische-termine.de/Veranstalter/xml.php';
    }

    if (!empty( $args[ 'reqstr' ] )) {
      $geturl .= '?' . $args[ 'reqstr' ];
    }
    echo '<p>geturl: ' . $geturl . '</p>';

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

  private function outputTermine( $xmlstr ) {
    if (function_exists('simplexml_load_string')) {
      $xmlobj = new SimpleXMLElement($xmlstr);
      foreach($xmlobj->Export->Veranstaltung as $event) {
        echo '<p>'. $event->DATUM . ' am ' . $event->_event_TITLE . '</p>';
      }
    }
  }

  private function doOutput($args) {
    $xmlstr = $this->getTermine($args);
    $this->outputTermine($xmlstr);
  }
}

/* Register the widget */
function EvEventInit() {
  register_widget('EvTermine_Widget');
}
add_action('widgets_init', 'EvEventInit');

?>
