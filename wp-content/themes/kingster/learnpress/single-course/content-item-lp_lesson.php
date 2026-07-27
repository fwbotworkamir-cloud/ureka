<?php
/**
 * Template for displaying lesson item content in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/content-item-lp_lesson.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$item   = LP_Global::course_item();
$course = LP_Global::course();
$user   = LP_Global::user();
?>

<div <?php learn_press_content_item_summary_class();?>>

	<?php

	do_action( 'learn-press/before-content-item-summary/' . $item->get_item_type() );

	do_action( 'learn-press/content-item-summary/' . $item->get_item_type() );

	?>
</div>
</div><!-- content-item-wrap -->
<?php
	$course = LP_Global::course();
	$user = LP_Global::user();
	if( class_exists('LP_Addon_Content_Drip_Preload') ){
		if( method_exists('LP_Addon_Content_Drip', 'check_drip_item') ){
			$drip_item = LP_Addon::$instances['LP_Addon_Content_Drip']->check_drip_item($item, $course, $user);
			if( empty($drip_item['locked']) ){
				do_action('gdlr_core_print_page_builder', $item->get_id());
			}
		}else{
			do_action('gdlr_core_print_page_builder', $item->get_id());
		}
	}else{
		do_action('gdlr_core_print_page_builder', $item->get_id());
	}
?>
<div class="content-item-wrap kingster-continue" >
	<?php

	do_action( 'learn-press/after-content-item-summary/' . $item->get_item_type() );

	?>
