<?php

function activate_widget($widget_name, $widget_path, $widget_class)
{
        $schema = $_SESSION['current_schema'];
    
	$active_widgets = get_fx_option('active_widgets_'.$schema, array());
	
	$active_widgets[$widget_name] = array('path' => $widget_path, 'class' => $widget_class);
	
	add_log_message(__FUNCTION__, 'Activate widget - '.$widget_name);
	
	return update_fx_option('active_widgets_'.$schema, $active_widgets);
}

function deactivate_widget($widget_name)
{
        $schema = $_SESSION['current_schema'];
    
	$active_widgets = get_fx_option('active_widgets_'.$schema, array());
	
	if (is_array($active_widgets) && array_key_exists($widget_name, $active_widgets)) {
		unset($active_widgets[$widget_name]);
	}
	
	add_log_message(__FUNCTION__, 'Deactivate widget - '.$widget_name);
	
	return update_fx_option('active_widgets_'.$schema, $active_widgets);
}

function scan_widget_dir($dir, &$widgets)
{
	if (is_dir($dir))
	{
		if ($cur_dir = @opendir($dir))
		{
			while (($item = readdir($cur_dir)) != false)
			{
				$path = $dir.(substr($dir, -1) != '/' ? '/' : '').$item;
				
				if(!in_array($path,$exclude_path))
				{
					if (is_file($path) && $path!==__FILE__)
					{
						$widget_data = implode('',file($path));
						$str = '';
                                                
						if (preg_match('/Widget Name:(.*)$/mi', $widget_data, $str))
						{
							$widget_name = trim($str[1]);

							preg_match('|Description:(.*)$|mi', $widget_data, $str);
							$widget_description = trim($str[1]);
		
							preg_match('|Version:(.*)$|mi', $widget_data, $str);
							$widget_version = trim($str[1]);
							
							preg_match('|Author:(.*)$|mi', $widget_data, $str);
							$widget_author = trim($str[1]);			
		
							preg_match('|Author URL:(.*)$|mi', $widget_data, $str);
							$widget_author_url = trim($str[1]);
		
							preg_match('|License:(.*)$|mi', $widget_data, $str);
							$widget_license = trim($str[1]);
                                                        
                                                        preg_match('|class\s*(.*?)\s*(extends)\s*(FX_Widget)|s', $widget_data, $str);
							$widget_class = trim($str[1]);
                                                        
							$widgets[] = array('path' => $path,
                                                                           'name' => $widget_name,
                                                                           'description' => $widget_description,									   
                                                                           'version' => $widget_version,
                                                                           'author' => $widget_author,
                                                                           'author_url' => $widget_author_url,
                                                                           'license' => $widget_license,
                                                                           'class' => $widget_class);
						}
					}
					elseif (is_dir($path) && $item != '.' && $item != '..') {
						scan_widget_dir($path, $widgets);
					}
				}
			}
		}
	}				
}




abstract class FX_Widget
{
	protected $widget_id;
        protected $widget_class;
        protected $widget_name;
        protected $schema;
	protected $widget_options = array();
	protected $mode = 'options';
	protected $error_last;
        protected $gridster_options = array();  //Only useful for widget init - they change on resize, but not here
        protected $style;
        protected $style_type = 'global';
        
        
        public function __construct($widget_params)
	{
                $this->widget_id = $widget_params['widget_id'] ? $widget_params['widget_id'] : 'widget-'.generate_random_string(10);
                
                
                if (isset($widget_params['widget_class']) && $widget_params['widget_class']) {
                    $this->widget_class = $widget_params['widget_class'];
                } else {
                    //$error_last = new FX_Error('widget_init', "No widget class name passed!");
                    fx_show_error_metabox(_('No widget class name passed'));
                }
                
                if (isset($widget_params['widget_name']) && $widget_params['widget_name']) {
                    $this->widget_name = $widget_params['widget_name'];
                } else {
                    //$error_last = new FX_Error('widget_init', "No widget class name passed!");
                    fx_show_error_metabox(_('No widget name passed'));
                }
                
                if (isset($widget_params['schema'])) {
                    $this->schema = $widget_params['schema'];
                } else {
                    fx_show_error_metabox(_('No schema id passed'));
                }
                
                if (isset($widget_params['style']) && $widget_params['style']) {
                    $this->style = $widget_params['style'];
                }
                
                if (isset($widget_params['style_type']) && $widget_params['style_type']) {
                    $this->style_type = $widget_params['style_type'];
                }
                
                
                if (!empty($widget_params['widget_options'])) {
                        $this->widget_options = $widget_params['widget_options'];
                }
                
                
                if (isset($widget_params['mode']) && $widget_params['mode']) {
                        $this->mode = $widget_params['mode'];
                }
                else {
			$this->mode = 'options';
		}
                
                
                if (isset($widget_params['gridster_options']) && $widget_params['gridster_options']) {
                        $this->gridster_options = $widget_params['gridster_options'];
		} else {
			$this->gridster_options = array("sizeX" =>1, "sizeY" => 1);
		}

		return true;
	}
        
        
        
        
        public function get_header()
        {
                $out = '';
                
                if ($this->mode == 'content') {
                    $out = method_exists($this, 'content_header') ? $this -> content_header($this->widget_options) : 'No header';
                    
                    $edit_bar = '<div class="widget_edit_bar">
                                    <button onclick="editWidget(\''.$this->widget_id.'\');">Edit</button>
                                    <button onclick="deleteWidget(\''.$this->widget_id.'\');">Delete</button>
                                </div>';
                }
                elseif ($this->mode == 'options') {
                    $out = method_exists($this, 'options_header') ? $this -> options_header($this->widget_options) : 'No header';
                }
                
		return '<div class="widget_header_'.$this->widget_class.'">'.$edit_bar.$out.'</div>';
        }
        
        
        
        
        public function get_content()
        {
            
                if ($this->mode == 'content') {
                        $out = method_exists($this, 'content') ? $this -> content($this->widget_options) : 'No content';
                }
                elseif ($this->mode == 'options') {
                        $out = method_exists($this, 'options') ? $this -> options($this->widget_options) : 'No content';
                }
                else {
                        $out = 'Wrong options mode';
                }

                return '<div class="widget_content_'.$this->widget_class.'">'.$out.'</div>';
        }

        
        
        
        
	public function get_footer()
        {
		$out = '';
                
                if ($this->mode == 'content') {
                    $out = method_exists($this, 'content_footer') ? $this -> content_footer($this->widget_options) : 'No footer';
                }
                elseif ($this->mode == 'options') {
                    $out = method_exists($this, 'options_footer') ? $this -> options_footer($this->widget_options) : 'No footer';
                }
		
		return '<div class="widget_footer_'.$this->widget_class.'">'.$out.'</div>';
	}
        
        
        public function get_global_style($args)
        {
            $out = method_exists($this, 'global_widget_style') ? $this -> global_widget_style($args) : 'No global styles';
            return $out;
        }
        
        
        //*************************************************
        public function get_style_options($args = '') {
            $out = method_exists($this, 'style_options') ? $this -> style_options($args) : 'No style optins method defined';
            return $out;
        }
        
        public function get_def_style_param() {
            $out = method_exists($this, 'widget_default_style') ? $this -> widget_default_style() : 'No style params method defined';
            return $out;
        }
        //*************************************************
        
        
        public function get_resize_function() {
            $out = method_exists($this, 'content_resize_fn') ? $this -> content_resize_fn() : 'console.debug("no resize fn for '.$this->widget_class.'");';
            return $out;
        }
        public function get_resize_stop_function() {
            $out = method_exists($this, 'content_resize_stop_fn') ? $this -> content_resize_stop_fn() : 'console.debug("no resize stop fn for '.$this->widget_class.'");';
            return $out;
        }
        
	public function show_widget($return = true)
        {
		$out = '';
                
   		//$out .= $this->get_style().$this->get_header().$this->get_content().$this->get_footer();
                $out .= $this->get_header().$this->get_content().$this->get_footer();
		
		if ($return === true) {
			return $out;
		}
		else {
			echo $out;
		}
	}
        
        
        public function get_widget_pdf($widget_code) {
            return $this -> widget_pdf($widget_code);
        }
	
        
        public static function is_fx_widget() {
            return true;
        }
}

function get_declared_widgets()
{
	$widgets  = array();
	
	foreach(get_declared_classes() as $class_name)
	{
		list($widget_sign, ) = explode('_', $class_name);
		
		if ($widget_sign == 'Widget') {
/*			$widget = new ReflectionClass($class_name);
			
			
			if (get_parent_class($widget) == 'FX_Widget') {
				$widgets[] = $class_name;
				unset($widget);
			}*/
			
			$widgets[] = $class_name;
		}
	}
	
	return $widgets;
}

