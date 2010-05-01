<?php
if ( ! defined('EXT')) exit('Invalid file request');

/**
 * Category Select Class
 * @package   Category Select
 * @author    Andrew Gunstone <andrew@thirststudios.com>
 * @copyright 2010 Andrew Gunstone
 * @license   http://creativecommons.org/licenses/by-sa/3.0/ Attribution-Share Alike 3.0 Unported
 */
 
class Sc_category_select extends Fieldframe_Fieldtype {
 	
	var $info = array(
		'name'             => 'SC Category Select',
		'version'          => '1.1.3:05',
		'desc'             => 'Creates a select menu from a selected EE category (Juxtaprose mod: allows multiselect)',
		'docs_url'         => 'http://sassafrasconsulting.com.au/software/category-select',
		'versions_xml_url' => 'http://sassafrasconsulting.com.au/versions.xml',
		'no_lang'  => FALSE
	);

	var $hooks = array(
		'submit_new_entry_absolute_end'
	);
	
	var $cache = array();

	/**
	 * On saving an entry, delete all categories for this post, then add in categories
	 * from all SC Category Select fields
	 *
	 * @param  int  $entry_id      The entry id
	 * @return nothing
	 */
	function submit_new_entry_absolute_end($entry_id)
	{
		global $DB;

		if (isset($this->cache['cat_del']) AND $this->cache['cat_del'] == 'true')
		{
			$DB->query("DELETE FROM exp_category_posts WHERE entry_id = $entry_id");
		}
		if (isset($this->cache['cat_id']) AND $this->cache['cat_id'] != '')
		{
			$sql = '';
			$cat_ids = explode(',', trim($this->cache['cat_id'],','));
			$cat_ids = array_unique($cat_ids);
			foreach ($cat_ids AS $id):
				$sql .= "($id, $entry_id),";
			endforeach;
			$DB->query("INSERT INTO exp_category_posts (cat_id, entry_id) VALUES ".trim($sql,','));	
//print_r($this->cache['get_parent_ids']);
			if ($this->cache['get_parent_ids'] != '') {
				$gpids = explode(',', trim($this->cache['get_parent_ids'],','));
				$gpids = array_unique($gpids);				$this->_insert_parent_categories($gpids,$entry_id);
			}			
//			$DB->query("SELECT asdhjasdh f");			
		}
	}
	
	/**
	 * Insert Parent Categories
	 *
	 * @param  array  $cat_ids      (child) category ids
	 * @param  string   $entry_id   The entry id
	 */		
	function _insert_parent_categories($cat_ids, $entry_id) 
	{
		global $DB;	
		$sql = '';	
		foreach ($cat_ids AS $id):
			$sql .= $id . ",";
		endforeach;

		$parents = $DB->query("SELECT DISTINCT parent_id FROM exp_categories WHERE cat_id IN (" . trim($sql,',')  .") and parent_id !=0 AND parent_id NOT IN (". trim($sql,',')  .")");

		$newsql = '';

		foreach ($parents->result as $parent):
			$newsql .= "(".$parent['parent_id'].", $entry_id),";
			$cat_ids[] = $parent['parent_id'];
		endforeach;
//print_r($cat_ids);
//echo "<br />";
		if ($newsql != '') {
			$DB->query("INSERT INTO exp_category_posts (cat_id, entry_id) VALUES ".trim($newsql,','));	
			$this->_insert_parent_categories($cat_ids,$entry_id);
		}
	}
 
	/**
	 * Display Field
	 *
	 * @param  string  $field_name      The field's name
	 * @param  mixed   $field_data      The field's current value
	 * @param  array   $field_settings  The field's settings
	 * @return string  The field's HTML
	 */
	function display_field($field_name, $field_data, $field_settings)
	{
	 	global $DSP, $DB;
			
		$group_id = (!isset($field_settings['options'])) ? 0 : $field_settings['options'];
		
		$mode = (!isset($field_settings['mode'])) ? 0 : $field_settings['mode'][0];		

		switch ($mode) {
			case 3: /* Checkboxes - multiselect */

				//make all of these setable in the future
				$show_children = true;
				$level_indent =	'';
				//$level_indent = '&nbsp&nbsp&nbsp&nbsp;&nbsp&nbsp;';

				$all_wrap_attr = ' ' . 'style="overflow: hidden;"';				
				$span_wrap_attr = ' ' . 'style="float: left; width: 220px; height: 24px; font-size: 12px;"';
				$input_attr = '';
				$label_attr = '';

				$opts = $this->_input_checkboxes(0,0,$group_id,$field_data,$field_name, $show_children, $level_indent, $span_wrap_attr, $input_attr, $label_attr);
				
				$r = '<div' . $all_wrap_attr . '>' . $opts[1] . '</div>';
				break;
		
		
			case 2: /* Radio Buttons - single select */
				//make these setable in the future
				// add a check for required field, and
				// add an "unselect" option when not required
				$show_children = true;
				$level_indent =	'';
				//$level_indent = '&nbsp&nbsp&nbsp&nbsp;&nbsp&nbsp;';
				
				$all_wrap_attr = ' ' . 'style="overflow: hidden;"';				
				$span_wrap_attr = ' ' . 'style="float: left; width: 100px; height: 24px; font-size: 12px;"';
				$input_attr = '';
				$label_attr = '';
				
				$opts = $this->_input_radios(0,0,$group_id,$field_data,$field_name, $show_children, $level_indent, $span_wrap_attr, $input_attr, $label_attr);
				
				$r = '<div' . $all_wrap_attr . '>' . $opts[1] . '</div>';
				break;
				
			case 1: /* Dropdown - multiselect */
				$opts = $this->_input_select_options(0,0,$group_id,$field_data);
				
				$size = $opts[0]+1;	
				//make this setable in the future
				if ($size > 6) {
					$size = 6;
				}
				
				$r = $DSP->input_select_header($field_name.'[]',1,$size);
				$r .= $DSP->input_select_option('', '--');
	
				$r .= $opts[1];
				
				$r .= $DSP->input_select_footer();
				break;
			case 0: /* Dropdown - single select */
			default:
				$r = $DSP->input_select_header($field_name);
				$r .= $DSP->input_select_option('', '--');
				
				$opts = $this->_input_select_options(0,0,$group_id,$field_data);
				$r .= $opts[1];
		
				$r .= $DSP->input_select_footer();
		} 
		return $r;
	}

	/**
	 * Display Cell
	 *
	 * @param  string  $cell_name      The cell's name
	 * @param  mixed   $cell_data      The cell's current value
	 * @param  array   $cell_settings  The cell's settings
	 * @return string  The cell's HTML
	 * @author Brandon Kelly <me@brandon-kelly.com>
	 */
	function display_cell($cell_name, $cell_data, $cell_settings)
	{
		return $this->display_field($cell_name, $cell_data, $cell_settings);
	}
	
	/**
	 * Display Field Settings
	 * 
	 * @param  array  $field_settings  The field's settings
	 * @return array  Settings HTML (cell1, cell2, rows)
	 */
	function display_field_settings($field_settings)
	{
		global $DSP, $LANG;
		
		$options = (!isset($field_settings['options'])) ? 0 : $field_settings['options'];

		$mode = (!isset($field_settings['mode'])) ? 0 : $field_settings['mode'];	

		$assign_parents = (!isset($field_settings['assign_parents'])) ? 0 : $field_settings['assign_parents'];	


		$cell = $DSP->qdiv('defaultBold', $LANG->line('select_category_group'))
		    . $this->_select_category($options)
			. $DSP->qdiv('defaultBold', $LANG->line('display_mode'))
		    . $this->_select_mode($mode)
			. $DSP->qdiv('defaultBold', $LANG->line('assign_parents'))
		    . $this->_select_parent_setting($assign_parents);

		$cell = $DSP->qdiv('rel_block', $cell);

		return array('cell1' => '', 'cell2' => $cell);
	}
	
	/**
	 * Display Field Settings
	 * 
	 * @param  array  $cell_settings  The cell's settings
	 * @return string  Settings HTML
	 */
	function display_cell_settings($cell_settings)
	{
		global $DSP, $LANG;

		$options = (!isset($cell_settings['options'])) ? 0 : $cell_settings['options'];

		$mode = (!isset($cell_settings['mode'])) ? 0 : $cell_settings['mode'];	
		
		$assign_parents = (!isset($field_settings['assign_parents'])) ? 0 : $field_settings['assign_parents'];	
				
		$r = '<label class="itemWrapper">'
		   . $DSP->qdiv('defaultBold', $LANG->line('select_category_group'))
		    . $this->_select_category($options)
			. $DSP->qdiv('defaultBold', $LANG->line('display_mode'))
		    . $this->_select_mode($mode)
			. $DSP->qdiv('defaultBold', $LANG->line('assign_parents'))
		    . $this->_select_parent_setting($assign_parents)		    
   		   . '</label>';

		return $r;
	}
	
	/**
	 * Save Field
	 *
	 * @param  mixed   $field_data      The field's data
	 * @param  array   $field_settings  The field's settings
	 * @param  int     $entry_id	    The entry id
	 * @return string  Modified $field_data
	 */
	function save_field($field_data, $field_settings)
	{
		
		$this->cache['cat_del'] = 'true';
		if (is_array($field_data)) {
			foreach ($field_data AS $id):
				$i .=   $id . ",";
			endforeach;
			$this->cache['cat_id'] .= ",".$i;
			$field_data = trim($i,",");
		} else {
			$this->cache['cat_id'] .= ",".$field_data;
		}	
		$this->cache['cat_id'] = trim($this->cache['cat_id'],",");
		if ($field_settings['assign_parents']==1) {
			$this->cache['get_parent_ids'] .= trim($this->cache['cat_id'],",");		
		}

//		echo "yo " . $field_settings['assign_parents'] . " : " . $this->cache['get_parent_ids'] . "<br />";
		return trim($field_data);
	}

	/**
	 * Save Cell
	 *
	 * @param  mixed   $cell_data      The cell's data
	 * @param  array   $cell_settings  The cell's settings
	 * @return string  Modified $cell_data
	 */
	function save_cell($cell_data, $cell_settings)
	{
		return $this->save_field($cell_data, $cell_settings);
	}

	/**
	 * Save Site Settings
	 *
	 * @param  array  $field_settings  The site settings post data
	 * @return array  The modified $site_settings
	 */
	function save_field_settings($field_settings)
	{
		$field_settings['options'] = implode(",", $field_settings["options"]);

		return $field_settings;
	}

	/**
	 * Save Cell Settings
	 *
	 * @param  array  $cell_settings  The site settings post data
	 * @return array  The modified $site_settings
	 */
	function save_cell_settings($cell_settings)
	{
		return $this->save_field_settings($cell_settings);
	}
	
	/**
	 * List all checkboxes
	 * 
	 * @param  int  $parent_id
	 * @param  int  $level
	 * @param  int  $group_id
	 * @param  string  $field_data      Currently saved field value
	 * @param  string  $field_name
	 * @param  boolean  $show_children	 
	 * @param  string  $level_indent	HTML or text prefix children items	 
	 * @param  string  $span_wrap_attr	HTML attributes for span that wraps around each input / label pair
	 * @param  string  $input_attr	HTML attributes for input
	 * @param  string  $label_attr	HTML attributes for label	 
	 * @return array  [0] count of inputs, [1] checkbox inputs corresponding to categories in category group
	 */
	function _input_checkboxes($parent_id,$level,$group_id,$field_data,$field_name, $show_children, $level_indent, $span_wrap_attr, $input_attr, $label_attr)
	{
		global $DSP, $DB;
				
		// fetch all categories
		$categories = $DB->query("SELECT cat_id, cat_name, parent_id, group_id, (SELECT COUNT(cat_id) FROM exp_categories WHERE parent_id = tblCat.cat_id) AS children FROM exp_categories tblCat WHERE parent_id = $parent_id  AND group_id IN ($group_id) ORDER BY group_id, cat_order");
		$r = '';
		$level_label = '';
		$current_group = 0;
		if ($level > 0)
		{
			$counter=0;
			while($counter < $level)
			{
				$level_label .= $level_indent;
				$counter++;
			} 
		}

		$cbCount = 0;
		foreach ($categories->result as $cat):
			if ($current_group == 0) $current_group = $cat['group_id'];
			$selected = '';
			$testSel = explode(',',$field_data);
			if (in_array($cat['cat_id'], $testSel)) {
				$selected = ' checked="checked"';			
			}

			$r .= '<span' . $span_wrap_attr . '>' . $level_label. '<input type="checkbox" class="checkbox" value="'. $cat['cat_id'] .'" name="'. $field_name.'[]" id="'. $field_name. '_'. $cat['cat_id'] . '"' . $selected . $input_attr . ' />';
			
			$r .= ' <label for="' . $field_name. '_'. $cat['cat_id']. '"' . $label_attr . '>' .$cat['cat_name'] . '</label></span>';
			
			$cbCount++;
			
			if ($cat['children'] > 0 && $show_children)
			{
				$xLevel = $level+1;
				$chicb = $this->_input_checkboxes($cat['cat_id'],$xLevel,$group_id,$field_data, $field_name, $show_children, $level_indent, $span_wrap_attr, $input_attr, $label_attr);
				$cbCount = $cbCount + $chicb[0];
				$r .= $chicb[1];
			}
		endforeach;
		return array($cbCount, $r);
	}		
	
	/**
	 * List all radio inputs
	 * 
	 * @param  int  $parent_id
	 * @param  int  $level
	 * @param  int  $group_id
	 * @param  string  $field_data      Currently saved field value
	 * @param  string  $field_name
	 * @param  boolean  $show_children	 
	 * @param  string  $level_indent	HTML or text prefix children items	 
	 * @param  string  $span_wrap_attr	HTML attributes for span that wraps around each input / label pair
	 * @param  string  $input_attr	HTML attributes for input
	 * @param  string  $label_attr	HTML attributes for label	 	 
	 * @return array  [0] count of inputs, [1] radio inputs corresponding to categories in category group
	 */
	function _input_radios($parent_id,$level,$group_id,$field_data,$field_name, $show_children, $level_indent, $span_wrap_attr, $input_attr, $label_attr)
	{
		global $DSP, $DB;
				
		// fetch all categories
		$categories = $DB->query("SELECT cat_id, cat_name, parent_id, group_id, (SELECT COUNT(cat_id) FROM exp_categories WHERE parent_id = tblCat.cat_id) AS children FROM exp_categories tblCat WHERE parent_id = $parent_id  AND group_id IN ($group_id) ORDER BY group_id, cat_order");
		$r = '';
		$level_label = '';
		$current_group = 0;
		if ($level > 0)
		{
			$counter=0;
			while($counter < $level)
			{
				$level_label .= $level_indent;
				$counter++;
			} 
		}

		$radioCount = 0;
		foreach ($categories->result as $cat):
			if ($current_group == 0) $current_group = $cat['group_id'];
			$selected = '';
			$testSel = explode(',',$field_data);
			if (in_array($cat['cat_id'], $testSel)) {
				$selected = ' checked="checked"';			
			}

			$r .= '<span' . $span_wrap_attr . '>' . $level_label. '<input type="radio" class="radio" value="'. $cat['cat_id'] .'" name="'. $field_name.'[]" id="'. $field_name. '_'. $cat['cat_id'] . '"' . $selected . $input_attr . ' />';
			
			$r .= ' <label for="' . $field_name. '_'. $cat['cat_id'] . '"' . $label_attr . '>' .$cat['cat_name'] . '</label></span>';
			
			$radioCount++;
			
			if ($cat['children'] > 0 && $show_children)
			{
				$xLevel = $level+1;
				$chirad = $this->_input_radios($cat['cat_id'],$xLevel,$group_id,$field_data, $field_name, $show_children, $level_indent, $span_wrap_attr, $input_attr, $label_attr);
				$radioCount = $radioCount + $chirad[0];
				$r .= $chirad[1];
			}
		endforeach;
		return array($radioCount, $r);
	}	
	
	/**
	 * List all select options
	 * 
	 * @param  int  $parent_id
	 * @param  int  $level
	 * @param  int  $group_id
	 * @param  string  $field_data      Currently saved field value
	 * @return array  [0] count of options, [1] OPTIONs corresponding to categories in category group
	 */
	function _input_select_options($parent_id,$level,$group_id,$field_data)
	{
		global $DSP, $DB;
				
		// fetch all categories
		$categories = $DB->query("SELECT cat_id, cat_name, parent_id, group_id, (SELECT COUNT(cat_id) FROM exp_categories WHERE parent_id = tblCat.cat_id) AS children FROM exp_categories tblCat WHERE parent_id = $parent_id  AND group_id IN ($group_id) ORDER BY group_id, cat_order");
		$r = '';
		$level_label = '';
		$current_group = 0;
		if ($level > 0)
		{
			$counter=0;
			while($counter < $level)
			{
				$level_label .= "&nbsp&nbsp&nbsp&nbsp;&nbsp&nbsp;";
				$counter++;
			} 
		}
		
		$optionSize = 0;
		foreach ($categories->result as $cat):
			if ($current_group == 0) $current_group = $cat['group_id'];
			if ($current_group != $cat['group_id'])
			{
				$r .= $DSP->input_select_option('', 
											'---');
				$optionSize++;											
				$current_group = $cat['group_id'];
			}
			$isSelected = false;
			$testSel = explode(',',$field_data);
			if (in_array($cat['cat_id'], $testSel)) {
				$isSelected = true;			
			}

			$r .= $DSP->input_select_option($cat['cat_id'],
			$level_label.$cat['cat_name'], $isSelected);
			$optionSize++;
			
			if ($cat['children'] > 0)
			{
				$xLevel = $level+1;
				$chiopt = $this->_input_select_options($cat['cat_id'],$xLevel,$group_id,$field_data);
				$optionSize = $optionSize + $chiopt[0];
				$r .= $chiopt[1];
			}
		endforeach;
		return array($optionSize, $r);
	}


	/**
	 * All category groups
	 * 
	 * @param  int  $current_option
	 * @return string  A list of available category groups
	 */
	function _select_category($options)
	{
		global $DB, $PREFS;

		$site_id = $PREFS->ini('site_id');
		$options = explode(",", $options);
		$block = "<div class='itemWrapper'><select name=\"options[]\" multiple=\"multiple\" style=\"width:45%\" >";
		
		$selected = (in_array(0, $options)) ? " selected=\"true\"" : "";
		$block .= "<option value=\"0\"$selected>None</option>";

		$dls = $DB->query("SELECT group_id, group_name FROM exp_category_groups WHERE site_id = $site_id ORDER BY group_name ASC");
		foreach($dls->result as $dl):
			$selected = (in_array($dl['group_id'], $options)) ? " selected=\"true\"" : "";
			$block .= "<option value=\"{$dl['group_id']}\"$selected>{$dl['group_name']}</option>";
		endforeach;
		
		$block .= "</select></div>";
		
		return $block;
	}


	/**
	 * Get Category Field Entry Mode options
	 * 
	 * @param  int  $current_option
	 * @return string  A list of options
	 */
	function _select_mode($mode)
	{
		if (is_array($mode)) {
			$mode = $mode[0];
		}	
		
		$block = "<div class='itemWrapper'><select name=\"mode\" style=\"width:45%\" >";
		
		$s= " selected=\"true\"";
		$block .= "<option value=\"0\"".(($mode==0) ? $s : ''). ">Dropdown - Single Select</option>";

		$block .= "<option value=\"1\"". (($mode==1) ? $s : '').">Dropdown - Multiselect</option>";

		$block .= "<option value=\"2\"". (($mode==2) ? $s : '').">Radio Buttons - Single Select</option>";

		$block .= "<option value=\"3\"". (($mode==3) ? $s : '').">Checkboxes - Multiselect</option>";

		$block .= "</select></div></div>";
		return $block;
	}

	/**
	 * Get Assign Parent setting options
	 * 
	 * @param  int  $current_option
	 * @return string  A list of options
	 */
	function _select_parent_setting($assign_parents)
	{

		$block = "<div class='itemWrapper'><select name=\"assign_parents\" style=\"width:45%\" >";
		
		$s= " selected=\"true\"";
		$block .= "<option value=\"0\"".(($assign_parents==0) ? $s : ''). ">No</option>";

		$block .= "<option value=\"1\"". (($assign_parents==1) ? $s : '').">Yes</option>";

		$block .= "</select></div></div>";
		return $block;
	}


	/**
	 * Show Heading Tag
	 *
	 * @param  array   $params          Name/value pairs from the opening tag
	 * @param  string  $tagdata         Chunk of tagdata between field tag pairs
	 * @param  string  $field_data      Currently saved field value
	 * @param  array   $field_settings  The field's settings
	 * @return string  relationship references
	 */
	function heading($params, $tagdata, $field_data, $field_settings)
	{
		return $this->_get_category_data($field_data,'cat_name');
	} 

	/**
	 * Show Description Tag
	 *
	 * @param  array   $params          Name/value pairs from the opening tag
	 * @param  string  $tagdata         Chunk of tagdata between field tag pairs
	 * @param  string  $field_data      Currently saved field value
	 * @param  array   $field_settings  The field's settings
	 * @return string  relationship references
	 */
	function description($params, $tagdata, $field_data, $field_settings)
	{
		return $this->_get_category_data($field_data,'cat_description');
	} 

	/**
	 * Show URL Title Tag
	 *
	 * @param  array   $params          Name/value pairs from the opening tag
	 * @param  string  $tagdata         Chunk of tagdata between field tag pairs
	 * @param  string  $field_data      Currently saved field value
	 * @param  array   $field_settings  The field's settings
	 * @return string  relationship references
	 */
	function url_title($params, $tagdata, $field_data, $field_settings)
	{
		return $this->_get_category_data($field_data,'cat_url_title');
	} 

	/**
	 * Get Category Data
	 *
	 * @param  string  $field_data      Currently saved field value
	 * @param  str     $col  The relevant category table column
	 * @return string  relationship references
	 */
	function _get_category_data($field_data,$col)
	{
		global $DB, $PREFS;
		$r = '';
		$query = $DB->query("SELECT $col FROM exp_categories WHERE cat_id = $field_data LIMIT 1");
		if (isset($query->row[$col]) AND trim($query->row[$col]) != '')
			$r = $query->row[$col];
		return $r;
	}

}


/* End of file ft.sc_category_select.php */
/* Location: ./system/extensions/fieldtypes/sc_category_select/ft.sc_category_select.php */