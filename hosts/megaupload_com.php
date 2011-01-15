<?php
if (! defined ( 'RAPIDLEECH' )) {
	require_once ("index.html");
	exit ();
}

class megaupload_com extends DownloadClass {
	
	public function Download($link) {
		global $premium_acc,$mu_cookie_user_value;
		$matches = "";
		$Url = parse_url(trim($link));
		if (preg_match ( "/f=(\w+)/", $Url ["query"], $matches )) {
			$page = $this->GetPage("http://www.megaupload.com/xml/folderfiles.php?folderid=" . $matches [1]);
			if (! preg_match_all ( "/url=\"(http[^\"]+)\"/", $page, $matches )) html_error ( 'link not found' );
			
			if (! is_file ( "audl.php" )) html_error ( 'audl.php not found' );
			echo "<form action=\"audl.php?GO=GO\" method=post>\n";
			echo "<input type=hidden name=links value='" . implode ( "\r\n", $matches [1] ) . "'>\n";
			foreach ( array ( "useproxy", "proxy", "proxyuser", "proxypass" ) as $v )
				echo "<input type=hidden name=$v value=" . $_GET [$v] . ">\n";
			echo "<script language=\"JavaScript\">void(document.forms[0].submit());</script>\n</form>\n";
			flush ();
			exit ();
		}
        if ( ($_REQUEST ["premium_acc"] == "on" && $_REQUEST ["premium_user"] && $_REQUEST ["premium_pass"]) ||
			($_REQUEST ["premium_acc"] == "on" && $premium_acc ["megaupload_com"] ["user"] && $premium_acc ["megaupload_com"] ["pass"] ) ||
			($mu_cookie_user_value))        
		{
			$this->DownloadPremium($link);
		}else
                if( $_POST['pass_pre'] == "ok") { 
					global $Referer;
					$post ["filepassword"] = $_POST ['password'];
					$cookie=$_POST['cookie'];
					$page = $this->GetPage($link, $cookie, $post, $Referer );
					if (stristr($page,'password protected')) 
					{
						echo  ("<center><font color=red><b>Wrong password .Please re-enter</b></font></center>");
						$this->DownloadPremium($link);exit;
					} 
					is_page($page);
					if (stristr ( $page, "Location:" )) {
						$Href = trim ( cut_str ( $page, "Location: ", "\n" ) );
						$Url = parse_url ( html_entity_decode($Href, ENT_QUOTES, 'UTF-8') );
						$FileName = basename ( $Url ["path"] );                
						$this->RedirectDownload($Href,$FileName,encrypt($premium_cookie));exit;
					}
                } else {
					if( $_POST['pass_free'] == "ok") { 
						global $Referer;
						$post ["filepassword"] = $_POST ['password'];
						$page = $this->GetPage($link, 0, $post, $Referer );
						is_page($page);
						if (stristr($page,'password protected')) {
							echo  ("<center><font color=red><b>Wrong password .Please re-enter</b></font></center>");
							$this->DownloadFree($link);
							exit;
						} 
						$countDown = trim ( cut_str ( $page, "count=",";" ) );
						insert_timer( $countDown, "<b>Megaupload Free User</b>.","",true );
						if (preg_match('/http:\/\/(.*)" class="down_butt1"/', $page, $match)) {
							$Href = "http://$match[1]";
							$Url = parse_url ( html_entity_decode($Href, ENT_QUOTES, 'UTF-8') );
							if (! is_array ( $Url )) {
							html_error ( "Download link not found", 0 );
							}
							$FileName = basename ( $Url ["path"] );
							$this->RedirectDownload($Href,$FileName);exit;
						}
					} else {
							$this->DownloadFree($link);
					}
				}
	}
	private function DownloadFree($link) {
         $Referer;

		$page = $this->GetPage($link,0,0,$Referer);
        is_page($page);

		if (stristr($page,'password protected')) {
			?>
			<form method="post">
			<div>The file Megaupload you're trying to download is password protected. Please enter the password to proceed.</div> 
			<input type="hidden" name="link" value="<?php echo $link; ?>" />
			<input type="hidden" name="pass_free" value="ok" />
			<input type="text" name="password" id="password"/><input type="submit" value="Proceed" />
			</form>
			<?php
			exit;
        }
		//$countDown = trim ( cut_str ( $page, "count=",";" ) );
		//insert_timer( $countDown, "<b>Megaupload Free User</b>.","",true );
		//
		//Don't edit this random $countDown to 0.
		$countDown = rand(5, 10);
		sleep($countDown);
		
		is_present ( $page, "The file you are trying to access is temporarily unavailable" );
		
		preg_match('/http:\/\/(.*)" class="down_butt1"/', $page, $match);
		if (isset($match[1])) {
			$Href = 'http://'.$match[1];
			$Url = parse_url ( html_entity_decode($Href, ENT_QUOTES, 'UTF-8') );
			if (! is_array ( $Url )) {
				html_error ( "Download link not found", 0 );
			}
			$FileName = basename ( $Url ["path"] );
			$this->RedirectDownload($Href,$FileName);
			exit ();
		}
	}
	private function DownloadPremium($link) {
		global $Referer, $premium_acc, $mu_cookie_user_value;

		$post = array ();
		$post ['login'] = 1;
		$post ["username"] = $_GET ["premium_user"] ? $_GET ["premium_user"] : $premium_acc ["megaupload_com"] ["user"];
		$post ["password"] = $_GET ["premium_pass"] ? $_GET ["premium_pass"] : $premium_acc ["megaupload_com"] ["pass"];
		$page = $this->GetPage('http://www.megaupload.com/?c=login',0,$post,'http://www.megaupload.com');
		is_page($page);
		$premium_cookie = GetCookies($page);
		if ($mu_cookie_user_value) {
				$premium_cookie = 'user=' . $mu_cookie_user_value;
		} elseif ($_GET ["mu_acc"] == "on" && $_GET ["mu_cookie"]) {
				$premium_cookie = 'user=' . $_GET ["mu_cookie"];
		} elseif (! stristr ( $premium_cookie, "user" )) {
				html_error ( "Cannot use premium account", 0 );
		}
		$page = $this->GetPage($link,$premium_cookie,0,$Referer);
		is_page ( $page );
		if (stristr($page,'password protected')) {
			?>
			<form method="post">
			<div>The file Megaupload you're trying to download is password protected. Please enter the password to proceed.</div> 
			<input type="hidden" name="link" value="<?php echo $link; ?>" />
			<input type="hidden" name="cookie" value="<?php echo $premium_cookie;?>" />
			<input type="hidden" name="pass_pre" value="ok" />
			<input type="text" name="password" id="password"/><input type="submit" value="Proceed" />
			</form>
			<?php
			exit;
		}
						
        if (stristr ( $page, "Location:" )) {
			$Href = trim ( cut_str ( $page, "Location: ", "\n" ) );
			$Url = parse_url ( html_entity_decode($Href, ENT_QUOTES, 'UTF-8') );
			$FileName = basename ( $Url ["path"] );                
			$this->RedirectDownload($Href,$FileName,encrypt($premium_cookie));
        } elseif ($page = cut_str ( $page, 'class="down_ad_pad1">', '" class="down_ad_butt1"' )) {
			$Href = cut_str ( $page, 'href="', '"' );
			$Referer = $link;
			$Url = parse_url ( html_entity_decode($Href, ENT_QUOTES, 'UTF-8') );
			$FileName = basename ( $Url ["path"] );                
			$this->RedirectDownload($Href,$FileName,encrypt($premium_cookie));
        } else {
			html_error ( "Download link not found", 0 );
        }
	}
}

// Updated by rajmalhotra on 10 Jan 2010 MegaUpload captcha is downloaded on server, then display
// Fixed by rajmalhotra on 20 Jan 2010 Fixed for Download link not found in happy hour
// Fixed by VinhNhaTrang 13.10.2010
// Fixed by VinhNhaTrang 30.11.2010
// Fixed by thangbom40000 on 2010.12.1, Fix for free user and premium download, no wait time, no capcha with free user.
// Fixed by thangbom40000 on 2010.12.2, Fix premium account can't login.
 ?>
