<?php
if (!mysql_num_rows(mysql_query("SHOW TABLES LIKE 'mod_yoncu_ayar'"))){
	mysql_query('CREATE TABLE IF NOT EXISTS `mod_yoncu_ayar` (
			`ayar_adi` int(11) NOT NULL,
			`ayar_ici` int(11) NOT NULL,
			PRIMARY KEY (`ayar_adi`)
		)
		ENGINE=MyISAM  DEFAULT CHARSET=utf8;
		insert into mod_yoncu_ayar ("YoncuApiID",null),("YoncuApiKey",null);
	');
}
function yoncu_curl($Islem,$params,$PostVeri=array(),$Deneme=0){
	$PostVeri['id']	= @mysql_fetch_assoc(@mysql_query("select ayar_ici from mod_yoncu_ayar where ayar_adi = 'YoncuApiID'"))['YoncuApiID'];
	$PostVeri['key']= @mysql_fetch_assoc(@mysql_query("select ayar_ici from mod_yoncu_ayar where ayar_adi = 'YoncuApiKey'"))['YoncuApiKey'];
	$Post	= array();
	foreach($PostVeri as $Adi => $Veri){
		$Post[]	= $Adi.'='.urlencode($Veri);
	}
	$URL	= 'http://www.yoncu.com/apiler/sunucu/'.$Islem.'.php';
	$ch = curl_init ();
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_COOKIEJAR, sys_get_temp_dir().DIRECTORY_SEPARATOR.'yoncu.com');
	curl_setopt($ch, CURLOPT_COOKIEFILE, sys_get_temp_dir().DIRECTORY_SEPARATOR.'yoncu.com');
	curl_setopt($ch, CURLOPT_USERAGENT, 'WHMCS ServerMod '.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
	curl_setopt($ch, CURLOPT_REFERER, $URL);
	curl_setopt($ch, CURLOPT_URL,'https://www.yoncu.com/YoncuTest/YoncuSec_Token');
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Cookie: YoncuKoruma='.$_SERVER['SERVER_ADDR'].';YoncuKorumaRisk=0;']);
	$Token = trim(curl_exec($ch));
	if(strlen($Token) != 32){
		return array(false,'Token Alınamadı');
	}
	curl_setopt($ch, CURLOPT_URL,$URL);
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Cookie: YoncuKoruma='.$_SERVER['SERVER_ADDR'].';YoncuKorumaRisk=0;YoncuSec-v1='.$Token]);
	curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&',$Post));
	$Json = curl_exec($ch);
	$HttpStatus	= curl_getinfo($ch, CURLINFO_HTTP_CODE);
	if($HttpStatus != 200){
		if($Deneme < 4){
			sleep(3);
			return yoncu_getcurlpage($Islem,$params,$PostVeri,($Deneme+1));
		}
		return array(false,'Veri Çekilemedi. Status: '.$HttpStatus);
	}elseif(trim($Json) != ""){
		return json_decode($Json);
	}else{
		return array(false,'Veri Boş Geldi');
	}
	curl_close($ch);
}
function yoncu_config(){
	$configarray = array(
		"name" => "Aktuel Sms",
		"description" => "WHMCS Sms Addon. You can see details from: https://github.com/AktuelSistem/WHMCS-SmsModule",
		"version" => "1.1.8",
		"author" => "Aktüel Sistem ve Bilgi Teknolojileri",
		"language" => "turkish",
	);
	return $configarray;
}
function yoncu_ConfigOptions(){
	$os = array(
		'CentOS 5.0 32 Bit',
		'CentOS 5.0 64 Bit',
		'CentOS 6.0 32 Bit',
		'CentOS 6.0 64 Bit',
		'CentOS 7.0 64 Bit',
		'Windows Server 2008 R2 TR',
		'Windows Server 2008 R2 EN',
	);
	return array(
        'OS'	=> array('FriendlyName'=>'İşletim Sistemi','Type'=>'dropdown','Options'=>implode(',', $os),'Description'=>'Kurulacak Sistem'),
        'HDD'	=> array('FriendlyName'=>'Sabit Disk','Type'=>'text','Size'=>'10','Description'=>'GB'),
        'CPU'	=> array('FriendlyName'=>'İşlemci Hızı','Type'=>'text','Size'=>'10','Description'=>'Mhz (Her Core 1000 Mhz`dir)'),
        'RAM'	=> array('FriendlyName'=>'Ram Bellek','Type'=>'text','Size'=>'10','Description'=>'MB'),
    );
}
function yoncu_ClientArea($params){
	if(!filter_var($params['model']['original']['dedicatedip'], FILTER_VALIDATE_IP)){
		return "<b style=color:red>HATA: Sunucu IP Adresi Tanımsız veya Geçersiz!</b>";
	}
	$HTML = '<hr><b>'.$params['model']['original']['dedicatedip'].' IP Adresli Sunucu Yönetimi:</b><br/>
<a class="btn btn-warning" href="clientarea.php?action=productdetails&id='.$params['serviceid'].'&modop=custom&Islem=ac">Aç</a>
<a class="btn btn-warning" href="clientarea.php?action=productdetails&id='.$params['serviceid'].'&modop=custom&Islem=kapat">Kapat</a>
<a class="btn btn-warning" href="clientarea.php?action=productdetails&id='.$params['serviceid'].'&modop=custom&Islem=reset">Yeniden Başlat</a>
';
	if(isset($_REQUEST['Islem'])){
		if($_REQUEST['Islem'] == 'reset'){
			$HTML .= '<br/>'.yoncu_reset($params);
		}
	}
	return $HTML;
}
function yoncu_reset($params){
	return "Yeniden Başlatıldı";
}
if(isset($YoncuAdmin)){
?>
<h4>Yöncü Sunucu Mdülü Admin Paneli</h4><br />
<form action="" method="post">
	API ID: <input /><br />
	API Key: <input /><br />
	<button>Kaydet</button>
</form>
<?
}
