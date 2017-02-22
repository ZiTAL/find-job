<?php
define('PATH', '/home/pi/scripts/php/find-job');

require_once(PATH."/lib/page.php");
require_once(PATH."/lib/phpmailer.php");
require_once(PATH."/lib/smtp.php");

class Find_job
{
	public function __construct($json)
	{
		$info = json_decode(file_get_contents($json), true);
		$smtp_config = $info['smtp'];

		$page_list = scandir(PATH."/page");

		$deny = array('.', '..');

		$results = array();
		foreach($page_list as $page)
		{
			if(preg_match("/\.php/", $page))
			{
				$path = realpath(PATH."/page/".$page);
				require_once($path);

				$class = basename($page, '.php');
				if(class_exists($class))
				{
					$instance = new $class($info);
					$tmp = $instance->request();

					foreach($info['probintziak'] as $probintzia)
					{
						if(!isset($results[$probintzia]))
							$results[$probintzia] = array();

						$results[$probintzia] = array_merge($results[$probintzia], $tmp[$probintzia]);
					}
				}
			}
		}

		$html = "<h1>Bilatzeko erabili diren TAG-ak:</h1>\n<ul>\n";
		foreach($info['tagak'] as $tag)
			$html.="\t<li>{$tag}</li>\n";
		$html.= "</ul>\n\n";

		$html.= "<ul>\n";

		$tmp = array();
		foreach($results as $province => $values)
		{
			$html.="\t<li>\n";
			$html.="\t\t<h1>".$province."</h1>\n";
			foreach($values as $eskaintza)
			{
				if(!in_array($eskaintza['link'], $tmp))
				{
					$html.="\t\t\t<ul>\n";
					$html.="\t\t\t\t<li>\n";
					$html.="\t\t\t\t\t<h2>".$eskaintza['title']."</h2>\n";
					$html.="\t\t\t\t\t<p>".$eskaintza['description']."</p>\n";
					$html.="\t\t\t\t\t<a href=".$eskaintza['link'].">".$eskaintza['link']."</a>\n";
					$html.="\t\t\t\t</li>\n";
					$html.="\t\t\t</ul>\n";
				}
			}
			$html.="\t</li>\n";
		}
		$html.="</ul>";

		echo $html;
		exit();

		//header('Content-Type: text/html; charset=utf-8');
		//echo $html;
		//exit();

		$mail = new PHPMailer;
		$mail->isSMTP();                                      // Set mailer to use SMTP
		$mail->SMTPAuth = true;				      // Enable SMTP authentication
		$mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted

		$mail->Host = $smtp_config['host'];  		      // Specify main and backup SMTP server
		$mail->Username = $smtp_config['user'];               // SMTP username
		$mail->Password = $smtp_config['passwd'];             // SMTP password
		$mail->Port = $smtp_config['port'];                   // TCP port to connect to

		$mail->From = $smtp_config['from'];
		$mail->FromName = $smtp_config['from_name'];

		if(count($smtp_config['to'])>0)
		{
			foreach($smtp_config['to'] as $to)
				$mail->addAddress($to);
		}

		if(count($smtp_config['bcc'])>0)
		{
			foreach($smtp_config['bcc'] as $bcc)
				$mail->addBCC($bcc);
		}

		$mail->isHTML(true);                                  // Set email format to HTML

		$mail->Subject = "Lan eskaintzak: ".date('Y/m/d - H:i:s');
		$mail->Body    = $html;

		if(!$mail->send())
		{
		    echo 'Message could not be sent.';
		    echo 'Mailer Error: ' . $mail->ErrorInfo;
		}
		else
		    echo 'Message has been sent';
	}
}
