<?php
namespace BlueSpice\Permission;

interface IRole {
	/**
	 * @param PermissionRegistry $permissionRegistry
	 * @return IRole
	 */
	public static function factory( PermissionRegistry $permissionRegistry );

	/**
	 * Returns permission array for the role
	 * @return array
	 */
	public function getPermissions();

	/**
	 * Returns the name of the role
	 * @return string
	 */
	public function getName();

	/**
	 * Adds single permission to the role
	 * @param string
	 */
	public function addPermission( $permission );

	/**
	 * Removes single permission from the role
	 * @param string
	 */
	public function removePermission( $permission );

	/**
	 * @return string[]
	 */
	public function getRequiredPermissions();
}
