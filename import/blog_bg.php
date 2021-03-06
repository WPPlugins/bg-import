﻿<?php
/**
* BG Import (blog import)
*
* @link http://kaloyan.info/blog/bg-import
* @author Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*/

/**
* @internal prevent from direct calls
*/
if (!defined('ABSPATH')) {
	return ;
	}

/////////////////////////////////////////////////////////////////////////////

/**
* Import from Blog.bg
*/
Class bg_import_blog_bg {

	/**
	* Constructor
	*/
	Function bg_import_blog_bg() {
		
		// Snopy - the HTTP client
		//
		require_once(ABSPATH . WPINC . '/class-snoopy.php');
		
		// the admin-handling class
		//
		require_once(WP_BG_IMPORT_DIR
			. '/lib/wp-admin-page/class.wp-admin-page.php'
			);

		// the UTF8 converter
		//
		define('MAP_DIR', WP_BG_IMPORT_DIR
			. '/lib/utf8-bg/');
		require_once(WP_BG_IMPORT_DIR
			. '/lib/utf8-bg/utf8.class.php'
			);
			
		// The cyr-cho oddity
		//
		require_once(WP_BG_IMPORT_DIR
			. '/lib/cyr-cho/class.cyr-cho.php'
			);

		// some extra post-related functionality
		//
		require_once ABSPATH . '/wp-admin/includes/post.php';
		require_once ABSPATH . '/wp-admin/includes/file.php';
		
		$this->dispatch();
		}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

	/**
	* Invoke the necessary step of the import process
	*/
	Function dispatch() {

		$step = isset($_POST['step']) ? (int) $_POST['step'] : 0;
		if ($step) {
			$data = $this->_form();
			}

		$this->header($data['step']);

		switch ($data['step']) {
			default :
				$this->form();
				break;

			case 1 : /* postove & kategorii */
				$this->import_posts($data);
				break;

			case 2 : /* komentari */
				$this->import_comments($data);
				break;

			case 3 : /* failove */
				$this->import_files($data);
				break;
			
			case 4 : /* blogroll & author */
				$this->import_meta($data);
				break;
				
			case 5 : /* we are done */
				$this->import_final($data);
				break;
			}

		$this->footer();
		}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 
	
	/**
	* Converts a date 
	*
	* @param string $string
	* @return string
	* @access protected
	* @uses cyr_cho::convert()
	*/
	Function _date($string) {
		$string = cyr_cho::convert($string);

		// append the missing date or year
		//
		list($_date, $_time) = explode(' ', $string);
		$_ = explode('.', $_date);
		switch(count($_)) {
			case 0:
				$_ = date('d.m.Y');
				break;
			case 2:
				$_[] = date('Y');
				break;
			}
		$_date = join('.', $_);
		$string = $_date . ' ' . $_time;

		$string = date('Y-m-d H:i:s', strToTime($string));
		return $string;
		}
	
	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

	/**
	* Show the first page from the import, e.g. "the form"
	*/
	Function form() {
		$data = $_POST + array(
			'url' => 'http://',
			'step' => 1,
			);
		?>
<div class="narrow">
	<p>
	Този модул за импорт ви позволява да прехвърлите данните от блог на Blog.bg във вашия WordPress блог.<br />
	Въведете името на блога, и след като сте готови натиснете &quot;Старт!&quot;.
	</p>
	
	<?php  wp_admin_page::error(null, 1);?>
	
	<form method="post"><input type="hidden" name="step" value="1" />
	
	<p><b>Адрес на блога</b>: <input name="url"
		value="<?php echo htmlSpecialChars($data['url']); ?>"
		/><br /> Въведете адреса на вашия блог на Blog.bg
		като не забравите да добавите <kbd>http://</kbd>
	</p>

	<p class="submit">
		<input class="button" type="submit" value="Старт!"/>
	</p>
	</form>
</div>
		<?php
		}

	/**
	* Process data from the first page (e.g. "the form")
	*/
	Function _form() {

		$data = $_POST + array(
			'url' => null,
			'step' => 0,
			'p' => 0,
			);

		// is it a log.bg blog ?
		//
		if (strstr(strToLower($data['url']), '.blog.bg') === false) {
			wp_admin_page::error(
				'Подаденото URL не съдържа в себе си <samp>.blog.bg</samp>'
				);
			}

		// blog exists ?
		//
		if (!wp_admin_page::error()) {
			$sn = new Snoopy;
			if (!@$sn->fetch($data['url'])) {
				wp_admin_page::error(
					'Не може да се зареди въведеното URL:
						<samp>' . $data['url'] . '</samp>'
					);
				}
			}
		
		// errors found ?
		//
		if (wp_admin_page::error()) {
			$data['step'] = 0;
			}

		return $data;
		}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

	/**
	* First step of the import process: import the posts!
	*/
	Function import_posts($data) {

		// show the form ?
		//
		if (!$data['save_post']) { ?>
<div class="narrow">
	<p>
	Първа стъпка от импортирането на блог от Blog.bg е да се прехвърлят
	постовете (статиите) от блога. За целта натиснете копчето &quot;Импорт на постове&quot;,
	или ако решите може да прескочите тази стъпка.
	</p>

	<p>
	Когато започне работата по импортирането на постовете (статиите),
	бъдете търпеливи и не презареждайте страницата ;)
	</p>

	<form method="post">
		<input type="hidden" name="step" value="1" />
		<input type="hidden" name="url"
			value="<?php echo htmlSpecialChars($data['url']); ?>" />
	
	<p class="submit">
		<input class="button" type="submit" name="save_post" value="Импорт на постове"/>
		<input type="button" value="Не, не искам да импортирам постове"
			onClick="this.form.elements['step'].value='2'; this.form.submit();"/>
	</p>
	</form>
</div>
		<?php return; }

		// start the job, first by making sure
		// there will be no interruptions
		//
		set_time_limit(0);
		ignore_user_abort(1);
		?>

<div class="wrap">
	<p>Импортирането започва.</p>
	<ul style="list-style: disc inside;">
		<li>Начало.</li>

<?php flush();

		// do the import !
		//
		$pages = array('current' => 1, 'total' => 1);

		$_urls = array();

		$utfConverter = new utf8(CP1251);
		$sn = new Snoopy;
		while ($pages['current'] <= $pages['total']) {

			$url = $data['url'] . '?page=' . $pages['current'];
			if (!$sn->fetch($url)) {
				wp_admin_page::error(
					'Не може да се зареди въведеното URL: <samp>'
						. $url . '</samp>'
					);
				$pages['current']++;
				continue;
				}
			$sn->results = $utfConverter->strToUtf8($sn->results);

			echo '<li>Страница #', $pages['current'], ' (<a target="_blank" href="',
				$url, '">', $url, '</a>)<br />
				<table class="widefat" cellspacing="0" style="margin-top: 5px;">';

			// how many pages are there ?
			//
			if (preg_match_all('~<a href="http://\w+\.blog\.bg\?page=\d+">(\d+)</a>~Uis', $sn->results, $R)) {
				$pages['total'] = max($R[1]);
				}

			// extract the post links
			//
			$records = array();
			if (preg_match_all('~<div class="t">\s*<span>.*\s+<span>Последна промяна\:</span>\s*.*\s*</div>~Uis', $sn->results, $matches)) {

				foreach ($matches[0] as $r) {
					$record = array();
					
					// title & url
					//
					if (preg_match('~<a href="http://\w+\.blog\.bg/(\w+/\d{4}/\d{2}/\d{2}/.*\.\d+)">\s*(.*)\s*</a>~', $r, $R)) {
						$record = $record + array(
							'url' => $data['url'] . '/' . $R[1],
							'title' => trim($R[2])
							);
						}

					// date 
					//
					if (preg_match('~<div class="t">\s*<span>(.*) - </span>~', $r, $R)) {
						$record = $record + array(
							'date' => $this->_date(trim($R[1])),
							);
						}

					// categories
					//
					if (preg_match_all('~<a href="http://blog.bg/\?cat=\d+">(.*)</a>~Uis', $r, $R)) {
						$record = $record + array(
							'categories' => array_map('trim', (array) $R[1]),
							);
						$record['categories'] = array_unique($record['categories']);
						}

					// extract the tags
					//
					if (preg_match_all('~<a href="tag\.php\?tag=.*" title=".*">(.*)</a>~Uis', $r, $R)) {
						$record = $record + array(
							'tags' => $R[1],
							);
						$record['tags'] = array_unique($record['tags']);
						}
					$records[] = $record;
					}
				}

			// insert the posts
			//
			foreach($records as $record) {

				// get post details
				//
				if (!$sn->fetch($record['url'])) {
					wp_admin_page::error(
						'Не може да се зареди въведеното URL: <samp>'
							. $record['url'] . '</samp>'
						);
					continue;
					}
				$sn->results = $utfConverter->strToUtf8($sn->results);

				// extract the content
				//
				if (preg_match('~</div>\s*<div class="tx">(.*)</div>\s*<div class="dots"></div>\s*<div class="info">\s*<span>Автор:</span>~Uis', $sn->results, $R)) {
					$record = $record + array(
						'content' => $R[1],
						);
					}
					
				// extract the tags
				//
				if (preg_match_all('~<a href="tag\.php\?tag=.*" title=".*">(.*)</a>~Uis', $sn->results, $R)) {
					$record = $record + array(
						'tags' => $R[1],
						);
					$record['tags'] = array_unique($record['tags']);
					}
					
				// do insert the post
				//
				global $current_user;
				$post = array(
					'post_status' => 'publish',
					'ping_status' => 'open',
					'comment_status' => 'open',
					'post_author' => $current_user->ID,
					'post_title' => $record['title'],
					'post_name' => cyr_cho::convert($record['title']),
					'post_content' => $record['content'],
					'post_date' => $record['date'],
					'tags_input' => $record['tags']
					);

				if ($id_exists = post_exists(
						$post['post_title'],
						$post['post_content'] /*,
						$post['post_date'] */
						)
					) {
					$_urls[$id_exists] = $record['url'];
					$this->_import_posts_row($record, 1);
					continue;
					}

				// do insert it
				//
				$post_id = wp_insert_post($post);
				
				// categories
				//
				if (count($record['categories']) > 0) {
					$post_cats = array();

					foreach ($record['categories'] as $category) {
						if ( '' == $category ) {
							continue;
							}
						$slug = sanitize_term_field('slug', $category, 0, 'category', 'db');
						$cat = get_term_by('slug', $slug, 'category');
						$cat_ID = 0;
						if ( ! empty($cat) ) {
							$cat_ID = $cat->term_id;
							}
						if ($cat_ID == 0) {
							$category = wpdb::escape($category);
							$cat_ID = wp_insert_category(array('cat_name' => $category));
							if ( is_wp_error($cat_ID) ) {
								continue;
								}
							}
						$post_cats[] = $cat_ID;
						}
					wp_set_post_categories($post_id, $post_cats);
					}

				// meta data
				//
				add_post_meta($post_id, 'blog_bg_original_url', $record['url'], true);
				$_urls[$post_id] = $record['url'];

				$this->_import_posts_row($record);
				flush();
				}

			// move to next page
			//
			$pages['current']++;
			echo '</table></li>';
			}

		// fix the urls
		//
		echo '<li>Променяне на връзките към старите статиити в прясно импортираното съдържание.
			<blockquote style="list-style: square inside;">';
				

		global $wpdb;
		foreach ($_urls as $post_id => $original) {
			$sql = "UPDATE {$wpdb->posts}
				SET `post_content` = REPLACE(`post_content`, '"
					. wpdb::escape($original) . "', '"
					. wpdb::escape(get_permalink($post_id)) . "')
				WHERE `post_type` = 'post' ";
			$wpdb->query($sql);
			
			if ($wpdb->rows_affected) {
				echo '<li>Променени са ', $wpdb->rows_affected, ' статии, съдържащи ', $original, '</li>';
				}
			}
?>
			</blockquote>
		</li>
		<li>Край.</li>
	</ul>
	<?php  wp_admin_page::error(null, 1);?>

	<br />
	<p>
	<b>Готови сме с пренасянето на постовете (статиите).</b> Сега може да се продължи нататък.
	</p>

	<form method="post">
		<input type="hidden" name="step" value="2" />
		<input type="hidden" name="url"
			value="<?php echo htmlSpecialChars($data['url']); ?>" />
	
	<p class="submit">
		<input class="button" type="submit" value="Напред към &quot;Импорт на коментарите&quot;"/>
	</p>
	</form>
</div>
		<?php
		}

	/**
	* Render the a row for an imported posts
	*/
	Function _import_posts_row($record, $exists = false) {
		
		$record['title'] = (!$exists)
			? ('<b>' . $record['title'] . '</b>')
			: $record['title'];
			
		if (!$record['categories']) {
			$record['categories'] = array(
				'</b><i>Няма</i><b>'
				);
			}
		if (!$record['tags']) {
			$record['tags'] = array(
				'</b><i>Няма</i><b>'
				);
			}
		
		
		static $i = 0;
		echo '<tr ', ($i++%2 ? '' : ' class="alternate"'), '>
<td><a target="_blank" href="', $record['url'], '"><big>',
	$record['title'], '</big></a><br/><small>Дата: <b>',
	$record['date'], '</b> | Категория: <b>',
	join((array) $record['categories'], '</b>, <b>'),'</b> | Тагове: <b>',
	join((array) $record['tags'], '</b>, <b>'), '</b></small></td>
<td style="width:25%;">', ($exists
	? '<span style="color:gray">Статията съществува (вече е импортирана).</span>'
	: '<b>Статията е импортирана успешно.</b>'), '</td>
		</tr>';
		flush();
		}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

	/**
	* Second step of the import process: do the comments
	*/
	Function import_comments($data) {

		// show the form ?
		//
		if (!$data['save_comment']) { ?>
<div class="narrow">
	<p>
	Втора стъпка от импортирането на блог от Blog.bg е да се прехвърлят
	коментарите от блога. За целта натиснете копчето &quot;Импорт на коментари&quot;,
	или ако решите може да прескочите тази стъпка.
	</p>

	<p>
	Когато започне работата по импортирането на коментарите,
	бъдете търпеливи и не презареждайте страницата ;)
	</p>

	<form method="post">
		<input type="hidden" name="step" value="2" />
		<input type="hidden" name="url"
			value="<?php echo htmlSpecialChars($data['url']); ?>" />
	
	<p class="submit">
		<input class="button" type="submit" name="save_comment" value="Импорт на коментари"/>
		<input type="button" value="Не, не искам да импортирам коментарите"
			onClick="this.form.elements['step'].value='3'; this.form.submit();"/>
	</p>
	</form>
</div>
		<?php return; }

		// start the job, first by making sure
		// there will be no interruptions
		//
		set_time_limit(0);
		ignore_user_abort(1);
		?>

<div class="wrap">
	<p>Импортирането започва.</p>
	<ul style="list-style: disc inside;">
		<li>Начало.</li>

<?php flush();

		// do the import !
		//
		$utfConverter = new utf8(CP1251);
		$sn = new Snoopy;

		global $wpdb;
		$results = $wpdb->get_results("
			SELECT `post_id`, `meta_value` AS `url`, `post_title`
			FROM {$wpdb->postmeta}
			INNER JOIN {$wpdb->posts}
				ON {$wpdb->posts}.id = {$wpdb->postmeta}.post_id
			WHERE meta_key = 'blog_bg_original_url' ", ARRAY_A);

		foreach ($results as $result) {

			echo '<li>Коментари на <a target="_blank" href="',
				$result['url'], '">', $result['post_title'], '</a></li>';
			flush();

			// comments are paginated
			//
			$_comments = 0;

			$url = $result['url'];
			if (!$sn->fetch($url)) {
				wp_admin_page::error(
					'Не може да се зареди въведеното URL: <samp>'
						. $url . '</samp>'
					);
				continue;
				}
			$sn->results = $utfConverter->strToUtf8($sn->results);

			echo '<table class="widefat" cellspacing="0" style="margin-top: 5px; margin-bottom: 7px;">';
			flush();

			// extract the comments
			//
			$sn->results = preg_replace('~^.*<a name="comments" class="anchor">Коментари</a>~Uis',
				'', $sn->results);
			if (preg_match_all('~<div class="t">\s*<span.*>\d+\.</span>\s*.*<div class="dots"></div>~Uis', $sn->results, $matches)) {
				foreach ($matches[0] as $r) {
					
					$_comments++;
					$comment = array(
						'comment_post_ID' => $result['post_id'],
						'comment_approved' => 1,
						'comment_author_IP' => '',
						'user_id' => 0,
						'comment_author_email' => 'blog.bg.import@kaloyan.info',
						'comment_author_url' => '',
						'comment_author' => '',
						'comment_agent' => 'Blog.bg Blog Importer',
						'comment_content' => ''
						);

					// content
					//
					if (preg_match('~<div class="tx">(.*)</div>\s*<a~Uis', $r, $R)) {
						$comment['comment_content'] = trim($R[1]);
						}

					// title, author & date ?
					//
					if (preg_match('~<span class="u_name">(.*) -</span>(.*<br />)<span.*>(.*)</span>~Uis', $r, $R)) {

						$comment['comment_date'] = $this->_date(trim($R[3]));
						$comment['comment_content'] = '<strong>' . $R[2] . '</strong>' . $comment['comment_content'];

						$comment['comment_author'] = trim(
							strip_tags($R[1])
							);
						if (preg_match('~http://.*\.blog\.bg~', $R[1], $R)) {
							$comment['comment_author_url'] = $R[0];
							}
						}

					// comment exists ?
					//
					$sql = "SELECT `comment_ID`
						FROM {$wpdb->comments}
						WHERE `comment_post_ID` = '{$comment['comment_post_ID']}'
							AND ( comment_author = '{$comment['comment_author']}' )
							AND comment_content = '{$comment['comment_content']}'
						LIMIT 1";
					if ( $wpdb->get_var($sql) ) {
						$this->_import_comments_row($comment, 1);
						} else {
						wp_insert_comment($comment);
						$this->_import_comments_row($comment);
						}
					}
				}				
			
			// no comments ?
			//
			if (!$_comments) {
				echo '<tr><td>Няма намерени коментари.</td></tr>';
				flush();
				}

			// move to next page
			//
			echo '</table></li>';
			flush();
			}
?>
		<li>Край.</li>
	</ul>
	<?php  wp_admin_page::error(null, 1);?>

	<br />
	<p>
	<b>Готови сме с пренасянето на коментарите.</b> Сега може да се продължи нататък.
	</p>

	<form method="post">
		<input type="hidden" name="step" value="3" />
		<input type="hidden" name="url"
			value="<?php echo htmlSpecialChars($data['url']); ?>" />
	
	<p class="submit">
		<input class="button" type="submit" value="Напред към &quot;Импорт на файловете&quot;"/>
	</p>
	</form>
</div>
		<?php
		}

	/**
	* Render the a row for an imported posts
	*/
	Function _import_comments_row($record, $exists = false) {
		
		$record['comment_content'] = ($exists)
			? ('<span style="color:gray;">' . $record['comment_content'] . '</span>')
			: $record['comment_content'];
		
		static $i = 0;
		echo '<tr ', ($i++%2 ? '' : ' class="alternate"'), '><td><big>',
			$record['comment_content'], '</big>', 
			'<br/><small>Автор: <b>', (
				$record['comment_author_url']
					? "<a target='_blank' href='{$record['comment_author_url']}'>{$record['comment_author']}</a>"
					: $record['comment_author']),
			'</b> | Дата: <b>', $record['comment_date'],
			'</b></small></td><td style="width:25%;">', ($exists
	? '<span style="color:gray">Коментарът съществува (вече е импортиран).</span>'
	: '<b>Коментарът е импортиран успешно.</b>'), '</td>
		</tr>';
		flush();
		}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

	/**
	* Third step of the import process: do the files
	*/
	Function import_files($data) {
		
		// show the form ?
		//
		if (!$data['save_files']) { ?>
<div class="narrow">
	<p>
	Третата стъпка от импортирането на блог от Blog.bg е да се прехвърлят
	файлове, качени от вас. За целта натиснете копчето &quot;Импорт на
	файлове&quot;, или ако решите може да прескочите тази стъпка.
	</p>

	<p>
	Когато започне работата по импортирането на файловете,
	бъдете търпеливи и не презареждайте страницата ;)
	</p>

	<form method="post">
		<input type="hidden" name="step" value="3" />
		<input type="hidden" name="url"
			value="<?php echo htmlSpecialChars($data['url']); ?>" />
	
	<p class="submit">
		<input class="button" type="submit" name="save_files" value="Импорт на файлове"/>
		<input type="button" value="Не, не искам да импортирам файловете"
			onClick="this.form.elements['step'].value='4'; this.form.submit();"/>
	</p>
	</form>
</div>
		<?php return; }

		// start the job, first by making sure
		// there will be no interruptions
		//
		set_time_limit(0);
		ignore_user_abort(1);
		?>

<div class="wrap">
	<p>Импортирането започва.</p>
	<ul style="list-style: disc inside;">
		<li>Начало.</li>

<?php flush();

		// do the import !
		//
		$sn = new Snoopy;

		global $wpdb, $current_user;
		$results = (array) $wpdb->get_results("
			SELECT `id`, `post_title`, `post_content`
			FROM `{$wpdb->posts}`
			WHERE `post_content` Like '%/photos/%'
				 -- AND `post_type` != 'attachment' 
			ORDER BY `ID` ", ARRAY_A);

		foreach ($results as $result) {

			echo '<li>Файлове от <a target="_blank" href="./../?p=',
				$result['id'], '">', $result['post_title'], '</a></li>';
			flush();

			echo '<table class="widefat" cellspacing="0" style="margin-top: 5px; margin-bottom: 6px;">';
			flush();

			$_files = 0;
			if (preg_match_all('~(?:http://[\.a-z0-9-]*blog\.bg)?/photos/\w+/[^"\'<>]+\.\w{3,4}~', $result['post_content'], $matches)) {

				$_files = 1;
				$matches[0] = array_unique($matches[0]);

				
				// walk the found urls
				//
				foreach ($matches[0] as $url) {

					// prefixed w/ 'blog.bg' ?
					//
					$real_url = $url;
					if (!preg_match('~^http:~', $url)) {
						$real_url = 'http://blog.bg' . $url;
						}
					
					// 'original' ?
					//
					//$real_url = str_replace("/original/", "/", $real_url);

					$filename = (strstr($real_url, '/original/')
							? 'original-'
							: ''
						). basename($url);

					// is it already added ?
					//
					$exists = $wpdb->get_var("
						SELECT `guid`
						FROM `{$wpdb->posts}`
						WHERE `post_type` = 'attachment'
							AND `post_name` ='" . $wpdb->escape(
								sanitize_title($filename)
								) . "'
						");

					// not added yet ?
					//
					$record = array(
						'original' => $real_url,
						'local' => $exists,
						);
					if (!$exists) {
						if (!$sn->fetch($real_url)) {
							wp_admin_page::error(
								'Не може да се зареди намереното URL: <samp>'
									. $real_url . '</samp>'
								);
							continue;
							}

						$_upload = wp_upload_dir();
						$upload = wp_upload_bits($filename, 0, $url);

						// create the tmp file
						//
						if (!$fp = fopen($upload['file'], 'w+')) {
							wp_admin_page::error(
								'Не може да се изтегли намереното URL: <samp>'
									. $url . '</samp>'
								);
							continue;
							}

						if (!fwrite($fp, $sn->results, strlen($sn->results))) {
							wp_admin_page::error(
								'Не може да се запише намереното URL: <samp>'
									. $url . '</samp>'
								);
							continue;
							}
						fclose($fp);
						$record['local'] = $upload['url'];

						$postdata = array(
							'post_parent' => $result['id'],
							'post_name' => $filename,
							'post_title' => $filename,
							'post_excerpt' => $url,
							'post_content' => "Файлът е импортиран от {$real_url}",
							'post_author' => $current_user->ID,
							'post_status' => 'publish',
							'ping_status' => 'open',
							'comment_status' => 'open',

							'guid' => $upload['url'],
							);

						if ( $info = wp_check_filetype($upload['file']) ) {
							$postdata['post_mime_type'] = $info['type'];
							}

						// ...as per wp-admin/includes/upload.php
						//
						$post_id = wp_insert_attachment(
							$postdata, $upload['file']
							);

						// do the meta
						//
						wp_update_attachment_metadata(
							$post_id, wp_generate_attachment_metadata(
								$post_id, $upload['file']
								)
							);
						}

					// replace the url in the post
					//
					$sql = "UPDATE `{$wpdb->posts}`
						SET `post_content` = Replace(`post_content`,
							'" . $wpdb->escape($url) . "',
							'" . $wpdb->escape($exists ? $exists : $upload['url']) . "')
						WHERE `post_content` Like '%{$url}%'
							AND `post_type` != 'attachment' ";
					$wpdb->query($sql);
					$this->_import_files_row($record, $exists);
					}
				}
			
			// no comments ?
			//
			if (!$_files) {
				echo '<tr><td>Няма намерени файлове.</td></tr>';
				flush();
				}

			echo '</table></li>';
			flush();
			}
?>
		<li>Край.</li>
	</ul>
	<?php  wp_admin_page::error(null, 1);?>

	<br />
	<p>
	<b>Готови сме с пренасянето на файловете.</b> Сега може да се продължи нататък.
	</p>

	<form method="post">
		<input type="hidden" name="step" value="4" />
		<input type="hidden" name="url"
			value="<?php echo htmlSpecialChars($data['url']); ?>" />
	
	<p class="submit">
		<input class="button" type="submit" value="Напред към &quot;Импорт на блогрола&quot;"/>
	</p>
	</form>
</div>
		<?php
		}

	/**
	* Render the a row for an imported files
	*/
	Function _import_files_row($record, $exists = false) {

		$record['title'] = (!$exists)
			? ('<b>' . basename($record['original']) . '</b>')
			: basename($record['original']);

		static $i = 0;
		echo '<tr ', ($i++%2 ? '' : ' class="alternate" '), '>
			<td><big>',
	$record['title'], '</big><br/><small>Източник: <a target="_blank" href="', $record['original'], '">',
	$record['original'], '</a> | Локално: <a target="_blank" href="', $record['local'], '">',
	$record['local'], '</a></small></td>
<td style="width:25%;">', ($exists
	? '<span style="color:gray">Файлът съществува (вече е импортиран).</span>'
	: '<b>Файлът е импортиран успешно.</b>'), '</td>
		</tr>';
		flush();
		}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

	/**
	* Fourth and final step of the import process: do the blogroll
	*/
	Function import_meta($data) {

		// show the form ?
		//
		if (!$data['save_blogroll']) { ?>
<div class="narrow">
	<p>
	Последната, четвърта стъпка от импортирането на блог от Blog.bg е да се
	прехвърлят линковете от вашия блогрол. За целта натиснете копчето
	&quot;Импорт на блогрол&quot;, или ако решите може да прескочите тази стъпка.
	</p>

	<p>
	Когато започне работата по импортирането на линковете от блогрола,
	бъдете търпеливи и не презареждайте страницата ;)
	</p>

	<form method="post">
		<input type="hidden" name="step" value="4" />
		<input type="hidden" name="url"
			value="<?php echo htmlSpecialChars($data['url']); ?>" />
	
	<p class="submit">
		<input class="button" type="submit" name="save_blogroll" value="Импорт на блогрол"/>
		<input type="button" value="Не, не искам да импортирам линковете от блогрола"
			onClick="this.form.elements['step'].value='5'; this.form.submit();"/>
	</p>
	</form>
</div>
		<?php return; }

		global $wpdb;

		?>

<div class="wrap">
	<p>Импортирането започва.</p>
	<ul style="list-style: disc inside;">
		<li>Начало.</li>

<?php flush();

		// do the import !
		//
		$sn = new Snoopy;
		$utfConverter = new utf8(CP1251);

		$url = $data['url'];
		if (!$sn->fetch($url)) {
			wp_admin_page::error(
				'Не може да се зареди намереното URL: <samp>'
					. $url . '</samp>'
				);
			} else {
			$sn->results = $utfConverter->strToUtf8($sn->results);
			
			if (preg_match_all('~</div>\s*<div class="line"></div>\s*<div class="list">.*<div class="footer-links">~Uis', $sn->results, $matches)) {

				foreach ($matches[0] as $chunk) {
				
					// links found ?
					//
					if (!preg_match_all('~<a[^>]*href="(http://.*)"[^>]*>(.*)</a>~Uis', $chunk, $L)) {
						continue;
						}
					
					echo '<li>Ето списъка от намерените линкове: <blockquote style="list-style: square inside;">';

					// insert the links
					//
					foreach ($L[0] as $i=>$r) {
						
						$link_id = $wpdb->get_var("
							SELECT `link_id`
							FROM {$wpdb->links}
							WHERE `link_url` = '" . $wpdb->escape($L[1][$i]) . "'
							");
						
						wp_insert_link(array(
							'link_id' => $link_id,
							'link_name' => $L[2][$i],
							'link_url' => $L[1][$i],
							'link_category' => array(
								get_option('default_link_category')
								),
							));

						echo '<li><b>', $L[2][$i], '</b>: <a target="_blank" href="', $L[1][$i], '">',
							$L[1][$i], '</a></li>';
						flush();
						}

					echo '</blockquote></li>';
					flush();
					}
				}
			}
?>
		<li>Край.</li>
	</ul>
	<?php  wp_admin_page::error(null, 1);?>

	<br />
	<p>
	<b>Готови сме с пренасянето на линковете от блогрола.</b> Сега може да се продължи нататък.
	</p>

	<form method="post">
		<input type="hidden" name="step" value="5" />
		<input type="hidden" name="url"
			value="<?php echo htmlSpecialChars($data['url']); ?>" />
	
	<p class="submit">
		<input class="button" type="submit" value="Напред към финала"/>
	</p>
	</form>
</div>
		<?php

		}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

	/**
	* We are done ;)
	*/
	Function import_final($data) { ?>

<div class="narrow">
	<p>
	Ами това е - честито интегриране на старият ви Blog.bg блог!
	Хайде лека работа и весело ползване на WordPress ;)
	</p>
</div>
		<?php
		}

	// -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- -- 

	/**
	* Print the page header for the import pages
	*/
	Function header($step = 0) {
		
		$_titles = array(
			0 => ': Адрес на блога',
			1 => ': Прехвърляне на постове',
			2 => ': Прехвърляне на коментари',
			3 => ': Прехвърляне на файлове',
			4 => ': Прехвърляне на линкове от блогрола',
			5 => ': Готово',
			);

		echo '<div class="wrap">';
		screen_icon();
		echo '<h2>Импорт на блог от Blog.bg',
				$_titles[(int)$step], '</h2>';
		flush();
		}

	/**
	* Print the page footer for the import pages
	*/
	Function footer() {
		echo '</div>';
		}

	Function _weak_escape($data) {
		return wpdb::_weak_escape($data);
		}

	//-end-of-class--
	}

/////////////////////////////////////////////////////////////////////////////

$bg_import_blog_bg = new bg_import_blog_bg();
