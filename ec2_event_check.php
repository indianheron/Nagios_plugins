#!/usr/bin/php
<?php
/*-------------------------------------------------------------------------------------------------------------
Description    	  	: This code will check for any AWS event sets against any instances on provided account.
			  To hide the details of the account we have only accepts the keys. So user must be sure 
			  while setting the plugin for any account.
USAGE			: ec2_event_check.php <access_key> <secret_key> [Only runs on command line]
-------------------------------------------------------------------------------------------------------------*/
require_once '/usr/local/nagios/libexec/aws-php-sdk-1.5.6.2/sdk.class.php' ;

$eventInst = new EventInstfinder();
$nagState = new NagiosState();

if(count($argv) == 1)
{
	echo "USAGE: $argv[0] <access_key> <secret_key>\n";
	exit($nagState->get_State_Unknown());
}
else
{
	$eventInst->cmdArg = $argv;
	extract($eventInst->init());
	if($output != "")
	{
		echo $output;
	    exit($sta);
	}
	$eventInst->getInstances();
	extract($eventInst->printArray());
	echo $output;
	exit($status);
}

class EventInstfinder
{
	private $instanceResponse;
	public  $cmdArg = array();
	private $instanceId = array();
	private $eventArray = array();
		
	public function printResponse()
	{
		print_r($this->cmdArg);
		print_r($this->instanceResponse);
	}
	public function getInstances()
	{
		$i=0;
		foreach($this->instance_response->body->instanceStatusSet->item as $inStance) {
			if($inStance->eventsSet != "")
			{	
				$this->instanceId[$i] 		= $inStance->instanceId;
				$this->eventArray[$i]['code']  	= $inStance->eventsSet->item->code;
				$this->eventArray[$i]['desc']  	= $inStance->eventsSet->item->description;
				$this->eventArray[$i]['doret'] 	= $inStance->eventsSet->item->notBefore;
				$i++;
			}
		}
	}
	public function printArray()
	{
		$output="";
		$status="";
		$warnState = NagiosState::get_State_Warning();
		$okState = NagiosState::get_State_Ok();
		
		if((count($this->instanceId) == count($this->eventArray)) && count($this->instanceId) != 0)
		{
			for($i=0; $i<count($this->instanceId); $i++)
			{
				$output=$output.$this->instanceId[$i]." marked for ".$this->eventArray[$i]['code']." on ".$this->eventArray[$i]['doret']." because \"".$this->eventArray[$i]['desc']."\"\n";
			}
			$status=$warnState;
		}
		else
		{
			$output=$output."No events for this account\n";
			$status=$okState;
		}
		return compact('output', 'status');
	}
	public function init()
	{
          	$output="";
                $sta="";
                $warnState = NagiosState::get_State_Warning();
                $okState = NagiosState::get_State_Ok();
		
		$ec2 = new AmazonEC2(array('key' =>$this->cmdArg[1], 'secret' =>$this->cmdArg[2]));
		$this->instance_response = $ec2->describe_instance_status();
		if(!$this->instance_response->isOK())
		{
			$output=$output."Problem in provided keys !!!\n";
			$sta=$warnState;
		}
		return compact('output', 'sta');
	}
}

class NagiosState
{
	# Nagios Constant 
	private static $state_Ok=0;
	private static $state_Warning=1;
	private static $state_Critical=2;
	private static $state_Unknown=3;

	public function get_State_Ok()
	{
		return self::$state_Ok;
	}

	public function get_State_Warning()
	{
		return self::$state_Warning;
	}
	
	public function get_State_Critical()
	{
		return self::$state_Critical;
	}
	
	public function get_State_Unknown()
	{
		return self::$state_Unknown;
	}
}
?>

