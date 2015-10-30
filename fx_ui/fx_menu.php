<?php

class FX_Menu {

	var $menu = array();
	
	function add($id = '', $title = '', $header = '', $link = '', $order = 0, $menu_icon_url = '', $page_icon_url = '')
	{
		if (empty($id)||empty($title)) {
			return;
		}
		
		if (empty($header)) {
			$header = $title;
		}
		
		if (empty($link)) {
			$link = $id;
		}
		
		if (!$order && $this -> menu) {	
			list( , , , $order) = end($this -> menu);
			$order += 10;
		}

		$this -> menu[$id] = array('title' => $title,
								   'header' => $header,
								   'link' => $link,
								   'order' => $order,
								   'menu_icon' => $menu_icon_url,
								   'page_icon' => $page_icon_url,
								   'submenu' => array());

		uasort($this -> menu, '_cmp_menu_order');
	}

	function add_submenu($id = '', $sub_id = '', $title = '', $header = '', $link = '', $order = 0)
	{
		if (empty($id)||empty($sub_id)||empty($title)) {
			return;
		}
		
		if (empty($header)) {
			$header=$title;
		}
		
		if (empty($link)) {
			$link=$id;
		}
		
		if (!$order && $this -> menu[$id]['submenu']) {
			list( , , , $order) = end($this -> menu[$id]['submenu']);
			$order += 10;
		}

		$this -> menu[$id]['submenu'][$sub_id] = array('title' => $title, 'header' => $header, 'link' => $link, 'order' => $order);

		uasort($this -> menu[$id]['submenu'], '_cmp_menu_order');
	}

	function get_menu_item($id = '',$sub_id = '')
	{
		if (empty($id) && empty($sub_id)) {
			return;
		}
		
		if ($id && empty($sub_id)) {
			return $this->menu[$id];
		}
		
		if ($id && $sub_id) {
			return $this->menu[$id]['submenu'][$sub_id];
		}
	}
		
	function get_menu()
	{
		return $this->menu;
	}	

	function get_submenu($id = '', $sub_id = '')
	{
		if (empty($id)) {
			return;
		}
		
		if (empty($sub_id)) {
			return $this->menu[$id]['submenu'];
		}
		
		return $this->menu[$id]['submenu'][$sub_id];
	}	
	
	function remove_menu_item($id = '')
	{
		if (empty($id)) {
			return;
		}
		
		unset($this->menu[$id]);
	}	

	function remove_submenu_item($id = '', $sub_id = '')
	{
		if (empty($id)) {
			return;
		}
		
		if (empty($sub_id)) {
			unset($this->menu[$id]['submenu']);
		}
		
		unset($this->menu[$id]['submenu'][$sub_id]);
	}
}

function _cmp_menu_order($a, $b)
{
	if ($a['order'] == $b['order']) {
		return 0;
	}

	return ($a['order'] < $b['order']) ? -1 : 1;
}

function is_fx_menu($object)
{
	if (is_object($object) && is_a($object,'FX_Menu')) {
		return true;
	}

	return false;
}