<?php

if ($argc < 3 || ($argv[1] != "main" && $argv[1] != "delta")) {
	printf("Usage: %s [main|delta] user-storage-path\n", $argv[0]);
	exit();
}

$prefix = $argv[2] . '/';
$prelen = strlen($prefix);

umask(0);

// turn off execution time limit
ini_set('max_execution_time', 0);

define("APP_PATH",realpath(dirname(__FILE__)."/../"));

if ($argv[1] == "main") {
	unlink(APP_PATH."/config/sphinx.ini");
	$cnt = 0;
} else {
	if (file_exists(APP_PATH."/config/sphinx.ini")) {
		$config = parse_ini_file(APP_PATH."/config/sphinx.ini",true);
		$cnt = $config['last_ID'];
	} else {
		printf("Please run main mode first and wait it to be finished\n");
		exit();
	}
}


if (!defined('CONFIG_SET')) {
	$config = parse_ini_file(APP_PATH."/config/config.ini",true);
	define('CONFIG_SET', 1);
	foreach ($config as $k=>$v){
		foreach ($v as $sk=>$sv){
			define(strtoupper("pandora_".$k."_".$sk),$sv);
		}
	}
}

require_once(APP_PATH."/models/mail/IDB_Mail.php");
require_once(APP_PATH."/models/mail/MailBase.php");


// open MySQL connection  
$dbconn = mysql_connect(PANDORA_DATABASE_HOST, PANDORA_DATABASE_USER, PANDORA_DATABASE_PASSWORD);
if (!$dbconn) {
	printf("Connect failed: %s\n", mysql_error());
    exit();
}
$db_selected = mysql_select_db(PANDORA_DATABASE_DBNAME, $dbconn);
if (!$db_selected) {
	printf("Use %s failed: %s\n", PANDORA_DATABASE_DBNAME, mysql_error());
	exit();
}

$sql = "SELECT id, username, maildir FROM users WHERE address_type=0";
$results = mysql_query($sql, $dbconn);
if (!$results) {
	printf("Query failed: %s\n", mysql_error());
    exit();
}

$num = mysql_num_rows($results);
for ($i=0; $i<$num; $i++) {
	$dbrow = mysql_fetch_row($results);
	if (0 != strncmp($dbrow[2], $prefix, $prelen)) {
		continue;
	}
	$midb = new IDB_Mail($dbrow[2]);
	$mbase = new WebMail_Model_MailBase($dbrow[2]);
	$cfg_content = file_get_contents($dbrow[2].'/config/sphinx.cfg');
	if ($cfg_content) {
		$sphinx_cfg = json_decode($cfg_content, true);
	}
	if ($argv[1] == "delta") {
		$last_time = $sphinx_cfg['last_time'];
	}
	$cur_time = time();
	$opt = $midb->listfolder();
	if (!$opt['state']) {
		continue;
	}
	$folders = array('inbox', 'sent', 'draft', 'junk', 'trash');
	if (!empty($opt['data'])) {
		$folders = array_merge($folders, $opt['data']);
	}
	for ($j=0; $j<count($folders); $j++) {
		if ($argv[1] == "main") {
			$opt = $midb->listing($folders[$j], "UID", "DSC");
			if ($opt['state']) {
				$mrows = $opt['data'];
				for ($k=0; $k<count($mrows); $k++) {
					$tmpobj = parse_digest($dbrow[2], $folders[$j], $mrows[$k], $mbase);
					$tmpjson['file'] = $tmpobj['file'];
					$tmpjson['username'] = $dbrow[1];
					$tmpjson['folder'] = $folders[$j];
					echo $cnt+1 . "\t" . $dbrow[0] . "\t" . json_encode($tmpjson) . "\t" . $tmpobj['from'] . "\t" . $tmpobj['to'] . "\t"  . $tmpobj['cc'] . "\t" . $tmpobj['subject'] . "\t" . $tmpobj['content'] . "\t" . $tmpobj['attachment'] . "\n";			
					$cnt++;
					
				}
			}
		} else {
			$opt = $midb->uidl($folders[$j]);
			if ($opt['state']) {
				$mrows = $opt['data'];
				for ($k=0; $k<count($mrows); $k++) {
					if (intval($mrows[$k][0]) > $last_time) {
						$res = $midb->match($folders[$j], $mrows[$k][0]);
						if ($res['state']) {
							$tmpobj = parse_digest($dbrow[2], $folders[$j], $res['data'], $mbase);
							$tmpjson['file'] = $tmpobj['file'];
							$tmpjson['username'] = $dbrow[1];
							$tmpjson['folder'] = $folders[$j];
							echo $cnt+1 . "\t" . $dbrow[0] . "\t" . json_encode($tmpjson) . "\t" . $tmpobj['from'] . "\t" . $tmpobj['to'] . "\t"  . $tmpobj['cc'] . "\t" . $tmpobj['subject'] . "\t" . $tmpobj['content'] . "\t" . $tmpobj['attachment'] . "\n";	
							$cnt++;
						}
					} else {
						$path = $dbrow[2] . '/eml/' . $mrows[$k][0];
						$node_stat = stat($path);
						if ($node_stat['ctime'] > $last_time) {
							$res = $midb->match($folders[$j], $mrows[$k][0]);
							if ($res['state']) {
								$tmpobj = parse_digest($dbrow[2], $folders[$j], $res['data'], $mbase);
								$tmpjson['file'] = $tmpobj['file'];
								$tmpjson['username'] = $dbrow[1];
								$tmpjson['folder'] = $folders[$j];
								echo $cnt+1 . "\t" . $dbrow[0] . "\t" . json_encode($tmpjson) . "\t" . $tmpobj['from'] . "\t" . $tmpobj['to'] . "\t"  . $tmpobj['cc'] . "\t" . $tmpobj['subject'] . "\t" . $tmpobj['content'] . "\t" . $tmpobj['attachment'] . "\n";	
								$cnt++;
							}
						}
					}
					
					
				}
			}
		}
		
	}
	
	$sphinx_cfg['last_time'] = $cur_time;
	file_put_contents($dbrow[2].'/config/sphinx.cfg', json_encode($sphinx_cfg));
}

mysql_close($dbconn);

file_put_contents(APP_PATH."/config/sphinx.ini", "last_ID = " . $cnt);




function format_tsv($str)
{
	$search = array("\r\n", "\r", "\n", "\t");
	$replace = array(" ", " ", " ", "    ");
	
	return strip_tags(str_replace($search, $replace, $str));
	
}

function parse_digest($maildir, $folder, $dmsg, $mbase)
{
	$tmp = json_decode($dmsg, true);
	$path = $maildir . '/eml/' . $tmp['file'];
	$retobj['file'] = $tmp['file'];
	for ($i=0;$i<count($tmp['mimes']);$i++){
		if((strtolower($tmp['mimes'][$i]['ctype'])=='text/plain')) {
			if(!empty($tmp['mimes'][$i]['charset'])){
				$charset = $tmp['mimes'][$i]['charset'];
			}else{
				$charset = 'gb2312';
			}
			if (empty($retobj['content'])) {
				$retobj['content'] = $mbase->getMailContent($path,$tmp['mimes'][$i]['begin'],$tmp['mimes'][$i]['length'],$tmp['mimes'][$i]['encoding'],$charset);
			}
		} else if (strtolower($tmp['mimes'][$i]['ctype'])=='text/html'){
			if(!empty($tmp['mimes'][$i]['charset'])){
				$charset = $tmp['mimes'][$i]['charset'];
			}else{
				$charset = 'gb2312';
			}
			$retobj['content'] = $mbase->getMailContent($path,$tmp['mimes'][$i]['begin'],$tmp['mimes'][$i]['length'],$tmp['mimes'][$i]['encoding'],$charset);
		}
		
		if ($tmp['mimes'][$i]['filename']) {
			if ($retobj['attachment']) {
				$retobj['attachment'] .= "; ";
			}
			$retobj['attachment'] .= $mbase->clearAddress($mbase->strProce($tmp['mimes'][$i]['filename'],$charset));
		}
	}
	
	if ($retobj['content']) {
		$retobj['content'] = format_tsv($retobj['content']);
	} else {
		$retobj['content'] = 'N/A';
	}
	
	if ($retobj['attachment']) {
		$retobj['attachment'] = format_tsv($retobj['attachment']);
	} else {
		$retobj['attachment'] = 'N/A';
	}
	

	if($tmp['from']){
		$from = $mbase->getAddress($tmp['from'],"ARRAY");
		if ($from[0]['name']) {
			$retobj['from'] = '"' . $from[0]['name'] . '"[' .  $from[0]['address'] . ']';
		} else {
			$retobj['from'] = '[' .  $from[0]['address'] . ']';
		}
		$retobj['from'] = format_tsv($retobj['from']);
	} else {
		$retobj['from'] = 'N/A';
	}
	
	if($tmp['to']){
		$to = $mbase->getAddress($tmp['to'],"ARRAY");
		for ($i=0;$i<count($to);$i++) {
			if ($retobj['to']) {
				$retobj['to'] .= "; ";
			}
			if ($to[$i]['name']) {
				$retobj['to'] .= '"' . $to[$i]['name'] . '"[' .  $to[$i]['address'] . ']';
			} else {
				$retobj['to'] .= '[' .  $to[$i]['address'] . ']';
			}
		}
		$retobj['to'] = format_tsv($retobj['to']);
	} else {
		$retobj['to'] = 'N/A';
	}
	
	if($tmp['cc']){
		$cc = $mbase->getAddress($tmp['cc'],"ARRAY");
		for ($i=0;$i<count($cc);$i++) {
			if ($retobj['cc']) {
				$retobj['cc'] .= "; ";
			}
			if ($cc[$i]['name']) {
				$retobj['cc'] .= '"' . $cc[$i]['name'] . '"[' .  $cc[$i]['address'] . ']';
			} else {
				$retobj['cc'] .= '[' .  $cc[$i]['address'] . ']';
			}
		}
		$retobj['cc'] = format_tsv($retobj['cc']);
	} else {
		$retobj['cc'] = 'N/A';
	}
	
	if($tmp['subject']){
		$retobj['subject'] = $mbase->clearAddress($mbase->strProce($tmp['subject'],$charset));
		$retobj['subject'] = format_tsv($retobj['subject']);
	} else {
		$retobj['subject'] = 'N/A';
	}
	
	return $retobj;
}
	
?>

