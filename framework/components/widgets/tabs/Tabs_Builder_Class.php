<?php
namespace SAF\Framework;

abstract class Tabs_Builder_Class
{

	//----------------------------------------------------------------------------------------- build
	/**
	 * Build tabs containing class properties
	 *
	 * @param Reflection_Class $class
	 * @return Tab[]
	 */
	public static function build(Reflection_Class $class)
	{
		$tab_annotations = $class->getAnnotation("group");
		$properties = $class->getAllProperties();
		return self::buildProperties($properties, $tab_annotations);
	}

	//------------------------------------------------------------------------------- buildProperties
	/**
	 * Build tabs containing class properties
	 *
	 * @param Reflection_Property[] $properties
	 * @param Class_Annotation_Tab[] $tab_annotations
	 * @return Tab[]
	 */
	protected static function buildProperties($properties, $tab_annotations)
	{
		if (!empty($tab_annotations)) {
			$tabs = array();
			foreach ($tab_annotations as $tab_annotation) {
				$tab =& $tabs;
				$prec = null;
				$prec_name = null;
				foreach (explode(".", $tab_annotation->name) as $tab_name) {
					if (is_numeric($tab_name)) {
						if (empty($tab->columns)) {
							if (!empty($tab->content)) {
								$tab->columns[0] = new Tab(0, $tab->content);
								$tab->content = array();
							}
						}
						if (!isset($tab->columns[$tab_name])) {
							$tab->columns[$tab_name] = new Tab($tab_name, array());
						}
						$tab =& $tab->columns[$tab_name];
					}
					elseif ($tab instanceof Tab) {
						if (!isset($tab->includes[$tab_name])) {
							$tab->includes[$tab_name] = new Tab($tab_name, array());
						}
						$tab =& $tab->includes[$tab_name];
					}
					else {
						if (!isset($tab[$tab_name])) {
							$tab[$tab_name] = new Tab($tab_name, array());
						}
						$tab =& $tab[$tab_name];
					}
				}
				if (!empty($tab->columns)) {
					if (!isset($tab->columns[0])) {
						$tab->columns[0] = new Tab(0, array());
						ksort($tab->columns);
					}
					$tab =& $tab->columns[0];
				}
				$tab->add(self::getProperties($properties, $tab_annotation->value, $tab_annotation->name));
			}
		}
		else {
			$tabs = array();
		}
		return $tabs;
	}

	//--------------------------------------------------------------------------------- getProperties
	/**
	 * Filter class properties using an array of properties names
	 *
	 * @param Reflection_Property[] $properties
	 * @param string[] $property_names
	 * @param string $tab_path
	 * @return Reflection_Property[]
	 */
	private static function getProperties($properties, $property_names, $tab_path)
	{
		$result = array();
		foreach ($property_names as $property_name) {
			if (isset($properties[$property_name])) {
				$properties[$property_name]->tab_path = $tab_path;
				$result[$property_name] = $properties[$property_name];
			}
		}
		return $result;
	}

}
