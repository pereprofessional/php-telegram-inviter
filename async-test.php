<?php
if (!file_exists('../madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include '../madeline.php';



function func($i)
{
	$start_time = time();

	$me = '';
	while(true) 
	{
    	if ((time() - $start_time) > 1) 
    	{
      		return false; // timeout, function took longer than 1 seconds
    	}
    
    	$MadelineProto = new \danog\MadelineProto\API('../session.test'.$i);
		$MadelineProto->async(true);
		$MadelineProto->loop(function () use ($MadelineProto) 
		{
		    yield $MadelineProto->start();
		    $me = yield $MadelineProto->getSelf();
		    /*yield $MadelineProto->messages->sendMessage([
		                'message' => 'test',
		                'peer' => 'bishaevasily'
		            ]);*/
		    var_dump($me);
		});
		if ($me['self'] == true) return false;
  	}

  	if ($me)
  	{
  		var_dump($me);
  	}
  	else
  	{
  		echo 'could not log in';
  	}

	
}

for ($i = 3; $i <= 4; $i++)
{
	func($i);
	
}

return;

$MadelineProto = new \danog\MadelineProto\API('../session.test4');
$MadelineProto->async(true);
$MadelineProto->loop(function () use ($MadelineProto) {
    yield $MadelineProto->start();
    $me = yield $MadelineProto->getSelf();
    var_dump($me);
    yield $MadelineProto->messages->sendMessage([
                'message' => 'test',
                'peer' => 'bishaevasily'
            ]);
    /*$MadelineProto->logger($me);

    if (!$me['bot']) {
        yield $MadelineProto->messages->sendMessage(['peer' => '@danogentili', 'message' => "Hi!\nThanks for creating MadelineProto! <3"]);
        yield $MadelineProto->channels->joinChannel(['channel' => '@MadelineProto']);

        try {
            yield $MadelineProto->messages->importChatInvite(['hash' => 'https://t.me/joinchat/Bgrajz6K-aJKu0IpGsLpBg']);
        } catch (\danog\MadelineProto\RPCErrorException $e) {
            $MadelineProto->logger($e);
        }

        yield $MadelineProto->messages->sendMessage(['peer' => 'https://t.me/joinchat/Bgrajz6K-aJKu0IpGsLpBg', 'message' => 'Testing MadelineProto!']);
    }
    yield $MadelineProto->echo('OK, done!');*/
});

echo '[end of file]';