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

class WW_GPX {

    private $filename=null;
    private $parser=null;

    # The variables needed to keep track of the current state during the SAX-Parse
    var $state=null;

    public function __construct ($filename) {
        $this->filename=$filename;
        $this->parser = xml_parser_create();

        xml_set_object($this->parser, $this);
        xml_set_element_handler($this->parser, "tag_open", "tag_close");
        xml_set_character_data_handler($this->parser, "cdata");
    }

    function parse() {
        $this->state=array('INIT');
        $this->meta=null;
        $this->track->waypoint=array();
        $data=file_get_contents($this->filename);
        xml_parse($this->parser, $data);
    }

    private function state($index) {
        if ($index<0) {
            $index=count($this->state)+$index;
        }
        return $this->state[$index];
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
                $data['lat']=$attributes['LAT'];
                $data['lon']=$attributes['LON'];
                array_push($this->track->waypoint,$data);
                $this->cursor=count($this->track->waypoint)-1;
                $last=$this->cursor-1;
                if ($last<0) $last=0;
                $this->track->waypoint[$this->cursor]['distance']=$this->distance($data['lat'],$data['lon'],$this->track->waypoint[$last]['lat'],$this->track->waypoint[$last]['lon'])*GPX_RADIUS;
                $this->track->waypoint[$this->cursor]['totaldistance']=0;
                $this->track->waypoint[$this->cursor]['totaldistance']=$this->track->waypoint[$last]['totaldistance']+$this->track->waypoint[$this->cursor]['distance'];
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
                array_push($this->state,$tag);
                break;
            }
            case 'GPXTPX:CAD': {
                if ($this->state(-1) != 'TRKPT') {
                    throw new Exception("INVALID $tag at current position. Please check GPX-File");
                }
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
            case 'ELE': {
                if ($this->state(-2) == 'TRKPT') {
                    $this->track->waypoint[$this->cursor]['elevation']=$cdata;
                }
                break;
            }
            case 'TIME': {
                if ($this->state(-2) == 'TRKPT') {
                    $this->track->waypoint[$this->cursor]['time']=$cdata;
                }
                break;
            }
            case 'GPXTPX:CAD': {
                if ($this->state(-2) == 'TRKPT') {
                    $this->track->waypoint[$this->cursor]['cadence']=$cdata;
                }
                break;
            }
            case 'GPXTPX:HR': {
                if ($this->state(-2) == 'TRKPT') {
                    $this->track->waypoint[$this->cursor]['heartrate']=$cdata;
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
            case 'TRK': 
            case 'TRKSEG': 
            case 'TRKPT': 
            case 'ELE': 
            case 'TIME': 
            case 'GPXTPX:HR': 
            case 'GPXTPX:CAD': 
            {
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
        foreach ($this->track->waypoint as $point) {
            if (array_key_exists($needle,$point)) {
                array_push($arr,$point[$needle]);
            } else {
                array_push($arr,0);
            }
        } 
        return $arr;
    }

    public function averageheartrate() {
        $data=$this->getall('heartrate');
        return array_sum($data)/count($data);
    }

    public function averagecadence() {
        $data=$this->getall('cadence');
        return array_sum($data)/count($data);
    }

    public function averageelevation() {
        $data=$this->getall('elevation');
        return array_sum($data)/count($data);
    }

    public function dump () {
        $data=var_export ($this->meta,true);
        $data.=var_export ($this->track,true);
        $data.='AVG HR:'.$this->averageheartrate().' CAD:'.$this->averagecadence().' ELEV:'.$this->averageelevation();
        return $data;
    }

}

?>
