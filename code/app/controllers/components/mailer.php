<?php

App::import('Component', 'SwiftMailer');
class MailerComponent extends SwiftMailerComponent
{
    var $smtpType = 'tls'; 
	var $smtpHost = 'smtp.gmail.com'; 
	var $smtpPort = 465;
	var $smtpUsername = 'fralik@lipovo.spb.ru'; 
	var $smtpPassword = '@scroll14%'; 

	// var $smtpType = 'ssl';
	// var $smtpHost = 'bolton.eukhost.com';
	// var $smtpPort = 465;
	// var $smtpUsername = 'mail+govorimvmeste.com';
	// var $smtpPassword = '!scroll14%';

	var $sendAs = 'text'; 
	var $from = 'mail@govorimvmeste.com'; 
	var $fromName = 'GovorimVmeste'; 
	var $template = 'default';

	// var $from = 'Vadim Frolov <fralik@localhost.com>';
	// var $replyTo = 'noreply@localhost.com';
	// var $sendAs = 'text';
	// var $delivery = 'smtp';
	// var $xMailer = 'Postman';
	// var $smtpOptions = array(
		// 'port'=> 25,
		// 'host' => 'smtp.gmail.com',
		// 'timeout' => 30,
		// 'username' => 'fralik@lipovo.spb.ru',
		// 'password' => '@scroll14%'
	// );
}
?>