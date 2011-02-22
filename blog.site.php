<?php
/*
 * This file is part of pluck, the easy content management system
 * Copyright (c) somp (www.somp.nl)
 * http://www.pluck-cms.org

 * Pluck is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * See docs/COPYING for the complete license.
*/

//Make sure the file isn't accessed directly.
defined('IN_PLUCK') or exit('Access denied!');

require_once ('data/modules/blog/functions.php');

function blog_pages_site() {
	include BLOG_POSTS_DIR.'/'.blog_get_post_filename($_GET['post']);

	$module_page_admin[] = array(
		'func'  => 'viewpost',
		'title' => $post_title
	);

	return $module_page_admin;
}

//---------------
// Theme: main
//---------------
function blog_theme_main() {
	global $lang;

	//Display existing posts.
	if (blog_get_posts()) {
		//Load posts in array.
		$posts = blog_get_posts();

		foreach ($posts as $post) {
		?>
			<div class="blog_post">
				<p class="blog_post_title">
					<a href="?file=<?php echo CURRENT_PAGE_SEONAME; ?>&amp;module=blog&amp;page=viewpost&amp;post=<?php echo $post['seoname']; ?>" title="<?php echo $post['title']; ?>"><?php echo $post['title']; ?></a>
				</p>
				<span class="blog_post_info">
					<?php echo $post['date'].' '.$lang['blog']['at'].' '.$post['time'].' '.$lang['blog']['in'].' '.$post['category']; ?>
				</span>
				<div class="blog_post_content">
					<?php
					//Check if we need to truncate
					if (module_get_setting('blog','truncate_posts') != '0')
						echo truncate($post['content'], module_get_setting('blog','truncate_posts'));
					else
						echo $post['content'];
					?>
				</div>
				<?php
				//If reactions are enabled, count reactions and display in 'read more'-link
				if (module_get_setting('blog','allow_reactions') == 'true') {
					$number = blog_get_reactions($post['seoname']);

					if ($number) {
						$number = count($number);

						if ($number == 1)
							$more_link = $number.' '.$lang['blog']['reaction'];
						else
							$more_link = $number.' '.$lang['blog']['reactions'];
					}
					else
						$more_link = $lang['blog']['no_reactions'];
				}
				//If reactions are disabled, display regular 'read more'-link
				else
					$more_link = $lang['blog']['read_more'];
				?>
				<p class="blog_post_more">
					<a href="?file=<?php echo CURRENT_PAGE_SEONAME; ?>&amp;module=blog&amp;page=viewpost&amp;post=<?php echo $post['seoname']; ?>" title="<?php echo $more_link; ?>">&raquo; <?php echo $more_link; ?></a>
				</p>
			</div>
		<?php
		}
		unset($post);
	}
}

//---------------
// Page: viewpost
//---------------
function blog_page_site_viewpost() {
	//Global language variables.
	global $lang;

	//Load blog post.
	$post = blog_get_post($_GET['post']);
	?>
		<div id="blog_post">
			<span id="blog_post_info">
				<?php echo $post['date'].' '.$lang['blog']['at'].' '.$post['time'].' '.$lang['blog']['in'].' '.$post['category']; ?>
			</span>
			<div id="blog_post_content">
				<?php echo $post['content']; ?>
			</div>
		</div>

		<?php
		//Check if reactions are enabled
		if (module_get_setting('blog','allow_reactions') == 'true') {
		?>
			<div id="blog_reactions">
				<p>
					<?php
					$number = blog_get_reactions($post['seoname']);

					if ($number) {
						$number = count($number);

						if ($number == 1)
							echo $number.' '.$lang['blog']['reaction'];
						else
							echo $number.' '.$lang['blog']['reactions'];
					}
					else
						echo $lang['blog']['no_reactions']
					?>
				</p>
				<?php
				$reactions = blog_get_reactions($_GET['post']);

				if ($reactions) {
					foreach ($reactions as $reaction) {
					?>
						<div class="blog_reaction" id="reaction<?php echo $reaction['id']; ?>">
							<p class="blog_reaction_name">
								<?php
								if (!empty($reaction['website']))
									echo '<a href="'.$reaction['website'].'">'.$reaction['name'].'</a>:';
								else
									echo $reaction['name'].':';
								?>
							</p>
							<span class="blog_reaction_info">
								<a href="#reaction<?php echo $reaction['id']; ?>"><?php echo $reaction['date'].' '.$lang['blog']['at'].' '.$reaction['time']; ?></a>
						</span>
							<p class="blog_reaction_message"><?php echo $reaction['message']; ?></p>
						</div>
					<?php
					}
				}
				?>
				<?php
				//If form is posted...
				if (isset($_POST['submit'])) {
					//Check if everything has been filled in.
					if (empty($_POST['blog_reaction_name']) || filter_input(INPUT_POST, 'blog_reaction_email', FILTER_VALIDATE_EMAIL) == false || filter_input(INPUT_POST, 'blog_reaction_website', FILTER_VALIDATE_URL) == false || empty($_POST['blog_reaction_message']))
						echo '<p class="error">'.$lang['contactform']['fields'].'</p>';

					//Add reaction.
					else {
						blog_save_reaction($_GET['post'], $_POST['blog_reaction_name'], $_POST['blog_reaction_email'], $_POST['blog_reaction_website'], $_POST['blog_reaction_message']);

						//Redirect user.
						redirect('?file='.CURRENT_PAGE_SEONAME.'&module=blog&page=viewpost&post='.$_GET['post'], 0);
					}
				}
				?>
				<form id="blog_post_form" method="post" action="">
					<div>
						<label for="blog_reaction_name"><?php echo $lang['general']['name']; ?></label>
						<br />
						<input name="blog_reaction_name" id="blog_reaction_name" type="text" />
						<br />
						<label for="blog_reaction_email"><?php echo $lang['general']['email']; ?></label>
						<br />
						<input name="blog_reaction_email" id="blog_reaction_email" type="text" />
						<br />
						<label for="blog_reaction_website"><?php echo $lang['general']['website']; ?></label>
						<br />
						<input name="blog_reaction_website" id="blog_reaction_website" type="text" value="http://" />
						<br />
						<label for="blog_reaction_message"><?php echo $lang['general']['message']; ?></label>
						<br />
						<textarea name="blog_reaction_message" id="blog_reaction_message" rows="7" cols="45"></textarea>
						<br />
						<input type="submit" name="submit" value="<?php echo $lang['general']['send']; ?>" />
					</div>
				</form>
			</div>

		<?php
		//End of commenting check.
		}
		?>

		<p>
			<a href="?file=<?php echo CURRENT_PAGE_SEONAME; ?>" title="<?php echo $lang['general']['back']; ?>">&lt;&lt;&lt; <?php echo $lang['general']['back']; ?></a>
		</p>
	<?php
}
?>