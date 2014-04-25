<?php
/**
 * @package Phpf\Common
 */
namespace Phpf\Common;

interface iEventable
{

	/**
	 * Attach a callback ("listen") to an object-namespaced event.
	 *
	 * @param string $event Event name/ID. Classes implementing this interface should
	 * namespace (prefix) their events, delimited with a "." (e.g.
	 * "myComponent.someEvent").
	 * @param callable $callback Callback to execute on event trigger.
	 * @return mixed
	 */
	public function on($event, $callback);

	/**
	 * Trigger an object-namespaced event.
	 *
	 * @param string|Phpf\Event\Event $event Event name/ID or object.
	 * @return mixed
	 */
	public function trigger($event);

}
