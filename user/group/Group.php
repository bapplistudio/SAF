<?php
namespace SAF\Framework\User;

use SAF\Framework\Dao;
use SAF\Framework\Traits\Has_Name;
use SAF\Framework\User\Group\Feature;
use SAF\Framework\User\Group\Low_Level_Feature;

/**
 * User group
 *
 * Used by access control plugins to manage the users access
 *
 * @business
 * @feature
 */
class Group
{
	use Has_Name;

	//------------------------------------------------------------------------------------- $features
	/**
	 * Each link to a feature is stored into the data-link as two strings : its name and path
	 *
	 * @link Map
	 * @var Feature[]
	 */
	public $features;

	//--------------------------------------------------------------------------- getLowLevelFeatures
	/**
	 * Gets all features from $this->includes + $this->features
	 *
	 * @return Low_Level_Feature[]
	 */
	public function getLowLevelFeatures()
	{
		$features = [];
		foreach ($this->features as $feature) {
			$features = array_merge($features, $feature->getAllFeatures());
		}
		return $features;
	}

}
