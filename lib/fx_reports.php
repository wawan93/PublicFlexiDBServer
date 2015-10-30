<?php

class FX_Report
{
    protected $_id = -1;
    protected $_code = array();
    protected $_orientation = 'portrait';
    protected $_format = 'a4';
    protected $_name = '';

    public function __construct(
        $report,
        $name = 'Untitled'
    ) {
        if (!is_array($report)) {
            $this->_id = (int)$report;
            $obj = get_object(get_type_id_by_name(0, 'report'), $this->_id);
            $this->_orientation = $obj['orientation'];
            $this->_format = $obj['format'];
            $this->_code = json_decode($obj['code'], true);
            $this->_name = $obj['display_name'];
        } else {
            //may be an error here, throw Exception
            $this->_code = $report;
            $this->_name = $name;
        }
    }

    /**
     * @param string $format 'a4'-'a0', 'letter', '11x17' etc. @see domPDF documentation
     * @param string $orientation 'landscape' or 'portrait'
     * @return FX_Error|string
     */
    public function fx_get_report_pdf(
        $format = '',
        $orientation = ''
    ) {
        if ($format) {
            $this->_format = $format;
        }
        if ($orientation) {
            $this->_orientation = $orientation;
        }
        global $pdf;
        
        include_once CONF_EXT_DIR . '/tcpdf/mypdf.php';
        
        $o = $this->_orientation==="portrait" ? "P" : "L";
        
        $pdf = new MYPDF($o, "mm", "A4", true, 'UTF-8', false);
        $pdf->SetDisplayMode('real');
        
        $report_options = $this->_code['report_options'];
        
        $pdf->set_report_options($report_options);
        $pdf->SetFont('times', '', $pdf->pixelsToUnits(14));
        
        if ($report_options['general']['header'] === "true") {
            $header = urldecode($this->_code['headerFooter']['header']);
            $header = str_replace('$$date$$', date('F j, Y, g:i a'), $header);
            $header = str_replace('$$display_name$$', $this->_name, $header);
            
            $pdf->set_header_data($header);
        } else {
            $pdf->SetPrintHeader(false);
        }
        if ($report_options['general']['footer'] === "true") {
            $footer = urldecode($this->_code['headerFooter']['footer']);
            $footer = str_replace('$$date$$', date('F j, Y, g:i a'), $footer);
            $footer = str_replace('$$display_name$$', $this->_name, $footer);
            
            $pdf->set_footer_data($footer);
        } else {
            $pdf->SetPrintFooter(false);
        }
        
        $pdf->AddPage($o, "A4", true);
        $pdf->SetAutoPageBreak(false);
        if ($report_options['general']['header'] === "true") {
            $pdf->BackgroundImage();
        }
        
        $pdf->SetLineWidth(0.1);
        
        $page_width = $pdf->getPageWidth();
        $page_height = $pdf->getPageHeight();
        $page_margins = $pdf->getMargins();
        $workable_width = $page_width - $page_margins['left'] - $page_margins['right'];
        $workable_height = $page_height - $page_margins['top'] - $page_margins['bottom'];
        $widget_gap = $pdf->pixelsToUnits(5);  //Define gaps between widgets is 5px - convert it to mm
        $columns = $report_options['general']['columns'];
        //Calculate grid size = (page_width - (columns-1)*widget_gap)/columns
        $grid_size = ($workable_width - ($columns-1)*$widget_gap)/$columns;
        
        $max_rows = floor(($workable_height-$widget_gap)/($grid_size+$widget_gap));
        
        $widgets = $this->_code['widgets'];
        foreach ($widgets as $widget) {
            $sizeY = (int)$widget['sizeY'];
            $row = (int)$widget['row'];
            if ($sizeY > $max_rows) {
                //WIDGET TOO BIG FOR PAGE - NOTIFY
                fx_show_error_metabox(_('Some widgets are too big for page - reduce their vertical size'));
            }
            $end_row = $row%$max_rows + $sizeY;
            if ($end_row > $max_rows) {
                $max_rows = $row;
            }
        }
        
        foreach ($widgets as $widget) {
            $schema = $widget['options']['schema'];
            $widget_class = $widget['options']['widget_class'];
            $widget_params = $widget['options'];
            
            //*********************************************************************************************************************************************************
            
            $sizeX = (int)$widget['sizeX'];
            $sizeY = (int)$widget['sizeY'];
            $row = (int)$widget['row'];
            $col = (int)$widget['col'];
            
            unset($widget['sizeX']); unset($widget['sizeY']); unset($widget['row']); unset($widget['col']);
            
            $widget_page = ceil(($row+1)/$max_rows);
                $total_pages = $pdf->getNumPages();
                while ($total_pages<$widget_page) {
                    $pdf->AddPage();
                    if ($report_options['general']['header'] === "true") {
                        $pdf->BackgroundImage();
                    }
                    //$pdf->setPageOrientation('P', false, $footer_height);
                    $total_pages = $pdf->getNumPages();
                }
                $pdf->setPage($widget_page);
                $row = $row%$max_rows;
                $widget_sizeX = $grid_size * $sizeX + ($sizeX-1)*$widget_gap;
                $widget_sizeY = $grid_size * $sizeY + ($sizeY-1)*$widget_gap;
                $widget_posX = $page_margins['left'] + $col*($grid_size+$widget_gap);
                $widget_posY = $page_margins['top'] + $row*($grid_size+$widget_gap);

                $widget['widget_dimensions'] = array("size_x"=>$widget_sizeX, "size_y"=>$widget_sizeY, "pos_x"=>$widget_posX, "pos_y"=>$widget_posY);


                //*********************************************************************************************************************************************************




                //Require widget class
                $active_widgets = get_fx_option('active_widgets_'.$schema, array());
                foreach ($active_widgets as $widget_name=>$widget_info) {
                    if ($widget_info['class'] === $widget_class) {
                        //Requested widget is activated
                        require_once $widget_info['path'];
                    }
                }

                if (class_exists($widget_class)) {  //Check if class has been imported properly
                    $widget_refl = new ReflectionClass($widget_class);
                    $widget_object = $widget_refl->newInstanceArgs(array($widget_params));

                    $widget_object->get_widget_pdf($widget);
                }
        }
        
        $pdf->Output(time().'.pdf', 'I');
        
    }
}