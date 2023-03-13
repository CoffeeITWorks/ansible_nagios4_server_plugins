#!/usr/bin/php -q
<?php
// check_doomsday.php
// Copyright (c) 2011 Nagios Enterprises, LLC. 
// Written by Ethan Galstad as an example of just how flexible Nagios is!
//  License: GPL v2


doit();

	
function doit(){
	global $argv;

	$args=parse_argv($argv);
	//print_r($argv);
	//print_r($args);
	
	$return_code=0;
	$result_string="";
	
	$long_output=array();
	
	$ok_string="Things are okay for now. %duration% left until %doomsdayname%";
	$passed_string="%doomsdayname% passed!";
	$warning_string="WARNING: Only %duration% left until %doomsdayname%";
	$critical_string="CRITICAL: Only %duration% left until %doomsdayname% - get ready!";
	$unknown_pre_string="%doomsdayname% is upon us...";
	$unknown_post_string="%doomsdayname% is happening now...";
	
	$duration="";
	
	$doomsdayname=grab_array_var($args,"name","Dec 21, 2012");
	$doomsdaydate=grab_array_var($args,"date","2012-12-21");
	$warning=grab_array_var($args,"warning",365); // days
	$critical=grab_array_var($args,"critical",60);  // days
	$unknown=grab_array_var($args,"unknown",24); // hours on each side of doomsday time
	
	$help=grab_array_var($args,"help",false); 
	
	if($help!=false){
		echo "\n";
		echo "Doomsday Plugin\n";
		echo "Copyright (c) 2011 Nagios Enterprises, LLC.\n";
		echo "\n";
		echo "Written by Ethan Galstad as an example of just how flexible Nagios is!\n";
		echo "License: GPL v2\n";
		echo "\n";
		echo "Usage ".$argv[0]." --date=<date> --name=<name> [--warning=<wdays>] [--critical=<cdays>] [--unknown=<uhours>]\n";
		echo "\n";
		echo " date    = Doomsday date (e.g. '2011-12-21')\n";
		echo " name    = Doomsday name (e.g. 'December 21, 2011')\n";
		echo " wdays   = Days prior to doomsday date to generate a warning alert\n";
		echo " cdays   = Days prior to doomsday date to generate a critical alert\n";
		echo " uhours  = HOURS prior to and after the doomsday day to be in an unknown state of alert\n";
		exit(1);
		}
		
	$now=time();
	
	$doomsday_ts=strtotime($doomsdaydate);
	//echo "DOOMSDAY: $doomsday_ts\n";
	//echo "WARNING: $warning\n";
	//echo "CRITICAL: $critical\n";
	
	// doomsday has passed
	if($doomsday_ts<$now){
	
		$diff=$now-$doomsday_ts;
		$duration=get_duration_string($diff);
		
		// still in an unknown window of danger
		if($diff<($unknown*60*60)){
			$return_code=3;
			$result_string=$unknown_post_string;
			}
		// otherwise we're in the clear
		else{
			$return_code=0;
			$result_string=$passed_string;
			}
		}
		
	// doomsday is coming
	else{
		$diff=$doomsday_ts-$now;
		$duration=get_duration_string($diff);
		
		// period of unknown danger
		if($diff<($unknown*60*60)){
			$return_code=3;
			$result_string=$unknown_pre_string;		
			}
		else if($diff<($critical*60*60*24)){
			$return_code=2;
			$result_string=$critical_string;		
			}
		else if($diff<($warning*60*60*24)){
			$return_code=1;
			$result_string=$warning_string;		
			}
		else{
			$return_code=0;
			$result_string=$ok_string;
			}
		}
		
	//echo "DIFF: $diff\n";

		
	// replace vars in string
	$vars=array(
		"doomsdayname" => $doomsdayname,
		"doomsdaydate" => $doomsdaydate,
		"duration" => $duration,
		);
	foreach($vars as $var => $val){
		$tvar="%".$var."%";
		$result_string=str_replace($tvar,$val,$result_string);
		}	
	//print_r($vars);
	echo $result_string."\n";
		
	// print service results
	foreach($long_output as $lo){
		echo $lo;
		}
		
	exit($return_code);	
	}
	
function parse_argv($argv){
    array_shift($argv);
    $out=array();
    foreach($argv as $arg){
	
        if(substr($arg,0,2)=='--'){
			$eq=strpos($arg,'=');
            if($eq===false){
                $key=substr($arg,2);
                $out[$key]=isset($out[$key])?$out[$key]:true;
				} 
			else{
                $key=substr($arg,2,$eq-2);
                $out[$key]=substr($arg,$eq+1);
				}
			} 
			
		else if(substr($arg,0,1)=='-'){
            if(substr($arg,2,1)=='='){
                $key=substr($arg,1,1);
                $out[$key]=substr($arg,3);
				}
			else{
                $chars=str_split(substr($arg,1));
                foreach($chars as $char){
                    $key=$char;
                    $out[$key]=isset($out[$key])?$out[$key]:true;
					}
				}
			} 
		else{
            $out[] = $arg;
			}
		}
		
    return $out;
	}
	
function grab_array_var($arr,$varname,$default=""){
	global $request;
	
	$v=$default;
	if(is_array($arr)){
		if(array_key_exists($varname,$arr))
			$v=$arr[$varname];
		}
	return $v;
	}
	
function get_duration_string($s,$nullval=null,$zeroval=null){

	$str="";
	$instr=false;
	
	if(is_null($s) || $s==""){
		if($nullval==null)
			$str="0s";
		else
			$str=$nullval;
		}
	else{
		$si=intval($s);
		if($si==0){
			if($zeroval==null)
				$str="0s";
			else
				$str=$zeroval;
			}
		else{
			$days=intval($si/86400);
			$si-=$days*86400;
			$hours=intval($si/3600);
			$si-=$hours*3600;
			$minutes=intval($si/60);
			$si-=$minutes*60;
			$seconds=$si;
		
			if($days>0){
				$str.=$days."d";
				$instr=true;
				}
			if($hours>0 || $instr==true){
				if($instr==true)
					$str.=" ";
				$str.=$hours."h";
				$instr=true;
				}
			if($minutes>0 || $instr==true){
				if($instr==true)
					$str.=" ";
				$str.=$minutes."m";
				$instr=true;
				}

			if($instr==true)
				$str.=" ";
			$str.=$seconds."s";
			}
		}

	return $str;
	}

?>
