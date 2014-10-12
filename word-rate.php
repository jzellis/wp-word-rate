<?php
/**
 * Plugin Name: Word Rate
 * Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
 * Description: Allows writers to have an attached word rate for estimating cost of articles
 * Version: 0.1
 * Author: Joshua Ellis
 * Author URI: http://www.standardnerds.com
 * License: GPL2
 */

defined('ABSPATH') or die("No script kiddies please!");


class JZE_Word_Rate{

static function showWordRatePage() {
	$errMsg = false;
	setlocale( LC_MONETARY, get_locale() );
	$localsettings = localeconv();
	if ( $localsettings['currency_symbol'] == "" ) {
		setlocale( LC_MONETARY, 'en_US' );
		$localsettings = localeconv();
	}
	if ( $_POST ) {
		$user_word_rates = $_POST['word_rate'];
		foreach ( $user_word_rates as $id=>$amount ) {
			$user = get_user_by('id',$id);
			if(is_numeric($amount)){
			$amount = money_format( '%i', $amount );
			update_user_meta( $id, "word_rate", $amount );
		}else{
		$errMsg .= "You must supply a numeric value for " . $user->user_nicename . "<br>";
		}
		}
	}
	if ( current_user_can( 'editor' ) || current_user_can( 'administrator' ) ): ?>
	<? if($errMsg != false): ?><div class='error'><?= $errMsg ?></div><? endif; ?>
<h2>Author Word Rate</h2>
<p>This allows you to assign a per word payment rate to authors, which will be displayed in the post list and in each post's editor, to help you plan your editorial budget.</p>
<form method="POST">
    <div class='tableWrapper'><table id='userList'><thead><tr><th>Username</th><th>Word Rate</th></tr></thead>
    <tbody>
        <?php
		foreach ( get_users( array(
					'blog_id' => $GLOBALS['blog_id'],
					'who' => 'authors'
				) ) as $theuser ) {
			if ( get_user_meta( $theuser->ID, "word_rate" ) ) {
				$word_rate = get_user_meta( $theuser->ID, "word_rate" );
			}
			else {
				$word_rate = array( "0.00" );
			}
			echo "<tr><td>" . get_avatar( $theuser->ID, 24 ) . " " . $theuser->user_nicename . "</td><td><input type='text' size='5' name='word_rate[" . $theuser->ID . "]' value='" . $word_rate[0] . "'></td></tr>";
		}
?>
    </tbody></table><input type='submit' value='Update'></div></form>
    <style>
    #userList th{
    text-align: left;
    }
    #userList td{
    font-weight: bold;
    width: 50%;
    line-height: 24px;
    padding-right: 4em;
    }
    #userList td img{
    vertical-align: middle;
    margin-right: 1em;
    }
    </style><?php else: ?>
    <b>You do not have sufficient privileges to access this page.</b>
    <?php endif;
}
static function word_rate_meta_box( $post ) {
	add_meta_box(
		'word-rate-meta-box',
		'Author Fee',
		array('JZE_Word_Rate','render_calculated_word_rate_box'),
		'post',
		'side' );
}
static function render_calculated_word_rate_box( $post ) {
	$post_author_word_rate = get_user_meta( $post->post_author, 'word_rate' );
	$rawContent = str_word_count( strip_tags( $post->post_content ) );
	echo "<ul>
        <li>Word Count: " . $rawContent . "</li>
        <li>Author Word Rate: " . money_format( '%i', $post_author_word_rate[0] ) . "</li>
        <li><b>Total: " . money_format( '%i', $post_author_word_rate[0] * $rawContent ) . "</li>
    </ul>";
}
static function my_users_menu() {
	add_users_page( 'Word Rate', 'Word Rate', 'edit_users', 'word-rate', array('JZE_Word_Rate','showWordRatePage') );
}
static function add_fee_column( $columns ) {
	if ( current_user_can( 'editor' ) || current_user_can( 'administrator' ) ):
		return array_merge( $columns,
			array( 'post_fee' => __( 'Post Fee' ) ) );
	else:
		return $columns;
	endif;
}
static function post_fee_column( $column, $post_id ) {
	if ( $column == "post_fee" && ( current_user_can( 'editor' ) || current_user_can( 'administrator' ) ) ) {
		$post = get_post( $post_id );
		$wc = str_word_count( strip_tags( $post->post_content ) );
		$post_author_word_rate = get_user_meta( $post->post_author, 'word_rate' );
		echo money_format( '%i', $post_author_word_rate[0] * $wc );
	}
}

static function user_fee_column($value,$column, $user_id ) {
	if ( $column == "post_fee" && ( current_user_can( 'editor' ) || current_user_can( 'administrator' ) ) ) {

		$wordrate = get_user_meta( $user_id, 'word_rate' );
		return $wordrate[0];
	}
}

}
add_filter( 'manage_post_posts_columns' , array('JZE_Word_Rate','add_fee_column') );
add_action( 'add_meta_boxes_post', array('JZE_Word_Rate','word_rate_meta_box') );
add_action( 'admin_menu', array('JZE_Word_Rate','my_users_menu' ));
add_action( 'manage_posts_custom_column' , array('JZE_Word_Rate','post_fee_column'), 10, 2 );
add_filter('manage_users_columns', array('JZE_Word_Rate','add_fee_column'));
add_filter('manage_users_custom_column', array('JZE_Word_Rate','user_fee_column'), 10, 3);

?>
