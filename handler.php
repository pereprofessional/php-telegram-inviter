<<<<<<< HEAD
<?php

class MadelineHandler
{
    protected $MadelineProto;
    protected $mysql_connection;

    function __construct()
    {
        include 'mp/vendor/autoload.php';
        include 'config.php';

        $mysql_constants = DB::getDatabaseInfo();
        $this->mysql_connection = mysqli_connect($mysql_constants['ip'], $mysql_constants['user'], $mysql_constants['pass'], $mysql_constants['db']);
        mysqli_set_charset($this->mysql_connection, "utf8mb4");
    }

    public function init($session_name) // Инициализировтаь сессию (процесс авторизации или информация о юзере)
    {
        $this->MadelineProto = new \danog\MadelineProto\API(dirname(__FILE__).'/sessions/session.'.$session_name);
        return $this->MadelineProto->start();

    }

    public function getSelf() // Информация о юзере: $ml->getSelf() (бесполезно, так как MadeLine->start() возрвщащает то же самое)
    {
        return $this->MadelineProto->getSelf();
    }


    public function getAllSessions() // Получить все сессии
    {
        $dir    = 'sessions/';
        $files = preg_grep('~^session.~', scandir($dir));
        $sessions = [];
        foreach ($files as $file)
        {
            if (substr_count($file, '.') == 1)
            {
                array_push($sessions, mb_substr($file, 8));
            }
        }
        return $sessions;
    }

    public function refreshProfileInfo($session_name)
    {
        $this->init($session_name);
        $me = $this->getSelf();
        if (!isset($me['id'])) return [ 'status' => 'fail', 'response' => 'Can not find ID field. Probably session is not logged in.' ];

        $sessionsFromMysql = mysqli_query($this->mysql_connection, "SELECT * FROM inviter_sessions WHERE session_name = '$session_name'")->fetch_all(MYSQLI_ASSOC);

        if (count($sessionsFromMysql) > 1) // если в бд больше 1 записи с таким именем сессии, то это ошибка какая-то
        {
            return [ 'status' => 'fail', 'response' => 'There is more than 1 session name in DB. It can not be.' ];
        }
        elseif (count($sessionsFromMysql) == 1) // если запись найдена, то обновляем её
        {
            $mysql_set = '';
            $mysql_set_divider = ',';
            $mysql_set .= "tg_source = '".json_encode($me, true)."' ".$mysql_set_divider;
            if (isset($me['first_name'])) $mysql_set .= "tg_first_name = '".$me['first_name']."'".$mysql_set_divider;
            if (isset($me['last_name'])) $mysql_set .= "tg_last_name = '".$me['last_name']."'".$mysql_set_divider;
            if (isset($me['username'])) $mysql_set .= "tg_username = '".$me['username']."'".$mysql_set_divider;
            if (isset($me['phone'])) $mysql_set .= "tg_phone = '".$me['phone']."'".$mysql_set_divider;
            if (isset($me['id'])) $mysql_set .= "tg_id = '".$me['id']."'".$mysql_set_divider;
            $mysql_set = substr($mysql_set, 0, -(strlen($mysql_set_divider)));


            if (mysqli_query($this->mysql_connection,"UPDATE inviter_sessions SET $mysql_set WHERE session_name = '".$session_name."'"))
                return [ 'status' => 'success', 'response' => 'Session data updated in DB.' ];

        }
        elseif (count($sessionsFromMysql) == 0) // если сессия авторизована, но её нет в бд, то вставляем её как новую запись
        {
            $mysql_values = '';
            $mysql_fields = '';
            $mysql_values_and_fields_divider = ',';
            $mysql_fields .= 'tg_source'.$mysql_values_and_fields_divider;
            $mysql_values .= "'".json_encode($me, true)."'".$mysql_values_and_fields_divider;
            $mysql_fields .= 'session_name'.$mysql_values_and_fields_divider;
            $mysql_values .= "'".$session_name."'".$mysql_values_and_fields_divider;
            $mysql_fields .= 'enable'.$mysql_values_and_fields_divider;
            $mysql_values .= "0".$mysql_values_and_fields_divider;
            if (isset($me['first_name']))
            {
                $mysql_fields .= 'tg_first_name'.$mysql_values_and_fields_divider;
                $mysql_values .= "'".$me['first_name']."'".$mysql_values_and_fields_divider;
            }
            if (isset($me['last_name']))
            {
                $mysql_fields .= 'tg_last_name'.$mysql_values_and_fields_divider;
                $mysql_values .= "'".$me['last_name']."'".$mysql_values_and_fields_divider;
            }
            if (isset($me['username']))
            {
                $mysql_fields .= 'tg_username'.$mysql_values_and_fields_divider;
                $mysql_values .= "'".$me['username']."'".$mysql_values_and_fields_divider;
            }
            if (isset($me['phone']))
            {
                $mysql_fields .= 'tg_phone'.$mysql_values_and_fields_divider;
                $mysql_values .= "'".$me['phone']."'".$mysql_values_and_fields_divider;
            }
            if (isset($me['id']))
            {
                $mysql_fields .= 'tg_id'.$mysql_values_and_fields_divider;
                $mysql_values .= "'".$me['id']."'".$mysql_values_and_fields_divider;
            }
            $mysql_fields = substr($mysql_fields, 0, -(strlen($mysql_values_and_fields_divider)));
            $mysql_values = substr($mysql_values, 0, -(strlen($mysql_values_and_fields_divider)));


            if (mysqli_query($this->mysql_connection, "INSERT INTO inviter_sessions ($mysql_fields) VALUES ($mysql_values)"))
                return [ 'status' => 'success', 'response' => 'Session data inserted in DB.' ];
        }
        return [];
    }

    public function getSessionsFromDB()
    {
        $sessionsFromMysql = mysqli_query($this->mysql_connection, "SELECT * FROM inviter_sessions")->fetch_all(MYSQLI_ASSOC);

        return $sessionsFromMysql;
    }

    public function getSpamBlockInfo($session_name)
    {
        $this->init($session_name);

        $this->MadelineProto->messages->sendMessage([
            'peer' => '@spambot',
            'message' => '/start'
        ]);

        sleep(1);

        $response = $this->MadelineProto->messages->getHistory([
            'peer' => '@spambot',
            'offset_id' => 0,
            'offset_date' => 0,
            'add_offset' => 0,
            'limit' => 10,
            'max_id' => 0,
            'min_id' => 0,
            'hash' => 0
        ]);

        $output = [];

        foreach ($response['messages'] as $key => $value)
        {
            //if(strpos($value['message'], '') !== false) // если имеет подстроку
            array_push($output, [ 'out' => $value['out'], 'date' => date('d.m.Y H:i:s', $value['date']), 'message' => $value['message'] ]);
        }

        return $output;
    }

    public function getMessageHistory($session_name, $recipient) // аналог функции getSpamBlockInfo($session_name), но без отправки сбщ
    {
        $this->init($session_name);

        $response = $this->MadelineProto->messages->getHistory([
            'peer' => $recipient,
            'offset_id' => 0,
            'offset_date' => 0,
            'add_offset' => 0,
            'limit' => 10,
            'max_id' => 0,
            'min_id' => 0,
            'hash' => 0
        ]);

        $output = [];

        foreach ($response['messages'] as $key => $value)
        {
            //if(strpos($value['message'], '') !== false) // если имеет подстроку
            array_push($output, [ 'out' => $value['out'], 'date' => date('d.m.Y H:i:s', $value['date']), 'message' => $value['message'] ]);
        }

        return $output;
    }

    public function multipleInviteToChannel($reqs)
    {
        foreach ($reqs as $key => $req)
        {
            $this->init($req['session_name']);

            $invitee_source = [];
            if ($req['invite_by_internal_id']) // инвайтим не по @username, а по внутреннему id юзера из таблы inviter_participants
            {
                $invitee_source = $reqs[$key]['invitee'];
                $sql = "SELECT * FROM inviter_participants WHERE id = '".$invitee_source['id']."' AND src_ch_id = '".$invitee_source['link_id']."'";
                $usersFromMysql = mysqli_query($this->mysql_connection, $sql)->fetch_all(MYSQLI_ASSOC);
                unset($reqs[$key]['invitee']);
                $reqs[$key]['invitee'] = $usersFromMysql[0]['user_username'];
            }
            var_dump($reqs[$key]['session_name']);
            $res = $this->MadelineProto->channels->inviteToChannel(['channel' => $req['channel'], 'users' => [ 'mirkhel_25' ], ], [ 'async' => true ] ); // baikal_miners, 680205619
            /*yield $this->MadelineProto->messages->sendMessage([
                'message' => 'test',
                'peer' => 'bishaevasily'
            ]);*/
            var_dump($res);
        }
        return $reqs;
    }

    // возможно пригласить по @username
    public function inviteToChannel($session_name, $invitee, $channel, $invite_by_internal_id = false)
    {
        $this->init($session_name);


        $invitee_source = [];
        if ($invite_by_internal_id) // инвайтим не по @username, а по внутреннему id юзера из таблы inviter_participants
        {
            $invitee_source = $invitee;
            $sql = "SELECT * FROM inviter_participants WHERE id = '".$invitee_source['id']."' AND src_ch_id = '".$invitee_source['link_id']."'";
            $usersFromMysql = mysqli_query($this->mysql_connection, $sql)->fetch_all(MYSQLI_ASSOC);
            unset($invitee);
            $invitee = $usersFromMysql[0]['user_username'];
        }

        //var_dump($invitee_source);

        try
        {
            //register_shutdown_function(array('MadelineHandler', 'shutdown')); // функция срабатывает, когда срабатывает ошибка max_execution_time
            //set_time_limit(1);
            $res = $this->MadelineProto->channels->inviteToChannel(['channel' => $channel, 'users' => [ $invitee ], ] ); // baikal_miners, 680205619
            if (isset($res['updates']))
            {
                if (count($res['updates']) > 0)
                {
                    foreach ($res['updates'] as $update)
                    {
                        if (isset($update['message']['action']['_']))
                        {
                            if ($update['message']['action']['_'] == 'messageActionChatAddUser')
                            {
                                if ($invite_by_internal_id)
                                {
                                    $sql = "UPDATE inviter_participants SET invited = 1 WHERE id = '".$invitee_source['id']."' AND src_ch_id = '".$invitee_source['link_id']."'";
                                    mysqli_query($this->mysql_connection, $sql);
                                }
                                $this->deleteMessageAboutAddingUser($session_name, $channel);
                                return [ 'status' => 'success', 'message' => 'Received messageActionChatAddUser.', 'code' => 1 ];
                            }
                        }
                    }
                    if ($invite_by_internal_id)
                    {
                        $sql = "UPDATE inviter_participants SET invited = 4 WHERE id = '".$invitee_source['id']."' AND src_ch_id = '".$invitee_source['link_id']."'";
                        mysqli_query($this->mysql_connection, $sql);
                    }
                    return [ 'status' => 'fail', 'message' => 'Got some updates, but did not received messageActionChatAddUser.', 'code' => 4 ];
                }
                else
                {
                    if ($invite_by_internal_id)
                    {
                        $sql = "UPDATE inviter_participants SET invited = 3 WHERE id = '".$invitee_source['id']."' AND src_ch_id = '".$invitee_source['link_id']."'";
                        mysqli_query($this->mysql_connection, $sql);
                    }
                    return [ 'status' => 'fail', 'message' => 'There is no error, but no invite too. Probably user has been invited before.', 'code' => 3 ];
                }

            }
        }
        catch (Exception $e)
        {
            $code = 2;
            if ($e->getMessage() == 'PEER_FLOOD') $code = 21;
            if ($invite_by_internal_id)
            {
                $sql = "UPDATE inviter_participants SET invited = $code WHERE id = '".$invitee_source['id']."' AND src_ch_id = '".$invitee_source['link_id']."'";
                mysqli_query($this->mysql_connection, $sql);
            }
            return [ 'status' => 'fail', 'message' => $e->getMessage(), 'code' => $code ];
        }
    }

    public function linkChannels($session_name, $source_internal_id, $target_username)
    {
        $this->init($session_name);

        try
        {
            $info = $this->MadelineProto->getFullInfo($target_username);

            if (isset($info['full']))
            {
                if ($info['full']['can_view_participants'] == true)
                {
                    $linksFromMysql = mysqli_query($this->mysql_connection, "SELECT * FROM inviter_source_channels WHERE ch_id = '$source_internal_id' AND tg_src_username = '".$info['Chat']['username']."'")->fetch_all(MYSQLI_ASSOC);

                    if (count($linksFromMysql) > 0)
                        return [ 'status' => 'fail', 'message' => 'These channels are already linked in DB.', 'code' => 5 ];
                    else
                    {
                        $tg_src_id = '';
                        $tg_src_username = '';
                        $tg_src_title = '';
                        $tg_src_type = '';
                        $tg_src_participants_count = '';
                        if (isset($info['full']['id'])) $tg_src_id = $info['full']['id'];
                        if (isset($info['Chat']['username'])) $tg_src_username = $info['Chat']['username'];
                        if (isset($info['Chat']['title'])) $tg_src_title = $info['Chat']['title'];
                        if (isset($info['type'])) $tg_src_type = $info['type'];
                        if (isset($info['full']['participants_count'])) $tg_src_participants_count = $info['full']['participants_count'];
                        $mysql_fields = "ch_id,tg_src_id,tg_src_username,tg_src_title,tg_src_type,tg_src_participants_count";
                        $mysql_values = "'$source_internal_id','$tg_src_id','$tg_src_username','$tg_src_title','$tg_src_type','$tg_src_participants_count'";

                        if (mysqli_query($this->mysql_connection, "INSERT INTO inviter_source_channels ($mysql_fields) VALUES ($mysql_values)"))
                            return [ 'status' => 'success', 'response' => 'Session data inserted into DB.', 'code' => 1  ];
                        else
                            return [ 'status' => 'fail', 'response' => 'Could not insert into DB.', 'code' => 5  ];
                    }
                }
                else
                    return [ 'status' => 'fail', 'message' => 'It is not allowed to view participants of this chat/channel.', 'code' => 3 ];

            }
            else
                return [ 'status' => 'fail', 'message' => 'Could not find full info.', 'code' => 4 ];
        }
        catch (Exception $e)
        {
            return [ 'status' => 'fail', 'message' => $e->getMessage(), 'code' => 2 ];
        }
    }

    public function parseParticipants($session_name, $link_id)
    {
        $this->init($session_name);

        $linksFromMysql = mysqli_query($this->mysql_connection, "SELECT * FROM inviter_source_channels WHERE id = '$link_id'")->fetch_all(MYSQLI_ASSOC);

        if (count($linksFromMysql) == 0)
        {
            return [ 'status' => 'fail', 'message' => 'There is no any link with this id in DB.', 'code' => 3 ];
        }
        else
        {
            $info = $this->MadelineProto->getPwrChat($linksFromMysql[0]['tg_src_username']);
            if (isset($info['can_view_participants']))
            {
                if ($info['can_view_participants'] == false)
                {
                    return [ 'status' => 'fail', 'message' => 'It is not allowed to view participants in this chat/channel.', 'code' => 5 ];
                }
                else
                {
                    $participantsFromMysql = mysqli_query($this->mysql_connection, "SELECT * FROM inviter_participants WHERE src_ch_id = '$link_id'")->fetch_all(MYSQLI_ASSOC);
                    if (count($participantsFromMysql) > 0)
                    {
                        return [ 'status' => 'fail', 'message' => 'There is already participants with this link id in DB. It supports inserting only new participants in DB at this time.', 'code' => 6 ];
                    }
                    else
                    {
                        $inserted_count = 0;
                        $iteration = 0;
                        $participants = $info['participants'];

                        set_time_limit(0);

                        foreach ($participants as $participant)
                        {
                            //if ($iteration > 20) continue; // для теста
                            if ($participant['user']['type'] == 'bot') continue; // пропускаем ботов
                            if ($participant['role'] == 'admin') continue; // пропускаем амдинов
                            if ($participant['role'] == 'creator') continue; // пропускаем создателя
                            if (!isset($participant['user']['username'])) continue; // пропускаем без юзернейма

                            $src_ch_id = $link_id;
                            $user_id = '';
                            $user_username = '';
                            $user_phone = '';
                            $invited = 0;
                            $user_type = '';
                            $user_first_name = '';
                            $user_last_name = '';
                            $user_status_was_online = '';
                            $user_access_hash = '';
                            $user_role = '';
                            $user_date = '';
                            $user_source = json_encode($participant, true);

                            if (isset($participant['user']['id'])) $user_id = $participant['user']['id'];
                            if (isset($participant['user']['username'])) $user_username = $participant['user']['username'];
                            if (isset($participant['user']['phone'])) $user_phone = $participant['user']['phone'];
                            if (isset($participant['user']['type'])) $user_type = $participant['user']['type'];
                            if (isset($participant['user']['first_name'])) $user_first_name = $participant['user']['first_name'];
                            if (isset($participant['user']['last_name'])) $user_last_name = $participant['user']['last_name'];
                            if (isset($participant['user']['status']['was_online'])) $user_status_was_online = $participant['user']['status']['was_online'];
                            if (isset($participant['user']['access_hash'])) $user_access_hash = $participant['user']['access_hash'];
                            if (isset($participant['role'])) $user_role = $participant['role'];
                            if (isset($participant['date'])) $user_date = $participant['date'];
                            $mysql_fields = "src_ch_id, user_id, user_username, user_phone, invited, user_type, user_first_name, user_last_name, user_status_was_online, user_access_hash, user_role, user_date, user_source";
                            $mysql_values = "'$src_ch_id','$user_id','$user_username','$user_phone','$invited','$user_type','$user_first_name','$user_last_name','$user_status_was_online','$user_access_hash','$user_role','$user_date','$user_source'";

                            $iteration++;




                            if (mysqli_query($this->mysql_connection, "INSERT INTO inviter_participants ($mysql_fields) VALUES ($mysql_values)"))
                                $inserted_count++;

                        }

                        return [ 'status' => 'success', 'message' => "Inserted: $inserted_count, Processed: $iteration, Total: ".count($participants), 'code' => 4 ];
                    }


                }
            }
            else
            {
                return [ 'status' => 'fail', 'message' => 'No field can_view_participants in result.', 'code' => 4 ];
            }
        }
    }

    public function enableOrDisableSession($session_id, $type)
    {
        if ($type == 'enable')
        {
            if (mysqli_query($this->mysql_connection,"UPDATE inviter_sessions SET enable = 1 WHERE id = $session_id"))
                return [ 'status' => 'success', 'response' => 'Updated.' ];
        }
        elseif ($type == 'disable')
        {
            if (mysqli_query($this->mysql_connection,"UPDATE inviter_sessions SET enable = 0 WHERE id = $session_id"))
                return [ 'status' => 'success', 'response' => 'Updated.' ];
        }
    }

    public function getChannelsFromDB()
    {   
        $channelsFromMysql = mysqli_query($this->mysql_connection, "
            SELECT s_ch.id, s_ch.tg_src_username, s_ch.tg_src_participants_count, s_ch.enable, t_ch.username as ch_username
            FROM inviter_source_channels s_ch
            JOIN inviter_target_channels t_ch
            ON s_ch.ch_id = t_ch.id
        ")->fetch_all(MYSQLI_ASSOC);

        foreach ($channelsFromMysql as $key => $value)
        {
            $res = mysqli_query($this->mysql_connection, "SELECT * FROM inviter_participants WHERE src_ch_id = '".$channelsFromMysql[$key]['id']."'")->fetch_all(MYSQLI_ASSOC);
            $channelsFromMysql[$key]['with_username'] = count($res);
        }

        foreach ($channelsFromMysql as $key => $value)
        {
            $res = mysqli_query($this->mysql_connection, "SELECT * FROM inviter_participants WHERE src_ch_id = '".$channelsFromMysql[$key]['id']."' AND invited != 0")->fetch_all(MYSQLI_ASSOC);
            $channelsFromMysql[$key]['invited_totally'] = count($res);
        }

        foreach ($channelsFromMysql as $key => $value)
        {
            $res = mysqli_query($this->mysql_connection, "SELECT * FROM inviter_participants WHERE src_ch_id = '".$channelsFromMysql[$key]['id']."' AND invited = 1")->fetch_all(MYSQLI_ASSOC);
            $channelsFromMysql[$key]['invited_successfully'] = count($res);
        }

        return $channelsFromMysql;
    }


    public function enableOrDisableLink($link_id, $type)
    {
        if ($type == 'enable')
        {
            if (mysqli_query($this->mysql_connection,"UPDATE inviter_source_channels SET enable = 1 WHERE id = $link_id"))
                return [ 'status' => 'success', 'response' => 'Updated.' ];
        }
        elseif ($type == 'disable')
        {
            if (mysqli_query($this->mysql_connection,"UPDATE inviter_source_channels SET enable = 0 WHERE id = $link_id"))
                return [ 'status' => 'success', 'response' => 'Updated.' ];
        }
    }

    public function getLinksForCron()
    {
        $linksFromMysql = $this->getChannelsFromDB();
        $finalArray = [];
        foreach ($linksFromMysql as $key => $value) // отбираем активные линки
            if ($linksFromMysql[$key]['enable'] == 1)
                array_push($finalArray, $linksFromMysql[$key]);
        return $finalArray;
    }

    public function getSessionsForCron()
    {
        $sessionsFromMysql = $this->getSessionsFromDB();
        $finalArray = [];
        foreach ($sessionsFromMysql as $key => $value) // отбираем активные линки
            if ($sessionsFromMysql[$key]['enable'] == 1)
                array_push($finalArray, $sessionsFromMysql[$key]);
        return $finalArray;
    }

    public function getParticipantsForCron($link_id, $limit)
    {
        /* error codes from inviteToChannel($session_name, $invitee, $channel):
         * code 0: default, when just created
         * code 1, success: Received messageActionChatAddUser
         * code 2: $e->getMessage() (message can be PEER_FLOOD)
         * code 3: There is no error, but no invite too. Probably user has been invited before
         * code 4: Got some updates, but did not received messageActionChatAddUser (maybe should delete this cond from sql)
         * code 21: PEER_FLOOD
         * */
        $sql = "SELECT * FROM inviter_participants WHERE src_ch_id = '$link_id' AND (invited = 0 OR invited = 21) LIMIT $limit";
        $participantsFromMysql = mysqli_query($this->mysql_connection, $sql)->fetch_all(MYSQLI_ASSOC);

        return $participantsFromMysql;
    }

    public function deleteMessageAboutAddingUser($session_name, $channel)
    {
        $this->init($session_name);
        $me = $this->MadelineProto->getSelf();
        $messages = $this->MadelineProto->messages->getHistory([
            'peer' => $channel,
            'offset_id' => 0,
            'offset_date' => 0,
            'add_offset' => 0,
            'limit' => 10,
            'max_id' => 0,
            'min_id' => 0,
            'hash' => 0
        ]);
        $messageIdsToDelete = [];
        foreach ($messages['messages'] as $key => $value)
        {
            if (isset($messages['messages'][$key]['from_id']['user_id']))
            {
                if (isset($messages['messages'][$key]['action']['_']))
                {
                    if (($messages['messages'][$key]['from_id']['user_id'] == $me['id']) && ($messages['messages'][$key]['action']['_'] == 'messageActionChatAddUser'))
                    {
                        array_push($messageIdsToDelete, $messages['messages'][$key]['id']);
                        $res = $this->MadelineProto->channels->deleteMessages(['channel' => $channel, 'id' => [ $messages['messages'][$key]['id'] ]]); // удаляем сообщение с этим id
                        //var_dump($messages['messages'][$key]['id']);
                        //sleep(1);
                    }
                }
            }
        }
    }

=======
<?php

class MadelineHandler
{
    protected $MadelineProto;
    protected $mysql_connection;

    function __construct()
    {
        include dirname(__FILE__).'/mp/vendor/autoload.php';
        include dirname(__FILE__).'/config.php';

        $mysql_constants = DB::getDatabaseInfo();
        $this->mysql_connection = mysqli_connect($mysql_constants['ip'], $mysql_constants['user'], $mysql_constants['pass'], $mysql_constants['db']);
        mysqli_set_charset($this->mysql_connection, "utf8mb4");
    }

    public function init($session_name) // Инициализировтаь сессию (процесс авторизации или информация о юзере)
    {
        $this->MadelineProto = new \danog\MadelineProto\API(dirname(__FILE__).'/sessions/session.'.$session_name);
        return $this->MadelineProto->start();

    }

    public function getSelf() // Информация о юзере: $ml->getSelf() (бесполезно, так как MadeLine->start() возрвщащает то же самое)
    {
        return $this->MadelineProto->getSelf();
    }


    public function getAllSessions() // Получить все сессии
    {
        $dir    = 'sessions/';
        $files = preg_grep('~^session.~', scandir($dir));
        $sessions = [];
        foreach ($files as $file)
        {
            if (substr_count($file, '.') == 1)
            {
                array_push($sessions, mb_substr($file, 8));
            }
        }
        return $sessions;
    }

    public function refreshProfileInfo($session_name)
    {
        $this->init($session_name);
        $me = $this->getSelf();
        if (!isset($me['id'])) return [ 'status' => 'fail', 'response' => 'Can not find ID field. Probably session is not logged in.' ];

        $sessionsFromMysql = mysqli_query($this->mysql_connection, "SELECT * FROM inviter_sessions WHERE session_name = '$session_name'")->fetch_all(MYSQLI_ASSOC);

        if (count($sessionsFromMysql) > 1) // если в бд больше 1 записи с таким именем сессии, то это ошибка какая-то
        {
            return [ 'status' => 'fail', 'response' => 'There is more than 1 session name in DB. It can not be.' ];
        }
        elseif (count($sessionsFromMysql) == 1) // если запись найдена, то обновляем её
        {
            $mysql_set = '';
            $mysql_set_divider = ',';
            $mysql_set .= "tg_source = '".json_encode($me, true)."' ".$mysql_set_divider;
            if (isset($me['first_name'])) $mysql_set .= "tg_first_name = '".$me['first_name']."'".$mysql_set_divider;
            if (isset($me['last_name'])) $mysql_set .= "tg_last_name = '".$me['last_name']."'".$mysql_set_divider;
            if (isset($me['username'])) $mysql_set .= "tg_username = '".$me['username']."'".$mysql_set_divider;
            if (isset($me['phone'])) $mysql_set .= "tg_phone = '".$me['phone']."'".$mysql_set_divider;
            if (isset($me['id'])) $mysql_set .= "tg_id = '".$me['id']."'".$mysql_set_divider;
            $mysql_set = substr($mysql_set, 0, -(strlen($mysql_set_divider)));


            if (mysqli_query($this->mysql_connection,"UPDATE inviter_sessions SET $mysql_set WHERE session_name = '".$session_name."'"))
                return [ 'status' => 'success', 'response' => 'Session data updated in DB.' ];

        }
        elseif (count($sessionsFromMysql) == 0) // если сессия авторизована, но её нет в бд, то вставляем её как новую запись
        {
            $mysql_values = '';
            $mysql_fields = '';
            $mysql_values_and_fields_divider = ',';
            $mysql_fields .= 'tg_source'.$mysql_values_and_fields_divider;
            $mysql_values .= "'".json_encode($me, true)."'".$mysql_values_and_fields_divider;
            $mysql_fields .= 'session_name'.$mysql_values_and_fields_divider;
            $mysql_values .= "'".$session_name."'".$mysql_values_and_fields_divider;
            $mysql_fields .= 'enable'.$mysql_values_and_fields_divider;
            $mysql_values .= "0".$mysql_values_and_fields_divider;
            if (isset($me['first_name']))
            {
                $mysql_fields .= 'tg_first_name'.$mysql_values_and_fields_divider;
                $mysql_values .= "'".$me['first_name']."'".$mysql_values_and_fields_divider;
            }
            if (isset($me['last_name']))
            {
                $mysql_fields .= 'tg_last_name'.$mysql_values_and_fields_divider;
                $mysql_values .= "'".$me['last_name']."'".$mysql_values_and_fields_divider;
            }
            if (isset($me['username']))
            {
                $mysql_fields .= 'tg_username'.$mysql_values_and_fields_divider;
                $mysql_values .= "'".$me['username']."'".$mysql_values_and_fields_divider;
            }
            if (isset($me['phone']))
            {
                $mysql_fields .= 'tg_phone'.$mysql_values_and_fields_divider;
                $mysql_values .= "'".$me['phone']."'".$mysql_values_and_fields_divider;
            }
            if (isset($me['id']))
            {
                $mysql_fields .= 'tg_id'.$mysql_values_and_fields_divider;
                $mysql_values .= "'".$me['id']."'".$mysql_values_and_fields_divider;
            }
            $mysql_fields = substr($mysql_fields, 0, -(strlen($mysql_values_and_fields_divider)));
            $mysql_values = substr($mysql_values, 0, -(strlen($mysql_values_and_fields_divider)));


            if (mysqli_query($this->mysql_connection, "INSERT INTO inviter_sessions ($mysql_fields) VALUES ($mysql_values)"))
                return [ 'status' => 'success', 'response' => 'Session data inserted in DB.' ];
        }
        return [];
    }

    public function getSessionsFromDB()
    {
        $sessionsFromMysql = mysqli_query($this->mysql_connection, "SELECT * FROM inviter_sessions")->fetch_all(MYSQLI_ASSOC);

        return $sessionsFromMysql;
    }

    public function getSpamBlockInfo($session_name)
    {
        $this->init($session_name);

        $this->MadelineProto->messages->sendMessage([
            'peer' => '@spambot',
            'message' => '/start'
        ]);

        sleep(1);

        $response = $this->MadelineProto->messages->getHistory([
            'peer' => '@spambot',
            'offset_id' => 0,
            'offset_date' => 0,
            'add_offset' => 0,
            'limit' => 10,
            'max_id' => 0,
            'min_id' => 0,
            'hash' => 0
        ]);

        $output = [];

        foreach ($response['messages'] as $key => $value)
        {
            //if(strpos($value['message'], '') !== false) // если имеет подстроку
            array_push($output, [ 'out' => $value['out'], 'date' => date('d.m.Y H:i:s', $value['date']), 'message' => $value['message'] ]);
        }

        return $output;
    }

    public function getMessageHistory($session_name, $recipient) // аналог функции getSpamBlockInfo($session_name), но без отправки сбщ
    {
        $this->init($session_name);

        $response = $this->MadelineProto->messages->getHistory([
            'peer' => $recipient,
            'offset_id' => 0,
            'offset_date' => 0,
            'add_offset' => 0,
            'limit' => 10,
            'max_id' => 0,
            'min_id' => 0,
            'hash' => 0
        ]);

        $output = [];

        foreach ($response['messages'] as $key => $value)
        {
            //if(strpos($value['message'], '') !== false) // если имеет подстроку
            array_push($output, [ 'out' => $value['out'], 'date' => date('d.m.Y H:i:s', $value['date']), 'message' => $value['message'] ]);
        }

        return $output;
    }

    public function multipleInviteToChannel($reqs)
    {
        foreach ($reqs as $key => $req)
        {
            $this->init($req['session_name']);

            $invitee_source = [];
            if ($req['invite_by_internal_id']) // инвайтим не по @username, а по внутреннему id юзера из таблы inviter_participants
            {
                $invitee_source = $reqs[$key]['invitee'];
                $sql = "SELECT * FROM inviter_participants WHERE id = '".$invitee_source['id']."' AND src_ch_id = '".$invitee_source['link_id']."'";
                $usersFromMysql = mysqli_query($this->mysql_connection, $sql)->fetch_all(MYSQLI_ASSOC);
                unset($reqs[$key]['invitee']);
                $reqs[$key]['invitee'] = $usersFromMysql[0]['user_username'];
            }
            var_dump($reqs[$key]['session_name']);
            $res = $this->MadelineProto->channels->inviteToChannel(['channel' => $req['channel'], 'users' => [ 'mirkhel_25' ], ], [ 'async' => true ] ); // baikal_miners, 680205619
            /*yield $this->MadelineProto->messages->sendMessage([
                'message' => 'test',
                'peer' => 'bishaevasily'
            ]);*/
            var_dump($res);
        }
        return $reqs;
    }

    // возможно пригласить по @username
    public function inviteToChannel($session_name, $invitee, $channel, $invite_by_internal_id = false)
    {
        $this->init($session_name);


        $invitee_source = [];
        if ($invite_by_internal_id) // инвайтим не по @username, а по внутреннему id юзера из таблы inviter_participants
        {
            $invitee_source = $invitee;
            $sql = "SELECT * FROM inviter_participants WHERE id = '".$invitee_source['id']."' AND src_ch_id = '".$invitee_source['link_id']."'";
            $usersFromMysql = mysqli_query($this->mysql_connection, $sql)->fetch_all(MYSQLI_ASSOC);
            unset($invitee);
            $invitee = $usersFromMysql[0]['user_username'];
        }

        //var_dump($invitee_source);

        try
        {
            //register_shutdown_function(array('MadelineHandler', 'shutdown')); // функция срабатывает, когда срабатывает ошибка max_execution_time
            //set_time_limit(1);
            $res = $this->MadelineProto->channels->inviteToChannel(['channel' => $channel, 'users' => [ $invitee ], ] ); // baikal_miners, 680205619
            if (isset($res['updates']))
            {
                if (count($res['updates']) > 0)
                {
                    foreach ($res['updates'] as $update)
                    {
                        if (isset($update['message']['action']['_']))
                        {
                            if ($update['message']['action']['_'] == 'messageActionChatAddUser')
                            {
                                if ($invite_by_internal_id)
                                {
                                    $sql = "UPDATE inviter_participants SET invited = 1 WHERE id = '".$invitee_source['id']."' AND src_ch_id = '".$invitee_source['link_id']."'";
                                    mysqli_query($this->mysql_connection, $sql);
                                }
                                $this->deleteMessageAboutAddingUser($session_name, $channel);
                                return [ 'status' => 'success', 'message' => 'Received messageActionChatAddUser.', 'code' => 1 ];
                            }
                        }
                    }
                    if ($invite_by_internal_id)
                    {
                        $sql = "UPDATE inviter_participants SET invited = 4 WHERE id = '".$invitee_source['id']."' AND src_ch_id = '".$invitee_source['link_id']."'";
                        mysqli_query($this->mysql_connection, $sql);
                    }
                    return [ 'status' => 'fail', 'message' => 'Got some updates, but did not received messageActionChatAddUser.', 'code' => 4 ];
                }
                else
                {
                    if ($invite_by_internal_id)
                    {
                        $sql = "UPDATE inviter_participants SET invited = 3 WHERE id = '".$invitee_source['id']."' AND src_ch_id = '".$invitee_source['link_id']."'";
                        mysqli_query($this->mysql_connection, $sql);
                    }
                    return [ 'status' => 'fail', 'message' => 'There is no error, but no invite too. Probably user has been invited before.', 'code' => 3 ];
                }

            }
        }
        catch (Exception $e)
        {
            $code = 2;
            if ($e->getMessage() == 'PEER_FLOOD') $code = 21;
            if ($invite_by_internal_id)
            {
                $sql = "UPDATE inviter_participants SET invited = $code WHERE id = '".$invitee_source['id']."' AND src_ch_id = '".$invitee_source['link_id']."'";
                mysqli_query($this->mysql_connection, $sql);
            }
            return [ 'status' => 'fail', 'message' => $e->getMessage(), 'code' => $code ];
        }
    }

    public function linkChannels($session_name, $source_internal_id, $target_username)
    {
        $this->init($session_name);

        try
        {
            $info = $this->MadelineProto->getFullInfo($target_username);

            if (isset($info['full']))
            {
                if ($info['full']['can_view_participants'] == true)
                {
                    $linksFromMysql = mysqli_query($this->mysql_connection, "SELECT * FROM inviter_source_channels WHERE ch_id = '$source_internal_id' AND tg_src_username = '".$info['Chat']['username']."'")->fetch_all(MYSQLI_ASSOC);

                    if (count($linksFromMysql) > 0)
                        return [ 'status' => 'fail', 'message' => 'These channels are already linked in DB.', 'code' => 5 ];
                    else
                    {
                        $tg_src_id = '';
                        $tg_src_username = '';
                        $tg_src_title = '';
                        $tg_src_type = '';
                        $tg_src_participants_count = '';
                        if (isset($info['full']['id'])) $tg_src_id = $info['full']['id'];
                        if (isset($info['Chat']['username'])) $tg_src_username = $info['Chat']['username'];
                        if (isset($info['Chat']['title'])) $tg_src_title = $info['Chat']['title'];
                        if (isset($info['type'])) $tg_src_type = $info['type'];
                        if (isset($info['full']['participants_count'])) $tg_src_participants_count = $info['full']['participants_count'];
                        $mysql_fields = "ch_id,tg_src_id,tg_src_username,tg_src_title,tg_src_type,tg_src_participants_count";
                        $mysql_values = "'$source_internal_id','$tg_src_id','$tg_src_username','$tg_src_title','$tg_src_type','$tg_src_participants_count'";

                        if (mysqli_query($this->mysql_connection, "INSERT INTO inviter_source_channels ($mysql_fields) VALUES ($mysql_values)"))
                            return [ 'status' => 'success', 'response' => 'Session data inserted into DB.', 'code' => 1  ];
                        else
                            return [ 'status' => 'fail', 'response' => 'Could not insert into DB.', 'code' => 5  ];
                    }
                }
                else
                    return [ 'status' => 'fail', 'message' => 'It is not allowed to view participants of this chat/channel.', 'code' => 3 ];

            }
            else
                return [ 'status' => 'fail', 'message' => 'Could not find full info.', 'code' => 4 ];
        }
        catch (Exception $e)
        {
            return [ 'status' => 'fail', 'message' => $e->getMessage(), 'code' => 2 ];
        }
    }

    public function parseParticipants($session_name, $link_id)
    {
        $this->init($session_name);

        $linksFromMysql = mysqli_query($this->mysql_connection, "SELECT * FROM inviter_source_channels WHERE id = '$link_id'")->fetch_all(MYSQLI_ASSOC);

        if (count($linksFromMysql) == 0)
        {
            return [ 'status' => 'fail', 'message' => 'There is no any link with this id in DB.', 'code' => 3 ];
        }
        else
        {
            $info = $this->MadelineProto->getPwrChat($linksFromMysql[0]['tg_src_username']);
            if (isset($info['can_view_participants']))
            {
                if ($info['can_view_participants'] == false)
                {
                    return [ 'status' => 'fail', 'message' => 'It is not allowed to view participants in this chat/channel.', 'code' => 5 ];
                }
                else
                {
                    $participantsFromMysql = mysqli_query($this->mysql_connection, "SELECT * FROM inviter_participants WHERE src_ch_id = '$link_id'")->fetch_all(MYSQLI_ASSOC);
                    if (count($participantsFromMysql) > 0)
                    {
                        return [ 'status' => 'fail', 'message' => 'There is already participants with this link id in DB. It supports inserting only new participants in DB at this time.', 'code' => 6 ];
                    }
                    else
                    {
                        $inserted_count = 0;
                        $iteration = 0;
                        $participants = $info['participants'];

                        set_time_limit(0);

                        foreach ($participants as $participant)
                        {
                            //if ($iteration > 20) continue; // для теста
                            if ($participant['user']['type'] == 'bot') continue; // пропускаем ботов
                            if ($participant['role'] == 'admin') continue; // пропускаем амдинов
                            if ($participant['role'] == 'creator') continue; // пропускаем создателя
                            if (!isset($participant['user']['username'])) continue; // пропускаем без юзернейма

                            $src_ch_id = $link_id;
                            $user_id = '';
                            $user_username = '';
                            $user_phone = '';
                            $invited = 0;
                            $user_type = '';
                            $user_first_name = '';
                            $user_last_name = '';
                            $user_status_was_online = '';
                            $user_access_hash = '';
                            $user_role = '';
                            $user_date = '';
                            $user_source = json_encode($participant, true);

                            if (isset($participant['user']['id'])) $user_id = $participant['user']['id'];
                            if (isset($participant['user']['username'])) $user_username = $participant['user']['username'];
                            if (isset($participant['user']['phone'])) $user_phone = $participant['user']['phone'];
                            if (isset($participant['user']['type'])) $user_type = $participant['user']['type'];
                            if (isset($participant['user']['first_name'])) $user_first_name = $participant['user']['first_name'];
                            if (isset($participant['user']['last_name'])) $user_last_name = $participant['user']['last_name'];
                            if (isset($participant['user']['status']['was_online'])) $user_status_was_online = $participant['user']['status']['was_online'];
                            if (isset($participant['user']['access_hash'])) $user_access_hash = $participant['user']['access_hash'];
                            if (isset($participant['role'])) $user_role = $participant['role'];
                            if (isset($participant['date'])) $user_date = $participant['date'];
                            $mysql_fields = "src_ch_id, user_id, user_username, user_phone, invited, user_type, user_first_name, user_last_name, user_status_was_online, user_access_hash, user_role, user_date, user_source";
                            $mysql_values = "'$src_ch_id','$user_id','$user_username','$user_phone','$invited','$user_type','$user_first_name','$user_last_name','$user_status_was_online','$user_access_hash','$user_role','$user_date','$user_source'";

                            $iteration++;




                            if (mysqli_query($this->mysql_connection, "INSERT INTO inviter_participants ($mysql_fields) VALUES ($mysql_values)"))
                                $inserted_count++;

                        }

                        return [ 'status' => 'success', 'message' => "Inserted: $inserted_count, Processed: $iteration, Total: ".count($participants), 'code' => 4 ];
                    }


                }
            }
            else
            {
                return [ 'status' => 'fail', 'message' => 'No field can_view_participants in result.', 'code' => 4 ];
            }
        }
    }

    public function enableOrDisableSession($session_id, $type)
    {
        if ($type == 'enable')
        {
            if (mysqli_query($this->mysql_connection,"UPDATE inviter_sessions SET enable = 1 WHERE id = $session_id"))
                return [ 'status' => 'success', 'response' => 'Updated.' ];
        }
        elseif ($type == 'disable')
        {
            if (mysqli_query($this->mysql_connection,"UPDATE inviter_sessions SET enable = 0 WHERE id = $session_id"))
                return [ 'status' => 'success', 'response' => 'Updated.' ];
        }
    }

    public function getChannelsFromDB()
    {   
        $channelsFromMysql = mysqli_query($this->mysql_connection, "
            SELECT s_ch.id, s_ch.tg_src_username, s_ch.tg_src_participants_count, s_ch.enable, t_ch.username as ch_username
            FROM inviter_source_channels s_ch
            JOIN inviter_target_channels t_ch
            ON s_ch.ch_id = t_ch.id
        ")->fetch_all(MYSQLI_ASSOC);

        foreach ($channelsFromMysql as $key => $value)
        {
            $res = mysqli_query($this->mysql_connection, "SELECT * FROM inviter_participants WHERE src_ch_id = '".$channelsFromMysql[$key]['id']."'")->fetch_all(MYSQLI_ASSOC);
            $channelsFromMysql[$key]['with_username'] = count($res);
        }

        foreach ($channelsFromMysql as $key => $value)
        {
            $res = mysqli_query($this->mysql_connection, "SELECT * FROM inviter_participants WHERE src_ch_id = '".$channelsFromMysql[$key]['id']."' AND invited != 0")->fetch_all(MYSQLI_ASSOC);
            $channelsFromMysql[$key]['invited_totally'] = count($res);
        }

        foreach ($channelsFromMysql as $key => $value)
        {
            $res = mysqli_query($this->mysql_connection, "SELECT * FROM inviter_participants WHERE src_ch_id = '".$channelsFromMysql[$key]['id']."' AND invited = 1")->fetch_all(MYSQLI_ASSOC);
            $channelsFromMysql[$key]['invited_successfully'] = count($res);
        }

        return $channelsFromMysql;
    }


    public function enableOrDisableLink($link_id, $type)
    {
        if ($type == 'enable')
        {
            if (mysqli_query($this->mysql_connection,"UPDATE inviter_source_channels SET enable = 1 WHERE id = $link_id"))
                return [ 'status' => 'success', 'response' => 'Updated.' ];
        }
        elseif ($type == 'disable')
        {
            if (mysqli_query($this->mysql_connection,"UPDATE inviter_source_channels SET enable = 0 WHERE id = $link_id"))
                return [ 'status' => 'success', 'response' => 'Updated.' ];
        }
    }

    public function getLinksForCron()
    {
        $linksFromMysql = $this->getChannelsFromDB();
        $finalArray = [];
        foreach ($linksFromMysql as $key => $value) // отбираем активные линки
            if ($linksFromMysql[$key]['enable'] == 1)
                array_push($finalArray, $linksFromMysql[$key]);
        return $finalArray;
    }

    public function getSessionsForCron()
    {
        $sessionsFromMysql = $this->getSessionsFromDB();
        $finalArray = [];
        foreach ($sessionsFromMysql as $key => $value) // отбираем активные линки
            if ($sessionsFromMysql[$key]['enable'] == 1)
                array_push($finalArray, $sessionsFromMysql[$key]);
        return $finalArray;
    }

    public function getParticipantsForCron($link_id, $limit)
    {
        /* error codes from inviteToChannel($session_name, $invitee, $channel):
         * code 0: default, when just created
         * code 1, success: Received messageActionChatAddUser
         * code 2: $e->getMessage() (message can be PEER_FLOOD)
         * code 3: There is no error, but no invite too. Probably user has been invited before
         * code 4: Got some updates, but did not received messageActionChatAddUser (maybe should delete this cond from sql)
         * code 21: PEER_FLOOD
         * */
        $sql = "SELECT * FROM inviter_participants WHERE src_ch_id = '$link_id' AND (invited = 0 OR invited = 21) LIMIT $limit";
        $participantsFromMysql = mysqli_query($this->mysql_connection, $sql)->fetch_all(MYSQLI_ASSOC);

        return $participantsFromMysql;
    }

    public function deleteMessageAboutAddingUser($session_name, $channel)
    {
        $this->init($session_name);
        $me = $this->MadelineProto->getSelf();
        $messages = $this->MadelineProto->messages->getHistory([
            'peer' => $channel,
            'offset_id' => 0,
            'offset_date' => 0,
            'add_offset' => 0,
            'limit' => 10,
            'max_id' => 0,
            'min_id' => 0,
            'hash' => 0
        ]);
        $messageIdsToDelete = [];
        foreach ($messages['messages'] as $key => $value)
        {
            if (isset($messages['messages'][$key]['from_id']['user_id']))
            {
                if (isset($messages['messages'][$key]['action']['_']))
                {
                    if (($messages['messages'][$key]['from_id']['user_id'] == $me['id']) && ($messages['messages'][$key]['action']['_'] == 'messageActionChatAddUser'))
                    {
                        array_push($messageIdsToDelete, $messages['messages'][$key]['id']);
                        $res = $this->MadelineProto->channels->deleteMessages(['channel' => $channel, 'id' => [ $messages['messages'][$key]['id'] ]]); // удаляем сообщение с этим id
                        //var_dump($messages['messages'][$key]['id']);
                        //sleep(1);
                    }
                }
            }
        }
    }

>>>>>>> ff00ca8e068a45f3d9bd7fefdab9d43f9c30df3a
}