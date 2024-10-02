<?php

if (!class_exists('ESIG_GF_VALUE')):

    abstract class ESIG_GF_VALUE {

        private static $entryId = null;

        public static function setEntryID($ID)
        {
            self::$entryId = $ID;
        }
        public static function getEntryID()
        {
            return self::$entryId;
        }

        public static function generate_value($formid, $field_id, $entry_id, $docId,$oldVersion,$display = "value", $option = "default") {

            $form = GFAPI::get_form($formid);
           
            $field = GFFormsModel::get_field($form, $field_id);

            $lead = GFAPI::get_entry($entry_id);
            if (is_wp_error($lead)) {
                if (empty($docId)) return false;
                $lead = ESIG_GF_SETTINGS::getEntry($docId);
            }

            if (!$lead) {
                return false;
            }
            
            $value = self::get_default_value($lead, $field, $form, $field_id, $display, $option,$oldVersion);

            return $value;
            
        }

        public static function get_field_type($formid, $field_id) {
            $form = GFAPI::get_form($formid);
            $field = GFFormsModel::get_field($form, $field_id);
            return esig_gf_get('type', $field);
        }

        public static function prepareDisplay($display, $value, $label) {
          
            if ($display == "label" && !empty($label)) {
                return $label;
            } elseif ($display == "value" && $value !== false) {
                return $value;
            } elseif ($display == "label_value" && !empty($label) && $value !== false) {
                return $label . ": " . $value;
            } elseif ($display == "label_value" && !empty($label) && $value !== false) {
                return $label . ": ";
            }
            
        }
        
        public static function get_product($lead, $field, $field_id, $form,$display) {
            $value = RGFormsModel::get_lead_field_value($lead, $field);
          
            $display_value = GFCommon::get_lead_field_display($field, $value, $lead['currency']);
          
            if(is_array($value)){
              $label= array_shift($value );
            }
            else {
               $label= esig_gf_get('label', $field);
            }
             
            $ret_value = apply_filters('gform_entry_field_value', $display_value, $field, $lead, $form);
           
            return self::prepareDisplay($display, trim($ret_value,","), $label);
           
        }

        public static function get_html($entries, $field, $field_id, $forms) {
            $html = '';
            if (!empty($field->content)) {
                $content = GFCommon::replace_variables_prepopulate($field->content); // adding support for merge tags
                $content = do_shortcode($content); // adding support for short
                $html .= $content;
            }
               
            $html .= str_replace('{FIELD}', '', GF_Fields::get('html')->get_field_content(esig_gf_get($entries,$field_id), true, $forms));

            return $html;
        }
        
         public static function get_post_image($entries, $field, $field_id, $forms) {
            $value = RGFormsModel::get_lead_field_value($entries, $field);
            return trim($value,"|:");
            
        }


        public static function getRadio($lead, $field, $form, $enableChoiceValue, $display, $label) {
            $defaultValue = self::defaultValue($lead, $field, $form, false, 'text');
            $choices = esig_gf_get('choices', $field);

            if (is_array($choices) && $enableChoiceValue) {
                foreach ($choices as $options) {
                    $text = esig_gf_get("text", $options);
                    $value = esig_gf_get("value", $options);
                    if ($value == $defaultValue) {
                        return self::prepareDisplay($display, $value, $text);
                    }
                }
            }

            return $defaultValue;
        }

        public static function getCheckbox($lead, $field, $form, $enableChoiceValue, $display, $label, $option) {

            if (!$enableChoiceValue && $display == "value") {

                if ($option == "check") {
                    $value = RGFormsModel::get_lead_field_value($lead, $field);

                    if (is_array($value)) {
                        $items = '';
                        foreach ($value as $key => $item) {
                            if (!rgblank($item)) {
                                $items .= '<li><span style="font-size:16px;">&#10003;</span>' . $item . '</li>';
                            }
                        }
                    }
                    return "<ul class='esig-checkbox-tick'>$items</ul>";
                } else {
                
                    return self::defaultValue($lead, $field, $form, false, 'html');
                }
            }
           
            $defaultValue = self::defaultValue($lead, $field, $form, false, 'text');
            $defaultLabel = self::defaultValue($lead, $field, $form, true, 'text');
            return self::prepareDisplay($display, $defaultValue, $defaultLabel);
            
        }

        public static function getMultiSelect($lead, $field, $form, $enableChoiceValue, $display, $option) {
            // return self::defaultValue($lead, $field, $form, false, 'html');
            if (!$enableChoiceValue && $display == "label") {
                return self::defaultValue($lead, $field, $form, false, 'html');
            }

            $defaultValue = self::defaultValue($lead, $field, $form, false, 'text');
            
            $currentValues = explode(",",$defaultValue);
            
            $result = '';
           
            if (!is_array($currentValues)) {
              
                return $defaultValue;
            }

            if ($enableChoiceValue && $display == "label") {
                foreach ($field->choices as $choice) {
                   
                    foreach ($currentValues as $item) {
                        if (RGFormsModel::choice_value_match($field, $choice, $item)) {
                            $result .= $choice['text'] . ', ';
                        }
                    }
                }
            }
            
            if ($enableChoiceValue && $display == "label_value") {
                
                foreach ($field->choices as $choice) {
                    foreach ($currentValues as $item) {
                        if (RGFormsModel::choice_value_match($field, $choice, $item)) {
                            $result .= $choice['text'] . ": " .$item . ', ';
                        }
                    }
                }
            }

            if (!$enableChoiceValue && $display == "label_value") {
               
                foreach ($field->choices as $choice) {
                    foreach ($currentValues as $item) {
                        if (RGFormsModel::choice_value_match($field, $choice, $item)) {
                            $result .= $choice['text'] . ', ';
                        }
                    }
                }
            }
            
            if ($enableChoiceValue && $display == "value") {
                if(is_array($currentValues))
                {
                    
                    foreach ($currentValues as $key => $value) {

                        $result .= $value . ', ';
                    }
                }
                else { return $currentValues ; }
               
            }
            if (!$enableChoiceValue && $display == "value") {
               
                if (is_array($currentValues)) {

                    foreach ($currentValues as $key => $value) {

                        $result .= $value . ', ';
                    }
                } else {
                    return $currentValues;
                }
            }
            return substr($result, 0, strlen($result) - 2);
        }


        public static function displayEditorChoice($lead,$field,$form,$display,$label){
            
                $defaultValue = self::defaultValue($lead, $field, $form, false, 'text');
                $defaultLabel = self::defaultValue($lead, $field, $form, true, 'text');      
            
            
                if(empty($defaultValue)){
                    $value = $defaultLabel;
                }else{
                    $value = $defaultValue;
                }
                
                if ($display == "label" && $label !== false) {
                    return $label;
                } elseif ($display == "value" && $defaultValue !== false) {
                    return $value;
                } elseif ($display == "label_value" && !empty($defaultValue)) {                     
                    return $label . " - " .$value;            
                }  
        }

        public static function remove_map_it($result) {
            return true;
        }

        public static function get_address_fields_label($field){
            $labelField = array();
            $addressInputs = esigget("inputs", $field);
            if (!is_array($addressInputs)) return $labelField;
            foreach ($addressInputs as $value) {

                if (array_key_exists("isHidden", $value) && $value['isHidden'] == 1) {
                    continue;
                }
                $labelField[$value['id']] = $value['label'];
            }
            return $labelField;
        }

        public static function get_address($lead, $field, $field_id, $form,$displayType) 
        {
            add_filter("gform_disable_address_map_link", array(__CLASS__, "remove_map_it"), 10, 1);
            $value = RGFormsModel::get_lead_field_value($lead, $field);
            $getLabelName = self:: get_address_fields_label($field);
            $displayBlock  = apply_filters("esig_gf_display_address_inline","html");
            $address = '';
            if($displayType == "label_value")
            {          
                foreach( $getLabelName as $key=> $labelName){
                    $addressValue = trim(rgget($key, $value));                    
                    $address .= $labelName .': ' . $addressValue .'<br>' ;
                }
                return $address;
            }
            else if($displayType == "label")
            {
                foreach( $getLabelName as $labelName){                                      
                    $address .= $labelName .'<br>' ;
                }
                return $address;
            }

            $display_value = GFCommon::get_lead_field_display($field, $value, $lead['currency'],false, $displayBlock );
            return apply_filters('gform_entry_field_value', $display_value, $field, $lead, $form);
        }
        
         public static function get_number($lead, $field, $field_id, $form) {
           
               $format = esig_gf_get('numberFormat', $field);
               $value = RGFormsModel::get_lead_field_value($lead, $field);
             
               return GFCommon::format_number( $value,  $format,rgar( $lead, 'currency' ),true);
               
        }

        public static function defaultValue($lead, $field, $form, $use_text = false, $format = 'html') {
            $value = RGFormsModel::get_lead_field_value($lead, $field);
            $display_value = GFCommon::get_lead_field_display($field, $value, $lead['currency'], $use_text, $format);
            return apply_filters('gform_entry_field_value', $display_value, $field, $lead, $form);
        }

        public static function get_default_value($lead, $field, $form, $field_id, $display, $option,$oldVersion) {

            //make a condition to check input field
            $type = esig_gf_get('type', $field);
            $label = isset($field->label) ? $field->label : false;
           
            if(empty($type) && !array_key_exists($field_id,$lead)){
                return false;
            }
        
            if(empty($type) && array_key_exists($field_id,$lead)){
                $type = "fieldDeleted";
            }

            
           
            switch ($type):
                case "product":
                    
                    return self::get_product($lead, $field, $field_id, $form,$display);
                case "html":
                    return self::get_html($lead, $field, $field_id, $form);
                case "post_image":
                    return self::get_post_image($lead, $field, $field_id, $form);    
                case "address":
                    return self::get_address($lead, $field, $field_id, $form,$display);
                case "number":
                    $ret_value = self::get_number($lead, $field, $field_id, $form);
                    return self::prepareDisplay($display, $ret_value, $label);   
                case "radio":                   
                    if($oldVersion){
                        $enableChoiceValue = esig_gf_get("enableChoiceValue", $field);
                        return self::getRadio($lead, $field, $form, $enableChoiceValue, $display, $label);
                    }else{
                        return self::displayEditorChoice($lead,$field,$form,$display,$label);
                    }
                case "checkbox":
                    if($oldVersion){
                        $enableChoiceValue = esig_gf_get("enableChoiceValue", $field);
                        return self::getCheckbox($lead, $field, $form, $enableChoiceValue, $display, $label, $option);
                    }else{
                        return self::displayEditorChoice($lead,$field,$form,$display,$label);
                    }
                case "select":
                    if($oldVersion){
                        $enableChoiceValue = esig_gf_get("enableChoiceValue", $field);
                        return self::getRadio($lead, $field, $form, $enableChoiceValue, $display, $label);
                    }else{
                        return self::displayEditorChoice($lead,$field,$form,$display,$label);
                    }

                case "multiselect":
                    if($oldVersion){
                        $enableChoiceValue = esig_gf_get("enableChoiceValue", $field);
                        return self::getMultiSelect($lead, $field, $form, $enableChoiceValue, $display, $option);
                    }else{
                        return self::displayEditorChoice($lead,$field,$form,$display,$label);
                    }
                case "fieldDeleted":
                    return self::displayDeletedField($field_id,$form,$lead) ;
                    break;  
                default:
                    $value = RGFormsModel::get_lead_field_value($lead, $field);
                    $display_value = GFCommon::get_lead_field_display($field, $value, $lead['currency']);
                    $ret_value = apply_filters('gform_entry_field_value', $display_value, $field, $lead, $form);
                    return self::prepareDisplay($display, $ret_value, $label);
            endswitch;
        }

        /**
         *  Display a deleted gravity field . If user deletes  a field from gravity form . It will still 
         *  Grab value from our database and display. For already submitted form.
         *  @param  int $fieldId  It is a Gravity Form field id  
         *  @param  int $form   It is a Gravity Form objet 
         *  @param  array $lead    It is gravity entry array . 
         * 
         *  @return {string/bool} | Value of deleted field entry.  
         */

        public static function displayDeletedField($fieldId,$formId,$lead){
           
             if(array_key_exists($fieldId,$lead)){

                  $fieldValue = esig_gf_get($fieldId,$lead);
                  return $fieldValue; 

             }
             return false;
        }

        public static function display_value($display, $document_id,$fieldType=false) {

            $display_type = ESIG_GF_SETTINGS::get_display_feed($document_id);
            
            if($fieldType == "email" || $fieldType == 'website')
            {
                
                if($display_type == "underline")
                {
                    if (preg_match('/(http|ftp|mailto)/', $display, $matches)) {
                       
                        return "<u>" . str_replace('<a', '<a class="esig-underline-link"', $display) . "</u>";
                    }
                    else {
                        return "<u>" . $display . "</u>";
                    }
                   
                } 
                
            }

            if ($display_type == "underline") {
                return "<u>" . $display . "</u>";
            } else {
                return $display;
            }
        }

    }

    

    

    

    

    

    

    

    

    

    

    

endif;