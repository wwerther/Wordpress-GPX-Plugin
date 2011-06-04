<?php
// vim: set ts=4 et nu ai syntax=php indentexpr= :vim

# http://www.schoenhoff.org/php-programmierung/streckenberechnung-zwischen-zwei-gps-geokordinaten-mit-php/
# http://www.php-resource.de/forum/xml/86227-gpx-file-lesen-und-daten-mit-php-weiterverarbeiten.html
# http://de.wikipedia.org/wiki/Referenzellipsoid
# http://de.wikipedia.org/wiki/Orthodrome
# http://www.kompf.de/gps/distcalc.html
# http://de.wikipedia.org/wiki/Erdradius
# http://scribu.net/wordpress/optimal-script-loading.html

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
                if ($this->state(-1) != 'TRK') {
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
                array_push($this->state,$tag);
                break;
            }
            case 'TIME': {
                $state=$this->state(-1);
                if (! (($state == 'TRKPT') || ($state == 'METADATA'))) {
                    throw new Exception("INVALID $tag at current position. Please check GPX-File");
                }
                array_push($this->state,$tag);
                break;
            }
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
            case 'GPXTPX:HR': {
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

    public function dump () {
        $data="<!--\n";
        $data.=var_export ($this->meta,true);
        $data.=var_export ($this->track,true);
        $data.='AVG HR:'.$this->averageheartrate().' CAD:'.$this->averagecadence().' ELEV:'.$this->averageelevation();
        $data.="\n-->\n";
        return $data;
    }

}

?>
