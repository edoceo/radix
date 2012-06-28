<?php
/**
    @file
    @brief Sends SMS via Carriers Email=>SMS Gateway
    $Id: SMS.php 1661 2011-03-06 19:23:41Z code@edoceo.com $
    
    @see http://www.notepage.net/smtp.htm
    @see http://www.tech-recipes.com/rx/939/sms_email_cingular_nextel_sprint_tmobile_verizon_virgin/
*/


class Radix_SMS
{
	// Uncomment the ones you want...
	public static $carrier_list = array(
        //'3 River Wireless' => 'sms.3rivers.net',
        //'ACS Wireless' => 'paging.acswireless.com',
        //'Advantage Communications' => 'advantagepaging.com',
        //'Airtouch Pagers' => 'myairmail.com',
        //'Airtouch Pagers' => 'alphapage.airtouch.com',
        //'Airtouch Pagers' => 'airtouch.net',
        //'Airtouch Pagers' => 'airtouchpaging.com',
        //'AlphNow' => 'alphanow.net',
        'Alltel' => 'message.alltel.com',
        //'Alltel PCS' => 'message.alltel.com',
        //'Ameritech Paging (see also American Messaging) 	10digitpagernumber@paging.acswireless.com
        //'Ameritech Paging (see also American Messaging) 	10digitpagernumber@pageapi.com
        //'American Messaging (SBC/Ameritech) 	10digitpagernumber@page.americanmessaging.net
        //'Ameritech Clearpath 	10digitpagernumber@clearpath.acswireless.com
        //'Andhra Pradesh Airtel 	phonenumber@airtelap.com
        //'Arch Pagers (PageNet) 	10digitpagernumber@archwireless.net
        //'Arch Pagers (PageNet) 	10digitpagernumber@epage.arch.com
        //'Arch Pagers (PageNet) 	10digitpagernumber@archwireless.net
        'AT&T' => 'txt.att.net',
        'AT&T Free2Go' => 'mmode.com',
        'AT&T PCS' => 'mobile.att.net',
        'AT&T Pocketnet PCS' => 'dpcs.mobile.att.net',
        //'Beepwear' => 'beepwear.net',
        //'BeeLine GSM 	phonenumber@sms.beemail.ru
        //'Bell Atlantic 	phonenumber@message.bam.com
        'Bell Canada' => 'txt.bellmobility.ca',
        //'Bell Canada' => 'bellmobility.ca',
        'Bell Mobility (Canada)' => 'txt.bell.ca',
        //'Bell Mobility 	number@txt.bellmobility.ca
        'Bell South (Blackberry)' => 'bellsouthtips.com',
        'Bell South' => 'sms.bellsouth.com',
        //'Bell South' => 'wireless.bellsouth.com',
        //'Bell South 	phonenumber@blsdcs.net
        //'Bell South 	phonenumber@bellsouth.cl
        //'Bell South Mobility 	phonenumber@blsdcs.net
        //'Blue Sky Frog 	phonenumber@blueskyfrog.com
        //'Bluegrass Cellular 	phonenumber@sms.bluecell.com
        'Boost' => 'myboostmobile.com',
        //'BPL mobile 	phonenumber@bplmobile.com
        //'Carolina West Wireless 	10digitnumber@cwwsms.com
        //'Carolina Mobile Communications 	10digitpagernumber@cmcpaging.com
        //'Cellular One East Coast 	phonenumber@phone.cellone.net
        //'Cellular One South West 	phonenumber@swmsg.com
        //'Cellular One PCS 	phonenumber@paging.cellone-sf.com
        'Cellular One' => 'mobile.celloneusa.com',
        //'Cellular One 	phonenumber@cellularone.txtmsg.com
        //'Cellular One 	phonenumber@cellularone.textmsg.com
        //'Cellular One 	phonenumber@cell1.textmsg.com
        //'Cellular One 	phonenumber@message.cellone-sf.com
        //'Cellular One 	phonenumber@sbcemail.com
        //'Cellular One West 	phonenumber@mycellone.com
        //'Cellular South 	phonenumber@csouth1.com
        //'Centennial Wireless 	10digitnumber@cwemail.com
        //'Central Vermont Communications 	10digitpagernumber@cvcpaging.com
        //'CenturyTel 	phonenumber@messaging.centurytel.net
        //'Chennai RPG Cellular 	phonenumber@rpgmail.net
        //'Chennai Skycell / Airtel 	phonenumber@airtelchennai.com
        //'Cincinnati Bell Wireless 	phonenumber@gocbw.com
        //'Cingular' => 'mycingular.com',
        //'Cingular 	mobilenumber@mycingular.net
        //'Cingular 	mobilenumber@mms.cingularme.com
        //'Cingular 	mobilenumber@page.cingular.com
        //'Cingular 	10digitphonenumber@cingularme.com
        //'Cingular Wireless 	10digitphonenumber@mycingular.textmsg.com
        //'Cingular Wireless 	10digitphonenumber@mobile.mycingular.com
        //'Cingular Wireless 	10digitphonenumber@mobile.mycingular.net
        //'Clearnet 	phonenumber@msg.clearnet.com
        'Comcast' => 'comcastpcs.textmsg.com',
        //'Communication Specialists 	7digitpin@pageme.comspeco.net
        //'Communication Specialist Companies 	pin@pager.comspeco.com
        //'Comviq 	number@sms.comviq.se
        //'Cook Paging 	10digitpagernumber@cookmail.com
        //'Corr Wireless Communications 	phonenumber@corrwireless.net
        //'Delhi Aritel 	phonenumber@airtelmail.com
        //'Delhi Hutch 	phonenumber@delhi.hutch.co.in
        //'Digi-Page / Page Kansas 	10digitpagernumber@page.hit.net
        //'Dobson Cellular Systems 	phonenumber@mobile.dobson.net
        //'Dobson-Alex Wireless / Dobson-Cellular One 	phonenumber@mobile.cellularone.com
        //'DT T-Mobile 	phonenumber@t-mobile-sms.de
        //'Dutchtone / Orange-NL 	phonenumber@sms.orange.nl
        'Edge Wireless' => 'sms.edgewireless.com',
        //'EMT 	phonenumber@sms.emt.ee
        //'Escotel 	phonenumber@escotelmobile.com
        'Fido' => 'fido.ca',
        //'Galaxy Corporation 	10digitpagernumber.epage@sendabeep.net
        //'GCS Paging 	pagernumber@webpager.us
        //'Goa BPLMobil 	phonenumber@bplmobile.com
        //'Golden Telecom 	phonenumber@sms.goldentele.com
        //'GrayLink / Porta-Phone 	10digitpagernumber@epage.porta-phone.com
        //'GTE 	number@airmessage.net
        //'GTE 	number@gte.pagegate.net
        //'GTE 	10digitphonenumber@messagealert.com
        //'Gujarat Celforce 	phonenumber@celforce.com
        //'Houston Cellular 	number@text.houstoncellular.net
        //'Idea Cellular 	phonenumber@ideacellular.net
        //'Infopage Systems 	pinnumber@page.infopagesystems.com
        //'Inland Cellular Telephone 	phonenumber@inlandlink.com
        //'The Indiana Paging Co 	last4digits@pager.tdspager.com
        //'JSM Tele-Page 	pinnumber@jsmtel.com
        //'Kerala Escotel 	phonenumber@escotelmobile.com
        //'Kolkata Airtel 	phonenumber@airtelkol.com
        //'Kyivstar 	number@smsmail.lmt.lv
        //'Lauttamus Communication 	pagernumber@e-page.net
        //'LMT 	phonenumber@smsmail.lmt.lv
        //'Maharashtra BPL Mobile 	phonenumber@bplmobile.com
        //'Maharashtra Idea Cellular 	phonenumber@ideacellular.net
        //'Manitoba Telecom Systems 	phonenumber@text.mtsmobility.com
        //'MCI Phone 	phonenumber@mci.com
        //'MCI 	phonenumber@pagemci.com
        //'Meteor 	phonenumber@mymeteor.ie
        //'Meteor 	phonenumber@sms.mymeteor.ie
        //'Metrocall 	10digitpagernumber@page.metrocall.com
        //'Metrocall 2-way 	10digitpagernumber@my2way.com
        //'Metro PCS 	10digitphonenumber@mymetropcs.com
        //'Metro PCS 	10digitphonenumber@metropcs.sms.us
        //'Microcell 	phonenumber@fido.ca
        //'Midwest Wireless 	phonenumber@clearlydigital.com
        //'MiWorld 	phonenumber@m1.com.sg
        //'Mobilecom PA 	10digitpagernumber@page.mobilcom.net
        //'Mobilecomm 	number@mobilecomm.net
        //'Mobileone 	phonenumber@m1.com.sg
        //'Mobilfone 	phonenumber@page.mobilfone.com
        'Mobility Bermuda' => 'ml.bm',
        //'Mobistar Belgium 	phonenumber@mobistar.be
        //'Mobitel Tanzania 	phonenumber@sms.co.tz
        //'Mobtel Srbija 	phonenumber@mobtel.co.yu
        //'Morris Wireless 	10digitpagernumber@beepone.net
        //'Motient 	number@isp.com
        //'Movistar 	number@correo.movistar.net
        //'Mumbai BPL Mobile 	phonenumber@bplmobile.com
        'Mumbai Orange' => 'orangemail.co.in',
        //'NBTel 	number@wirefree.informe.ca
        //'Netcom 	phonenumber@sms.netcom.no
        //'Nextel 	10digitphonenumber@messaging.nextel.com
        //'Nextel 	10digitphonenumber@page.nextel.com
        //'Nextel 	10digitphonenumber@nextel.com.br
        //'NPI Wireless 	phonenumber@npiwireless.com
        //'Ntelos 	number@pcs.ntelos.com
        //'O2' => 'o2.co.uk',
        'O2' => 'o2imail.co.uk',
        //'O2 (M-mail) 	number@mmail.co.uk
        //'Omnipoint 	number@omnipoint.com
        //'Omnipoint 	10digitphonenumber@omnipointpcs.com
        //'One Connect Austria 	phonenumber@onemail.at
        //'OnlineBeep 	10digitphonenumber@onlinebeep.net
        //'Optus Mobile 	phonenumber@optusmobile.com.au
        'Orange' => 'orange.net',
        //'Orange Mumbai 	phonenumber@orangemail.co.in
        //'Orange - NL / Dutchtone 	phonenumber@sms.orange.nl
        //'Oskar 	phonenumber@mujoskar.cz
        //'P&T Luxembourg 	phonenumber@sms.luxgsm.lu
        'Pacific Bell' => 'pacbellpcs.net',
        //'PageMart 	7digitpinnumber@pagemart.net
        //'PageMart Advanced /2way 	10digitpagernumber@airmessage.net
        //'PageMart Canada 	10digitpagernumber@pmcl.net
        //'PageNet Canada 	phonenumber@pagegate.pagenet.ca
        //'PageOne NorthWest 	10digitnumber@page1nw.com
        //'PCS One 	phonenumber@pcsone.net
        //'Personal Communication 	sms@pcom.ru (number in subject line)
        //'Pioneer / Enid Cellular 	phonenumber@msg.pioneerenidcellular.com
        //'PlusGSM 	phonenumber@text.plusgsm.pl
        //'Pondicherry BPL Mobile 	phonenumber@bplmobile.com
        //'Powertel 	phonenumber@voicestream.net
        //'Price Communications 	phonenumber@mobilecell1se.com
        //'Primeco 	10digitnumber@email.uscc.net
        //'Primtel 	phonenumber@sms.primtel.ru
        //'ProPage 	7digitpagernumber@page.propage.net
        //'Public Service Cellular 	phonenumber@sms.pscel.com
        //'Qualcomm 	name@pager.qualcomm.com
        'Qwest' => 'qwestmp.com',
        //'RAM Page 	number@ram-page.com
        'Rogers AT&T Wireless' => 'pcs.rogers.com',
        'Rogers Canada' => 'pcs.rogers.com',
        //'Safaricom 	phonenumber@safaricomsms.com
        //'Satelindo GSM 	phonenumber@satelindogsm.com
        //'Satellink 	10digitpagernumber.pageme@satellink.net
        //'SBC Ameritech Paging (see also American Messaging) 	10digitpagernumber@paging.acswireless.com
        //'SCS-900 	phonenumber@scs-900.ru
        //'SFR France 	phonenumber@sfr.fr
        //'Skytel Pagers 	7digitpinnumber@skytel.com
        //'Skytel Pagers 	number@email.skytel.com
        //'Simple Freedom 	phonenumber@text.simplefreedom.net
        //'Smart Telecom 	phonenumber@mysmart.mymobile.ph
        //'Southern LINC 	10digitphonenumber@page.southernlinc.com
        //'Southwestern Bell 	number@email.swbw.com
        'Sprint' => 'sprintpaging.com',
        'Sprint PCS' => 'messaging.sprintpcs.com',
        //'ST Paging 	pin@page.stpaging.com
        //'SunCom 	number@tms.suncom.com
        //'SunCom 	number@suncom1.com
        //'Sunrise Mobile 	phonenumber@mysunrise.ch
        //'Sunrise Mobile 	phonenumber@freesurf.ch
        //'Surewest Communicaitons 	phonenumber@mobile.surewest.com
        //'Swisscom 	phonenumber@bluewin.ch
        'T-Mobile' => 'tmomail.net',
        //'T-Mobile 	10digitphonenumber@voicestream.net
        'T-Mobile Austria' => 'sms.t-mobile.at',
        //'T-Mobile Germany' => 't-d1-sms.de',
        'T-Mobile Germany' => 't-mobile-sms.de',
        'T-Mobile UK' => 't-mobile.uk.net',
        //'Tamil Nadu BPL Mobile 	phonenumber@bplmobile.com
        //'Tele2 Latvia 	phonenumber@sms.tele2.lv
        //'Telefonica Movistar 	phonenumber@movistar.net
        //'Telenor 	phonenumber@mobilpost.no
        //'Teletouch 	10digitpagernumber@pageme.teletouch.com
        //'Telia Denmark 	phonenumber@gsm1800.telia.dk
        //'Telus 	phonenumber@msg.telus.com
        //'TIM 	10digitphonenumber@timnet.com
        //'Triton 	phonenumber@tms.suncom.com
        //'TSR Wireless 	pagernumber@alphame.com
        //'TSR Wireless 	pagernumber@beep.com
        //'UMC 	phonenumber@sms.umc.com.ua
        //'Unicel 	phonenumber@utext.com
        //'Uraltel 	phonenumber@sms.uraltel.ru
        //'US Cellular 	10digitphonenumber@email.uscc.net
        //'US Cellular 	10digitphonenumber@uscc.textmsg.com
        //'US West 	number@uswestdatamail.com
        //'Uttar Pradesh Escotel 	phonenumber@escotelmobile.com
        //'Verizon Pagers 	10digitpagernumber@myairmail.com
        'Verizon PCS' => 'vtext.com',
        //'Verizon PCS 	10digitphonenumber@myvzw.com
        //'Vessotel 	phonenumber@pager.irkutsk.ru
        'Virgin Mobile' => 'vmobl.com',
        //'Virgin Mobile 	phonenumber@vxtras.com
        'Vodafone Italy' => 'sms.vodafone.it',
        'Vodafone Japan' => 'c.vodafone.ne.jp',
        //'Vodafone Japan 	phonenumber@h.vodafone.ne.jp
        //'Vodafone Japan 	phonenumber@t.vodafone.ne.jp
        'Vodafone UK' => 'vodafone.net',
        'VoiceStream / T-Mobile' => 'voicestream.net',
        //'WebLink Wiereless 	pagernumber@airmessage.net
        //'WebLink Wiereless 	pagernumber@pagemart.net
        //'West Central Wireless 	phonenumber@sms.wcc.net
        //'Western Wireless 	phonenumber@cellularonewest.com
        //'Wyndtell 	number@wyndtell.com
    );
	/**
	    Send It...
	*/
	function send($from,$cell,$host,$text)
	{
		$hdrs = array(
			//'Errors-To'=>'David Busby <busby@edoceo.com>',
			//'Reply-To'=>'David Busby <busby@edoceo.com>',
			'From'=>$from,
			//'Subject'=>$subj
		);
		$res = mail("$cell@$host",null,$text);
		return $res;
	}
}
