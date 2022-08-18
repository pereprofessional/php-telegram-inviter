<?php
$action = $_GET['action'];

if ($action == 'get_spam_block')
{
    require_once('handler.php');
    $ml = new MadelineHandler();
    $session_name = $_GET['session_name'];

    $response = $ml->getSpamBlockInfo($session_name);

    print_r(json_encode($response));
}


if ($action == 'get_message_history')
{
    require_once('handler.php');
    $ml = new MadelineHandler();
    $session_name = $_GET['session_name'];
    $recipient = $_GET['recipient'];

    $response = $ml->getMessageHistory($session_name, $recipient);

    print_r(json_encode($response));
}

if ($action == 'invite_to_channel')
{
    require_once('handler.php');
    $ml = new MadelineHandler();
    $session_name = $_GET['session_name'];
    $invitee = $_GET['invitee'];
    $channel = $_GET['channel'];

    $response = $ml->inviteToChannel($session_name, $invitee, $channel);

    var_dump($response);
}

if ($action == 'link_channels')
{
    require_once('handler.php');
    $ml = new MadelineHandler();
    $session_name = $_GET['session_name'];
    $source_internal_id = $_GET['source_internal_id'];
    $target_username = $_GET['target_username'];

    $response = $ml->linkChannels($session_name, $source_internal_id, $target_username);

    var_dump($response);
}


if ($action == 'parse_participants')
{
    require_once('handler.php');
    $ml = new MadelineHandler();
    $session_name = $_GET['session_name'];
    $link_id = $_GET['link_id'];

    $response = $ml->parseParticipants($session_name, $link_id);

    var_dump($response);
}

if (($action == 'action__set_session_disable') || ($action == 'action__set_session_enable'))
{
    require_once('handler.php');
    $ml = new MadelineHandler();
    $session_id = $_GET['session_id'];

    if ($action == 'action__set_session_disable')
        $response = $ml->enableOrDisableSession($session_id, 'disable');
    elseif ($action == 'action__set_session_enable')
        $response = $ml->enableOrDisableSession($session_id, 'enable');

    print_r(json_encode($response));
}

if (($action == 'set_link_disable') || ($action == 'set_link_enable'))
{
    require_once('handler.php');
    $ml = new MadelineHandler();
    $link_id = $_GET['link_id'];

    if ($action == 'set_link_disable')
        $response = $ml->enableOrDisableLink($link_id, 'disable');
    elseif ($action == 'set_link_enable')
        $response = $ml->enableOrDisableLink($link_id, 'enable');

    print_r(json_encode($response));
}

if ($action == 'refresh_profile_info')
{
    require_once('handler.php');
    $ml = new MadelineHandler();
    $session_name = $_GET['session_name'];

    $response = $ml->refreshProfileInfo($session_name);

    var_dump($response);
}

?>