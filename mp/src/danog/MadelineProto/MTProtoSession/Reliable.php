<?php

/**
 * Reliable module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2020 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto\MTProtoSession;

use danog\MadelineProto\MTProto;
use danog\MadelineProto\Tools;

/**
 * Manages responses.
 */
trait Reliable
{
    /**
     * Called when receiving a new_msg_detailed_info.
     *
     * @param array $content
     * @return void
     */
    public function onNewMsgDetailedInfo(array $content): void
    {
        if (isset($this->incoming_messages[$content['answer_msg_id']])) {
            $this->ackIncomingMessage($this->incoming_messages[$content['answer_msg_id']]);
        } else {
            Tools::callFork($this->objectCall('msg_resend_req', ['msg_ids' => [$content['answer_msg_id']]], ['postpone' => true]));
        }
    }
    /**
     * Called when receiving a msg_detailed_info.
     *
     * @param array $content
     * @return void
     */
    public function onMsgDetailedInfo(array $content): void
    {
        if (isset($this->outgoing_messages[$content['msg_id']])) {
            $this->onNewMsgDetailedInfo($content);
        }
    }
    /**
     * Called when receiving a msg_resend_req.
     *
     * @param array  $content
     * @param string $current_msg_id
     * @return void
     */
    public function onMsgResendReq(array $content, $current_msg_id): void
    {
        $ok = true;
        foreach ($content['msg_ids'] as $msg_id) {
            if (!isset($this->outgoing_messages[$msg_id]) || isset($this->incoming_messages[$msg_id])) {
                $ok = false;
            }
        }
        if ($ok) {
            foreach ($content['msg_ids'] as $msg_id) {
                $this->methodRecall('', ['message_id' => $msg_id, 'postpone' => true]);
            }
        } else {
            $this->sendMsgsStateInfo($content['msg_ids'], $current_msg_id);
        }
    }
    /**
     * Called when receiving a msg_resend_ans_req.
     *
     * @param array  $content
     * @param string $current_msg_id
     * @return void
     */
    public function onMsgResendAnsReq(array $content, $current_msg_id): void
    {
        $this->sendMsgsStateInfo($content['msg_ids'], $current_msg_id);
    }

    /**
     * Called when receiving a msgs_all_info.
     *
     * @param array  $content
     * @return void
     */
    public function onMsgsAllInfo(array $content): void
    {
        foreach ($content['msg_ids'] as $key => $msg_id) {
            $info = \ord($content['info'][$key]);
            $msg_id = MsgIdHandler::toString($msg_id);
            $status = 'Status for message id '.$msg_id.': ';
            /*if ($info & 4) {
             *$this->gotResponseForOutgoingMessageId($msg_id);
             *}
             */
            foreach (MTProto::MSGS_INFO_FLAGS as $flag => $description) {
                if (($info & $flag) !== 0) {
                    $status .= $description;
                }
            }
            $this->logger->logger($status, \danog\MadelineProto\Logger::NOTICE);
        }
    }
    /**
     * Send state info for message IDs.
     *
     * @param array      $msg_ids    Message IDs to send info about
     * @param string|int $req_msg_id Message ID of msgs_state_req that initiated this
     *
     * @return void
     */
    public function sendMsgsStateInfo(array $msg_ids, $req_msg_id): void
    {
        $this->logger->logger('Sending state info for '.\count($msg_ids).' message IDs');
        $info = '';
        foreach ($msg_ids as $msg_id) {
            $cur_info = 0;
            if (!isset($this->incoming_messages[$msg_id])) {
                $msg_id = new \phpseclib3\Math\BigInteger(\strrev($msg_id), 256);
                if ((new \phpseclib3\Math\BigInteger(\time() + $this->time_delta + 30))->bitwise_leftShift(32)->compare($msg_id) < 0) {
                    $this->logger->logger("Do not know anything about {$msg_id} and it is too big");
                    $cur_info |= 3;
                } elseif ((new \phpseclib3\Math\BigInteger(\time() + $this->time_delta - 300))->bitwise_leftShift(32)->compare($msg_id) > 0) {
                    $this->logger->logger("Do not know anything about {$msg_id} and it is too small");
                    $cur_info |= 1;
                } else {
                    $this->logger->logger("Do not know anything about {$msg_id}");
                    $cur_info |= 2;
                }
            } else {
                $this->logger->logger("Know about {$msg_id}");
                $cur_info = $this->incoming_messages[$msg_id]->getState();
            }
            $info .= \chr($cur_info);
        }
        Tools::callFork($this->objectCall('msgs_state_info', ['req_msg_id' => $req_msg_id, 'info' => $info], ['postpone' => true]));
    }
}
