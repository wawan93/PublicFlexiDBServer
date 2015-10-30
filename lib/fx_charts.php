<?php

/**
 * @param $object - id or $_REQUEST
 * @param string $output - 'render', 'stroke' or 'both'
 * @param $g_width - image width
 * @param $g_height - image height
 * @return FX_Error
 */
function draw_chart($object, $output='both', $g_width = false, $g_height = false)
{
    require_once CONF_EXT_DIR."/pChart/class/pData.class.php";
    require_once CONF_EXT_DIR."/pChart/class/pDraw.class.php";
    require_once CONF_EXT_DIR."/pChart/class/pCache.class.php";
    require_once CONF_EXT_DIR."/pChart/class/pImage.class.php";

    $chart_type_id = get_type_id_by_name(0,'chart');

    if(is_numeric($object)){
        $chart_object = get_object($chart_type_id,$object);
        if(is_fx_error($chart_object)){ return new FX_Error(__FUNCTION__, _("There is no chart objects with this ID.")); }
    } elseif(is_array($object)) {
        $chart_object = (array)json_decode($object['obj']);
        if(!$chart_object){ return new FX_Error(__FUNCTION__, "Incorrect object!"); }
    }

    // get chart type from enum (line, pie, etc.)
    $chart_type = get_type_fields($chart_type_id,'all');
    $chart_type = (int)$chart_type['chart_type']['type'];
    $chart_object['chart_type'] = strtolower(get_enum_label($chart_type, (int)$chart_object['chart_type']));

    if(is_numeric($object)){
        $fields = json_decode($chart_object['code'],true);
    } elseif(is_array($object)){
        $fields = json_decode($object['fields'],true);
        if(!$fields){ return new FX_Error(__FUNCTION__, "Incorrect fields!"); }
    }
    $fields = $fields[$chart_object['query_id']];

    $query_object = exec_fx_query((int)$chart_object['query_id'],0);
    if(!$chart_object){ return new FX_Error(__FUNCTION__, "Incorrect query ID!"); }

// sorting by X-axis
    foreach($query_object as $row){
        foreach($row as $k=>$v) {
            $y[$row[$chart_object['x']]][$k] = $v;
        }
    }
    if(empty($y)){ return new FX_Error(__FUNCTION__, "Query is empty!"); }

    ksort($y,SORT_NUMERIC);
    foreach($y as $row) {
        $x[] = $row[$chart_object['x']];
        foreach($fields as $k=>$f) {
            $tmp_y[$k][] = $row[$k];
        }
    }

    $y = $tmp_y;

	if (!$g_width) {
    	$g_width = ($chart_object['g_width'] != 0) ? (int)$chart_object['g_width'] : 700;
	}
	
	if (!$g_height) {
    	$g_height = ($chart_object['g_height'] != 0) ? (int)$chart_object['g_height'] : 230;
	}

    $g_border = !empty($chart_object['g_border']) ? (bool)$chart_object['g_border'] : true;
    $g_aa = !empty($chart_object['g_aa']) ? (bool)$chart_object['g_aa'] : false;
    $g_shadow = !empty($chart_object['g_shadow']) ? (bool)$chart_object['g_shadow'] : true;
    $g_title_enabled = !empty($chart_object['g_title_enabled']) ? (bool)$chart_object['g_title_enabled'] : false;
    $g_title = !empty($chart_object['g_title']) ? $chart_object['g_title'] : "";
    $g_title_align = "TEXT_ALIGN_MIDDLEMIDDLE";
    $g_title_x = $g_width/2;
    $g_title_y = 20;
    $g_title_color = "000000";
    $g_title_font = "Forgotte.ttf";
    $g_title_font_size = 14;
    $g_title_box = false;
    $g_solid_enabled = true;
    $g_solid_color =  !empty($chart_object['g_solid_color']) ? $chart_object['g_solid_color'] : "#AAB757";
    $g_solid_color = strtoupper(substr($g_solid_color,1));
    $g_solid_dashed = !empty($chart_object['g_solid_dashed']) ? (bool)$chart_object['g_solid_dashed'] :  false;
    $g_gradient_enabled = !empty($chart_object['g_gradient_enabled']) ? (bool)$chart_object['g_gradient_enabled'] :  false;
    $g_gradient_start = !empty($chart_object['g_gradient_start']) ? $chart_object['g_gradient_start'] : "#DBE78B";
    $g_gradient_start = strtoupper(substr($g_gradient_start,1));
    $g_gradient_end = !empty($chart_object['g_gradient_end']) ? $chart_object['g_gradient_end'] : '#018A44';
    $g_gradient_end = strtoupper(substr($g_gradient_end,1));
    $g_gradient_direction = 'vertical';
    $g_gradient_alpha = 50;
    $g_transparent = !empty($chart_object['g_transparent']) ? (bool)$chart_object['g_transparent'] :  false;
    $d_serie1_axis = 0;
    $d_axis0_name = '1st axis';
    $d_axis0_unit = '';
    $d_axis0_position = 'left';
    $d_axis0_format = 'AXIS_FORMAT_DEFAULT';
    $s_x = (int)($g_width*0.1);
    $s_y = (int)($g_height*0.15);
    $s_width = (int)($g_width - $s_x*2);
    $s_height = (int)($g_height - $s_y*2);
    $s_direction = 'SCALE_POS_LEFTRIGHT';
    $s_arrows_enabled = false;
    $s_mode = 'SCALE_MODE_FLOATING';
    $s_cycle_enabled = true;
    $s_x_margin = 0;
    $s_y_margin = 0;
    $s_automargin_enabled = 'true';
    $s_x_labeling = 'LABELING_ALL';
    $s_x_skip = 0;
    $s_x_label_rotation = 0;
    $s_grid_color = 'FFFFFF';
    $s_grid_alpha = 50;
    $s_grid_x_enabled = true;
    $s_grid_y_enabled = true;
    $s_ticks_color = '000000';
    $s_ticks_alpha = 50;
    $s_subticks_color = 'FF0000';
    $s_subticks_alpha = 50;
    $s_subticks_enabled = true;
    $s_font = 'pf_arma_five.ttf';
    $s_font_size = 6;
    $s_font_color = '000000';
    $c_family = !empty($chart_object['chart_type']) ? $chart_object['chart_type'] : 'spline';
    $c_display_values = false;
    $c_plot_size = 3;
    $c_border_size = 2;
    $c_border_enabled = true;
    $c_transparency = 50;
    $c_forced_transparency = true;
    $c_around_zero2 = true;
    $c_break = false;
    $c_break_color = 'EA371A';
    $l_enabled = !empty($chart_object['l_enabled']) ? (bool)$chart_object['l_enabled'] : false;
    $l_font = 'pf_arma_five.ttf';
    $l_font_size = 6;
    $l_font_color = '000000';
    $l_margin = 6;
    $l_alpha = 30;
    $l_format = 'LEGEND_NOBORDER';
    $l_orientation = !empty($chart_object['l_orientation']) ? (int)$chart_object['l_orientation'] : 690902;
    $l_box_size = 5;
    $l_position = !empty($chart_object['l_position']) ? (int)$chart_object['l_position'] : 4;
    $l_x = 10;
    $l_y = 10;
//    $p_template = 'navy';
    $l_family = 'LEGEND_SERIE_BOX';
    $sl_enabled = false;
    $sl_shaded = true;
    $sl_caption_enabled = true;
    $sl_caption_line = true;


    $my_data = new pData();

//    if ( $p_template != "default" )
//        $my_data->loadPalette(CONF_EXT_DIR."/pChart/palettes/".$p_template.".color",true);

    $Axis = "";
    foreach($y as $name=>$points){
        $Values = $points;
        foreach($Values as $Value)
        { if ( $Value == "" ) { $Value = VOID; } $my_data->addPoints($Value,$name); }

        $my_data->setSerieDescription($name,$name);
        if($fields[$name]['color']){
            list($R,$G,$B) = chart_extract_colors(strtoupper(substr($fields[$name]['color'],1)));
            $Settings = array("R"=>$R,"G"=>$G,"B"=>$B);
            $my_data->setPalette($name,$Settings);
        }
        if($fields[$name]['width']) {
            $my_data->setSerieWeight($name,(int)$fields[$name]['width']);
        }
        $my_data->setSerieOnAxis($name,$d_serie1_axis);
        $Axis[$d_serie1_axis] = true;
    }

    $Values  = $x;
    foreach($Values as $Value)
    {
        if ( $Value == "" ) { $Value = VOID; }
        $my_data->addPoints($Value,"Absissa");
    }
    $my_data->setAbscissa("Absissa");

    if ( isset($Axis[0]) )
    {
        if ( $d_axis0_position == "left" ) { $my_data->setAxisPosition(0,AXIS_POSITION_LEFT); } else { $my_data->setAxisPosition(0,AXIS_POSITION_RIGHT); }
        $my_data->setAxisName(0,$d_axis0_name);
        $my_data->setAxisUnit(0,$d_axis0_unit);

        if ( $d_axis0_format == "AXIS_FORMAT_METRIC" )	{ $my_data->setAxisDisplay(0,680004); }
        if ( $d_axis0_format == "AXIS_FORMAT_CURRENCY" )	{ $my_data->setAxisDisplay(0,680005,"$"); }
    }

    if ( $g_transparent == true )
        $my_picture = new pImage($g_width,$g_height,$my_data,true);
    else
        $my_picture = new pImage($g_width,$g_height,$my_data);

    if ( $g_aa == false )
    {
        $my_picture->Antialias = false;
    }

    if ( $g_solid_enabled == true )
    {
        list($R,$G,$B) = chart_extract_colors($g_solid_color);
        $Settings = array("R"=>$R,"G"=>$G,"B"=>$B);

        if ( $g_solid_dashed == true ) { $Settings["Dash"] = true; $Settings["DashR"]=$R+20; $Settings["DashG"]=$G+20; $Settings["DashB"]=$B+20; }

        $my_picture->drawFilledRectangle(0,0,$g_width,$g_height,$Settings);

    }

    if ( $g_gradient_enabled == true )
    {
        list($StartR,$StartG,$StartB) = chart_extract_colors($g_gradient_start);
        list($EndR,$EndG,$EndB)       = chart_extract_colors($g_gradient_end);

        $Settings = array("StartR"=>$StartR,"StartG"=>$StartG,"StartB"=>$StartB,"EndR"=>$EndR,"EndG"=>$EndG,"EndB"=>$EndB,"Alpha"=>$g_gradient_alpha);

        if ( $g_gradient_direction == "vertical" )
            $my_picture->drawGradientArea(0,0,$g_width,$g_height,DIRECTION_VERTICAL,$Settings);
        else
            $my_picture->drawGradientArea(0,0,$g_width,$g_height,DIRECTION_HORIZONTAL,$Settings);
    }

    if ( $g_border == true ) { $my_picture->drawRectangle(0,0,$g_width-1,$g_height-1,array("R"=>0,"G"=>0,"B"=>0)); }
    if ( $g_shadow == true ) { $my_picture->setShadow(true,array("X"=>1,"Y"=>1,"R"=>50,"G"=>50,"B"=>50,"Alpha"=>20)); }

    if ( $g_title_enabled == true )
    {
        $my_picture->setFontProperties(array("FontName"=>CONF_EXT_DIR."/pChart/fonts/".$g_title_font,"FontSize"=>$g_title_font_size));

        list($R,$G,$B) = chart_extract_colors($g_title_color);

        $TextSettings = array("Align"=>chart_get_text_align_code($g_title_align),"R"=>$R,"G"=>$G,"B"=>$B);
        if ( $g_title_box == "true" ) { $TextSettings["DrawBox"] = true; $TextSettings["BoxAlpha"] = 30; }

        $my_picture->drawText($g_title_x,$g_title_y,$g_title,$TextSettings);

    }

    /* Scale section */

    if ( $g_shadow == true ) { $my_picture->setShadow(false); }

    $my_picture->setGraphArea($s_x,$s_y,$s_x+$s_width,$s_y+$s_height);

    list($R,$G,$B) = chart_extract_colors($s_font_color);

    $my_picture->setFontProperties(array("R"=>$R,"G"=>$G,"B"=>$B,"FontName"=>CONF_EXT_DIR."/pChart/fonts/".$s_font,"FontSize"=>$s_font_size));

    if($c_family != 'pie') {
        /* Scale specific parameters -------------------------------------------------------------------------------- */
        list($GridR,$GridG,$GridB) = chart_extract_colors($s_grid_color);
        list($TickR,$TickG,$TickB) = chart_extract_colors($s_ticks_color);
        list($SubTickR,$SubTickG,$SubTickB) = chart_extract_colors($s_subticks_color);

        if ( $s_direction == "SCALE_POS_LEFTRIGHT" ) { $Pos = 690101; } else { $Pos = 690102; }
        if ( $s_x_labeling == "LABELING_ALL") { $Labeling = 691011; } else { $Labeling = 691012; }
        if ( $s_mode == "SCALE_MODE_FLOATING" ) { $iMode = 690201; }
        if ( $s_mode == "SCALE_MODE_START0" ) { $iMode = 690202; }
        if ( $s_mode == "SCALE_MODE_ADDALL" ) { $iMode = 690203; }
        if ( $s_mode == "SCALE_MODE_ADDALL_START0" ) { $iMode = 690204; }

        $Settings = array("Pos"=>$Pos,"Mode"=>$iMode,"LabelingMethod"=>$Labeling,"GridR"=>$GridR,"GridG"=>$GridG,"GridB"=>$GridB,"GridAlpha"=>$s_grid_alpha,"TickR"=>$TickR,"TickG"=>$TickG,"TickB"=>$TickB,"TickAlpha"=>$s_ticks_alpha,"LabelRotation"=>$s_x_label_rotation);

        if ( $s_x_skip	!= 0 ) { $Settings["LabelSkip"] = $s_x_skip; }
        if ( $s_cycle_enabled == "true" ) { $Settings["CycleBackground"] = true; }
        if ( $s_arrows_enabled == "true" ) { $Settings["DrawArrows"] = true; }
        if ( $s_grid_x_enabled == "true" ) { $Settings["DrawXLines"] = true; } else { $Settings["DrawXLines"] = 0; }
        if ( $s_subticks_enabled == "true" )
        { $Settings["DrawSubTicks"] = true; $Settings["SubTickR"] = $SubTickR; $Settings["SubTickG"] = $SubTickG; $Settings["SubTickB"] = $SubTickB; $Settings["SubTickAlpha"] = $s_subticks_alpha;}
        if ( $s_automargin_enabled == "false" )
        { $Settings["XMargin"] = $s_x_margin; $Settings["YMargin"] = $s_y_margin; }

        if ( $s_grid_y_enabled == "true" ) { $Settings["DrawYLines"] = ALL; } else { $Settings["DrawYLines"] = NONE; }
        $my_picture->drawScale($Settings);

        /* ---------------------------------------------------------------------------------------------------------- */

        if ( $sl_enabled == "true" )
        {
            $Config = "";
            $Config["CaptionMargin"] = 10;
            $Config["CaptionWidth"]  = 10;

            if ( $sl_shaded == "true" ) { $Config["ShadedSlopeBox"] = true; }
            if ( $sl_caption_enabled != "true" ) { $Config["Caption"] = false; }
            if ( $sl_caption_line == "true" ) { $Config["CaptionLine"] =true; }

            $my_picture->drawDerivative($Config);

        }

    } // not pie

    if ( $g_shadow == "true" ) { $my_picture->setShadow(true,array("X"=>1,"Y"=>1,"R"=>50,"G"=>50,"B"=>50,"Alpha"=>10)); }

    /* Chart specific parameters -------------------------------------------------------------------------------- */
    if ( $c_display_values == "true" ) { $Config = array("DisplayValues"=>true); } else { $Config = ""; }

    if ( $c_family == "pie" )
    {

        require_once CONF_EXT_DIR."/pChart/class/pPie.class.php";
        /* Create the pPie object */
        $pie_chart = new pPie($my_picture,$my_data);

        /* Draw a splitted pie chart */
        $pie_chart->draw3DPie((int)$g_width/2,(int)$g_height/2,array("Radius"=>min($g_width,$g_height)/2,"DataGapAngle"=>12,"DrawLabels"=>true,"DataGapRadius"=>10,"Border"=>true));

    }

    if ( $c_family == "plot" )
    {
        $Config["PlotSize"] = $c_plot_size;
        if ( $c_border_enabled == "true" ) { $Config["PlotBorder"] = true; $Config["BorderSize"] = $c_border_size; }

        $my_picture->drawPlotChart($Config);

    }

    if ( $c_family == "line" )
    {
        if ( $c_break == "true" )
        {
            list($BreakR,$BreakG,$BreakB) = chart_extract_colors($c_break_color);

            $Config["BreakVoid"] = 0;
            $Config["BreakR"] = $BreakR;
            $Config["BreakG"] = $BreakG;
            $Config["BreakB"] = $BreakB;
        }

        $my_picture->drawLineChart($Config);

    }

    if ( $c_family == "spline" )
    {
        if ( $c_break == "true" )
        {
            list($BreakR,$BreakG,$BreakB) = chart_extract_colors($c_break_color);

            $Config["BreakVoid"] = 0;
            $Config["BreakR"] = $BreakR;
            $Config["BreakG"] = $BreakG;
            $Config["BreakB"] = $BreakB;
        }

        $my_picture->drawSplineChart($Config);

    }

    if ( $c_family == "bar" )
    {
        $Config["AroundZero"]=1;
        $my_picture->drawBarChart($Config);

    }

    if ( $c_family == "area" )
    {
        if ( $c_forced_transparency == "true" ) { $Config["ForceTransparency"] = $c_transparency; }
        if ( $c_around_zero2 == "true" ) { $Config["AroundZero"] = true; }

        $my_picture->drawAreaChart($Config);

    }

    if ( $c_family == "filled spline" )
    {
        if ( $c_forced_transparency == "true" ) { $Config["ForceTransparency"] = $c_transparency; }
        if ( $c_around_zero2 == "true" ) { $Config["AroundZero"] = true; }

        $my_picture->drawFilledSplineChart($Config);

    }

    if ( $l_enabled == true )
    {
        list($R,$G,$B) = chart_extract_colors($l_font_color);

        $Config = "";
        $Config["FontR"]    = $R; $Config["FontG"] = $G; $Config["FontB"] = $B;
        $Config["FontName"] = CONF_EXT_DIR."/pChart/fonts/".$l_font;
        $Config["FontSize"] = $l_font_size;
        $Config["Margin"]   = $l_margin;
        $Config["Alpha"]    = $l_alpha;
        $Config["BoxSize"]  = $l_box_size;

        if ( $l_format == "LEGEND_NOBORDER" ) { $Config["Style"] = 690800; }
        if ( $l_format == "LEGEND_BOX" )      { $Config["Style"] = 690801; }
        if ( $l_format == "LEGEND_ROUND" )    { $Config["Style"] = 690802; }

        $Config["Mode"] = $l_orientation;

        if ( $l_family == "LEGEND_FAMILY_CIRCLE" ) { $Config["Family"] = 691052; }
        if ( $l_family == "LEGEND_FAMILY_LINE" ) { $Config["Family"] = 691053; }

        $Size = $my_picture->getLegendSize($Config);
        if ( $l_position == 1 )
        { $l_y = $l_margin + 10; $l_x = $l_margin + 10; }
        if ( $l_position == 2 )
        { $l_y = $g_height - $Size["Height"] - 10 + $l_margin; $l_x = $l_margin + 10; }
        if ( $l_position == 3 )
        { $l_y = $g_height - $Size["Height"] - 10 + $l_margin; $l_x = $g_width - $Size["Width"] - 10 + $l_margin; }
        if ( $l_position == 4 )
        { $l_y = $l_margin + 10; $l_x = $g_width - $Size["Width"] - 10 + $l_margin; }

        if($c_family != 'pie')
            $my_picture->drawLegend($l_x,$l_y,$Config);

    }

    if($output=='render' || $output=='both'){
        if(is_numeric($object)) {
            $id = $object;
        } elseif(is_array($object)) {
            $id = $object['id'];
        }

        $dir = CONF_UPLOADS_DIR.'/'.$chart_type_id.'/'.$id;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $path = $dir . '/chart.png';
        $my_picture->render($path);
        $image = imagecreatefrompng($path);
        $outputFile = $path = $dir . '/chart.jpg';
        imagejpeg($image, $outputFile, 100);
        imagedestroy($image);

    }
    if($output=='stroke' || $output=='both') {
        $my_picture->stroke();
    }

}

function chart_extract_colors($Hexa)
{
    if ( strlen($Hexa) != 6 ) { return(array(0,0,0)); }

    $R = hexdec(chart_left($Hexa,2));
    $G = hexdec(chart_mid($Hexa,3,2));
    $B = hexdec(chart_right($Hexa,2));

    return(array($R,$G,$B));
}

function chart_get_text_align_code($Mode)
{
    if ( $Mode == "TEXT_ALIGN_TOPLEFT" )      { return(690401); }
    if ( $Mode == "TEXT_ALIGN_TOPMIDDLE" )    { return(690402); }
    if ( $Mode == "TEXT_ALIGN_TOPRIGHT" )     { return(690403); }
    if ( $Mode == "TEXT_ALIGN_MIDDLELEFT" )   { return(690404); }
    if ( $Mode == "TEXT_ALIGN_MIDDLEMIDDLE" ) { return(690405); }
    if ( $Mode == "TEXT_ALIGN_MIDDLERIGHT" )  { return(690406); }
    if ( $Mode == "TEXT_ALIGN_BOTTOMLEFT" )   { return(690407); }
    if ( $Mode == "TEXT_ALIGN_BOTTOMMIDDLE" ) { return(690408); }
    if ( $Mode == "TEXT_ALIGN_BOTTOMRIGHT" )  { return(690409); }
}

function chart_left($value,$NbChar)
{ return substr($value,0,$NbChar); }

function chart_right($value,$NbChar)
{ return substr($value,strlen($value)-$NbChar,$NbChar); }

function chart_mid($value,$Depart,$NbChar)
{ return substr($value,$Depart-1,$NbChar); }