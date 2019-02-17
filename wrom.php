<?php
    /**********************************************************
    *                 集合各大网站的爬虫程序                  *
    ***********************************************************
    *                 目前支持网站有                          *
    ***********************************************************
    *                 酷狗,虾米,百度,qq音乐                   *
    ***********************************************************
    *                 版本 : VERSION_1.1                      *
    ***********************************************************
    *                 作者 : wangfei                          *
    **********************************************************/
    //命名空间
    namespace CROM ;
    //导入工具类 html解析器 来源于https://github.com/samacs/simple_html_dom/blob/master/simple_html_dom.php
    include_once ("util/simple_html_dom.php");
    
    use \DOMDocumemt;
    
    //定义常量 , 控制访问路径
    define("CROM_KUGOU"  , 1);
    define("CROM_XIAMI"  , 2);
    define("CROM_BAIDU"  , 3);
    define("CROM_QQ"     , 4);
    //定义常量 , 控制请求内容
    define("CROM_MUSICINFO" , 1); #获取歌曲信息 , 包括歌手图片 , 歌曲地址 , 歌曲歌词
    define("CROM_LIST"      , 2); #获取歌单信息
    define("CROM_ALBUM"     , 3); #获取专辑信息
    define("CROM_SEARCH"    , 4); #搜索歌曲 (仅支持歌曲搜索)
    
    //定义爬虫类
    class Crom{       
        public $from    ;
        public $method  ;
        public $value   ;
        
        //构造函数私有化 , 使对象生成让自己控制
        private function __construct(){}
        //获取实例对象
        public static function getInstance($api){
            if(($api["from"] !== CROM_BAIDU && $api["from"] !== CROM_XIAMI && $api["from"] !== CROM_KUGOU && $api["from"] !== CROM_QQ) || ($api["method"] !== CROM_MUSICINFO 
                && $api["method"] !== CROM_LIST && $api["method"] !== CROM_ALBUM && $api["method"] !== CROM_SEARCH) ){
                return  false;
            }
            $crom = new Crom() ;
            $crom -> from   = $api["from"]   ;
            $crom -> method = $api["method"] ;
            $crom -> value  = isset($api["value"]) ? $api["value"] : array()  ;
            return  $crom;
        }
        //给用户返回结果
        public function getInfo(){
            switch($this -> method){
                case CROM_MUSICINFO:
                return $this -> musicInfo() ;
                
                case CROM_LIST:
                return $this -> playList() ;
                
                case CROM_SEARCH:
                return $this -> musicSearch() ;
                
                case CROM_ALBUM:
                return $this -> musicAlbum() ;
                
                default:
                return $this -> error(304 , "参数异常<br>ERRORLINE : ".__LINE__);
            }
        }
        //执行请求
        public function exec($data){
            //初始化http请求
            $curl = curl_init() ;
            //格式化参数
            if($data["METHOD"] == "GET" && isset($data["BODY"])){
                $data["URL"] .=  URLDecode("?".(http_build_query($data["BODY"]))) ;
            }
            if($data["METHOD"] == "POST"){
                curl_setopt($curl , CURLOPT_POST , true);
                curl_setopt($curl , CURLOPT_POSTFIELDS , is_array($data["BODY"]) ? http_build_query($data["BODY"]) : $data["BODY"]);
            }
            curl_setopt($curl , CURLOPT_URL , $data["URL"]);
            curl_setopt($curl , CURLOPT_RETURNTRANSFER , true);
            curl_setopt($curl , CURLOPT_HEADER , false);
            curl_setopt($curl , CURLOPT_SSL_VERIFYPEER , false);
            curl_setopt($curl , CURLOPT_HTTPHEADER , $this -> headerFormat($data["HEADER"]));
            //项目表服务器发起请求
            $response = curl_exec($curl);
            curl_close($curl) ;
            if($response === False){
                return $this -> error(404 , "页面找不到<br>ERRORLINE : ".__LINE__) ;
            }
            if(!$data["flag"]){
				//部分信息返回结果无法直接转换成array需要提交resultFormat函数处理
                $response = $this -> resultFormat(array("data" => $response , "method" => $data["med"])) ;
                return $response ;
            }        
            //对处理好的数据进行json格式化
            $response = json_decode($response , true) ;
            if(isset($response)){
                return $response;
            }
            return $this -> error(500 , "数据处理错误<br>ERRORLINE : ".__LINE__) ;
        }
        //对爬虫返回结果进行处理(处理没有json返回,解析网页源码的部分)
        public function resultFormat($data){
            switch($this -> method){
                case CROM_MUSICINFO:
                //百度音乐歌词请求时
                if($this -> from === CROM_BAIDU){
                    return array("lrc" => $this -> UnicodeEncode($data["data"]));
                }
                //虾米音乐返回结果处理
                if($this -> from === CROM_XIAMI){   
                    if($data["method"] == "lrc"){
						//解析虾米音乐的歌词
                        return array("lrc" => $this -> UnicodeEncode($data["data"]));
                    }else if($data["method"] == "songInfo"){
						//解析虾米音乐的xml文件
                        $data = $data["data"] ;
                        $doc = new \DOMDocument() ;
                        @$doc -> loadXML($data);
                        if(!isset($doc -> documentElement )){
                            return $this -> error(304 , "参数异常<br>ERRORLINE : ".__LINE__) ;
                        }
                        $track = $doc -> documentElement -> getElementsByTagName("track") -> item(0);
                        $result = array(    
                            "songName" => $track -> getElementsByTagName("song_name") -> item(0) -> childNodes -> item(0) -> nodeValue,
                            "songId"   => $track -> getElementsByTagName("song_id") -> item(0) -> nodeValue,
                            "albumid"  => $track -> getElementsByTagName("album_id") -> item(0) -> nodeValue,
                            "albumImg" => $track -> getElementsByTagName("album_cover") -> item(0) -> childNodes -> item(0) -> nodeValue ,
                            "albumName"=> $track -> getElementsByTagName("album_name") -> item(0) -> childNodes -> item(0) ->nodeValue ,
                            "singer"   => $track -> getElementsByTagName("artist_name") -> item(0) -> childNodes -> item(0) -> nodeValue,
                            "singerId" => $track -> getElementsByTagName("artist_id") -> item(0) -> childNodes -> item(0) -> nodeValue,
                            "location" => $track -> getElementsByTagName("location") -> item(0) -> childNodes -> item(0) ->nodeValue
                        ) ;
                        $url = $this -> xiamiDecode($result["location"]) ;
                        if(!is_string($url)){
                            return $url ;
                        }
                        $result["play_url"] = $url;
                        return $result ;
                    } 
                }
                break ;
                
                case CROM_LIST:
                if($data["method"] == "list_K"){
					//处理酷狗歌单的网页源码
                    $match = null; 
                    if(!preg_match_all("/global.features = (\[.*\]);/",$data["data"] , $match)){
                        return $this -> error(500 , "酷狗正则 匹配失败<br>ERRORLINE : ".__LINE__);
                    };
                    $str = $match[1][0] ;
                    return json_decode($str , true) ;
                }else if($data["method"] == "list_B"){
					//处理百度歌单的网页源码
                    $html = str_get_html($data["data"]);
                    if(!isset($html)){
                        return $this -> error(404 , "服务器未能给出正确响应<br>ERRORLINE : ".__LINE__);
                    }
                    $result = array("status" => 200 , "data" => array());
                    foreach($html -> find(".song-list ul li") as $e){
                        $arr = array(
                            "musicname"  => $e -> find(".song-item .song-title")[0] -> first_child() -> getAttribute("title") ,
                            "musicid"    => substr($e -> find(".song-item .song-title")[0] -> first_child() -> getAttribute("href") , strrpos($e -> find(".song-item .song-title")[0] -> first_child() -> getAttribute("href") , "/")+1) ,
                            "singername" => $e -> find(".song-item .singer .author_list")[0] -> getAttribute("title") ,
                            "singerid"   => substr($e -> find(".song-item .singer .author_list")[0] -> first_child() -> getAttribute("href") , strrpos($e -> find(".song-item .singer .author_list")[0] -> first_child() -> getAttribute("href") , "/")+1) ,
                            "albumname"  => $e -> find(".song-item .album-title")[0] -> first_child() -> getAttribute("title") ,
                            "albumid"    => substr($e -> find(".song-item .album-title")[0] -> first_child() -> getAttribute("href") , strrpos($e -> find(".song-item .album-title")[0] -> first_child() -> getAttribute("href") , "/")+1)
                        ) ;
                        $result["data"][] = $arr ;
                    }
                    return $result;
                }else if($data["method"] == "list_Q"){
					//去掉qq音乐返回的jsonp格式的首尾,利用json_decode编码成array
                    if(!preg_match('/playlistinfoCallback\((.*)\)/' , $data["data"] , $result)){
                        return $this -> error(500 , "qq音乐歌单解析时正则匹配失败");
                    }
                    return $result[1];
                }
                break;
                
                case CROM_SEARCH:
                if($data["method"] == "search_X"){
					//搜索时虾米网站源码解析
                    $html = str_get_html($data["data"]);
                    if(!isset($html)){
                        return $this -> error(404 , "服务器未能做出正确响应<br>ERRORLINE : ".__LINE__);
                    }
                    $html = $html -> find(".track_list",0) -> children(1) -> find("tr") ;
                    $result = array("status" => 200 , "data" => array());
                    //遍历标签提取歌曲信息
                    foreach($html as $e){
                        $arr = array(
                            "songName"      => $e -> find(".song_name a" , 0) -> title ,
                            "singerName"    => $e -> find(".song_artist a" , 0) -> title ,
                            "albumName"     => $e -> find(".song_album a" , 0) -> innertext() ,
                            "albumId"       => '' ,
                            "songId"        => $e -> find(".chkbox input" , 0) -> value 
                        );
                        $result["data"][] = $arr ;
                    }
                    return $result ;
                }else if($data["method"] == "search_B"){
					//搜索时百度网站源码解析
                    $doc = new \DOMDocument();
                    @$doc -> loadHTML($data["data"]) ;
                    if(!isset($doc -> documentElement)){
                        return $this -> error(404 , "服务器未能做出正确响应<br>ERRORLINE : ".__LINE__);
                    }
                    $html = $doc -> getElementById("result_container");
                    $div = $html -> childNodes[1] -> getElementsByTagName("ul")[0] -> childNodes ;
                    $result = array("status" => 200 , "data" => array());
                    foreach($div as $e){
                        if(!isset($e -> attributes["data-albumid"])){
                            continue ;
                        }
                        $album = '' ;
                        if(preg_match_all('/《(.*)》/' , $e -> childNodes[0] -> childNodes[4] -> childNodes[0] -> attributes -> item(5) -> nodeValue , $temp)){
                            if($e -> childNodes[0] -> childNodes[4] -> childNodes[0] -> attributes -> item(5) -> nodeValue == "title"){
                                $album = $temp[1][0] ;
                            }
                        }
                        $d = json_decode($e -> attributes -> item(1) -> nodeValue , true);
                        $arr = array(
                            "musicName"       => $d["songItem"]["sname"] ,
                            "musicId"         => $d["songItem"]["sid"] ,
                            "singerName"      => preg_replace('/<.*em>/' , "" , $d["songItem"]["author"]) ,
                            "singerId"        => isset(preg_split('/=/' , $e -> childNodes[0] -> childNodes[5] -> getElementsByTagName("a")[0] -> attributes -> item(2) -> nodeValue )[1]) ? preg_split('/=/' , $e -> childNodes[0] -> childNodes[5] -> getElementsByTagName("a")[0] -> attributes -> item(2) -> nodeValue )[1] : '',
                            "albumId"         => $e -> attributes -> item(0) -> nodeValue ,
                            "albumName"       => $album
                        );
                        $result["data"][] = $arr ;
                    }
                    return $result ;
                }
                break;
                
                case CROM_ALBUM:
                if($data["method"] == "album_K"){
                    if(!preg_match_all('/var data=(\[.*\]);/' , $data["data"] , $result)){
                        return $this -> error(500 , "酷狗专辑正则匹配失败<br>ERRORLINE : ".__LINE__);
                    }
                    return json_decode($result[1][0] , true);
                }
                break;
                   
                default:
                return $this -> error(304 , "参数异常<br>ERRORLINE : ".__LINE__) ;  
            }
        }
        //专辑请求
        public function musicAlbum(){
            switch($this -> from){
                case CROM_BAIDU:
                if(!isset($this -> value["albumId"])){
                    return $this -> error(304 , "参数异常<br>ERRORLINE".__LINE__);
                }
                $data = array(
                    "METHOD"       => "GET" ,
                    "URL"          => "http://play.taihe.com/data/music/box/album" ,
                    "HEADER"       =>  array(
                        "user-agent" => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36" ,
                        "referer"    => "http://play.taihe.com/?__m=mboxCtrl.playSong&__a=608296304&fr=-1||www.baidu.com"
                    ) ,
                    "BODY"         =>  array(
                        "albumId"    => $this -> value["albumId"] ,
                        "type"       => "album"
                    ),
                    "flag"         => true
                );
                $d = $this -> exec($data);
                $ids = '' ;
                foreach($d["data"]["songIdList"] as $v){
                    $ids .= ",".$v ;
                }
                $ids = substr($ids , 1) ;
                $result = $this -> albumInfo(array("ids" => $ids)) ;
                if($result === false){
                    return $this -> error(205 , "无数据<br>ERRORLINE : ".__LINE__);
                }
                return $result ;
                
                case CROM_XIAMI:
                if(!isset($this -> value["albumId"])){
                    return $this -> error(304 , "参数异常<br>ERRORLINE : ".__LINE__);
                }
                $data = array(
                    "METHOD"       => "GET" ,
                    "URL"          => "https://www.xiami.com/song/playlist/id/".$this -> value["albumId"]."/type/1/cat/json" ,
                    "HEADER"       =>  array(
                        "user-agent" => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36" ,
                        "referer"    => "https://www.xiami.com/play?ids=/song/playlist/id/1802949282/object_name/default/object_id/0"
                    ) ,
                    "flag"         => true
                );
                return $this -> exec($data);
                
                case CROM_QQ:
                if(!isset($this -> value["albumId"])){
                    return $this -> error(304 , "参数错误<br>ERRORLINE : ".__LINE__);
                }
                $data = array(
                    "METHOD"      => "GET" ,
                    "URL"         => "https://c.y.qq.com/v8/fcg-bin/fcg_v8_album_info_cp.fcg" ,
                    "HEADER"      =>  array(
                        "User-Agent"    => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36" ,
                        "referer"       => "https://y.qq.com/n/yqq/album/001Ty7rP04rSeN.html"
                    ),
                    "BODY"        =>  array(
                        "ct"            =>  24 ,
                        "albummid"      =>  $this -> value["albumId"] ,
                        "g_tk"          =>  5381 ,
                        "loginUin"      =>  0 ,
                        "hostUin"       =>  0 ,
                        "format"        =>  "json" ,
                        "inCharset"     =>  "utf8" ,
                        "outCharset"    =>  "utf-8" ,
                        "notice"        =>  0 ,
                        "platform"      =>  "yqq.json" ,
                        "needNewCode"   =>  0
                    ),
                    "flag"         =>  true
                );
                return $this -> exec($data);
                
                case CROM_KUGOU:
                if(!isset($this -> value["albumId"])){
                    return $this -> error(304 , "参数异常<br>ERRORLINE : ".__LINE__);
                }
                $data = array(
                    "METHOD"      => "GET" ,
                    "URL"         => "https://www.kugou.com/yy/album/single/".$this -> value["albumId"].".html" ,
                    "HEADER"      =>  array(
                        "User-Agent"    => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36"
                    ),
                    "flag"        =>  false ,
                    "med"         => "album_K"
                );
                return $this -> exec($data);
                
                default:
                return $this -> error(304 , "参数异常<br>ERRORLINE".__LINE__);
            }
        }
        //部分音乐网站专辑详细信息需要额外处理
        public function albumInfo($value = array()){
            switch($this -> from){
                case CROM_BAIDU:
                if(!isset($value["ids"])){
                    return false ;
                }
                $data = array(
                    "METHOD"        => "POST" ,
                    "URL"           => "http://play.taihe.com/data/music/songinfo" ,
                    "HEADER"        =>  array(
                        "user-agent" => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36" ,
                        "Referer"    => "http://play.taihe.com/?__m=mboxCtrl.playSong&__a=608296304&fr=-1||www.baidu.com"
                    ),
                    "BODY"          =>  array(
                        "songIds"    => $value["ids"]
                    ),
                    "flag"          => true
                ) ;
                return $this -> exec($data);
                
                default:
                return false;
            }
        }
        //歌曲搜索(目前只支持歌曲搜索)
        public function musicSearch(){
            if(isset($this -> value["key"])){
                $this -> value["key"] = str_replace(' ' , '+' , trim($this -> value["key"])) ;
            }
            switch($this -> from){
                case CROM_KUGOU:
                if(!isset($this -> value["key"]) || !isset($this -> value["page"])){
                    return $this -> error(304 , "参数异常<br>ERRORLINE : ".__LINE__);
                }
                $data = array(
                    "METHOD"    => "GET",
                    "URL"       => "https://songsearch.kugou.com/song_search_v2" ,
                    "HEADER"    =>  array(
                        "user-agent" => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36" ,
                        "referer"    => "https://www.kugou.com/yy/html/search.html"
                    ),
                    "BODY"      =>  array(
                        "keyword"           => URLEncode($this -> value["key"]) ,
                        "page"              => $this -> value["page"] ,
                        "pagesize"          => 30 ,
                        "userid"            => -1 ,
                        "platform"          => "WebFilter" ,
                        "tag"               => "em" ,
                        "filter"            => 2 ,
                        "iscorrection"      => 1 ,
                        "privilege_filter"  => 0
                    ),
                    "flag"      => true
                );
                return $this -> exec($data) ;
                
                case CROM_XIAMI:
                if(!isset($this -> value["key"]) || !isset($this -> value["page"])){
                    return $this -> error(304 , "参数异常<br>ERRORLINE : ".__LINE__) ;
                }
                $data = array(
                    "METHOD"    => "GET" ,
                    "URL"       => "https://www.xiami.com/search/song/page/".$this -> value["page"]."?key=".urlencode($this -> value["key"]) ,
                    "HEADER"    => array(
                        "user-agent" => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36" 
                    ),
                    "flag"      => false ,
                    "med"       => "search_X"
                );
                return $this -> exec($data);
                
                case CROM_BAIDU :
                if(!isset($this -> value["key"]) || !isset($this -> value["page"])){
                    return $this -> error(304 , "参数异常<br>ERRORLINE : ".__LINE__);
                }
                $data = array(
                    "METHOD"    => "GET" ,
                    "URL"       => "http://music.taihe.com/search/song" ,
                    "HEADER"    =>  array(
                        "user-agent" => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36" ,
                        "Host"       => "music.taihe.com"
                    ),
                    "BODY"      =>  array(
                        "s"       =>  1,
                        "key"     =>  URLEncode($this -> value["key"]) ,
                        "start"   =>  ($this -> value["page"]*1 - 1) * 20 ,
                        "size"    =>  20
                    ),
                    "flag"     => false ,
                    "med"      => "search_B"
                );
                return $this -> exec($data);
                
                case CROM_QQ:
                if(!isset($this ->value["key"]) || !isset($this -> value["page"])){
                    return $this -> error(304 , "参数异常<br>ERRORLINE : ".__LINE__);
                }
                $data = array(
                    "METHOD"        => "GET" ,
                    "URL"           => "https://c.y.qq.com/soso/fcgi-bin/client_search_cp" ,
                    "HEADER"        =>  array(
                        "user-agent"    => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36" ,
                        "referer"       => "https://y.qq.com/portal/search.html"
                    ),
                    "BODY"          =>  array(
                        "ct"              => 24 ,
                        "qqmusic_ver"     => 1298 ,
                        "new_json"        => 1 ,
                        "remoteplace"     => "txt.yqq.song" ,
                        "searchid"        => rand(10000000000000000 , 99999999999999999) ,
                        "t"               => 0 ,
                        "aggr"            => 1 ,
                        "cr"              => 1 ,
                        "catZhida"        => 1 ,
                        "lossless"        => 0 ,
                        "flag_qc"         => 0 ,
                        "p"               => $this -> value["page"] ,
                        "n"               => 30 ,
                        "w"               => $this -> value["key"] ,
                        "g_tk"            => 5381 ,
                        "loginUin"        => 0 ,
                        "hostUin"         => 0 ,
                        "format"          => "json" ,
                        "inCharset"       => "utf8" ,
                        "outCharset"      => "utf-8" ,
                        "notice"          => 0 ,
                        "platform"        => "yqq.json" ,
                        "needNewCode"     => 0
                    ),
                    "flag"      => true
                );
                return $this -> exec($data);
                
                default:
                return $this -> error(304 , "参数异常<br>ERRORLINE : ".__LINE__);
            }
        }
        //歌单请求
        public function playList(){
            switch($this -> from){
                case CROM_KUGOU:
                if(!isset($this -> value["listId"]) || !isset($this -> value["page"])){
                    return $this -> error(304 , "参数异常<br>ERRORLINE : ".__LINE__);
                }
                $data = array(
                    "METHOD"     => "GET" ,
                    "URL"        => "https://www.kugou.com/yy/rank/home/".$this->value["page"]."-".$this->value["listId"].".html?from=rank",
                    "HEADER"     => array(
                        "user-agent" => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36" 
                    ),
                    "flag"       => false ,
                    "med"        => "list_K"
                );
                return $this -> exec($data) ;
                
                case CROM_XIAMI:
                if(!isset($this -> value["listId"])){
                    return $this -> error(304 , "参数异常<br>ERRORLINE : ".__LINE__);
                }
                $data = array(
                    "METHOD"    => "GET" ,
                    "URL"       => "https://www.xiami.com/song/playlist/id/".$this -> value["listId"]."/type/3/cat/json" ,
                    "HEADER"    =>  array(
                        "user-agent" => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36" ,
                        "referer"    => "https://www.xiami.com/play?ids=/song/playlist/id/".$this -> value["listId"]
                    ),
                    "flag"      => true
                );
                return $this -> exec($data);
                
                case CROM_BAIDU:
                if(!isset($this -> value["listId"])){
                    return $this -> error(304 , "参数异常<br>ERRORLINE : ".__LINE__);
                }
                $data = array(
                    "METHOD"    => "GET" ,
                    "URL"       => "http://music.taihe.com/songlist/".$this -> value["listId"] ,
                    "HEADER"    =>  array(
                        "user-agent" => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36" ,
                        "Host"       => "music.taihe.com"
                    ),
                    "flag"      => false ,
                    "med"       => "list_B"
                );
                return $this -> exec($data);
                break ;
                
                case CROM_QQ:
                if(!isset($this -> value["listId"])){
                    return $this -> error(304 , "参数异常<br>ERRORLINE : ".__LINE__);
                }
                $data = array(
                    "METHOD"  => "GET" ,
                    "URL"     => "https://c.y.qq.com/qzone/fcg-bin/fcg_ucc_getcdinfo_byids_cp.fcg" ,
                    "HEADER"  =>  array(
                        "referer"     => "https://y.qq.com/n/yqq/playlist/".$this -> value["listId"].".html" ,
                        "user-agent"  => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36" 
                    ),
                    "BODY"    =>  array(
                        'type' => '1',
                        'json' => '1',
                        'utf8' => '1',
                        'onlysong' => '0',
                        'disstid' => $this -> value["listId"],
                        'format' => 'jsonp',
                        'g_tk' => '1089387893',
                        'jsonpCallback' => 'playlistinfoCallback',
                        'loginUin' => '0',
                        'hostUin' => '0',
                        'inCharset' => 'utf8',
                        'outCharset' => 'utf-8',
                        'notice' => 0,
                        'platform' => 'yqq',
                        'needNewCode' => 0
                    ),
                    "flag"  => false ,
                    "med"   => "list_Q"
                );
                $result = json_decode($this -> exec($data) , true);
                $ids    = preg_split('/,/' , $result["cdlist"][0]["songids"]) ;
                $types  = preg_split('/,/' , $result["cdlist"][0]["songtypes"]) ;
                for($i = 0 , $len = count($ids) ; $i < $len ; $i++){
                    $ids[$i] = intval($ids[$i]) ;
                    $types[$i] = intval($types[$i]) ;
                }
                
                $result = $this -> pic(array("ids" => $ids , "types" => $types));
                return $result ;
                break;
                
                default:
                return $this -> error(304 , "参数异常<br>ERRORLINE : ".__LINE__) ;
            }
        }
        //CROM_MUSICINFO的处理函数
        public function musicInfo(){
            switch($this -> from){
                case CROM_KUGOU:
                if(!isset($this -> value["songId"])){
                    return $this -> error(305 , "内部参数异常<br>ERRORLINE : ".__LINE__) ;
                }
                //歌曲 歌手图片 歌词
                $data = array(
                    "METHOD" => "GET" ,
                    "URL"    => "https://wwwapi.kugou.com/yy/index.php " ,
                    "HEADER" => array(
                        "user-agent" => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36" 
                    ),
                    "BODY"   => array(
                        "r"        => "play/getdata" ,
                        "hash"     => $this -> value["songId"]  
                    ),
                    "flag"   => true
                );
                return $this -> exec($data) ;
                
                case CROM_BAIDU :
                if(!isset($this -> value["songId"])){
                    return $this -> error(305 , "内部参数异常<br>ERRORLINE : ".__LINE__);
                }
                //请求歌曲
                $data = array(
                    "METHOD"  => "POST" ,
                    "URL"     => "http://play.taihe.com/data/music/songlink" ,
                    "HEADER"  =>  array(
                        "user-agent" => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36" ,
                        "Host"       => "play.taihe.com" 
                    ),
                    "BODY"    =>  array(
                        "songIds"   => $this -> value["songId"] ,
                        "hq"        => 0,
                        "type"      => "m4a,mp3" ,
                        "rate"      => '' ,
                        "pt"        => 0 ,
                        "flag"      => -1 ,
                        "s2p"       => -1 ,
                        "prerate"   => -1 ,
                        "bwt"       => -1 ,
                        "dur"       => -1 ,
                        "bat"       => -1 ,
                        "bp"        => -1 ,
                        "pos"       => -1 ,
                        "auto"      => -1
                    ),
                    "flag"    => true
                );
                $music = $this -> exec($data) ;
                $lrc   = $this -> lrc(array("url" => $music["data"]["songList"][0]["lrcLink"])) ;
                $pic   = $this -> pic(array("url" => "http://play.taihe.com/data/music/songinfo" , "songIds" => $this -> value["songId"] , "queryId" => $music["data"]["songList"][0]["queryId"])) ;
                $result = array(
                    "status" => "1" ,
                    "data"   => array(
                        "albumId"      => $pic["data"]["songList"][0]["albumId"] ,
                        "album_name"   => $pic["data"]["songList"][0]["albumName"] ,
                        "author_id"    => $pic["data"]["songList"][0]["artistId"],
                        "author_name"  => $pic["data"]["songList"][0]["artistName"] ,
                        "timelength"   => $music["data"]["songList"][0]["time"]."000",
                        "lyrics"       => $lrc["lrc"],
                        "img"          => $pic["data"]["songList"][0]["songPicRadio"],
                        "play_url"     => $music["data"]["songList"][0]["songLink"],
                        "songId"       => $music["data"]["songList"][0]["songId"]
                    )
                ) ;
                return $result ;
                
                case CROM_XIAMI:
                if(!isset($this -> value["songId"])){
                    return $this -> error(305 , "内部参数异常<br>ERRORLINE : ".__LINE__);
                }
                $data = array(
                    "METHOD" => "GET" ,
                    "URL"    => "https://www.xiami.com/widget/xml-single/uid/0/sid/".$this -> value["songId"] ,
                    "HEADER" => array(
                        "user-agent" => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36" ,
                        ":authority" => "www.xiami.com" ,
                        ":method"    => "GET" ,
                        ":path"      => "/widget/xml-single/uid/0/sid/".$this -> value["songId"] ,
                        ":scheme"    => "https" ,
                    ),
                    "flag"   => false ,
                    "med"    => "songInfo"
                );
                $result = array("status" => 1 , "data" => array()) ;
                $music = $this -> exec($data);
                $pic   = $this -> pic() ;
                if(isset($pic["data"]["trackList"][0]["lyric"]) && trim($pic["data"]["trackList"][0]["lyric"])){
                    $lrc   = $this -> lrc(array("url" => $pic["data"]["trackList"][0]["lyric"])) ;
                    $result["data"] = array_merge($result["data"] , $lrc );
                }else{
                    $result["data"]["lrc"] = "" ;
                }
                $result["data"] = array_merge($result["data"] , $music );
                $result["data"]["img"] = $pic["data"]["trackList"][0]["pic"] ;
                return $result ;
                
                case CROM_QQ:
                if(!isset($this -> value["songId"])){
                    return $this -> error(304 , "参数异常<br>ERRORLINE : ".__LINE__);
                }
                $pic = $this -> pic(array("ids" => array($this -> value["songId"]*1) , "types" => array(0)));
                $data = array(
                    "METHOD"    => "GET" ,
                    "URL"       => "https://u.y.qq.com/cgi-bin/musicu.fcg" ,
                    "HEADER"    =>  array(
                        "referer"     => "https://y.qq.com/portal/player.html" ,
                        "user-agent"  => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36" ,
                        "origin"      => "https://y.qq.com" ,
                     ),
                    "BODY"      =>  array(
                        "-"            => "getplaysongvkey24716462078114199",
                        "g_tk"         => "5381" ,
                        "loginUin"     => "0" ,
                        "hostUin"      => "0" ,
                        "notice"       => "0" ,
                        "format"       => "json" ,
                        "inCharset"    => "utf-8" ,
                        "outCharset"   => "utf-8" ,
                        "platform"     => "yqq.json" ,
                        "needNewCode"  =>  0 ,
                        "data"         =>  str_replace("\\","",preg_replace(array('%"\{%','%\}"%') , array("{" , "}") , json_encode(array(
                            "req_0"          => str_replace("\\","",preg_replace(array('%"\{%','%\}"%') , array("{" , "}") , json_encode(array(
                                "module"        => "vkey.GetVkeyServer" ,
                                "method"        => "CgiGetVkey" ,
                                "param"         =>  str_replace("\\","",preg_replace(array('%"\{%','%\}"%') , array("{" , "}") , json_encode(array(
                                    "guid"          => "".rand(100000000,999999999) ,
                                    "songmid"       =>  [$pic["data_id"]["data"]["tracks"][0]["mid"]] ,
                                    "songtype"      =>  [0] ,
                                    "uin"           => "0" ,
                                    "loginflag"     =>  1 ,
                                    "platform"      => "20"
                                ))))
                            )))),
                            "comm"           => str_replace("\\","",preg_replace(array('%"\{%','%\}"%') , array("{" , "}") , json_encode(array(
                                "uin"           =>  0 , 
                                "format"        => "json" ,
                                "ct"            =>  24 ,
                                "cv"            =>  0
                            ))))
                        ))))
                    ),
                    "flag"              => true 
                );
                $music = $this -> exec($data);
                $lrc = $this -> lrc(array("id" => $pic["data_id"]["data"]["tracks"][0]["mid"]));
                $lrc["lyric"] = base64_decode($lrc["lyric"]) ;
                $result = array(
                    "status"    => 200 ,
                    "data"      => array(
                        "lyric"          => $lrc["lyric"] ,
                        "img"            => "https://y.gtimg.cn/music/photo_new/T002R300x300M00000".substr($pic["data_id"]["data"]["tracks"][0]["album"]["mid"] , 2).".jpg?max_age=2592000" ,
                        "songName"       => $pic["data_id"]["data"]["tracks"][0]["name"] ,
                        "singer"         => $pic["data_id"]["data"]["tracks"][0]["singer"][0]["name"]
                    )
                );
                for($i = 0 , $len = count($music["req_0"]["data"]["sip"]) ; $i < $len ; $i++){
                    $result["data"]["play_url".$i] = $music["req_0"]["data"]["sip"][$i].$music["req_0"]["data"]["midurlinfo"][0]["purl"] ;
                }
                return $result ;
                
                default:
                return $this -> error(304 , "参数异常<br>ERRORLINE : ".__LINE__);
            }
        }
        //歌词爬取
        public function lrc($value = array()){
            switch($this -> from){
                case CROM_BAIDU:
                if(!isset($value["url"])){
                    return $this -> error(305 , "内部参数异常<br>ERRORLINE : ".__LINE__);
                }
                $data = array(
                    "METHOD"  => "GET" ,
                    "URL"     => $value["url"] ,
                    "HEADER"  =>  array(
                        "user-agent" => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36" ,
                        "Host"       => "qukufile2.qianqian.com" 
                    ),
                    "flag"    => false ,
                    "med"     => "lrc"
                );
                return $this -> exec($data) ;
                
                case CROM_XIAMI:
                if(!isset($value["url"])){
                    return $this -> error(305 , "内部参数异常<br>ERRORLINE : ".__LINE__) ;
                }
                $data = array(
                    "METHOD"  => "GET" ,
                    "URL"     => "http:".$value["url"] ,
                    "HEADER"  =>  array(
                        "user-agent" => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36"    
                    ),
                    "flag"    => false ,
                    "med"     => "lrc"
                );
                $result = $this -> exec($data) ;
                return $result ;
                
                case CROM_QQ:
                if(!isset($this -> value["songId"])){
                    return $this -> error(304 , "参数异常<br>ERRORLINE : ".__LINE__);
                }
                $data = array(
                    "METHOD"        => "GET" ,
                    "URL"           => "https://c.y.qq.com/lyric/fcgi-bin/fcg_query_lyric_new.fcg" ,
                    "HEADER"        =>  array(
                        "user-agent" => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36" ,
                        "referer"    => "https://y.qq.com/portal/player.html"
                    ),
                    "BODY"          =>  array(
                        "pcachetime"     => time().rand(100,999) ,
                        "songmid"        => $value["id"] ,
                        "g_tk"           => 5381 ,
                        "loginUin"       => 0 ,
                        "hostUin"        => 0 ,
                        "format"         => "json" ,
                        "inCharset"      => "utf-8" ,
                        "outCharset"     => "utf-8" ,
                        "notice"         => 0 ,
                        "platform"       => "yqq.json" ,
                        "needNewCode"    => 0
                    ),
                    "flag"          => true 
                );
                return $this -> exec($data) ;
                
                default:
                return $this -> error(304 , "参数异常<br>ERRORLINE : ".__LINE__) ;
            }
        }
        //图片爬取
        public function pic($value = array()){
            switch($this -> from){
                case CROM_BAIDU:
                //部分歌曲信息图片没在一起需要分开爬取
                if(!isset($value["url"])){
                    return $this -> error(305 , "内部参数异常<br>ERRORLINE : ".__LINE__) ;
                }
                $data = array(
                    "METHOD"  => "POST" ,
                    "URL"     => $value["url"] ,
                    "HEADER"  =>  array(
                        "user-agent" => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36" ,
                        "Host"       => "play.taihe.com" 
                    ),
                    "BODY"    => array(
                        "songIds" => $value["queryId"]
                    ),
                    "flag"    => true
                );
                return $this -> exec($data) ;
                
                case CROM_XIAMI:
                $data = array(
                    "METHOD"   => "GET" ,
                    "URL"      => "https://www.xiami.com/song/playlist/id/".$this -> value["songId"]."/cat/json" ,
                    "HEADER"   =>  array(
                        "user-agent" => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36" ,
                        "referer"    => "https://www.xiami.com/play?ids=/song/playlist/id/".$this -> value["songId"]
                    ) ,
                    "flag"     => true 
                );
                return $result = $this -> exec($data);
                
                case CROM_QQ:
                $data = array(
                    "METHOD"    => "POST" ,
                    "URL"       => "https://u.y.qq.com/cgi-bin/musicu.fcg?g_tk=5381" ,
                    "HEADER"    =>  array(
                        "referer"    => "https://y.qq.com/portal/player.html" ,
                        "user-agent" => "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36"
                    ),
                    "BODY"      =>  str_replace("\\" , "" ,preg_replace(array('/"\{/','/\}"/') , array("{" , "}") , json_encode(array(
                        "comm"      => str_replace("\\" , "" ,preg_replace(array('/"\{/','/\}"/') , array("{" , "}") ,json_encode(array(
                            "uin"       => "0" ,
                            "ct"        => "24" ,
                            "cv"        => "0" ,
                            "gzip"      => "0" ,
                            "mcc"       => "460" ,
                            "mnc"       => "1" 
                        )))),
                        "data_id"   => str_replace("\\" , "" ,preg_replace(array('/"\{/','/\}"/') , array("{" , "}") , json_encode(array(
                            "module"        => "track_info.UniformRuleCtrlServer" ,
                            "method"        => "GetTrackInfo"   ,
                            "param"         => str_replace("\\" , "" ,preg_replace(array('/"\{/','/\}"/') , array("{" , "}") ,json_encode(array(
                                "ids"    => $value["ids"] ,
                                "types"  => $value["types"]
                            ))))
                        ))))
                    )))),
                    "flag"      => true
                );
                return $this -> exec($data) ;
                
                default:
                return $this -> error(304 , "参数异常<br>ERRORLINE : ".__LINE__);
            }
        }
        //虾米音乐解析localhost
        public function xiamiDecode($location){
            if(!isset($location) || !is_string($location)){
                return $this -> error(305 , "内部参数异常<br>ERRORLINE : ".__LINE__) ;
            }
            $spliter = array("t","t","p","%","3","A","%","2","f","%","2","f","m","1","2","8",".","x","i","a","m","i") ;
            $line = (int)$location[0] ;
            $location = substr($location , 1) ;
            $index = (int)(strlen($location) / $line) ;
            $result = array() ;
            $error_count = 0 ;
            
            for($i = 0 ; $i < $line - 1 ; $i ++){
                $flag = 0 ;
                $arr = array() ;
                while($flag != $index && $flag != $index + 1){
                    $flag = stripos($location , $spliter[$i] , $flag + 1);
                    if(++$error_count >= 50){
                        return $this -> error(500 , "虾米音乐location字段异常<br>ERRORLINE : ".__LINE__);
                    }
                }
                $str = substr($location , 0 , $flag);
                $location = substr($location , $flag);
                $arr = str_split($str , 1);
                $result[] = $arr ;
            }
            $arr = str_split($location , 1);
            $result[] = $arr ;
            
            $url = '';
            for($i = 0 ; $i < $index + 1 ; $i++){
                for($j = 0 , $len = count($result) ; $j < $len ; $j ++){
                    if(isset($result[$j][$i])){
                        $url .= $result[$j][$i] ;
                    }else{
                        continue ;
                    }
                }
            }
            $url = str_ireplace("%5e" , "0" , $url);
            return URLDecode($url) ;
        }
        //封装错误处理函数
        public function error($errCode , $value=''){
            $this -> error = array("errorCode" => $errCode,"statement" => $value);
            return $this -> error;
        }
        //格式化访问头
        public function headerFormat($header){
            return array_map(function($k , $v){
                return $k.": ".$v ;
            } , array_keys($header) , $header);
        }
        //unicode编码函数
        function UnicodeEncode($str){
            preg_match_all('/./u',$str,$matches);     
            $unicodeStr = "";
            foreach($matches[0] as $m){
                $unicodeStr .= "&#".base_convert(bin2hex(iconv('UTF-8',"UCS-4",$m)),16,10);
            }
            return $unicodeStr;
        }
    }
?>
