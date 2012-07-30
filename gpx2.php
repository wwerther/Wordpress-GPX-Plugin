<?php
// vim: set ts=4 et nu ai syntax=php indentexpr= :vim

# http://www.schoenhoff.org/php-programmierung/streckenberechnung-zwischen-zwei-gps-geokordinaten-mit-php/
# http://www.php-resource.de/forum/xml/86227-gpx-file-lesen-und-daten-mit-php-weiterverarbeiten.html
# http://de.wikipedia.org/wiki/Referenzellipsoid
# http://de.wikipedia.org/wiki/Orthodrome
# http://www.kompf.de/gps/distcalc.html
# http://de.wikipedia.org/wiki/Erdradius
# http://scribu.net/wordpress/optimal-script-loading.html
# http://blog.gauner.org/blog/2007/08/30/xml-dokumente-mit-namespaces-in-php5simplexml-verarbeiten/

# As defined in WGS84 R0
define('GPX_RADIUS',6371000.8);
#define('GPX_RADIUS',6378000.388);
define('GPX_GRAD2RAD',pi()/180);

/* These interfaces seem to cause problems on my host-europe installation. Anyway they are not really necessary
interface Comparable {
    public function compare(self $compare);
}

interface Sortable {
    public static function sort (&$collection);
    public static function sorter ($a,$b);
}
*/

class GPX_helper {
    public static function distance($lat1,$lon1,$lat2,$lon2) {
        if ((($lat1-$lat2)==0) and (($lon1-$lon2)==0)) {
            return 0;
        }
        return acos(sin($lat2*GPX_GRAD2RAD)*sin($lat1*GPX_GRAD2RAD)+cos($lat2*GPX_GRAD2RAD)*cos($lat1*GPX_GRAD2RAD)*cos(($lon2 - $lon1)*GPX_GRAD2RAD));
    }
}

# Comparable,Sortable
class GPX_TRACKPOINT implements ArrayAccess {

    protected $data;

    public function __construct () {
        $this->data['speed']=null;
        $this->data['speed']=null;
        $this->data['cadence']=null;
        $this->data['heartrate']=null;

        $this->data['totalinterval']=0;
        $this->data['totaldistance']=0;
        $this->data['totalrise']=0;
        $this->data['totalfall']=0;
    }

    public function distance (self $trackpoint) {
        $this->data['distance']=GPX_helper::distance($this->data['lat'],$this->data['lon'],$trackpoint['lat'],$trackpoint['lon'])*GPX_RADIUS;
        $this->data['totaldistance']=$this->data['distance']+$trackpoint['totaldistance'];

        # ToDo: This calculation sucks somehow. I got the impression it returns incorrect values
        $this->data['height']=$this->data['elevation']-$trackpoint['elevation'];
        $this->data['totalrise']=$trackpoint['totalrise'];
        $this->data['totalfall']=$trackpoint['totalfall'];
        if ($this->data['height']>0) $this->data['totalrise']+=$this->data['height'];
        if ($this->data['height']<0) $this->data['totalfall']-=$this->data['height'];
        
        $this->data['interval']=abs($this->data['time']-$trackpoint['time']);
        $this->data['totalinterval']=$this->data['interval']+$trackpoint['totalinterval'];
        if ($this->data['interval']>0) {
            $this->data['speed']=($this->data['distance']/$this->data['interval'])*3.6;
        }
    }


    /**
     * Compareable-Interface
     */
    public function compare (self $compare) {
        if ($this['time'] == $compare['time']) {
            return 0;
        }
        return ($this['time'] < $compare['time']) ? -1 : 1;
    }


    /**
     * Sortable-Interface
     */
    public static function sorter ($a,$b) {
        return $a->compare($b);
    }

    public static function sort (&$collection) {
        usort ($collection, array(__CLASS__,'sorter'));
    }

    /**
     * ArrayAccess-Interface
     */
    public function offsetExists ( $offset ) {
        $offset=strtolower($offset);
        return array_key_exists($offset,$this->data);
    }
    
    public function offsetGet (  $offset ) {
        $offset=strtolower($offset);
        return $this->data[$offset];
    }
    
    public function offsetSet ( $offset , $value ) {
        $offset=strtolower($offset);
        switch ($offset) {
            case 'time' : {
                $this->data['time']=strtotime($value);
                break;
             }
            case 'lat' : 
            case 'lon' : 
            case 'elevation' : 
            case 'heartrate' : 
            case 'cadence' : 
            {
                $this->data[$offset]=floatval($value);
                break;
            }
            default: {
                $this->data[$offset]=$value;
            }
        }
    }

    public function offsetUnset ( $offset ) {
        unset ($this->data[$offset]);
    }


    public function return_pair ( $elem ) {
        if (is_null($this->data[$elem])){
            return '['.floor($this->data['time']*1000).', null ]';
        } else {
            return '['.floor($this->data['time']*1000).','.$this->data[$elem].']';
        }
    }

    public function return_assoc ( $elem ) {
        return floor($this->data['time']*1000).':'.$this->data[$elem];
    }

}

class WW_GPX implements Countable, ArrayAccess{

    private $filename=null;
    private $parser=null;

    # The variables needed to keep track of the current state during the SAX-Parse
    private $state=null;

    private $currenttp=null;

    private $maxelem=0;

    public function __construct ($filename) {
        $this->filename=$filename;
        $this->parser = xml_parser_create();

        xml_set_object($this->parser, $this);
        xml_set_element_handler($this->parser, "tag_open", "tag_close");
        xml_set_character_data_handler($this->parser, "cdata");
    }

    public static function mysort($a,$b) {
            if ($a['time']==$b['time']) return 0;
            return ($a['time']<$b['time'])? -1 : 1;
    }

    function parse() {
        $this->state=array('INIT');
        $this->meta=null;
        $this->track->waypoint=array();
        $this->track->track=array();
        $this->currenttp=null;
        $this->meta->cadence=false;
        $this->meta->heartrate=false;
        $this->meta->elevation=false;

        if (! $data=@file_get_contents($this->filename)) {
            return false;
        }

        # First parse the GPX-File
        xml_parse($this->parser, $data);

        # Now change the sorting order of the waypoints, so they are in a chronologic order
        usort ($this->track->waypoint,'WW_GPX::mysort');

        GPX_TRACKPOINT::sort($this->track->track);

        # And now calculate all extended attributes (speed, distance, etc.)
        for ($walk=0;$walk<count($this->track->track);$walk++) {
                $last=$walk-1;
                if ($last<0) $last=0;
                $this->track->track[$walk]->distance($this->track->track[$last]);

        }
        return true;
    }

    private function state($index) {
        if ($index<0) {
            $index=count($this->state)+$index;
        }
        return $this->state[$index];
    }


    public function setmaxelem ($elem) {
        $this->maxelem=$elem;
    }

    function tag_open($parser, $tag, $attributes) {
        $tag=strtoupper($tag);
        switch ($tag) {
             case 'GPX': {
                $this->meta->creator=$attributes['CREATOR'];
                $this->meta->version=$attributes['VERSION'];
                array_push($this->state,$tag);
                # var_dump($attributes); 
                break;        
            }
            case 'METADATA': {
                if ($this->state(-1) != 'GPX') {
                    throw new Exception("INVALID $tag at current position. Please check GPX-File");
                }
                array_push($this->state,$tag);
                break;
            }
            case 'LINK': {
                $this->meta->link='';
                $this->meta->link->href=$attributes['HREF'];
                array_push($this->state,$tag);
                break;
            }
            case 'TRK': {
                if ($this->state(-1) != 'GPX') {
                    throw new Exception("INVALID $tag at current position. Please check GPX-File");
                }
                array_push($this->state,$tag);
                break;
            }
            case 'NAME': {
                if (! (($this->state(-1) == 'TRK') or ($this->state(-1) == 'METADATA'))) {
                    throw new Exception("INVALID $tag at current position. Please check GPX-File");
                }
                array_push($this->state,$tag);
                break;
            }
            case 'TRKSEG': {
                if ($this->state(-1) != 'TRK') {
                    throw new Exception("INVALID $tag at current position. Please check GPX-File");
                }
                array_push($this->state,$tag);
                break;
            }
            case 'TRKPT': {
                if ($this->state(-1) != 'TRKSEG') {
                    throw new Exception("INVALID $tag at current position. Please check GPX-File");
                }
                $this->currenttp=new GPX_TRACKPOINT();
                array_push($this->track->track,$this->currenttp);
                $this->currenttp['lat']=$attributes['LAT'];
                $this->currenttp['lon']=$attributes['LON'];

                array_push($this->state,$tag);
                break;
            }
            case 'ELE': {
                if ($this->state(-1) != 'TRKPT') {
                    throw new Exception("INVALID $tag at current position. Please check GPX-File");
                }
                $this->track->meta->elevation=true;
                array_push($this->state,$tag);
                break;
            }
            case 'TIME': {
                $state=$this->state(-1);
                if (! (($state == 'TRKPT') || ($state == 'METADATA'))) {
#                    throw new Exception("INVALID $tag at current position. Please check GPX-File");
                }
                array_push($this->state,$tag);
                break;
            }
            case 'GPXDATA:HR': 
            case 'GPXTPX:HR': {
                if ($this->state(-1) != 'TRKPT') {
                    throw new Exception("INVALID $tag at current position. Please check GPX-File");
                }
                $this->track->meta->heartrate=true;
                array_push($this->state,$tag);
                break;
            }
            case 'GPXTPX:CAD': {
                if ($this->state(-1) != 'TRKPT') {
                    throw new Exception("INVALID $tag at current position. Please check GPX-File");
                }
                $this->track->meta->cadence=true;
                array_push($this->state,$tag);
                break;
            }
            case 'TEXT': {
                array_push($this->state,$tag);
                # var_dump($parser, $tag, $attributes); 
                break;
            }
            default: {
                # print "TAG-OPEN: $tag \n";
                # var_dump($parser, $tag, $attributes); 
            }
        }
    }

    function cdata($parser, $cdata) {
        # print "CDATA: \n";
        switch ($this->state(-1)) {
            case 'TEXT': {
                if (($this->state(-2)=='LINK') and ($this->state(-3)=='METADATA')) {
                    $this->meta->link->text=$cdata;
                }
                break;
            }
            case 'NAME': {
                $this->meta->name=$cdata;
                break;
            }
            case 'ELE': {
                if ($this->state(-2) == 'TRKPT') {
                    $this->currenttp['elevation']=$cdata;
                    $this->meta->elevation=true;
                }
                break;
            }
            case 'TIME': {
                if ($this->state(-2) == 'TRKPT') {
                    # We want to store the time information as Unix-Timestamp. This makes calculation of speed etc. a lot easier
                    $this->currenttp['time']=$cdata;
                }
                break;
            }
            case 'GPXTPX:CAD': {
                if ($this->state(-2) == 'TRKPT') {
                    $this->currenttp['cadence']=$cdata;
                    $this->meta->cadence=true;
                }
                break;
            }
            case 'GPXTPX:HR': 
            case 'GPXDATA:HR': {
                if ($this->state(-2) == 'TRKPT') {
                    $this->currenttp['heartrate']=$cdata;
                    $this->meta->heartrate=true;
                }
                break;
            }
            default: {
               # var_dump($cdata);
            }
       }
    }

    function tag_close($parser, $tag) {
        $tag=strtoupper($tag);

        switch ($tag) {
            case 'GPX': 
            case 'METADATA': 
            case 'LINK': 
            case 'TEXT': 
            case 'NAME': 
            case 'TRK': 
            case 'TRKSEG': 
            case 'TRKPT': 
            case 'ELE': 
            case 'TIME': 
            case 'GPXTPX:HR': 
            case 'GPXDATA:HR': 
            case 'GPXTPX:CAD':  {
                if (array_pop($this->state)!=$tag) {
                    throw new Exception ("ungültige Schachtelung");
                }
                break;
            }
            default: {
                # print "CLOSE: $tag\n";
                # var_dump($parser, $tag);
            }
        }
    }


    /*
     * Returns the distance between two points on a sphere
     * In case the same point is given we return 0 without further sin and cos calculation. The distance is in a sphere with radius 1, so to get a distance e.g. on earth
     * the returned value has to be multiplied with the average radius of earth
     */
    public static function distance($lat1,$lon1,$lat2,$lon2) {
        if ((($lat1-$lat2)==0) and (($lon1-$lon2)==0)) {
            return 0;
        }
        return acos(sin($lat2*GPX_GRAD2RAD)*sin($lat1*GPX_GRAD2RAD)+cos($lat2*GPX_GRAD2RAD)*cos($lat1*GPX_GRAD2RAD)*cos(($lon2 - $lon1)*GPX_GRAD2RAD));
    }

    public function getall ($needle) {
        $arr=array();
        foreach ($this->track->track as $point) {
            array_push($arr,$point[$needle]);
        } 
        return $arr;
    }

    public function min($series) {
        $data=$this->getall($series);
        return min($data);
    }

    public function max($series) {
        $data=$this->getall($series);
        return max($data);
    }

    public function avg($series) {
        $data=$this->getall($series);
        return sprintf('%.2f',array_sum($data)/count($data));
    }

    public function averageheartrate() {
        $data=$this->getall('heartrate');
        return sprintf('%.2f',array_sum($data)/count($data));
    }

    public function averagecadence() {
        if ($this->meta->cadence) {
            $data=$this->getall('cadence');
            return sprintf('%.2f',array_sum($data)/count($data));
        } else {
            return '';
        }
    }

    public function averageelevation() {
        $data=$this->getall('elevation');
        return sprintf('%.2f',array_sum($data)/count($data));
    }

    public function averagespeed() {
        $avgspeed=$this[-1]['totaldistance']/abs($this[0]['time']-$this[-1]['time']);
        $avgspeed*=3.6;
        return sprintf('%.2f',$avgspeed);
    }

    public function totaldistance() {
        return sprintf('%.2f',$this[-1]['totaldistance']/1000);
    }


    /**
     * Countable-Interface
     */
    public function count() {
        if ($this->maxelem>0 and $this->maxelem<count($this->track->track)) return $this->maxelem;
        return count($this->track->track);
    }

    /**
     * ArrayAccess-Interface
     */
    public function offsetExists ( $offset ) {
        if ($this->maxelem>0 and $this->maxelem<count($this->track->track)) {
            # Wie geht das?
        } else {
            if ($offset>=0) return defined($this->track->track[$offset]);
            if ($offset<0) return defined($this->track->track[count($this->track->track)-$offset]);
        }
    }
    
    public function offsetGet (  $offset ) {
        if ($this->maxelem>0 and $this->maxelem<count($this->track->track)) {
            $noff=$offset;
            if ($offset<0) $noff=$this->maxelem+$offset;
            
            $factor=count($this->track->track)/$this->maxelem;
            
            return $this->track->track[floor($factor*$noff)];

        } else {
            if ($offset>=0)  return $this->track->track[$offset]; 
            if ($offset<0) return $this->track->track[count($this->track->track)+$offset]; 
        }
    }
    
    public function offsetSet ( $offset , $value ) {
        $this->track->track[$offset]=$value;
    }

    public function offsetUnset ( $offset ) {
        unset ($this->track->track[$offset]);
    }

    public function return_pair ($elem) {
        $arr=array();
        for ($c=0;$c<count($this);$c++) {
            array_push($arr,$this[$c]->return_pair($elem));
        }
        return $arr;
    }

    public function return_assoc ($elem) {
        $arr=array();
        for ($c=0;$c<count($this);$c++) {
            array_push($arr,$this[$c]->return_assoc($elem));
        }
        return $arr;
    }

    public function getunavailable () {
        $unavailable=array();
        if (! $this->meta->heartrate) array_push($unavailable,'heartrate');
        if (! $this->meta->cadence) array_push($unavailable,'cadence');
        return $unavailable;
    }

    public function contain($series) {
        if ($series=='heartrate') return $this->meta->heartrate;
        if ($series=='cadence') return $this->meta->cadence;
        if ($series=='elevation') return $this->meta->elevation;
        if ($series=='speed') return true;
        if ($series=='distance') return true;
        return '';
    }

    public function dump () {
        $data="<!--\n";
        $data.=var_export ($this->meta,true);
        $data.=var_export ($this->track,true);
        $data.='AVG HR:'.$this->averageheartrate().' CAD:'.$this->averagecadence().' ELEV:'.$this->averageelevation();
        $data.="\n-->\n";
        return $data;
    }

}


define('GPX_ALLTRACKS',null);
define('GPX_ALLSEGMENTS',null);


define('XMLREMOVE','XMLREMOVE_REMOVE_THIS_ITEM');


define('XMLelementprefix','GPX_');

class ww_XMLelement extends SimpleXMLElement {
    
    // taken from http://www.php.net/manual/en/book.simplexml.php
    /*
     * Returns this object as an instance of the given class.
     */
    public function asInstanceOf($class_name) {
        // should check that class_name is valid
        return simplexml_import_dom(dom_import_simplexml($this), $class_name);
    }

    public function addNamespace($prefix,$namespace,$xsd=null) {
        $namespaces=$this->getDocNamespaces(true);

        if (array_key_exists($prefix,$namespaces)) {
            # echo "Namespace-prefix $prefix already exists";
            return $this;
        }

        $classname=get_class($this);
        $this->addattribute('xmlns:xmlns:'.$prefix,$namespace);
        if (! is_null($xsd) ) {
            $schemaLocation=$this->attributes('http://www.w3.org/2001/XMLSchema-instance')->schemaLocation; 
            $schemaLocation.=" $namespace $xsd";
            $this->attributes('http://www.w3.org/2001/XMLSchema-instance')->schemaLocation=$schemaLocation;
        }
        return simplexml_load_string($this->asXML(),$classname);
    }

    public function __call($name, array $arguments) {
        // class could be mapped according $this->getName()
        $class_name = XMLelementprefix.$this->getName();
        
        if (class_exists($class_name)) {
            if (method_exists($class_name,$name)) {
                echo "magic __call called for method '$name' on instance of '".get_class()."' for class '".$this->getName()."'\n";    
                $instance = $this->asInstanceOf($class_name);
                return call_user_func_array(array($instance, $name), $arguments);
            }
            throw new Exception('Method '.$name.' does not exist in class '.$class_name);
        }
        #echo "magic __call failed for method '$name' on instance of '".get_class()."' for class '".$this->getName()."'\n";    
        throw new Exception('Class ' . $class_name . ' does not exist');
    }

}

class GPX_gpx extends ww_XMLelement {

    public function getpoints() {
        $datapoints=array();
        $min=0;
        $max=count($this->trk);
        print "trk $min-$max\n";
        for ($tc=$min;$tc<$max;$tc++) {
            print "runpoint $tc";
            print get_class($this->trk[$tc]);
            #]->getpoints();
        }
        return $datapoints;
    }
}

class GPX_trkpt extends ww_XMLelement {
/*
<trkpt lat="48.161728" lon="11.751040">
<ele>521.65</ele>
<time>2011-04-25T15:54:53Z</time>
<extensions>
<gpxtpx:TrackPointExtension>
<gpxtpx:hr>90</gpxtpx:hr>
</gpxtpx:TrackPointExtension>
</extensions>
</trkpt>
*/

/* 
 * Garmin extensions v3
 *
 * Garmin Trackpoint Extension v1
 *      http://www.garmin.com/xmlschemas/TrackPointExtension/v1
 * 
 * atemp    -> ambient temperature (Celsius)
 * wtemp    -> water temperature (Celsius)
 * depth    -> depth (meters)
 * hr       -> heartrate
 * cad      -> Cadence
 */

    public function hr($newval=null) {
        $child=$this->extensions[0]->children('http://www.garmin.com/xmlschemas/TrackPointExtension/v1');

        if ($newval==XMLREMOVE) {
            unset($child->TrackPointExtension->hr);
            return;
        }

        if (! is_null($newval)) {
            $child->TrackPointExtension->hr=$newval;
        }

        return $child->TrackPointExtension->hr;
    }

    public function cad($newval=null) {
        $child=$this->extensions[0]->children('http://www.garmin.com/xmlschemas/TrackPointExtension/v1');

        if ($newval==XMLREMOVE) {
            unset($child->TrackPointExtension->cad);
            return;
        }

        if (! is_null($newval)) {
            $child->TrackPointExtension->cad=$newval;
        }

        return $child->TrackPointExtension->cad;
    }

    public function atemp($newval=null) {
        $child=$this->extensions[0]->children('http://www.garmin.com/xmlschemas/TrackPointExtension/v1');

        if ($newval==XMLREMOVE) {
            unset($child->TrackPointExtension->atemp);
            return;
        }

        if (! is_null($newval)) {
            $child->TrackPointExtension->atemp=$newval;
        }

        return $child->TrackPointExtension->atemp;
    }

    public function wtemp($newval=null) {
        $child=$this->extensions[0]->children('http://www.garmin.com/xmlschemas/TrackPointExtension/v1');

        if ($newval==XMLREMOVE) {
            unset($child->TrackPointExtension->wtemp);
            return;
        }

        if (! is_null($newval)) {
            $child->TrackPointExtension->wtemp=$newval;
        }

        return $child->TrackPointExtension->wtemp;
    }

    public function depth($newval=null) {
        $child=$this->extensions[0]->children('http://www.garmin.com/xmlschemas/TrackPointExtension/v1');

        if ($newval==XMLREMOVE) {
            unset($child->TrackPointExtension->depth);
            return;
        }

        if (! is_null($newval)) {
            $child->TrackPointExtension->depth=$newval;
        }

        return $child->TrackPointExtension->depth;
    }

    public function speed($newval=null) {
        $child=$this->extensions[0]->children('http://wwerther.de/xmlschemas/TrackPointExtension/v1');
        
        if ($newval==XMLREMOVE) {
            unset($child->TackPointExtension->speed);
            return;
        }

        if (! is_null($newval)) {
            $child->TackPointExtension->speed=$newval;
        }

        return $child->TackPointExtension->speed;
    }


    public function ele($newval=null) {
        if ($newval==XMLREMOVE) {
            unset($this->ele);
            return;
        }

        if (! is_null($newval)) {
            $this->ele=$newval;
        }

        return $this->ele;
    }

    public function time($newval=null) {
        if ($newval==XMLREMOVE) {
            unset($this->time);
            return;
        }

        if (! is_null($newval)) {
            # 2011-04-25T15:54:53Z
            $this->time=strftime('%Y-%m-%dT%H:%M:%SZ',$newval);
        }

        return strtotime($this->time);
    }

    public function lat($newval=null) {
        if ($newval==XMLREMOVE) {
            unset($this['lat']);
            return;
        }

        if (! is_null($newval)) {
            $this['lat']=$newval;
        }

        return $this['lat'];
    }

    public function lon($newval=null) {
        if ($newval==XMLREMOVE) {
            unset($this['lon']);
            return;
        }

        if (! is_null($newval)) {
            $this['lon']=$newval;
        }

        return $this['lon'];
    }

}

class Test extends ww_XMLelement {
    public function setValue($string) {
        $this->{0} = $string;
    }
}

class GPX2 {

    private $filename=null;
    private $xml=null;

    public function __construct ($filename) {
        $this->filename=$filename;
        $this->xml = simplexml_load_file($filename,'ww_XMLelement',LIBXML_NOBLANKS);
    }


    public function get_trackpoints($track=GPX_ALLTRACKS,$segment=GPX_ALLSEGMENTS) {
    
        if ($track==GPX_ALLTRACKS) {
            foreach ($this->xml->trk as $trk) {
                
            }
        } else {
            $trk=$this->xml->trk[$track];

        }
    
    }

    private function __get_tp ($trk,$segment) {
        if ($segment==GPX_ALLSEGMENTS) {
            foreach ($trk->trkseg as $trkseg) {
            }
        } else {
            $trkseg=$trk->trkseg[$trkseg];
        }
    }

    public function statistics() {
        $text='';
        
        $text.='Statistic of '.$this->filename."\n";

        $text.=var_export($this->xml->getdocnamespaces(true),true);

#      $text.='tc '.$this->xml->get_trk_count()."\n";

        $text.='instance '.get_class($this->xml)."\n";

        #$this->xml->trk[0]->set_name('Hallo Walter');

#        $text.='hr '.$this->xml->trk[0]->trkseg[0]->trkpt[0]->hr()."\n";

        $text.='trackpoints: '.$this->xml->getpoints();

        #foreach ($this->xml->trk as $trk) {
            #$trk=$trk->asInstanceOf('GPX_trk');
        #    $text.='trks '.var_export($trk)."\n";
        #}

        return $text;
    }

    public function asXML() {
        $dom = dom_import_simplexml($this->xml)->ownerDocument;
        $dom->formatOutput = true;
        return $dom->saveXML();
    
        #return $this->xml->asXML();
    }

    public function getall ($needle) {
        $arr=array();
        foreach ($this->xml->trk[0]->trkseg[1] as $point) {
            array_push($arr,$point->lat());
        } 
        return $arr;
    }
}


class xmlelement implements RecursiveIterator , Countable, ArrayAccess {

    private $attributes=null;
    private $children=null;
    private $namespaces=null;

    private $name='';
    
    public function __construct ($data) {
        $this->attributes=array();
    }

    public function load_string($data) {
        $reader = new XMLReader();
        $reader->XML($data);


        $tree = null;
        while($xml->read()) {
            switch ($xml->nodeType) {
                case XMLReader::END_ELEMENT: return $tree;
                case XMLReader::ELEMENT:
                    $node = array('tag' => $xml->name, 'value' => $xml->isEmptyElement ? '' : xml2assoc($xml));
                    if($xml->hasAttributes)
                        while($xml->moveToNextAttribute())
                            $node['attributes'][$xml->name] = $xml->value;
                            $tree[] = $node;
                break;
                case XMLReader::TEXT:
                case XMLReader::CDATA:
                    $tree .= $xml->value;
            }
            return $tree; 
        }

    }

    public function getChildren() {
    }
            
    public function hasChildren () {
    }
     
    public function getName() {
        return $this->name; 
    }

    public function current() {
    }

    public function key () {
    }

    public function next() {
    }

    public function rewind() {
   
    }
    
    public function valid() {

    }
    
    public function count() {

    }

    public function curnamespace($current) {
        $this->currentnamespace=$current;
    }

    public function offsetExists ( $offset ) {
        return array_key_exists($this->attributes[$this->currentnamespace],$offset);
    }

    public function offsetGet ( $offset ) {
        return $this->attributes[$this->currentnamespace][$offset];
    }

    public function offsetSet ( $offset , $value ) {
        $this->attributes[$this->currentnamespace][$offset]=$value;    
    }

    public function offsetUnset ( $offset ) {
        unset($this->attributes[$this->currentnamespace][$offset]);        
    }
    
    public function asXML() {
        $writer=new XMLWriter();
        # $writer->setIndent(true);
        $writer->openMemory();

        $writer->startDocument('1.0','UTF-8');
        # $writer->startdtd('html','-//WAPFORUM//DTD XHTML Mobile 1.0//EN', 'http://www.wapforum.org/DTD/xhtml-mobile10.dtd');
        # $writer->enddtd();
        
        $this->__xml($writer);

        $writer->endDocument();
        return $writer->outputMemory(TRUE);
    }

    public function __xml($writer) {
        # print "__xml\n";
        $writer->startelement($this->getName());

        # $writer->writeattribute( 'xmlns', 'http://www.wapforum.org/DTD/xhtml-mobile10.dtd');
        $writer->writeattribute( 'xm:lang', 'en');

        foreach ($this->attributes as $nspaceuri=>$attributes) {
            foreach ($attributes as $key=>$value) {
                $writer->startAttributeNS ( 'prefix' , $value , $nspaceuri );
                $writer->text($value);
                $writer->endAttribute();
            }
        }
        
 $writer->startAttributeNS ( '' , 'nae', 'http://wwerther.de/uri' );

        $writer->endAttribute();

        $writer->startElementNS ( 'prefix' , $this->getName(), 'http://wwerther.de/uri' );
        # $writer->startelement($this->getName());

        # xmlwriter_write_attribute( $writer, 'xmlns', 'http://www.wapforum.org/DTD/xhtml-mobile10.dtd');
        # xmlwriter_write_attribute( $writer, 'xm:lang', 'en');

        $writer->endelement();
        $writer->endelement();
    }

}

/*
$xml = new ww_XMLelement('<example><test/></example>');
$test = $xml->test->asInstanceOf('Test');
echo 'xml-test   '.get_class($xml->test), "\n";
echo 'test-cast  '.get_class($test), "\n";

$test->setValue('value set directly by instance of Test');
echo (string)$xml->test, "\n";
echo (string)$test, "\n";

$xml->test->setValue('value set by instance of XmlClass and magic __call');
echo (string)$xml->test, "\n";
echo (string)$test, "\n";

 
*/

$filename='./test/heartrate_pretty.gpx';
#   $filename='./test/long.gpx';

$filename='./test/heartrate.gpx';


if (file_exists($filename)) {
//    $xml = simplexml_load_file($filename);
//	$doc = new DOMDocument();
//	$doc->load($filename,LIBXML_NOBLANKS);
    #$gpx=new GPX($filename);
} else {
   exit('Konnte test.xml nicht Ã¶ffnen.');
}

$gpx=new xmlelement('');
print nl2br(htmlspecialchars($gpx->asXML()));
//$doc->formatOutput=true;

//echo htmlspecialchars($doc->saveXML());


#$xml->registerXPathNamespace('c', 'http://www.garmin.com/xmlschemas/WaypointExtension/v1');

/*
foreach ($xml->trk->trkseg->trkpt as $data) {
    $extensions=$data->extensions;
    $child=$extensions->children('http://www.garmin.com/xmlschemas/TrackPointExtension/v1');
    var_dump( $extensions->getNamespaces(TRUE) );
    var_dump( $child );
}
*/

# var_dump($gpx);
#        $this->xml=$this->xml->addNamespace('ww','http://wwerther.de/xmlschemas/TrackPointExtension/v1');
print '<pre>';
#print $gpx->statistics();

#$xml->addchild('ww:Walter','test','http://wwerther.de/extensions/');

#$namespaces = $xml->getNamespaces(TRUE);
#var_dump($namespaces);

#print nl2br(htmlspecialchars($gpx->asXML()));

#var_dump($xml);

#print nl2br(var_export($gpx->getall('hr')));
print '</pre>';


?>
