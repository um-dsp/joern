<?php
/**
 * Elgg Groups.
 * Groups contain other entities, or rather act as a placeholder for other entities to
 * mark any given container as their container.
 *
 * @package Elgg.Core
 * @subpackage DataModel.Group
 */

/**
 * Checks if a group has a specific tool enabled.
 * Forward to the group if the tool is disabled.
 *
 * @param string $option     The group tool option to check
 * @param int    $group_guid The group that owns the page. If not set, this
 *                           will be pulled from elgg_get_page_owner_guid().
 *
 * @return void
 * @throws \Elgg\Http\Exception\GroupToolGatekeeperException
 * @since 3.0.0
 */
function elgg_group_tool_gatekeeper($option, $group_guid = null) {
	$group_guid = $group_guid ?: elgg_get_page_owner_guid();
	
	$group = get_entity($group_guid);
	if (!$group instanceof \ElggGroup) {
		return;
	}
	
	if ($group->isToolEnabled($option)) {
		return;
	}

	$ex = new \Elgg\Http\Exception\GroupToolGatekeeperException();
	$ex->setRedirectUrl($group->getURL());
	$ex->setParams([
		'entity' => $group,
		'tool' => $option,
	]);

	throw $ex;
}

/**
 * Allow group members to write to the group container
 *
 * @param \Elgg\Hook $hook 'container_permissions_check', 'all'
 *
 * @return bool
 * @internal
 */
function _elgg_groups_container_override(\Elgg\Hook $hook) {
	$container = $hook->getParam('container');
	$user = $hook->getUserParam();

	if ($container instanceof ElggGroup && $user) {
		if ($container->isMember($user)) {
			return true;
		}
	}
}

/**
 * init the groups library
 *
 * @return void
 *
 * @internal
 */
function _elgg_groups_init() {
	elgg_register_plugin_hook_handler('container_permissions_check', 'all', '_elgg_groups_container_override');
}

/**
 * @see \Elgg\Application::loadCore Do not do work here. Just register for events.
 */
return function(\Elgg\EventsService $events, \Elgg\HooksRegistrationService $hooks) {
	$events->registerHandler('init', 'system', '_elgg_groups_init');
};
