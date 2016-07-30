#!/usr/bin/php
<?php
/*-------------------------------------------------------------------------------------------------------------
Description 	: The script will check the cloudwatch value of the provided Instance URL 
Usage 		: php ec2_cloudwatch_net.php -a <access_key> -s <secrete_key> -i <instance_public_url> -w <warning_threshold> -c <critical_threshold>
-------------------------------------------------------------------------------------------------------------*/
require_once '/usr/local/nagios/include/sdk-1.5.12/sdk.class.php' ;
define('state_Ok','0');
define('state_Warning','1');
define('state_Critical','2');
define('state_Unknown','3');
date_default_timezone_set('UTC');
#https://forums.aws.amazon.com/thread.jspa?messageID=381720&#381720
if(count($argv) < 4)
{
	echo "USAGE: $argv[0] <access_key> <secret_key> <ec2_Public_URL>\n";
	exit(3);
}
else
{
	$Obj = new cloudWatch_plugin();
	extract($Obj->check_argv());
        if($output != "")
        {
           echo $output;
           exit($sta);
        }
	$Obj->Init();
	extract($Obj->Get_Instance_Id());
	if($output != "")
	{
	    echo $output;
	    exit($sta);
	}
	$Obj->Get_Stats_CPUUtilization();
	$Obj->Get_Stats_NetworkIn();
	$Obj->Get_Stats_NetworkOut();
	extract($Obj->Print_vals());
	echo $output;
	exit($status);
}

class cloudWatch_plugin
{
	public  $cmdArg = array();
	private $interval = array();
	private $cW;
	private $rsp;
	private $instance_Response;
	private $instance_Id;
	private $ass_array = array();
	
	 public function check_argv()
        {
                $output="";
                $sta="";
                $this->options = getopt("a:s:i:w:c:");
                if(isset($this->options['w']) && isset($this->options['c']) )
                {
                        if($this->options['w'] > $this->options['c'])
                        {
                                $output = $output."Warning level should always less than critical\n";
                                $sta = 3;
                        }
                }
                else
                {
                        $output = $output."Only one parameter is not enough. Set both the parameters -w and -c\n";
                        $sta = 1;
                }
                if(!isset($this->options['w']) && !isset($this->options['c'])) 
                {
                        $output = "";
                        $sta = "";
                }
                 return compact('output', 'sta');
        }

	public function Init()
	{
		$this->interval[] = date("Y-m-d H:i:s", strtotime('-10 minutes'));
		$this->interval[] = date("Y-m-d H:i:s");
		$this->cW = new AmazonCloudWatch(array('key' =>$this->options['a'], 'secret' =>$this->options['s']));
	}
	
	public function Get_Instance_Id()
	{
		$output="";
		$sta="";
		$ec2 = new AmazonEC2(array('key' =>$this->options['a'], 'secret' =>$this->options['s']));
		$this->instance_Response = $ec2->describe_instances(array(
		'Filter' => array( array('Name' => 'dns-name', 'Value' => $this->options['i']), )
		));
		if(!$this->instance_Response->isOK())
		{
			$output=$output."ACCESS DENIED : Check the credentials\n";
			$sta=1;
		}
		$tmp[] = $this->instance_Response->body->instanceId();
		$this->instance_Id = (string)$tmp[0][0][0];
		return compact('output', 'sta');
	}
	
	public function Get_Stats_CPUUtilization()
	{
		$this->rsp = $this->cW->get_metric_statistics('AWS/EC2', 'CPUUtilization', $this->interval[0], $this->interval[1], 300, array('Minimum','Maximum','Average','Sum', 'SampleCount'), 'Percent', array('Dimensions' => array(array("Name" => "InstanceId", "Value" => "$this->instance_Id"))));

		$tmp[] = $this->rsp->body->SampleCount();
		$tmp[] = $this->rsp->body->Unit();
		$tmp[] = $this->rsp->body->Minimum();
		$tmp[] = $this->rsp->body->Maximum();
		$tmp[] = $this->rsp->body->Sum();
		$tmp[] = $this->rsp->body->Average();

		$this->ass_array['CPUUtilization']['SampleCount'] = $tmp[0][0];
		$this->ass_array['CPUUtilization']['Unit']        = $tmp[1][0];
		$this->ass_array['CPUUtilization']['Minimum'] 	  = $tmp[2][0];
		$this->ass_array['CPUUtilization']['Maximum']	  = $tmp[3][0];
		$this->ass_array['CPUUtilization']['Sum'] 	  = $tmp[4][0];
		$this->ass_array['CPUUtilization']['Average'] 	  = $tmp[5][0];
	}
	
	public function Get_Stats_NetworkIn()
	{
		$this->rsp = $this->cW->get_metric_statistics('AWS/EC2', 'NetworkIn', $this->interval[0], $this->interval[1], 300, array('Minimum','Maximum','Average','Sum', 'SampleCount'), 'Bytes', array('Dimensions' => array(array("Name" => "InstanceId", "Value" => "$this->instance_Id"))));

		$tmp[] = $this->rsp->body->SampleCount();
		$tmp[] = $this->rsp->body->Unit();
		$tmp[] = $this->rsp->body->Minimum();
		$tmp[] = $this->rsp->body->Maximum();
		$tmp[] = $this->rsp->body->Sum();
		$tmp[] = $this->rsp->body->Average();

		$this->ass_array['NetworkIn']['SampleCount'] = $tmp[0][0];
		$this->ass_array['NetworkIn']['Unit']        = $tmp[1][0];
		$this->ass_array['NetworkIn']['Minimum']     = $tmp[2][0];
		$this->ass_array['NetworkIn']['Maximum']     = $tmp[3][0];
		$this->ass_array['NetworkIn']['Sum'] 	     = $tmp[4][0];
		$this->ass_array['NetworkIn']['Average']     = $tmp[5][0];
	}
	
	public function Get_Stats_NetworkOut()
	{
		$this->rsp = $this->cW->get_metric_statistics('AWS/EC2', 'NetworkOut', $this->interval[0], $this->interval[1], 300, array('Minimum','Maximum','Average','Sum', 'SampleCount'), 'Bytes', array('Dimensions' => array(array("Name" => "InstanceId", "Value" => "$this->instance_Id"))));

		$tmp[] = $this->rsp->body->SampleCount();
		$tmp[] = $this->rsp->body->Unit();
		$tmp[] = $this->rsp->body->Minimum();
		$tmp[] = $this->rsp->body->Maximum();
		$tmp[] = $this->rsp->body->Sum();
		$tmp[] = $this->rsp->body->Average();

		$this->ass_array['NetworkOut']['SampleCount'] = $tmp[0][0];
		$this->ass_array['NetworkOut']['Unit'] 	      = $tmp[1][0];
		$this->ass_array['NetworkOut']['Minimum']     = $tmp[2][0];
		$this->ass_array['NetworkOut']['Maximum']     = $tmp[3][0];
		$this->ass_array['NetworkOut']['Sum'] 	      = $tmp[4][0];
		$this->ass_array['NetworkOut']['Average']     = $tmp[5][0];
	}
		
	public function Print_vals()
	{
		$output="";
		$status = 0;
                $lable = "STATS OK :";
		if($this->ass_array['NetworkIn']['Average'] > $this->options['w'] || $this->ass_array['NetworkOut']['Average'] > $this->options['w'])
                {
                        $status = 1;
                        $lable = "STATS Warning :";
                }
                if($this->ass_array['NetworkIn']['Average'] > $this->options['c'] || $this->ass_array['NetworkOut']['Average'] > $this->options['c'])
                {
                        $status = 2;
                        $lable = "STATS Critical :";
                }

     	$output = "$lable NetworkIn - ".$this->ass_array['NetworkIn']['Average']."B NetworkOut - ".$this->ass_array['NetworkOut']['Average']."B | NetworkIn=".$this->ass_array['NetworkIn']['Average']."B NetworkOut=".$this->ass_array['NetworkOut']['Average']."B\n";
		return compact('output', 'status');
	}
}
?>


