<?php
/*
Plugin Name: ATRU Upcoming Events
Description: Upcoming Events from EventBooking
Version: 0.1
Author: Eddie Moya
 */

class ATRU_UpcomingEvents extends WP_Widget {
      
    /**
     * Name for this widget type, should be human-readable - the actual title it will go by.
     * 
     * @var string [REQUIRED]
     */
    var $widget_name = 'ATRU Upcoming Events';
   
    /**
     * Root id for all widgets of this type. Will be automatically generate if not set.
     * 
     * @var string [OPTIONAL]. FALSE by default.
     */
    var $id_base = 'UpcomingEvents';
    
    /**
     * Shows up under the widget in the admin interface
     * 
     * @var string [OPTIONAL]
     */
    private $description = 'Upcoming Events for ATRU';

    /**
     * CSS class used in the wrapping container for each instance of the widget on the front end.
     * 
     * @var string [OPTIONAL]
     */
    private $classname = 'upcoming-events';
    
    
    /**
     * Be careful to consider PHP versions. If running PHP4 class name as the contructor instead.
     * 
     * @author Eddie Moya
     * @return void
     */
    public function ATRU_UpcomingEvents(){
        $widget_ops = array(
            'description' => $this->description,
            'classname' => $this->classname
        );
        
        $control_options = array(
            'height' => $this->height,
            'width' => $this->width
        );

        parent::WP_Widget($this->id_base, $this->widget_name, $widget_ops, $control_options);
    }
    
    /**
     * Self-registering widget method.
     * 
     * This can be called statically.
     * 
     * @author Eddie Moya
     * @return void
     */
    public function register_widget() {
        add_action('widgets_init', create_function( '', 'register_widget("' . __CLASS__ . '");' ));
    }
    
    /**
     * The front end of the widget. 
     * 
     * Do not call directly, this is called internally to render the widget.
     * 
     * @author [Widget Author Name]
     * 
     * @param array $args       [Required] Automatically passed by WordPress - Settings defined when registering the sidebar of a theme
     * @param array $instance   [Required] Automatically passed by WordPress - Current saved data for the widget options.
     * @return void 
     */
    public function widget($args, $instance) {
        global $post;
        extract($args);

            $title = (isset($instance['title'])) ? $instance['title'] : 'Upcoming Events';

            echo $before_widget;

            if ($instance["title"]) {
                echo $before_title . $title . $after_title;
            }

            $tax_query = array();
            $event_types = get_the_terms($post->ID, 'event_type');

            if(!empty($event_types) && !is_wp_error($event_types)){
                $et_ids = wp_list_pluck($event_types, 'term_id');

                $tax_query =   array(
                                array(
                                    'taxonomy' => 'event_type',
                                    'field' => 'id',
                                    'terms' => $et_ids,
                                    'operator' => 'AND'
                                )
                            );
            
            }

            $event_query = array(
                'post_type' => 'event',
                'tax_query' => $tax_query,
                'posts_per_page' => $instance['limit']
                );

            $events = new WP_Query( $event_query );

            ?>
            <ul class="event-list">

            <?php foreach($events->posts as $event) : ?>

                <?php if ($event->ID == $post->ID) continue; ?>
                <li>
                    <article id="post-<?php $event->ID; ?>">
                        <?php if ( has_post_thumbnail($event->ID)) : ?>
                            <?php echo get_the_post_thumbnail($event->ID, 'thumbnail'); ?>
                        <?php endif; ?>

                        <header class="entry-header">
                            <h4 class="entry-title"><?php echo $event->post_title; ?></h4>
                            <span><?php  echo $this->get_event_date_range($event->ID); ?></span>
                        </header>

                        <section class="button-list">
                            <a class="learn-more button" href="<?php echo get_permalink($event->ID); ?>" alt="Learn more about <?php echo $event->post_title; ?>">Learn More</a>
                            <a class="buy-now button" href="<?php echo get_permalink($event->ID); ?>" alt="Buy tickets for <?php echo $event->post_title; ?>">Buy Now</a>
                        </section>

                </li>

            <?php endforeach; ?>

            </ul>

            <?php
            
            echo $after_widget;
    }

    function get_event_date_range($post_id){
        $metadata = get_post_custom($post_id); 
        $_startdate = $metadata['start_date'][0];
        $_enddate = $metadata['end_date'][0];

        if($_startdate == $_enddate){
            $date = date("F j, Y", strtotime($_startdate));
        } else {
            $startdate = date("F j", strtotime($_startdate));
            $enddate = date("-j, Y", strtotime($_enddate));
            $date = $startdate.$enddate;
        }

        return $date;

    }

    /**
     * Data validation. 
     * 
     * Do not call directly, this is called internally to render the widget
     * 
     * @author [Widget Author Name]
     * 
     * @uses esc_attr() http://codex.wordpress.org/Function_Reference/esc_attr
     * 
     * @param array $new_instance   [Required] Automatically passed by WordPress
     * @param array $old_instance   [Required] Automatically passed by WordPress
     * @return array|bool Final result of newly input data. False if update is rejected.
     */
    public function update($new_instance, $old_instance){
        
        /* Lets inherit the existing settings */
        $instance = $old_instance;

        /**
         * Sanitize each option - be careful, if not all simple text fields,
         * then make use of other WordPress sanitization functions, but also
         * make use of PHP functions, and use logic to return false to reject
         * the entire update. 
         * 
         * @see http://codex.wordpress.org/Function_Reference/esc_attr
         */
        foreach($new_instance as $key => $value){
                $instance[$key] = esc_attr($value);  
        }
        
        //Handle unchecked checkboxes
        foreach($instance as $key => $value){
            if($value == 'on' && !isset($new_instance[$key])){
                $instance[$key] = '';
            }

        }
    
        return $instance;
    }
    
    /**
     * Generates the form for this widget, in the WordPress admin area.
     * 
     * The use of the helper functions form_field() and form_fields() is not
     * neccessary, and may sometimes be inhibitive or restrictive.
     * 
     * @author Eddie Moya
     * 
     * @uses wp_parse_args() http://codex.wordpress.org/Function_Reference/wp_parse_args
     * @uses self::form_field()
     * @uses self::form_fields()
     * 
     * @param array $instance [Required] Automatically passed by WordPress
     * @return void 
     */
    public function form($instance){
        
        /* Setup default values for form fields - associtive array, keys are the field_id's */
        $defaults = array(
            'title' => 'Upcoming Events', 
            'limit' => '3',
            'widget_name' => $this->classname
            );
        
        /* Merge saved input values with default values */
        $instance = wp_parse_args($instance, $defaults);
       
        $this->form_field('title', 'text', 'Title', $instance);
        $this->form_field('limit', 'text', 'Number to show', $instance);

    }
    

    /**
     * Helper function - does not need to be part of widgets, this is custom, but 
     * is helpful in generating multiple input fields for the admin form at once. 
     * 
     * This is a wrapper for the singular form_field() function.
     * 
     * @author Eddie Moya
     * 
     * @uses self::form_fields()
     * 
     * @param array $fields     [Required] Nested array of field settings
     * @param array $instance   [Required] Current instance of widget option values.
     * @return void
     */
    private function form_fields($fields, $instance, $group = false){
        
        if($group) {
            echo "<p>";
        }
            
        foreach($fields as $field){
            
            extract($field);
            $label = (!isset($label)) ? null : $label;
            $options = (!isset($options)) ? null : $options;
            $this->form_field($field_id, $type, $label, $instance, $options, $group);
        }
        
        if($group){
             echo "</p>";
        }
    }
    
    /**
     * Helper function - does not need to be part of widgets, this is custom, but 
     * is helpful in generating single input fields for the admin form at once. 
     *
     * @author Eddie Moya
     * 
     * @uses get_field_id() (No Codex Documentation)
     * @uses get_field_name() http://codex.wordpress.org/Function_Reference/get_field_name
     * 
     * @param string $field_id  [Required] This will be the CSS id for the input, but also will be used internally by wordpress to identify it. Use these in the form() function to set detaults.
     * @param string $type      [Required] The type of input to generate (text, textarea, select, checkbox]
     * @param string $label     [Required] Text to show next to input as its label.
     * @param array $instance   [Required] Current instance of widget option values. 
     * @param array $options    [Optional] Associative array of values and labels for html Option elements.
     * 
     * @return void
     */
    private function form_field($field_id, $type, $label, $instance, $options = array(), $group = false){
  
        if(!$group)
             echo "<p>";
            
        $input_value = (isset($instance[$field_id])) ? $instance[$field_id] : '';
        switch ($type){
            
            case 'text': ?>
            
                    <label for="<?php echo $this->get_field_id( $field_id ); ?>"><?php echo $label; ?>: </label>
                    <input type="text" id="<?php echo $this->get_field_id( $field_id ); ?>" class="widefat" style="<?php echo (isset($style)) ? $style : ''; ?>" class="" name="<?php echo $this->get_field_name( $field_id ); ?>" value="<?php echo $input_value; ?>" />
                <?php break;
            
            case 'select': ?>
                    <label for="<?php echo $this->get_field_id( $field_id ); ?>"><?php echo $label; ?>: </label>
                    <select id="<?php echo $this->get_field_id( $field_id ); ?>" class="widefat" name="<?php echo $this->get_field_name($field_id); ?>">
                        <?php
                            foreach ( $options as $value => $label ) :  ?>
                        
                                <option value="<?php echo $value; ?>" <?php selected($value, $input_value) ?>>
                                    <?php echo $label ?>
                                </option><?php
                                
                            endforeach; 
                        ?>
                    </select>
                    
                <?php break;
                
            case 'textarea':
                
                $rows = (isset($options['rows'])) ? $options['rows'] : '16';
                $cols = (isset($options['cols'])) ? $options['cols'] : '20';
                
                ?>
                    <label for="<?php echo $this->get_field_id( $field_id ); ?>"><?php echo $label; ?>: </label>
                    <textarea class="widefat" rows="<?php echo $rows; ?>" cols="<?php echo $cols; ?>" id="<?php echo $this->get_field_id($field_id); ?>" name="<?php echo $this->get_field_name($field_id); ?>"><?php echo $input_value; ?></textarea>
                <?php break;
            
            case 'radio' :
                /**
                 * Need to figure out how to automatically group radio button settings with this structure.
                 */
                ?>
                    
                <?php break;
            

            case 'hidden': ?>
                    <input id="<?php echo $this->get_field_id( $field_id ); ?>" type="hidden" style="<?php echo (isset($style)) ? $style : ''; ?>" class="widefat" name="<?php echo $this->get_field_name( $field_id ); ?>" value="<?php echo $input_value; ?>" />
                <?php break;

            
            case 'checkbox' : ?>
                    <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id($field_id); ?>" name="<?php echo $this->get_field_name($field_id); ?>"<?php checked( (!empty($instance[$field_id]))); ?> />
                    <label for="<?php echo $this->get_field_id( $field_id ); ?>"><?php echo $label; ?></label>
                <?php
        }
        
        if(!$group)
             echo "</p>";
            
    }
}

ATRU_UpcomingEvents::register_widget();

