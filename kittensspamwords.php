<?php
/**
*	\file kittensspamwords.php
*	Self contained plugin file, nothing else is necessary for use.
*/
/**
Plugin Name: Kitten's Spam Words
Version: 2.2c
Plugin URI: http://blog.mookitty.co.uk/devblog/kittens-spam-words/
Description: This plugin adds a "Delete comments as spam" button to the comments mass editing page. When used to delete unwanted comments, the email address, url, and IP address, and any links in the comment body, will be added to your spam words list. Future comments matching any of those items will automatically be moderated.<br>(c) 2004, licensed under the GPL
Author: Kitten
Author URI: http://blog.mookitty.co.uk
*/
/** \mainpage
\section meta Plugin Description:
This plugin adds a "Delete comments as spam" button to the comments mass editing page. When used to delete unwanted comments, the email address, url, and IP address, and any the domain of any links in the comment body, will be added to your spam words list (SWL). Future comments matching any of those items will automatically be moderated.
\section install Installing:
	-# Copy the file \c kittensspamwords.php to your \c /wp-contents/plugins/ folder. 
	-# Activate by clicking on the "Activate" link to the right of the plugin descriptions on your Plugins Admin page.
	-# Congratulations! You're now set up to begin building a custom SWL.
\section use Usage:
To delete comments and add the IP address, email address, url, and any urls in the body of the comment, follow these steps:

	-# In your admin pages choose: <code>Edit > Comments > Mass Edit Mode</code>.
	-# Select the comments that you'd like to delete as spam.
	-# Click the "Delete Comments As Spam" button.
	-# Click 'OK' in the popup box to verify that you're doing this on purpose.
	-# Make any edits that you feel are necessary to the items found.
	 - To abort and not add any of these words to your SWL, click the "Return to edit page w/o adding words" link.
	-# You will be taken to the Discussion Options page to verify that the words were added to your SWL.
\subsection mail Deleting by email:
As of 2.1 you have the option of adding a "Delete Comments as Spam" link to the emails that are sent when comment is moderated. 

To activate this:
	-# For WP version 1.2 use the files in the folder "For-WordPress-1.2"
	-# For WP version 1.3 use the files in the folder "For-WordPress-1.3"
	-# Copy the file \c functions.php to your \c wp-includes folder, replacing your existing \c functions.php file.
	-# If you prefer you can apply the .diff file \c functions.diff to your existing \c functions.php file.
	-# Now when you get an email about a comment held for moderation, it now has a link to delete the comment as spam. 
	-# Click the link in the email, the comment will be deleted and you will be taken to the delete page of the plugin where you can add the words in the list to the SWL.
\section upgrade Upgrading:
If you've installed a previous version, and made the needed edits to your <code>edit-comments.php</code> file, don't worry! This version doesn't use those edits, but includes the necessary placeholder so that your page won't break. If you want, you can replace <code>edit-comments.php</code> with a fresh version, but it's not necessary.
\section details How it works:
The button in inserted with Javascript and placed using the DOM. The Javascript in the button also redirects the form results into the plugin itself. The comments are parsed and deleted, and then the found words are displayed. After the user makes any edits to the list it is merged with the existing SWL, sorted and saved.

Now, as of version 2.1, to make matching more broadly based, only the domain of the spam link is added. So <code>www.domain.com</code>, <code>foo.domain.com</code>, <code>bar.domain.com</code>, and <code>foo.domain.co.uk</code>, will be entred as just <code>domain</code>. This is to remove cruft from your SWL, and to make future matches more broadly based.
\section change Changelog:
	- 1.0 - Initial relase
	- 1.1 - Features added
	- 1.1.1 - Fix pass by reference bug
	- 2.0 - Insert button via DOM & code improvements
	- 2.0.1 - Add mk_delete_spam stub for backwards compat.
	- 2.1 - Now doesn't add the subdomains to the SWL. Add delete as spam link to the moderation email.
	- 2.1.1 - Fix error where plugin drops into spam checking mode unexpectedly.
	- 2.2 - Update for 1.3, style admin page.
	- 2.2a - Fix namespace error
	- 2.2b - Redir back to mass edit page after adding words
	- 2.2c - Fix redeclare class error
	- 2.2d - Fix class error, realise 1.3a5 is broken
\section credit Credits:
\author Kitten
\author mookitty@gmail.com
\author http://mookitty.co.uk/devblog - Find more cool stuff.
\author http://blog.mookitty.co.uk/ - Watch my brain melt in real time.
\author &copy; 2004, Licenced under the GNU GPL

*/
/**************----------------  STANDALONE SECTION  ---------------**************/
/// Control block if spambutton is pressed.
if( !empty($_POST['mk_spambutton']) ) {
	if( count( $_POST['delete_comments']) > 0 ) {
		require('../../wp-config.php');
		$killer = new sw_spam_mgr();
		$result = $killer->delete_spam( $_POST['delete_comments'] );
		$killer->display_edit_form( $result['num_deleted'], $result['words_found'] );
	} else {
		header('Location: ../../wp-admin/edit-comments.php?mode=edit');
	}
	exit;
}
/// Control block for adding spam words.
if( !empty($_POST['mk_spamaddbutton']) ) {
	require('../../wp-config.php');
	$adder = new sw_spam_mgr();
	$adder->add_spam_words( $_POST['mk_spamwordslist'] );
	header('Location: ../../wp-admin/edit-comments.php?mode=edit');
}

/// Control block for email links.
if (!empty($_GET['comment']) && !empty($_GET['user']) ) {
	require('../../wp-config.php');
	if ( $_GET['user'] == md5( $_GET['comment'].$wpdb->get_var("SELECT user_pass FROM $tableusers WHERE ID = '1'") ) ) {
		$killer = new sw_spam_mgr();
		$result = $killer->delete_spam( array($_GET['comment']) );
		$killer->display_edit_form( $result['num_deleted'], $result['words_found'] );
	} else {
		echo "Sorry you're not authorised.";
	}
	exit;
}

/**************--------------  END STANDALONE SECTION  -------------**************/

/**************-----------------  PLUGIN FUNCTIONS  ----------------**************/
/** The base plugin function.
*
*	This function is called by the plugin manager when the footer of an admin page loads. If the page is the comments mass editing page, the button is displayed.
*	\param none
*/
if ( ! function_exists( 'sw_insert_gui_widget' ) ) :
function sw_insert_gui_widget()
{
	// Check page
	if( strpos( $_SERVER['PHP_SELF'], 'edit-comments.php' ) && $_GET['mode'] == 'edit' )
	{
		$button = new sw_button_mgr( get_settings('siteurl') );
		$button->insert_button();

	}	// End if Check page

}	// End mk_insert_gui_widget
endif;

/** Strictly for backwards compatibility
*
This function was used in a previous version, and is maintained so the anyone who has edited their edit-comments.php page doesn't need to re-edit it to upgrade.
*/
function mk_show_button() 
{
	return;
}
/** Strictly for backwards compatibility
*
This function was used in a previous version, and is maintained so the anyone who has edited their edit-comments.php page doesn't need to re-edit it to upgrade.
*/
function mk_delete_spam()
{
	return;
}

/**************---------------- END PLUGIN FUNCTIONS  --------------**************/

/**************----------------  CLASS DEFINITIONS  ----------------**************/
/** Creates the button and javascript
*
*	This class holds the javascript that will be inserted when called by mk_insert_gui_widget()
*/
if ( ! class_exists( 'sw_button_mgr' ) ) :
class sw_button_mgr {
	///	Holds url to plugin file.
	var $button_url;
	
	/** Builds the full url to the plugin file.
	*	\param $url - Base url of the WP install.
	*/
	function sw_button_mgr( $url )
	{
		$this->button_url = $url.'/wp-content/plugins/kittensspamwords.php';
	}
	
	/** Outputs the javascipt onto the page.
	*	\param none
	*	\todo Make this a return value, instead of echoing directly.
	*/
	function insert_button()
	{
		echo '<input type="submit" id="mk_spambutton" name="mk_spambutton" value="Delete Checked Comments As Spam" onclick="document.getElementById(\'deletecomments\').action = \''.$this->button_url.'\'; return confirm(\'Deleted the checked comments as spam?\');" />';
		// spit out script
		echo '<script language="JavaScript" type="text/javascript">'."\n";
		echo 'var deleteTable = document.getElementById("deletecomments");'."\n";
		echo 'var spamButton = document.getElementById("mk_spambutton");'."\n";
		echo 'deleteTable.appendChild(spamButton);'."\n";
		echo '</script>';
	} // End sw_button_mgr constructor

}	// End sw_button_mgr class
endif;
/** Manages the deletion of comments and finding of spam words
*
*/
class sw_spam_mgr {
	
	/** Extracts the spam items and deletes the comments.
	*	
	*	\param $delete_comments - The array of comment IDs that are to be deleted.
	*	\todo Seperate out the extraction & deletion functions.
	*	\todo Also check the domain of the email address.
	*/
	function delete_spam( $delete_comments )
	{
		global $wpdb, $tableposts, $tablecomments;

		$i = 0; 
		$mod_words = $this->cleanup_list();
		$comment_words = '';
		$comment_meta  = '';
		
		foreach ($delete_comments as $comment) {
			$comment = intval($comment);
			$comment_info = $wpdb->get_row("SELECT * FROM $tablecomments WHERE comment_ID = $comment");

			$comment_meta .= $comment_info->comment_author_email."\n";
			$comment_meta .= $comment_info->comment_author_IP."\n";

			// get the author's email domain
			$regex_url   = "/([a-z]{3,5})(:\/\/)(www\.)?([^\/\"<\s]*)/m";
			$mk_regex_array = array();
			preg_match($regex_url, $comment_info->comment_author_url, $mk_regex_array);
			if(!strpos($mod_words, $mk_regex_array[4])){
				$comment_words .= "\n".$mk_regex_array[4];
			}

			//get links found in body of comment.
			$regex_url   = "/(href=\")([a-z]{2,5})(:\/\/)(www\.)?([^\/\"<\s]*)/im";
			$mk_regex_array = array();
			preg_match_all($regex_url, $comment_info->comment_content, $mk_regex_array);
			
			for( $cnt=0; $cnt < count($mk_regex_array[5]); $cnt++ ) {
				$comment_words .= "\n".$mk_regex_array[5][$cnt];
			}

			// Weed out the subdomains:
			$regex_url = "/^([\w-]+)\.([\w-]+)(\.[\w]+)?/im";
			$sub_dom_arr = array();
			$domain_arr = array();
			
			preg_match_all($regex_url, $comment_words, $sub_dom_arr );
			for( $cnt = 0; $cnt < count($sub_dom_arr[0]); $cnt++ ) {
				if( strlen($sub_dom_arr[2][$cnt]) > 5 ) {
					$domain_arr[] = $sub_dom_arr[2][$cnt];
				} else {
					$domain_arr[] = $sub_dom_arr[1][$cnt];
				}
			}
			
			$return_str .= "\n".implode("\n", $domain_arr);
			$wpdb->query("DELETE FROM $tablecomments WHERE comment_ID = $comment");
			++$i;
		}
		return array('num_deleted' => $i, 'words_found' => $comment_meta.$return_str );
		
	}	// End function delete_spam

	/** Displays the edit form showing the found spam items.
	*	\param $num - The number of comments deleted by delete_spam().
	*	\param $words - List of words to display, as string, seperated by new lines.
	*	\return None
	*/
	function display_edit_form( $num, $words )
	{
	?>
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
	<title><?php bloginfo('name') ?> &rsaquo; Delete As Spam &#8212; WordPress</title>
	<link rel="stylesheet" href="../../wp-admin/wp-admin.css" type="text/css" />
	<link rel="shortcut icon" href="../../wp-images/wp-favicon.png" />
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo get_settings('blog_charset'); ?>" />
	</head>
	<body>
	<h2 class="wrap">Kitten's Spam Words Admin Page</h2>
	<div class='updated'><p><?php echo $num ?> comment(s) deleted and marked as spam.</p></div>
	<form action="" method="POST">
	<div class="wrap"><p><strong>These are the flagged spam words, edit and submit:</strong><br />
	(Duplicates will be removed upon add.)</p>
	<textarea rows="10" cols="40" name=mk_spamwordslist><?php echo $this->cleanup_list( $words ); ?></textarea><br />
	<input type="submit" name="mk_spamaddbutton" value="Add these items" /></form>
	<p><a href="../../wp-admin/edit-comments.php?mode=edit">Return to edit page w/o adding words</a></p>
	</div>
	</body>
	</html>
	<?php
	} 	// End function display_edit_form
	
	/** Prepares the list of words for diplaying.
	*
	*	This member removes all extraneous white space from the list, turn it into an array, removes any duplicate values, and then sorts it before recombining it into a string.
	*	\param $list (optional) - The word list to process, or if empty the contents of the built-in spam words list.
	*	\return A cleaned up list.
	*/
	function cleanup_list( $list = '' ) 
	{
		if( empty( $list ) ) {
			$list = get_settings('moderation_keys');
		}
		$list = trim( $list );
		$list = explode( "\n", $list );
		$list = array_unique( $list );
		natcasesort($list);
		$list = implode( "\n", $list );
		
		return $list;
	
	}	// End function cleanup_list
	
	/** Adds the words to the Spam Words List
	*
	*	\param $word_list - The word list to add to the built-in spam words list.
	*	\return None
	*/
	function add_spam_words( $word_list )
	{
		$new_list = $this->cleanup_list()."\n".$word_list;
		update_option('moderation_keys',$this->cleanup_list($new_list));
		
		return;
	}	// End function add_spam_words

}	// End class spam_mgr
/**************--------------  END CLASS DEFINITIONS  --------------**************/

/**************-----------------  PLUGIN API HOOKS  ----------------**************/

if( function_exists('add_action') ) {
	add_action('admin_footer','sw_insert_gui_widget');
}
?>
