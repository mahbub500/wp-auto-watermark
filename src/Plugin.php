<?php

namespace WPAutoWatermark;

use WP_Query;
use Exception;

class Plugin {

    

    public function __construct() {

        

        

        
    }

    

    

    

    

    

   

    /* ================= WATERMARK ================= */

    public function auto_watermark_on_upload($metadata, $attachment_id) {
        $this->apply_watermark($attachment_id);
        return $metadata;
    }

    

    
    
}
