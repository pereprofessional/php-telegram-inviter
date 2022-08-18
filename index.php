<?
require_once('handler.php');
$ml = new MadelineHandler();

echo '<pre>';
//var_dump($ml->getSessionsFromDB());
echo '</pre>';

?>
<html>
    <head>
        <title>v2</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no" data-meta-dynamic="true">
        <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" href="css/style.css">
    </head>
    <body>
        <div class="workarea">
            <div class="segment">
            <span class="segment-ttl">List of sessions in database:</span>
                <table>
                    <tr>
                        <th>DB ID</th>
                        <th>Session</th>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Phone</th>
                        <th>1st&last name</th>
                        <th>Spam block</th>
                        <th>Enable</th>
                        <th>Activity</th>
                    </tr>
                    <?
                    $sessionsFromDb = $ml->getSessionsFromDB();
                    if (count($sessionsFromDb) > 0) :
                        foreach($sessionsFromDb as $session) : ?>
                            <tr>
                                <td><?=$session['id']?></td>
                                <td><?=$session['session_name']?></td>
                                <td><?=$session['tg_id']?></td>
                                <td><?=$session['tg_username']?></td>
                                <td>+<?=$session['tg_phone']?></td>
                                <td><?=$session['tg_first_name']?> <?=$session['tg_last_name']?></td>
                                <td><?=$session['spam_block']?></td>
                                <td class="session_status_enable" id="<?=$session['id']?>"><?=$session['enable']?></td>
                                <td>
                                    <button disabled>Upd. profile</button>
                                    <button disabled>Upd. spam</button>
                                    <?
                                    if ($session['enable'] == 1)
                                        echo '<button class="action__set_session_disable" id="'.$session['id'].'">Dis.</button>';
                                    else
                                        echo '<button class="action__set_session_enable" id="'.$session['id'].'">Enab.</button>';

                                    ?>

                                </td>
                            </tr>
                        <? endforeach;
                    else : ?>
                        <tr><td><i>no sessions in folder</i></td></tr>
                    <? endif; ?>
                </table>
            </div>

            <div class="segment">
                <span class="segment-ttl">List of sessions in folder:</span>
                <table>
                    <tr>
                        <th>Session name</th>
                    </tr>
                    <? $sessions = $ml->getAllSessions(); ?>
                    <? if (count($sessions) > 0) : ?>
                        <? foreach ($sessions as $session) : ?>
                            <tr><td><?=$session?></td></tr>
                        <? endforeach; ?>
                    <? else : ?>
                        <tr><td><i>no sessions in folder</i></td></tr>
                    <? endif; ?>
                </table>
            </div>

            <div class="segment">
                <div style="position: absolute; width: 100%; height: 100% !important; left: 0px; height: 0px; background-color: rgba(255, 255, 255, 0.5)"></div>
                <span class="segment-ttl">Add new session:</span>
                <div class="input-fld">
                    <label>Session name:</label>
                    <input type="text" />
                </div>
                <button>Log in</button>
            </div>

            <hr/>

            <div class="segment">
                <span class="segment-ttl">Check spam block:</span>
                <div class="input-fld">
                    <label>Session name:</label>
                    <input type="text" class="action__get_spam_block__input__session_name" />
                </div>
                <button class="action__get_spam_block">Check</button>
                <div class="pre-response">
                    <label>Response:</label>
                    <pre class="action__get_spam_block__response">code code code</pre>
                </div>
            </div>

            <div class="segment">
                <div style="position: absolute; width: 100%; height: 100% !important; left: 0px; height: 0px; background-color: rgba(255, 255, 255, 0.5)"></div>
                <span class="segment-ttl">Send message:</span>
                <div class="input-fld">
                    <label>Session name:</label>
                    <input type="text" />
                </div>
                <div class="input-fld">
                    <label>Recipient:</label>
                    <input type="text" />
                </div>
                <div class="input-fld">
                    <label>Message:</label>
                    <input type="text" />
                </div>
                <button>Send</button>
                <div class="pre-response">
                    <label>Response:</label>
                    <pre>code code code</pre>
                </div>
            </div>

            <div class="segment">
                <span class="segment-ttl">Get message history:</span>
                <div class="input-fld">
                    <label>Session name:</label>
                    <input type="text" class="action__get_message_history__input__session_name" />
                </div>
                <div class="input-fld">
                    <label>Recipient:</label>
                    <input type="text" value="@spambot" class="action__get_message_history__input__recipient" />
                </div>
                <button class="action__get_message_history">Get</button>
                <div class="pre-response">
                    <label>Response:</label>
                    <pre class="action__get_message_history__response">code code code</pre>
                </div>
            </div>

            <div class="segment">
                <span class="segment-ttl">Invite user to chat/channel:</span>
                <div class="input-fld">
                    <label>Session name:</label>
                    <input type="text" class="action__invite_to_channel__input__session_name" />
                </div>
                <div class="input-fld">
                    <label>Invitee:</label>
                    <input type="text" value="" class="action__invite_to_channel__input__invitee" />
                </div>
                <div class="input-fld">
                    <label>Chat/channel:</label>
                    <input type="text" value="@baikal_miners" class="action__invite_to_channel__input__channel" />
                </div>
                <button class="action__invite_to_channel">Invite</button>
                <div class="pre-response">
                    <label>Response:</label>
                    <pre class="action__invite_to_channel__response">code code code</pre>
                </div>
            </div>

            <hr/>

            <div class="segment">
                <span class="segment-ttl">Linked target (tc) and source (sc) channels:</span>
                <table>
                    <tr>
                        <th>ID DB</th>
                        <th>TC username</th>
                        <th>SC username</th>
                        <th>Participants</th>
                        <th>With username</th>
                        <th>Invited totally</th>
                        <th>Invited successfully</th>
                        <th>Enable</th>
                        <th>Activity</th>
                    </tr>
                    <? $channels = $ml->getChannelsFromDB(); ?>
                    <? if (count($channels) > 0) : ?>
                        <? foreach ($channels as $channel) : ?>
                            <tr>
                                <td><?=$channel['id']?></td>
                                <td><?=$channel['ch_username']?></td>
                                <td><?=$channel['tg_src_username']?></td>
                                <td><?=$channel['tg_src_participants_count']?></td>
                                <td><?=$channel['with_username']?></td>
                                <td><?=$channel['invited_totally']?></td>
                                <td><?=$channel['invited_successfully']?></td>
                                <td class="link_status_enable" id="<?=$channel['id']?>"><?=$channel['enable']?></td>
                                <td>
                                    <?
                                    if ($channel['enable'] == 1)
                                        echo '<button class="action__set_link_disable" id="'.$channel['id'].'">Dis.</button>';
                                    else
                                        echo '<button class="action__set_link_enable" id="'.$channel['id'].'">Enab.</button>';

                                    ?>
                                </td>
                            </tr>
                        <? endforeach; ?>
                    <? else : ?>
                        <tr><td><i>no channels in db</i></td></tr>
                    <? endif; ?>
                </table>
            </div>

            <div class="segment">
                <span class="segment-ttl">Link target and source channels:</span>
                <div class="input-fld">
                    <label>Session name (needed for checking chat info only):</label>
                    <input type="text" class="action__link_channels__input__session_name" />
                </div>
                <div class="input-fld">
                    <label>Target channel, internal id (where invite users to):</label>
                    <input type="text" class="action__link_channels__input__target" />
                </div>
                <div class="input-fld">
                    <label>Source channel, tg username (where parse users from):</label>
                    <input type="text" value="" class="action__link_channels__input__source" />
                </div>
                <button class="action__link_channels">Link</button>
                <div class="pre-response">
                    <label>Response:</label>
                    <pre class="action__link_channels__response">code code code</pre>
                </div>
            </div>

            <div class="segment">
                <span class="segment-ttl">Parse participants:</span>
                <div class="input-fld">
                    <label>Session name (needed for checking chat info only):</label>
                    <input type="text" class="action__parse_participants__input__session_name" />
                </div>
                <div class="input-fld">
                    <label>Link id, internal:</label>
                    <input type="text" class="action__parse_participants__input__link_id" />
                </div>
                <button class="action__parse_participants">Parse</button>
                <div class="pre-response">
                    <label>Response:</label>
                    <pre class="action__parse_participants__response">code code code</pre>
                </div>
            </div>


        </div>
    </body>
    <script src="js/jquery-3.3.1.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/jquery.maskedinput.min.js"></script>

    <script>
        $('.action__get_spam_block').click(function()
        {
            $('.action__get_spam_block__response').html('');
            var session_name = $('.action__get_spam_block__input__session_name').val();
            $.get('ajax.php?action=get_spam_block&session_name='+session_name, function(response)
            {
                response = JSON.parse(response);
                for (let i = 0; i < response.length; i++)
                {
                    if (response[i]['out'])
                        $('.action__get_spam_block__response').append(`<div style="background-color: #62ff0038; padding: 10px; margin-bottom: 10px;"><strong>` + response[i]['date'] + `</strong><br/><br/>` + response[i]['message'] + `</div>`);
                    else
                        $('.action__get_spam_block__response').append(`<div style="background-color: #ff002f38; padding: 10px; margin-bottom: 10px;"><strong>` + response[i]['date'] + `</strong><br/><br/>` + response[i]['message'] + `</div>`);
                }
            });
        });

        $('.action__get_message_history').click(function() // аналог функции $('.action__get_spam_block').click(function()
        {
            $('.action__get_message_history__response').html('');
            var session_name = $('.action__get_message_history__input__session_name').val();
            var recipient = $('.action__get_message_history__input__recipient').val();
            $.get('ajax.php?action=get_message_history&session_name='+session_name+'&recipient='+recipient, function(response)
            {
                response = JSON.parse(response);
                for (let i = 0; i < response.length; i++)
                {
                    if (response[i]['out'])
                        $('.action__get_message_history__response').append(`<div style="background-color: #62ff0038; padding: 10px; margin-bottom: 10px;"><strong>` + response[i]['date'] + `</strong><br/><br/>` + response[i]['message'] + `</div>`);
                    else
                        $('.action__get_message_history__response').append(`<div style="background-color: #ff002f38; padding: 10px; margin-bottom: 10px;"><strong>` + response[i]['date'] + `</strong><br/><br/>` + response[i]['message'] + `</div>`);
                }
            });
        });

        $('.action__invite_to_channel').click(function()
        {
            $('.action__invite_to_channel__response').html('');
            var session_name = $('.action__invite_to_channel__input__session_name').val();
            var invitee = $('.action__invite_to_channel__input__invitee').val();
            var channel = $('.action__invite_to_channel__input__channel').val();
            $.get('ajax.php?action=invite_to_channel&session_name='+session_name+'&invitee='+invitee+'&channel='+channel, function(response)
            {
                //console.log('action__invite_to_channel response');
                //console.log(response);
                $('.action__invite_to_channel__response').html(response);
            });
        });

        $('.action__link_channels').click(function()
        {
            $('.action__link_channels__response').html('');
            var source_internal_id = $('.action__link_channels__input__target').val();
            var target_username = $('.action__link_channels__input__source').val();
            var session_name = $('.action__link_channels__input__session_name').val();
            $.get('ajax.php?action=link_channels&session_name='+session_name+'&source_internal_id='+source_internal_id+'&target_username='+target_username, function(response)
            {
                //console.log('action__invite_to_channel response');
                //console.log(response);
                $('.action__link_channels__response').html(response);
            });
        });

        $('.action__parse_participants').click(function()
        {
            $('.action__parse_participants__response').html('');
            var session_name = $('.action__parse_participants__input__session_name').val();
            var link_id = $('.action__parse_participants__input__link_id').val();
            $.get('ajax.php?action=parse_participants&session_name='+session_name+'&link_id='+link_id, function(response)
            {
                //console.log('action__invite_to_channel response');
                //console.log(response);
                $('.action__parse_participants__response').html(response);
            });
        });

        $('.action__set_session_disable').click(function()
        {
            var session_id = $(this).attr('id');
            var btn = $(this);
            $.get('ajax.php?action=action__set_session_disable&session_id='+session_id, function(response)
            {
                $('#'+session_id+'.session_status_enable').html(0);
                btn.attr('class', 'action__set_session_enable');
                btn.html('Enab.');
            });
        });

        $('.action__set_session_enable').click(function()
        {
            var session_id = $(this).attr('id');
            var btn = $(this);
            $.get('ajax.php?action=action__set_session_enable&session_id='+session_id, function(response)
            {
                response = JSON.parse(response);
                if (response['status'] == 'success')
                {
                    $('#'+session_id+'.session_status_enable').html(1);
                    btn.attr('class', 'action__set_session_disable');
                    btn.html('Dis.');
                }
            });
        });

        $('.action__set_link_disable').click(function()
        {
            var link_id = $(this).attr('id');
            var btn = $(this);
            $.get('ajax.php?action=set_link_disable&link_id='+link_id, function(response)
            {
                $('#'+link_id+'.link_status_enable').html(0);
                btn.attr('class', 'action__set_link_enable');
                btn.html('Enab.');
            });
        });

        $('.action__set_link_enable').click(function()
        {
            var link_id = $(this).attr('id');
            var btn = $(this);
            $.get('ajax.php?action=set_link_enable&link_id='+link_id, function(response)
            {
                response = JSON.parse(response);
                if (response['status'] == 'success')
                {
                    $('#'+link_id+'.link_status_enable').html(1);
                    btn.attr('class', 'action__set_link_disable');
                    btn.html('Dis.');
                }
            });
        });
    </script>
</html>
