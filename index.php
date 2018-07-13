<?php
include_once('config.php');
// Debug check
if($debug=='true'){
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}

// Anti flood shit
session_start();
// anti flood protection
if($_SESSION['last_session_request'] > TIME() - 5){
    die('<img src="src/images/nospam.jpg"></img>');
}

$_SESSION['last_session_request'] = TIME();
// End of anti flood shit

// Online check

$ts3_VirtualServer = TeamSpeak3::factory("serverquery://".$login_name.":".$login_password."@".$ip.":".$query_port."/?server_port=".$virtualserver_port."&nickname=R4P3.".rand(1000,10000)."&blocking=0");
$ips = getIp();
//$ips = '127.0.0.1';
unset($_SESSION['login']);
foreach ($ts3_VirtualServer->clientList() as $client) {
	if($client["client_type"]) continue;
	if($client['connection_client_ip']==$ips){
		$_SESSION['login'] = ('loged');
	}
	
}
// Check for channel creation (main)
if(isset($_POST['submit']))
{
	$name = trim($_POST['name']);
	$check = $_POST['check'];
	if($name===''){
		$name = ("[Random room]".rand(100,1000));
	}

	$cid = $ts3_VirtualServer->channelCreate(array(
        "channel_name" => "[cspacer".rand(1,10000)."]".$name."",
        "channel_topic" => "",
        "channel_codec" => TeamSpeak3::CODEC_SPEEX_ULTRAWIDEBAND,
        "channel_codec_quality" => 0x08,
        "channel_flag_permanent" => true,
        ));


    $Onecid = $ts3_VirtualServer->channelCreate(array(
        "channel_name" => "Channel 1",
        "channel_topic" => "Channel 1",
        "channel_codec" => TeamSpeak3::CODEC_SPEEX_ULTRAWIDEBAND,
        "channel_codec_quality" => 0x08,
        "channel_flag_permanent" => true,
        "cpid" => $cid,                                
        ));

    $Twocid = $ts3_VirtualServer->channelCreate(array(
        "channel_name" => "Channel 2",
        "channel_topic" => "Channel 2",
        "channel_codec" => TeamSpeak3::CODEC_SPEEX_ULTRAWIDEBAND,
        "channel_codec_quality" => 0x08,
        "channel_flag_permanent" => true,
        "cpid" => $cid,
        ));

    if($check=='on')
    {
    	foreach($ts3_VirtualServer->clientList() as $client)
		{
	        if($client["client_type"]) continue;
	        $clientInfo = $client->getInfo();
	        if($clientInfo['connection_client_ip'] == $ips)
	        {
	        	$ts3_VirtualServer->clientGetById($clientInfo['clid'])->setChannelGroup($cid, $agroup);
	        	$ts3_VirtualServer->clientMove($clientInfo['clid'], $cid);
	        	$ts3_VirtualServer->clientGetById($clientInfo['clid'])->message("[b][color=green]Channel Created![/color][/b]");
	        }  
		}
    }else{

    	foreach($ts3_VirtualServer->clientList() as $client)
		{
	        if($client["client_type"]) continue;
	        $clientInfo = $client->getInfo();
	        if($clientInfo['connection_client_ip'] == $ips)
	        {
	        	$ts3_VirtualServer->clientGetById($clientInfo['clid'])->setChannelGroup($cid, $agroup);
	        	$ts3_VirtualServer->clientGetById($clientInfo['clid'])->message("[b][color=green]Channel Created![/color][/b]");
	        }  
		}
    }
    // Inserting channel id into db
    $stmt = $db->prepare("INSERT INTO sobe(channel_id,ip) VALUES (?,?)");
    $stmt->bind_param("ss", $cid, $ips);     
    $stmt->execute(); 
    $stmt->close();
    header('Refresh: 5; URL=index.php');
}

// Check if someone requsted channel delete
if(isset($_GET['delete'])) {
	// Trim variable
	$id = trim($_GET['delete']);
	// Check if is empty spaced
	if($id===''){
		// Redirect to main page with wait time of 5 seconds to not get caught in spam protection
		header('Refresh: 5; URL=index.php');
	}
	// Now checking if user is owner of that channel/ subchannel

	// standard db channel name check
	include_once('config.php');
	$stmt = $db->prepare("SELECT channel_id FROM `sobe` WHERE ip= ? ");
    $stmt->bind_param("s", $ips);     
    $stmt->execute();                           
    $stmt->bind_result($channel_id);                    
    $stmt->fetch();
    $stmt->close();


    //All TS3 channel get
	$all = $ts3_VirtualServer->channelList();
	//Parse trough channels
	foreach ($all as $value) {
		// Check for main channel
		if($id==$channel_id){
			if($value['cid']==$channel_id){
				// Channel delete
				$ts3_VirtualServer->channelDelete($value['cid'],true);
				// Delete our channel record from database
				$stmt = $db->prepare("DELETE FROM sobe WHERE ip=?"); 
				$stmt->bind_param("s", $ips);   
			    $stmt->execute();                          
				break;
			}	
		}
		// Check if delete id even exists on server
		if($value['cid']==$id){
			// Check if users owns this channel
			if($value['pid']==$channel_id){
				// Delete and break
				$ts3_VirtualServer->channelDelete($value['cid'],true);
				break;
			}
		}
	}

}

// Check for subchannel creation
if(isset($_POST['submitsub'])){

	// Trim name and password
	$name = trim($_POST['name']);
	$password = trim($_POST['password']);

	// Check if name is empty
	if($name===''){
		//Assign random name
		$name = ("[Subchannel]".rand(100,1000));
	}

	// Check if password is empty
	if($password===''){
		$password = rand(100,200);
	}

	//DB channel id get
	include_once('config.php');
	$stmt = $db->prepare("SELECT channel_id FROM `sobe` WHERE ip= ? ");
    $stmt->bind_param("s", $ips);     
    $stmt->execute();                           
    $stmt->bind_result($channel_id);                    
    $stmt->fetch();
    $stmt->close();

	// Channel creation
	$create = $ts3_VirtualServer->channelCreate(array(
        "channel_name" => $name,
        "channel_topic" => $name,
        "channel_password" => $password,
        "channel_codec" => TeamSpeak3::CODEC_SPEEX_ULTRAWIDEBAND,
        "channel_codec_quality" => 0x08,
        "channel_flag_permanent" => true,
        "cpid" => $channel_id,                                
        ));

	foreach($ts3_VirtualServer->clientList() as $client)
	{
        if($client["client_type"]) continue;
        $clientInfo = $client->getInfo();
        if($clientInfo['connection_client_ip'] == $ips)
        {

        	$ts3_VirtualServer->clientGetById($clientInfo['clid'])->message("[b][color=green]Sub Channel Created![/color][color=blue] Password:[/color][color=green]".$password."[/color][/b]");
        }  
	}

	header('Refresh: 5; URL=index.php');
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>R4P3 Channel Creator</title>
	<link rel="icon" type="image/png" href="src/images/mstile-144x144.png">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" integrity="sha384-WskhaSGFgHYWDcbwN70/dfYBj47jz9qbsMId/iRN3ewGhXQFZCSftd1LZCfmhktB" crossorigin="anonymous">
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css" integrity="sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt" crossorigin="anonymous">
	<link rel="stylesheet" href="src/css/main.css">
</head>
<body>
<?php

if(isset($_SESSION['login'])){
	// Check if user has channels on TS3 server already
	include_once('config.php');
	$stmt = $db->prepare("SELECT id FROM `sobe` WHERE ip= ? ");
    $stmt->bind_param("s", $ips);
    $stmt->execute();                               
    $stmt->bind_result($id);                    
    $stmt->fetch();                                
	$stmt->close();
	// 
	if(is_null($id))
	{?>
		<div class="row">
			<div class="col-md-2"></div>
				<div class="col-md-8">
					<div class="card">
					  <div class="card-header">
					    <img src="src/images/logo.gif" class="img-rounded"></img> Create own channel
					  </div>
					  <div class="card-body">
							<form action="#" method="POST">
								<div class="form-group">
								    <label for="formGroupExampleInput">Name of the channel:</label>
								    <input type="text" class="form-control" id="formGroupExampleInput" placeholder="Name" name="name">
								</div>
								<div class="form-group">
								    <div class="form-check">
								      	<input class="form-check-input" type="checkbox" id="gridCheck" name="check">
								      	<label class="form-check-label" for="gridCheck">
								        Move me to channel after creation
								    </label>
								    </div>
								</div>
								<button type="submit" name="submit" class="btn btn-primary">Create</button>
							</form>
					  </div>
					</div>
				</div>
			<div class="col-md-2"></div>
		</div>

	<?php
	}else
	{?>
		<div class="row">
			<div class="col-md-2"></div>
				<div class="col-md-8">
					<div class="card">
					  	<div class="card-header">
					    	<img src="src/images/logo.gif" class="img-rounded"></img>  Channel stats
						</div>
						<div class="card-body">
							<div class="row">
								<div class="col-md-6">
									<h6>Channel list:</h6>
									<ul class="list-group">
									<?php
										// Select users stored channel id from db
										include_once('config.php');
										$stmt = $db->prepare("SELECT channel_id FROM `sobe` WHERE ip= ? ");
									    $stmt->bind_param("s", $ips);     
									    $stmt->execute();                           
									    $stmt->bind_result($channel_id);                    
									    $stmt->fetch();
									    $stmt->close();

									    // All TS3 channel get
									    $all = $ts3_VirtualServer->channelList();
									    //Parse trough channels
									    foreach ($all as $value) {
									    	// Removing of [cspacer] from name
									    	$string = preg_replace('/\[([^\]]*)\]/', '', $value['channel_name']);
									    	//check if it belongs to our user and displaying main channel
									    	if($value['cid']==$channel_id)
									    	{?>
									    		<li class="list-group-item active"><?= $string; ?>
									    			<div class="float-right">
									    				<i style="color:white;margin-right:5px;" class="fas fa-user"></i><span class="badge badge-light"><?= $value['total_clients']; ?></span>
									    				<a href="index.php?delete=<?=$value['cid']?>">
									    					<span style="color:white" class="fas fa-trash-alt"></span>
									    				</a>
									    			</div>
									    		</li>
									    	<?php
									    	}
									    	// Displaying subchannels from our user
									    	if($value['pid']==$channel_id)
									    	{?>
									    		<li class="list-group-item"><?= $string; ?>
									    			<div class="float-right">
									    				<i style="color:black;margin-right:5px;" class="fas fa-user"></i><span class="badge badge-light"><?= $value['total_clients']; ?></span>
									    				<a href="index.php?delete=<?=$value['cid']?>">
									    					<span style="color:black" class="fas fa-trash-alt"></span>
									    				</a>
									    			</div>
									    		</li>
									    	<?php
									    	}
									    }

										
									?>
									</ul>
								</div>
								<div class="col-md-6">
									<h6>Create subchannel:</h6>
									<form action="#" method="POST">
										<div class="form-group">
										    <label for="formGroupExampleInput">Name of the channel:</label>
										    <input type="text" class="form-control" id="formGroupExampleInput" placeholder="Name" name="name">
										</div>
										<div class="form-group">
										    <label for="formGroupExampleInput">Password:</label>
										    <input type="text" class="form-control" id="formGroupExampleInput" placeholder="Password" name="password">
										</div>
										<button type="submit" name="submitsub" class="btn btn-primary">Create</button>
									</form>									
								</div>
							</div>
					  	</div>
				</div>
			</div>
		<div class="col-md-2"></div>
	</div>

	<?php
	}
?>
	

<?php
}else{
?>
	<div class="row">
		<div class="col-md-2"></div>
		<div class="col-md-8">
			<div class="card">
			  <div class="card-header">
			    	<img src="src/images/logo.gif" class="img-rounded"></img>  TS3 Channel menager
			  </div>
			  <div class="card-body">
					<div class="alert alert-danger" role="alert">
					  <h4 class="alert-heading">Error!</h4>
					  <p>Aww snap. You need to connect on our TS3 server to use this feature.</p>
					  <hr>
					  <p class="mb-0">Click button below to connect. After you connect just refresh this page.</p>
					</div>
					<a href="ts3server://<?= $ip; ?>" class="btn btn-primary">Connect</a>
			  </div>
			</div>
		</div>
		<div class="col-md-2"></div>
	</div>
<?php	
}
?>
	<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js" integrity="sha384-smHYKdLADwkXOn1EmN1qk/HfnUcbVRZyYmZ4qpPea6sjB/pTJ0euyQp0Mk8ck+5T" crossorigin="anonymous"></script>
</body>
</html>