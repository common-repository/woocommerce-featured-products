<?php
    /*
    Plugin Name: Woocommerce featured products by Catergory
    Plugin URI: http://joomquery.com/how-to-installation-woocommerce-multiple-tabs-plugin-for-wordpress
    Description: woocommerce featured products plugin for WooCommerce products to show featured products by catergory.
    Author: Lamvt - Vu Thanh Lam
    Version: 3.9.1
    Author URI: http://www.joomquery.com
    */

add_action( 'widgets_init', 'WooCommerce_widget_d4j_featured_products_Custom_widget' );

function WooCommerce_widget_d4j_featured_products_Custom_widget() {
	register_widget( 'WooCommerce_widget_d4j_featured_products_Custom' );
}

class WooCommerce_widget_d4j_featured_products_Custom extends WP_Widget {

	/** Variables to setup the widget. */
	var $woo_widget_cssclass;
	var $woo_widget_description;
	var $woo_widget_idbase;
	var $woo_widget_name;


	/**
	 * constructor
	 *
	 * @access public
	 * @return void
	 */
	function WooCommerce_widget_d4j_featured_products_Custom() {

		/* Widget variable settings. */
		$this->woo_widget_cssclass = 'widget_featured_products_custom';
		$this->woo_widget_description = __( 'Display a list of featured products on your site.', 'woocommerce' );
		$this->woo_widget_idbase = 'woocommerce_featured_products';
		$this->woo_widget_name = __('D4J Featured Products', 'woocommerce' );

		/* Widget settings. */
		$widget_ops = array( 'classname' => $this->woo_widget_cssclass, 'description' => $this->woo_widget_description );

		/* Create the widget. */
		$this->WP_Widget('featured-products', $this->woo_widget_name, $widget_ops);

		add_action( 'save_post', array(&$this, 'flush_widget_cache') );
		add_action( 'deleted_post', array(&$this, 'flush_widget_cache') );
		add_action( 'switch_theme', array(&$this, 'flush_widget_cache') );
	}


	/**
	 * widget function.
	 *
	 * @see WP_Widget
	 * @access public
	 * @param array $args
	 * @param array $instance
	 * @return void
	 */
	function widget($args, $instance) {
		global $woocommerce;

		$cache = wp_cache_get('widget_featured_products_custom', 'widget');

		if ( !is_array($cache) ) $cache = array();

		if ( isset($cache[$args['widget_id']]) ) {
			echo $cache[$args['widget_id']];
			return;
		}

		ob_start();
		extract($args);

		$title = apply_filters('widget_title', empty($instance['title']) ? __('Featured Products', 'woocommerce') : $instance['title'], $instance, $this->id_base);
		
		$cat_slug_lamvt =  trim($instance['cat_slug_lamvt']);
		//echo $cat_slug_lamvt;
		if ( !$number = (int) $instance['number'] )
			$number = 12;
		else if ( $number < 1 )
			$number = 1;
		else if ( $number > 12 )
			$number = 12;
		
			$query_args = array('posts_per_page' => $number, 'no_found_rows' => 1, 'post_status' => 'publish', 'post_type' => 'product','product_cat' => $cat_slug_lamvt );
			$query_args['meta_query'] = array();
			if($featured==1){
			$query_args['meta_query'][] = array(
				'key' => '_featured',
				'value' => 'yes'
			);
			}
			$query_args['meta_query'][] = $woocommerce->query->stock_status_meta_query();
			$query_args['meta_query'][] = $woocommerce->query->visibility_meta_query();
		

		$r = new WP_Query($query_args);

		if ($r->have_posts()) : ?>
		<?php echo $before_widget; ?>		
		<?php 		
		if ( $title ){ 
			echo $before_title . $title . $after_title;		
		}
		?>
		
		<?php 
		$countPost = 1;
		$i=0;
		while ($r->have_posts()) : $r->the_post(); global $product; 
		if($countPost == $number){			
			$countPost = 0;
		}
			if ( has_post_thumbnail() ){
				$image = wp_get_attachment_image_src(get_post_thumbnail_id(get_the_ID()), apply_filters( 'single_product_large_thumbnail_size', 'shop_single' ));
				$image = $image[0];
				}		
		?>
		<?php if($style_theme ==0){?>
		<div class="col-md-2 col-xs-6 col-sm-2">
			<div class="box-item-inner">
				<a class="thumbnail" href="<?php echo get_permalink(); ?>" title="<?php the_title(); ?>">
				<?php //echo get_the_post_thumbnail( $post->ID, 'medium' ); ?>
				<img class="img-responsive" src="<?php echo $image ; ?>" alt="<?php the_title(); ?>" />
				</a>
				<h4><a href="<?php echo get_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a></h4>
				
			</div>
		</div>
		<?php }else{?>
		<div class="col-sm-6 col-md-2 col-sms-12 <?php echo ++$i%2 ?'odd':'even';?> img_<?php echo $i ;?> home_page" >
			<?php wc_get_template_part( 'content', 'product' ); ?>			
		</div>
		<?php } ?>
		<?php
		if($countPost%$number == 0){
			echo '<div class="spacer"></div>';
		}
		?>
		
		
		<?php $countPost++;?>
		<?php endwhile; ?>
		<?php echo $after_widget; ?>
		<?php endif;
		$content = ob_get_clean();
		if ( isset( $args['widget_id'] ) ) $cache[$args['widget_id']] = $content;
		echo $content;
		wp_cache_set('widget_featured_products_custom', $cache, 'widget');
        wp_reset_postdata();
	}


	/**
	 * update function.
	 *
	 * @see WP_Widget->update
	 * @access public
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array
	 */
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['cat_slug_lamvt'] = strip_tags($new_instance['cat_slug_lamvt']);
		$instance['style_theme'] = strip_tags($new_instance['style_theme']);
		$instance['featured'] = strip_tags($new_instance['featured']);
		$instance['number'] = (int) $new_instance['number'];
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset($alloptions['widget_featured_products_custom']) ) delete_option('widget_featured_products_custom');

		return $instance;
	}


	/**
	 * flush_widget_cache function.
	 *
	 * @access public
	 * @return void
	 */
	function flush_widget_cache() {
		wp_cache_delete('widget_featured_products_custom', 'widget');
	}


	/**
	 * form function.
	 *
	 * @see WP_Widget->form
	 * @access public
	 * @param array $instance
	 * @return void
	 */
	function form( $instance ) {
		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		$cat_slug_lamvt = isset($instance['cat_slug_lamvt']) ? esc_attr($instance['cat_slug_lamvt']) : '';
		$style_theme = isset($instance['style_theme']) ? esc_attr($instance['style_theme']) : '0';
		$featured = isset($instance['featured']) ? esc_attr($instance['featured']) : '1';
		if ( !isset($instance['number']) || !$number = (int) $instance['number'] )
			$number = 4;
?>
		<p>D4J Featured Products Display a list of Woocommerce featured products on your site developed by Lamvt, support: <a href="http://joomquery.com/"> JoomQuery </a></p>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'woocommerce'); ?></label>
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></p>
<p><label for="<?php echo $this->get_field_id('cat_slug_lamvt'); ?>"><?php _e('Category :', 'woocommerce'); ?></label>
		
		<select name="<?php echo esc_attr( $this->get_field_name('cat_slug_lamvt') ); ?>" id="<?php echo esc_attr( $this->get_field_id('cat_slug_lamvt') ); ?>">
				<option value=''><?php _e('Default'); ?></option>
				<?php

				$product_cats = get_terms("product_cat", "hide_empty=0");
				$default ='';
				 if ( !empty( $product_cats ) && !is_wp_error( $product_cats ) ){
					//$default ='default';

					if(isset($cat_slug_lamvt )){
						$default = $cat_slug_lamvt ;					
					}
					//echo $default.'lamvt';
					foreach ($product_cats as $cat )
					: if ( $default == $cat->slug )
					  $selected = " selected='selected'";
					else
					  $selected = '';
				  echo "\n\t<option value='".$cat->slug."' $selected>$cat->name</option>";
				  endforeach;
				 }
				?>
				</select>
		<!--<input class="widefat" id="<?php //echo esc_attr( $this->get_field_id('cat_slug_lamvt') ); ?>" name="<?php //echo esc_attr( $this->get_field_name('cat_slug_lamvt') ); ?>" type="text" value="<?php //echo esc_attr( $cat_slug_lamvt ); ?>" />-->
		
		
		
		
		</p>
<p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of products to show:', 'woocommerce'); ?></label>
		<input id="<?php echo esc_attr( $this->get_field_id('number') ); ?>" name="<?php echo esc_attr( $this->get_field_name('number') ); ?>" type="text" value="<?php echo esc_attr( $number ); ?>" size="3" /></p>
		
<p>
<label for="<?php echo $this->get_field_id('style_theme'); ?>"><?php _e('Style:', 'woocommerce'); ?></label>
	<select name="<?php echo esc_attr( $this->get_field_name('style_theme') ); ?>" id="<?php echo esc_attr( $this->get_field_id('style_theme') ); ?>">
		<option value='0' <?php if($style_theme == 0){echo "selected='selected'";}?>><?php _e('Default'); ?></option>
		<option value='1' <?php if($style_theme == 1){echo "selected='selected'";}?>><?php _e('Your Style'); ?></option>
	</select>
</p>
<p>
<label for="<?php echo $this->get_field_id('featured'); ?>"><?php _e('Show featured:', 'woocommerce'); ?></label>
	<select name="<?php echo esc_attr( $this->get_field_name('featured') ); ?>" id="<?php echo esc_attr( $this->get_field_id('featured') ); ?>">
		<option value='1' <?php if($featured == 1){echo "selected='selected'";}?>><?php _e('Yes'); ?></option>
		<option value='0' <?php if($featured == 0){echo "selected='selected'";}?>><?php _e('No'); ?></option>
	</select>
</p>
<?php
	//$product_cats = get_terms("product_cat", "hide_empty=0");
	//print_r($product_cats);
	}
}