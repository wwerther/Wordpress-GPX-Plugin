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

        # First parse the GPX-File
        xml_parse($this->parser, $data);

        # Now change the sorting order of the waypoints, so they are in a chronologic order
        usort ($this->track->waypoint,function ($a,$b) {
            if ($a['time']==$b['time']) return 0;
            return ($a['time']<$b['time'])? -1 : 1;
        });

        # And now calculate all extended attributes (speed, distance, etc.)
        for ($walk=0;$walk<count($this->track->waypoint);$walk++) {
                $last=$walk-1;
                if ($last<0) $last=0;
                $this->track->waypoint[$walk]['distance']=abs($this->distance($this->track->waypoint[$walk]['lat'],$this->track->waypoint[$walk]['lon'],$this->track->waypoint[$last]['lat'],$this->track->waypoint[$last]['lon'])*GPX_RADIUS);
                $this->track->waypoint[$walk]['totaldistance']=0;
                $this->track->waypoint[$walk]['totaldistance']=$this->track->waypoint[$last]['totaldistance']+$this->track->waypoint[$walk]['distance'];
                $this->track->waypoint[$walk]['interval']=abs($this->track->waypoint[$walk]['time']-$this->track->waypoint[$last]['time']);
                # in m/s
                $this->track->waypoint[$walk]['speed']=$this->track->waypoint[$walk]['distance']/$this->track->waypoint[$walk]['interval'];
                if (!$this->track->waypoint[$walk]['speed']) $this->track->waypoint[$walk]['speed']=0;
                $this->track->waypoint[$walk]['speed']*=3.6; # -> in km/h
                #$this->track->waypoint[$this->cursor]['speed']=$this->track->waypoint[$this->cursor]['speed']/16.666; # -> in min/km
        }

    }

    private function state($index) {
        if ($index<0) {
            $index=count($this->state)+$index;
        }
        return $this->state[$index];
    }


    function compact_array ($arr,$elem) {

        # If we should return 0 elements, we will return all elements
        if ($elem==0) return $arr;

        $total=count($arr);

        # If we should return more elements than exist, we will return all elements
        if ($elem>$total) return $arr;

        $factor=$total/$elem;

        $dst_arr=array();

        # We always want to keep the first value
        array_push($dst_arr,$arr[0]);

        # We fill up the intermediate records
        for ($i=1;$i<$elem;$i++) {

            # new average function for element. I'm not really sure, that this is better....
            $start=floor(($i-1)*$factor);
            $end=floor(($i+1)*$factor);
            $length=$end-$start;
            $tarr = array_slice ($arr, $start, $length);
            $el=array_sum($tarr)/count($tarr);

            # old "average function" for elements
            # $el=$arr[floor($i*$factor)];
            array_push($dst_arr,$el);

        }

        # We always want to keep the last value
        array_push($dst_arr,$arr[count($arr)-1]);
        
        return ($dst_arr);

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
                $data['lat']=floatval($attributes['LAT']);
                $data['lon']=floatval($attributes['LON']);
                array_push($this->track->waypoint,$data);
                $this->cursor=count($this->track->waypoint)-1;
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
                    $this->track->waypoint[$this->cursor]['elevation']=floatval($cdata);
                }
                break;
            }
            case 'TIME': {
                if ($this->state(-2) == 'TRKPT') {
                    # We want to store the time information as Unix-Timestamp. This makes calculation of speed etc. a lot easier
                    $this->track->waypoint[$this->cursor]['time']=strtotime($cdata);
                }
                break;
            }
            case 'GPXTPX:CAD': {
                if ($this->state(-2) == 'TRKPT') {
                    $this->track->waypoint[$this->cursor]['cadence']=floatval($cdata);
                    $this->meta->cadence=true;
                }
                break;
            }
            case 'GPXTPX:HR': {
                if ($this->state(-2) == 'TRKPT') {
                    $this->track->waypoint[$this->cursor]['heartrate']=floatval($cdata);
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
            case 'TRKSEG': {
                if (array_pop($this->state)!=$tag) {
                    throw new Exception ("ungültige Schachtelung");
                }
                break;
            }
            case 'TRKPT': {
                if (array_pop($this->state)!=$tag) {
                    throw new Exception ("ungültige Schachtelung");
                }
                break;
            }
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
                array_push($arr,'null');
            }
        } 
        return $arr;
    }

    public function averageheartrate() {
        $data=$this->getall('heartrate');
        return sprintf('%.2f',array_sum($data)/count($data));
    }

    public function averagecadence() {
        $data=$this->getall('cadence');
        return sprintf('%.2f',array_sum($data)/count($data));
    }

    public function averageelevation() {
        $data=$this->getall('elevation');
        return sprintf('%.2f',array_sum($data)/count($data));
    }

    public function averagespeed() {
        $avgspeed=$this->track->waypoint[count($this->track->waypoint)-1]['totaldistance']/abs($this->track->waypoint[0]['time']-$this->track->waypoint[count($this->track->waypoint)-1]['time']);
        $avgspeed*=3.6;
        return sprintf('%.2f',$avgspeed);
    }

    public function totaldistance() {
        return sprintf('%.2f',$this->track->waypoint[count($this->track->waypoint)-1]['totaldistance']/1000);
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
