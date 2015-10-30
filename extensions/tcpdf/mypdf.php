<?php
    require_once dirname(__FILE__).'/tcpdf.php';

    class MYPDF extends TCPDF {
        
        protected $_footer_data, $_header_data;
        protected $_report_options = array();
        
        public function set_header_data($data) {
            $this->_header_data = str_replace('$$page$$', $this->getAliasNumPage(), $data);
        }
        
        public function set_footer_data($data) {
            $this->_footer_data = str_replace('$$page$$', $this->getAliasNumPage(), $data);
        }
        
        public function set_report_options($options) {
            $this->_report_options = $options;
        }
        public function get_report_options() {
            return $this->_report_options;
        }
        
        public function BackgroundImage() {
            $options = $this->get_report_options();
            
            if ($options['general']['bg_img']!=="none") {
                $page_width = $this->getPageWidth();
                $page_height = $this->getPageHeight();
                $opacity = $options['general']['bg_img_op'];
                $this->setAlpha($opacity);
                switch ($options['general']['bg_img_halign']) {
                    case 'left':
                        $H = 'L';
                        break;
                    case 'cent':
                    default:
                        $H = 'C';
                        break;
                    case 'right':
                        $H = 'R';
                        break;
                }
                switch ($options['general']['bg_img_valign']) {
                    case 'top':
                        $V = 'T';
                        break;
                    case 'cent':
                    default:
                        $V = 'M';
                        break;
                    case 'bot':
                        $V = 'B';
                        break;
                }
                
                $this->Image($options['general']['bg_img_path'], 0, 0, $page_width, $page_height, '', '', '', true, 300, '', false, false, 0, $H.$V, false, false);
                $this->setAlpha(1);
            }
        }
        
        public function Header() {
            
            $this->BackgroundImage();
            
            //$border = array('LRTB'=>array('width'=>0.1));
            $this->SetY(1);
            $this->SetFont('helvetica', 'I', 10);
            $this->writeHTMLCell(0, 0, $this->GetX(), $this->GetY(), $this->_header_data, $border, 2);
            $header_height = $this->GetY()+1;
            $this->SetTopMargin($header_height);
            
            $footer_height = $this->getFooterSize();
            $this->setPageOrientation($this->CurOrientation, false, $footer_height);
        }
        
        public function Footer() {
            //$border = array('LRTB'=>array('width'=>0.1));
            //Draw dummy cell in document to calculate footer height
            
            $footer_height = $this->getFooterSize();
            //Draw actual footer
            $this->SetFont('helvetica', 'I', 8);
            $this->SetY(-$footer_height);
            $this->writeHTMLCell(0, 0, $this->GetX(), $this->GetY(), $this->_footer_data, $border);
            
        }
        
        private function getFooterSize() {
            $this->SetY(1);
            $this->SetFont('helvetica', 'I', 8);
            $this->startTransaction();
            $this->writeHTMLCell(0, 0, $this->GetX(), $this->GetY(), $this->_footer_data, '', 2);
            $final_pos = $this->GetY();
            $this->rollbackTransaction(true);
            $footer_height = $final_pos + 1;
            
            return $footer_height;
        }
        
    }
    

?>