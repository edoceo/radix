<?php
/**
    @file
    @brief Tools for working with HTML, mostly for output.
    $Id$
*/

class Radix_HTML
{
    
    public static function select($name,$data,$list,$attr=null)
    {
        $attr = self::_attr_init($name,$attr);
        $html = '<select ' . self::_attr_html($attr) . '>';
        foreach ($list as $k=>$v) {
            $html.= '<option';
            if ($k == $data) {
                $html.= ' selected="selected"';
            }
            $html.= ' value="' . htmlspecialchars($k,ENT_QUOTES) . '">' . htmlspecialchars($v,ENT_QUOTES) . '</option>';
        }
        $html.= '</select>';
        return $html;
    }
    /**
    */
    public static function submit($name,$data=null,$attr=null)
    {
        $args = array('type'=>'submit','value' => $data);
        $attr = self::_attr_init($name,$attr,$args);
        $html = '<input ' . self::_attr_html($attr) . ' />';
        return $html;
    }
    /**
    */
    public static function text($name,$data=null,$attr=null)
    {
        $args = array('type'=>'text','value' => $data);
        $attr = self::_attr_init($name,$attr,$args);
        $html = '<input ' . self::_attr_html($attr) . ' />';
        return $html;
    }
    /**
    */
    /**
        Turns HTML into Formatted Text
        @param string $html
        @return text/plain of HTML that is stripped of tags and formatted pretty(ish)
    */
    static function asText($html)
    {
        $er = error_reporting(0);
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        error_reporting($er);

        $text = self::_html2text_node($dom);

        return $text;
    }
    /**
        Recursive Utility function for html2text
        @param object $node
        @return string text/plain
    */
    private static function _html2text_node($pn)
    {
        $node_post = null;
        $node_text = null;
        $nl = $pn->childNodes->length;
        for ($i=0; $i<$nl; $i++) {
            $cn = $pn->childNodes->item($i);
            //if ($cn == null) continue;
            // Process Nodes
            // echo sprintf('%s #%d/%d',$cn->nodeName,$this->_node_step,count($this->_node_post)) . "\n";

            // Analyse Specific Node Name
            switch ($cn->nodeName) {
            case 'a': // used to create a hyperlink
                $node_post.= ' <' . $cn->getAttribute('href') . '> ';
                break;
            // case 'abbr':
            case 'acronym':
                $x = $cn->getAttribute('title');
                if (!empty($x)) {
                    $node_post = sprintf(' (%s)',$x);
                }
                break;
            // case 'applet':
            // case 'area':
            // case 'b': // deprecated
            // case 'base':
            // case 'basefont': // deprecated
            // case 'bdo':
            // case 'blackface': // deprecated
            case 'blockquote': // used to identify larger amounts of quoted text
                $node_post = "\n";
                break;
            // case 'big': // deprecated
            // case 'body':
            case 'br':
                $node_post = "\n";
                break;
            // case 'button': // used to create button controls for forms
            // case 'caption':
            // case 'center': // deprecated
            // case 'cite':
            // case 'code':
            // case 'col':  // @todo Check Align, Width
            // case 'colgroup': // @todo Check Align, Width
            case 'dd':
                $node_text.= "    ";
                $node_post = "\n";
                break;
            // case 'del':
            // case 'dfn': // dfn - contains the defining instance of the enclosed term.
            // case 'dir': // deprecated
            // case 'div': // div - offers a generic way of grouping areas of content.
            case 'dl': // used to create a list where each item in the list comprises two parts: a term and a description.
                // $node_text.= "\n";
                $node_post = "\n";
                $node_pads = '  ';
                break;
            case 'dt': // dt - is a definition term for an item in a definition list
                $node_text.= "  ";
                $node_post = "\n";
                break;
            // case 'em': // em - is used to indicate emphasis.
            // case 'embed': // deprecated
            // case 'fieldset': // adds structure to forms by grouping together related controls and labels.
            // case 'font': // deprecated
            // case 'form': // is used to create data entry forms.
            // @todo Check Action && Method
            case 'h1':
                $node_text.= "\n# ";
                $node_post = "#\n";
                break;
            case 'h2':
                $node_text.= "\n## ";
                $node_post = "##\n";
                break;
            case 'h3':
                $node_text.= "\n### ";
                $node_post = "###\n";
                break;
            case 'h4':
                $node_text.= "\n#### ";
                $node_post = "####\n";
                break;
            case 'h5':
                $node_text.= "\n##### ";
                $node_post = "#####\n";
                break;
            case 'h6':
                $node_text.= "\n###### ";
                $node_post = "######\n";
                break;
            case 'hr': // used to separate sections of content
                $node_text.= sprintf("\n%s\n", str_repeat('-',72) );
                break;
            // case 'img':
            // case 'meta':
            // case 'input': // a multi-purpose form control
            // case 'ins': // used to mark up content that has been inserted into the current version of a document
            // case 'kbd': // indicates input to be entered by the user.
            // case 'label': // associates a label with form controls such as input, textarea, select and object.
            // case 'legend': // caption to a fieldset element.
            case 'li': // represents a list item in ordered lists and unordered lists
                $node_text.= '  -';
                $node_post = "\n";
                break;
            // case 'link': // conveys relationship information that can be used by Web browsers and search engines
            // case 'map': specifies a client-side image map that may be referenced by elements such as img, select and object.
            // case 'meta':
            // case 'noscript': // allows authors to provide alternate content when a script is not executed.
            // case 'object': // provides a generic way of embedding objects such as images, movies and applications (Java applets, browser plug-ins, etc.) into Web pages.
            case 'ol': // used to create ordered lists
                $node_text.= "\n";
                $node_post = "\n";
                break;
            // case 'optgroup': // used to group the choices offered in select form controls
            // case 'option': // represents a choice offered by select form controls.
            case 'p':
                $node_text.= "\n";
                $node_post = "\n";
                break;
            // case 'param':
            case 'pre':
                $node_text.= "\n";
                $node_post = "\n";
                break;
            // case 'q':
            // case 'rb':
            // case 'rbc':
            // case 'rp':
            // case 'rt':
            // case 'rtc':
            // case 'ruby':
            // case 's': // deprecated
            // case 'samp':
            // case 'script':
            // case 'select':
            // case 'shadow':
            // case 'small':
            // case 'span':
            // case 'strike':
            // case 'strong':
            // case 'style':
            // case 'sub':
            // case 'sup':
            case 'table':
                $node_text.= sprintf("\n%s\n", str_repeat('-',72) );
                $node_post = sprintf("\n%s\n", str_repeat('-',72) );
                break;
            // case 'tbody':
            // case 'td':
            // case 'textarea':
            // case 'tfoot':
            // case 'th':
            // case 'thead':
            case 'title':
                // Remove Title
                $cn->removeChild( $cn->firstChild );
                break;
            case 'tr':
                $node_text.= "\n";
                $node_post = "\n";
                break;
            // case 'tt':
            case 'u': // deprecated
                $node_text.= '_';
                $node_post = '_ ';
                break;
            case 'ul':
                // $node_text.= "\n";
                $node_post = "\n";
                break;
            // case 'var':
            // case 'isindex':
            // case 'layer':
            // case 'menu':
            // case 'noembed':
            // Default Handler!
            default:
                if ($cn->nodeType == XML_TEXT_NODE) {
                    $x = trim(html_entity_decode($cn->nodeValue));
                    if (!empty($x)) {
                        $node_text.= $x;
                    }
                    $node_text.= ' ';
                }
            }

            // Sub-Routine to Check Children Elements
            if ($cn->nodeType == XML_ELEMENT_NODE) {
                $node_text.= self::_html2text_node($cn);
                if (strlen($node_post)) {
                    $node_text.= $node_post;
                    $node_post = null;
                }
            }


        }
        // Close an Open Node
        //if (count($this->_node_post)) {
        //    $this->_text.= array_pop($this->_node_post);
        //}
        //if (count($this->_node_open)) {
        //  $this->_page_dump.= sprintf('</%s>',array_pop($this->_node_open));
        //}
        // Back out and up!
        //return ($dump . "\n");
        return $node_text;
    }
    /**
    */
    private static function _attr_html($attr)
    {
        ksort($attr);
        $buf = array();
        foreach ($attr as $k=>$v) {
            $buf[] = sprintf('%s="%s"',$k,htmlspecialchars($v,ENT_QUOTES));
        }
        return implode(' ',$buf);
    }
    /**
    */
    private static function _attr_init($name,$attr,$args=null)
    {
        if (!is_array($attr)) {
            $attr = array();
        }
        if (!is_array($args)) {
            $args = array();
        }
        $ret = array_merge($attr,$args);
        if (empty($ret['id'])) {
            $ret['id'] = $name;
        }
        if (empty($ret['name'])) {
            $ret['name'] = $name;
        }
        return $ret;
    }

}