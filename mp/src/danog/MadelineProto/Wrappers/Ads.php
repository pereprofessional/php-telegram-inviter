<?php

/**
 * Ads module.
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

namespace danog\MadelineProto\Wrappers;

use danog\MadelineProto\Db\DbArray;

/**
 * Manages ads.
 *
 * @property DbArray $sponsoredMessages
 */
trait Ads
{
    /**
     * Get sponsored messages for channel.
     * This method will return an array of [sponsored message objects](https://docs.madelineproto.xyz/API_docs/constructors/sponsoredMessage.html).
     *
     * See [the API documentation](https://core.telegram.org/api/sponsored-messages) for more info on how to handle sponsored messages.
     *
     * @param int|array $peer Channel ID, or Update, or Message, or Peer.
     * @return \Generator
     */
    public function getSponsoredMessages($peer): \Generator
    {
        $peer = (yield from $this->getInfo($peer))['bot_api_id'];
        $cache = yield $this->sponsoredMessages[$peer];
        if ($cache && $cache[0] > \time()) {
            return $cache[1];
        }
        $result = (yield from $this->methodCallAsyncRead('channels.getSponsoredMessages', ['channel' => $peer]))['messages'];
        $this->sponsoredMessages->set($peer, [\time() + 5*60, $result]);
        return $result;
    }
    /**
     * Mark sponsored message as read.
     *
     * @param int|array $peer Channel ID, or Update, or Message, or Peer.
     * @param string|array{random_id: string} $message Random ID or sponsored message to mark as read.
     *
     * @return \Generator Bool
     */
    public function viewSponsoredMessage($peer, $message): \Generator
    {
        if (\is_array($message)) {
            $message = $message['random_id'];
        }
        return $this->methodCallAsyncRead('channels.viewSponsoredMessage', ['channel' => $peer, 'random_id' => $message]);
    }
}
