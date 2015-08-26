<?php
$WixID = '0';
$RequestPath = '';
$matchingHostName = '';
function plugin_wix_init(){
	define( 'PatternFile', dirname( __FILE__ ) . '/WixPattern.txt' );
	require_once( $_SERVER['DOCUMENT_ROOT'] . '/wix_cms_newbody.php' );
	runkit_function_rename('catbody', 'old_catbody');
	runkit_function_rename('new_catbody', 'catbody');
}
function plugin_wix_convert(){
	//ホントは1行ずつ読み込んでcatbody部分だけを抽出。その後このプラグインに書き込み(バージョン変更に伴うソース変更に対応するため。)とりあえず後回し
	// $file = file_get_contents(dirname('plugin').'/lib/html.php', FILE_USE_INCLUDE_PATH);
	// var_dump($file);
	return '';
}
function new_catbody($title, $page, $body){
	global $script, $vars, $arg, $defaultpage, $whatsnew, $help_page, $hr;
	global $attach_link, $related_link, $cantedit, $function_freeze;
	global $search_word_color, $_msg_word, $foot_explain, $note_hr, $head_tags;
	global $trackback, $trackback_javascript, $referer, $javascript;
	global $nofollow;
	global $_LANG, $_LINK, $_IMAGE;

	global $pkwk_dtd;     // XHTML 1.1, XHTML1.0, HTML 4.01 Transitional...
	global $page_title;   // Title of this site
	global $do_backup;    // Do backup or not
	global $modifier;     // Site administrator's  web page
	global $modifierlink; // Site administrator's name

	if (! file_exists(SKIN_FILE) || ! is_readable(SKIN_FILE))
		die_message('SKIN_FILE is not found');

	$_LINK = $_IMAGE = array();

	// Add JavaScript header when ...
	if ($trackback && $trackback_javascript) $javascript = 1; // Set something If you want
	if (! PKWK_ALLOW_JAVASCRIPT) unset($javascript);

	$_page  = isset($vars['page']) ? $vars['page'] : '';
	$r_page = rawurlencode($_page);

	// Set $_LINK for skin
	$_LINK['add']      = "$script?cmd=add&amp;page=$r_page";
	$_LINK['backup']   = "$script?cmd=backup&amp;page=$r_page";
	$_LINK['copy']     = "$script?plugin=template&amp;refer=$r_page";
	$_LINK['diff']     = "$script?cmd=diff&amp;page=$r_page";
	$_LINK['edit']     = "$script?cmd=edit&amp;page=$r_page";
	$_LINK['filelist'] = "$script?cmd=filelist";
	$_LINK['freeze']   = "$script?cmd=freeze&amp;page=$r_page";
	$_LINK['help']     = "$script?" . rawurlencode($help_page);
	$_LINK['list']     = "$script?cmd=list";
	$_LINK['new']      = "$script?plugin=newpage&amp;refer=$r_page";
	$_LINK['rdf']      = "$script?cmd=rss&amp;ver=1.0";
	$_LINK['recent']   = "$script?" . rawurlencode($whatsnew);
	$_LINK['refer']    = "$script?plugin=referer&amp;page=$r_page";
	$_LINK['reload']   = "$script?$r_page";
	$_LINK['rename']   = "$script?plugin=rename&amp;refer=$r_page";
	$_LINK['rss']      = "$script?cmd=rss";
	$_LINK['rss10']    = "$script?cmd=rss&amp;ver=1.0"; // Same as 'rdf'
	$_LINK['rss20']    = "$script?cmd=rss&amp;ver=2.0";
	$_LINK['search']   = "$script?cmd=search";
	$_LINK['top']      = "$script?" . rawurlencode($defaultpage);
	if ($trackback) {
		$tb_id = tb_get_id($_page);
		$_LINK['trackback'] = "$script?plugin=tb&amp;__mode=view&amp;tb_id=$tb_id";
	}
	$_LINK['unfreeze'] = "$script?cmd=unfreeze&amp;page=$r_page";
	$_LINK['upload']   = "$script?plugin=attach&amp;pcmd=upload&amp;page=$r_page";

	// Compat: Skins for 1.4.4 and before
	$link_add       = & $_LINK['add'];
	$link_new       = & $_LINK['new'];	// New!
	$link_edit      = & $_LINK['edit'];
	$link_diff      = & $_LINK['diff'];
	$link_top       = & $_LINK['top'];
	$link_list      = & $_LINK['list'];
	$link_filelist  = & $_LINK['filelist'];
	$link_search    = & $_LINK['search'];
	$link_whatsnew  = & $_LINK['recent'];
	$link_backup    = & $_LINK['backup'];
	$link_help      = & $_LINK['help'];
	$link_trackback = & $_LINK['trackback'];	// New!
	$link_rdf       = & $_LINK['rdf'];		// New!
	$link_rss       = & $_LINK['rss'];
	$link_rss10     = & $_LINK['rss10'];		// New!
	$link_rss20     = & $_LINK['rss20'];		// New!
	$link_freeze    = & $_LINK['freeze'];
	$link_unfreeze  = & $_LINK['unfreeze'];
	$link_upload    = & $_LINK['upload'];
	$link_template  = & $_LINK['copy'];
	$link_refer     = & $_LINK['refer'];	// New!
	$link_rename    = & $_LINK['rename'];

	// Init flags
	$is_page = (is_pagename($_page) && ! arg_check('backup') && $_page != $whatsnew);
	$is_read = (arg_check('read') && is_page($_page));
	$is_freeze = is_freeze($_page);

	// Last modification date (string) of the page
	$lastmodified = $is_read ?  format_date(get_filetime($_page)) .
		' ' . get_pg_passage($_page, FALSE) : '';

	// List of attached files to the page
	$attaches = ($attach_link && $is_read && exist_plugin_action('attach')) ?
		attach_filelist() : '';

	// List of related pages
	$related  = ($related_link && $is_read) ? make_related($_page) : '';

	// List of footnotes
	ksort($foot_explain, SORT_NUMERIC);
	$notes = ! empty($foot_explain) ? $note_hr . join("\n", $foot_explain) : '';

	// Tags will be inserted into <head></head>
	$head_tag = ! empty($head_tags) ? join("\n", $head_tags) ."\n" : '';

	// 1.3.x compat
	// Last modification date (UNIX timestamp) of the page
	$fmt = $is_read ? get_filetime($_page) + LOCALZONE : 0;

	// Search words
	if ($search_word_color && isset($vars['word'])) {
		$body = '<div class="small">' . $_msg_word . htmlsc($vars['word']) .
			'</div>' . $hr . "\n" . $body;

		// BugTrack2/106: Only variables can be passed by reference from PHP 5.0.5
		// with array_splice(), array_flip()
		$words = preg_split('/\s+/', $vars['word'], -1, PREG_SPLIT_NO_EMPTY);
		$words = array_splice($words, 0, 10); // Max: 10 words
		$words = array_flip($words);

		$keys = array();
		foreach ($words as $word=>$id) $keys[$word] = strlen($word);
		arsort($keys, SORT_NUMERIC);
		$keys = get_search_words(array_keys($keys), TRUE);
		$id = 0;
		foreach ($keys as $key=>$pattern) {
			$s_key    = htmlsc($key);
			$pattern  = '/' .
				'<textarea[^>]*>.*?<\/textarea>' .	// Ignore textareas
				'|' . '<[^>]*>' .			// Ignore tags
				'|' . '&[^;]+;' .			// Ignore entities
				'|' . '(' . $pattern . ')' .		// $matches[1]: Regex for a search word
				'/sS';
			$decorate_Nth_word = create_function(
				'$matches',
				'return (isset($matches[1])) ? ' .
					'\'<strong class="word' .
						$id .
					'">\' . $matches[1] . \'</strong>\' : ' .
					'$matches[0];'
			);
			$body  = preg_replace_callback($pattern, $decorate_Nth_word, $body);
			$notes = preg_replace_callback($pattern, $decorate_Nth_word, $notes);
			++$id;
		}
	}
	// var_dump($body);
	/*******/
	if ( $vars['cmd'] == 'edit' ) {
		//テキストエリアにもアタッチが及んでいるので、必要部分だけ抽出してアタッチし、後に文字列連結
		$frontBody = strstr($body, '<div', true);
		$rearBody = strstr(strstr($body, '<div'), '</div>');

		$attachBody = strstr(strstr($body, '<div'), '</div>', true);
		$newBody = new_body($attachBody);

		$body = $frontBody . $newBody . $rearBody;
	} else {
		$body = new_body($body);
	}
	/*******/

	$longtaketime = getmicrotime() - MUTIME;
	$taketime     = sprintf('%01.03f', $longtaketime);

	require(SKIN_FILE);
}

// function new_body( $content ) {

// 	//プレビューと違ってEnterキーによる空白(空行)を1文字としてカウントしてしまって、preview_attachとattachが噛み合わなくなってたからこうしている。
// 	//これよくない！
// 	// $content = preg_replace('/(\s|　)/','',$content);

// 	$WixID = returnWixID();

// 	$attachURL = 'http://trezia.db.ics.keio.ac.jp/sakusa_WIXServer_0.3.5/attach';
// 	$ch = curl_init();
// 	$data = array(
// 	    'minLength' => 3,
// 	    'rewriteAnchorText' => 'false',
// 	    'bookmarkedWIX' => $WixID,
// 	    'body' => mb_convert_encoding($content, 'UTF-8'),
// 	    // 'decideFileInfo' => $DecideFileInfo,
// 	);
// 	$data = http_build_query($data, "", "&");


// 	try {
// 		//送信
// 		curl_setopt( $ch, CURLOPT_URL, $attachURL );
// 		curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded') );
// 		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
// 		curl_setopt( $ch, CURLOPT_POST, true );
// 		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );

// 		$response = curl_exec($ch);

// 		if ( $response === false ) {
// 		    // エラー文字列を出力する
// 		    $response = 'エラーです. newBody.php-> ' .curl_error( $ch );
// 		}
// 	} catch ( Exception $e ) {
// 		$response = '捕捉した例外: ' . $e -> getMessage() . "\n";
// 	} finally {
// 		curl_close($ch);
// 	}

// 	// $response = $content . 'です';

// 	return $response;
// }

// function returnWixID() {
// 	global $WixID, $RequestPath, $matchingHostName;

// 	$firstCandidates = returnCandidates();
// 	$RequestPath = subjectPath();

// 	//ソート後
// 	$secondCandidates = array();
// 	//Selection後
// 	$finalCandidates = array();
// 	//既入wid除外用Array
// 	$finalCandidates_wids = array();

// 	$no_attachFlag = false;

// 	try{
// 		foreach ( $firstCandidates as $key => $value ) {
// 			$pattern = $key;
// 			$wids = $value;

// 			//正規表現パス
// 			if ( strpos( $pattern, '"' ) !== false ) {

// 				$pattern = removeSpace( $pattern );
// 				$pattern = substr( $pattern, 1, strlen($pattern) - 2 );

// 			} else {
// 				//パスパターン
// 				if ( strpos( substr( $pattern, 0, 1), '/' ) !== false ) 
// 					$pattern = '^' . $pattern;
// 				else 
// 					$pattern = '^/' . $pattern;
				
// 				if ( strpos( $pattern, ' ' ) !== false ) $pattern = removeSpace( $pattern );

// 				if ( strpos( $pattern, '*' ) !== false ) $pattern = preg_replace('/\\*/', '.*', $pattern );

// 				if ( strpos( $pattern, '?' ) !== false ) {
// 					//元々ある.をエスケープしてから?を.に。
// 					$pattern = preg_replace('/\\./', '\\\\.', $pattern );
// 					$pattern = preg_replace('/\\?/', '.', $pattern );
// 				}
// 			}

// 			//URLパスとマッチしたら$secondCandidatesに。
// 			if ( preg_match( '{' . $pattern . '}', $RequestPath, $matches, PREG_OFFSET_CAPTURE ) == 1 ) {

// 				if ( mb_strtolower( $wids ) !== 'no_attach'  ) {
// 					//$matches[0][0]にマッチング文字列, [0][1]にマッチング開始位置
// 					array_push( $secondCandidates, 
// 								array(
// 										'pattern' => $pattern,
// 										'wids' => $wids,
// 										'endLocation' => (strlen( $matches[0][0] ) + $matches[0][1]),
// 										'patternLength' => strlen( $matches[0][0] )
// 									)
// 							);

// 				} else {
// 					//最長一致でない限りフラグは立てない
// 					if ( (strlen( $matches[0][0] ) + $matches[0][1]) == strlen( $RequestPath ) ) {
// 						$no_attachFlag = true;
// 						break;
// 					}
// 				}
			
// 			}
// 		}

// 		if ( $no_attachFlag == false ) {
// 			$endLocation = array();
// 			$patternLength = array();

// 			foreach ( $secondCandidates as $key => $row ){
// 				//endLocation:マッチング終端位置, patternLength:マッチング文字列長
// 				$endLocation[$key] = $row['endLocation'];
// 				$patternLength[$key] = $row['patternLength'];

// 			}
// 			//ソート
// 			array_multisort( $endLocation, SORT_DESC, $patternLength, SORT_DESC, $secondCandidates );

// 			//オプションの判定
// 			$finalCandidates = selectCandidates( $secondCandidates );

// 			$decided_WixID = '';


// 			//wids決定
// 			foreach ( $finalCandidates as $key => $value ) {
// 				$tmpArray = $value;

// 				//既にあるwidは返さない
// 				if ( empty( $finalCandidates_wids ) === true ) {

// 					$decided_WixID = $tmpArray['wids'];

// 					$finalCandidates_wids = explode( '-', $tmpArray['wids'] );

// 				} else {

// 					$flag = false;

// 					foreach ( $finalCandidates_wids as $key => $value ) {
// 						$tmpWid = $value;

// 						if ( strpos( $tmpArray['wids'], $tmpWid ) !== false ) {

// 							$flag = true;
// 							break;
// 						}

// 					}
// 					if ( $flag == false ) {
// 						$decided_WixID = $decided_WixID . '-' . $tmpArray['wids'];
						
// 						foreach ( explode( '-', $tmpArray['wids'] ) as $key => $value ) {
// 							array_push( $finalCandidates_wids, $value );
// 						}	
// 					}

// 				}

// 			}

// 			$WixID = $decided_WixID;

// 		} else {
// 			$WixID = '0';
// 		}

// 	} catch ( Exception $e ) {
// 		echo '捕捉した例外: ',  $e -> getMessage(), "\n";
// 	}


// 	return $WixID;
// }

// function returnCandidates() {
// 	global $matchingHostName;
// 	$candidates = array();

// 	try{
// 		if ( file_exists( PatternFile ) && is_readable( PatternFile ) ) {

// 			$requestHost = requestURL_part( PHP_URL_HOST );

// 			//ファイル内容
// 			$fileContents = file_get_contents( PatternFile, FILE_USE_INCLUDE_PATH );

// 			//ホスト名探索($matches[1]にホスト名群)
// 			preg_match_all('/<(.*)>/', $fileContents, $matches);

// 			$subContents_num = array();

// 			//パターンファイル内該当箇所
// 			$subContents = '';

// 			foreach ( $matches[1] as $key => $value ) {
// 				if ( !empty( $value ) && preg_match( '/' . $value . '/', $requestHost ) ) {
					
// 					$matchingHostName = $value;
					
// 					//該当ホスト名の次に< >がある場合
// 					if ( isset( $matches[0][$key + 1] ) ) {
// 						array_push( 
// 									$subContents_num, 
// 									strpos( $fileContents, $matches[0][$key] ) + strlen($matches[0][$key] ),
// 									strpos( $fileContents, $matches[0][$key + 1] ) 
// 									);
// 						//該当ホスト名 ~ 次の<ホスト名>までを抽出。substrの第３引数は文字数
// 						$subContents = substr( $fileContents, $subContents_num[0], $subContents_num[1] - $subContents_num[0] );
// 					} else {
// 						array_push( 
// 									$subContents_num, 
// 									strpos( $fileContents, $matches[0][$key] ) + strlen($matches[0][$key] )
// 									);
// 						$subContents = substr( $fileContents, $subContents_num[0] );
// 					}
// 					break;
// 				}
// 			}

// 			try{
// 				$patterns = array();
// 				$wids = array();
// 				$tmp = '';
// 				$flag = false;

// 				$candidates = splitSpace( $subContents );

// 				/* 空白と:を除いた要素を$candidatesから取り除く*/
// 				foreach ( $candidates as $key => $value ) {
// 					if ( ($key = array_search(':', $candidates)) !== false 
// 							|| ($key = array_search('', $candidates)) !== false  ) {

// 						unset( $candidates[$key] );
// 					}
// 				}
// 				$candidates = array_merge( $candidates );

// 				//patternとwidへの分離
// 				foreach ( $candidates as $key => $value ) {
// 					if ( (strpos( $value, '/' ) !== false) || (strpos( $value, '"' ) !== false) ) {

// 						array_push( $patterns, $value );

// 						if ( $flag == true ) {
// 							array_push( $wids, $tmp );
// 							$tmp = '';
// 						}

// 					} else {
// 						$flag = true;

// 						if ( $tmp === '' ) $tmp = $value;
// 						else $tmp = $tmp . '-' . $value;

// 						if ( $key ===  count($candidates) - 1 )
// 							array_push( $wids, $tmp );
// 					}

// 				}

// 				$candidates = array();

// 				//連想配列作成
// 				for ( $i = 0; $i < count($patterns); $i++ ) {
// 					$candidates += array( $patterns[$i] => $wids[$i] );
// 				}


// 			} catch ( Exception $e ) {
// 				echo '捕捉した例外: ',  $e -> getMessage(), "\n";
// 			}

// 		} else {
// 			echo 'パターンファイルがありません。';
// 		}

// 	} catch ( Exception $e ) {
// 		echo '捕捉した例外: ',  $e -> getMessage(), "\n";
// 	}

// 	return $candidates;
// }

// function selectCandidates( $array ) {
// 	global $RequestPath;

// 	foreach ( $array as $key => $value ) {
// 		$tmpArray = $value;

// 		//widにonlyが付いてたら、そのパターンが最長一致した時のみ適用(卒論時のoffと同意)
// 		if ( strpos($tmpArray['wids'], 'only') !== false ) {

// 			//一致しなかったら候補から除外
// 			if ( $tmpArray['endLocation'] != strlen( $RequestPath ) ) {
// 				unset( $array[$key] );
// 			} else {
// 				$array[$key]['wids'] = str_replace( '-only', '', $tmpArray['wids'] );
// 			}
// 		}
// 	}

// 	return $array;
// }

// function startsWith( $haystack, $needle )	{
// 	return $needle === "" || strpos( $haystack, $needle ) === 0;
// }

// function endsWith( $haystack, $needle ) {
// 	return $needle === "" || substr( $haystack, -strlen($needle) ) === $needle;
// }

// function removeSpace( $str ) {
// 	$str = preg_replace('/(\s|　)/','',$str);
// 	return $str;
// }

// function splitSpace( $str ) {
// 	$str = preg_split("/[\s,]+/", $str);
// 	return $str;
// }

// function requestURL_part( $option ) {
// 	$url = (empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
// 	return parse_url( urldecode( $url ), $option );
// }

// function subjectPath() {
// 	$requestPath = requestURL_part( PHP_URL_PATH );
// 	$requestQuery = requestURL_part( PHP_URL_QUERY );

// 	$subjectPath;

// 	if ( isset( $requestQuery ) ) {
// 		$subjectPath = $requestPath . '?' . $requestQuery;
// 	} else {
// 		$subjectPath = $requestPath;
// 	}

// 	return $subjectPath;
// }
?>