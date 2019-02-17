<?php
    require_once("wrom.php");   
    use Crom\Crom ;
    
    //虾米音乐专辑
    header("Content-Type: text/html;charset=utf-8");
    $api = array(
        "from"      => CROM_XIAMI,
        "method"    => CROM_ALBUM,
        "value"     => array(
            "albumId"     => "2103689571" , 
        )
    );
    $crom = Crom::getInstance($api);
    if($crom === false){
        die("参数不正确,创建对象失败");
    }
    $arr = $crom -> getInfo();
    echo json_encode($arr);
    //虾米音乐歌曲地址 , 歌曲图片 , 歌曲歌词
    $api = array(
        "from"      => CROM_XIAMI,
        "method"    => CROM_MUSICINFO,
        "value"     => array(
            "songId"     => "1802949282" , 
        )
    );
    $crom = Crom::getInstance($api);
    if($crom === false){
        die("参数不正确,创建对象失败");
    }
    $arr = $crom -> getInfo();
    echo json_encode($arr);
    //虾米音乐歌单请求
    $api = array(
        "from"      => CROM_XIAMI,
        "method"    => CROM_LIST,
        "value"     => array(
            "listId"     => "666450887" , 
        )
    );
    $crom = Crom::getInstance($api);
    if($crom === false){
        die("参数不正确,创建对象失败");
    }
    $arr = $crom -> getInfo();
    echo json_encode($arr);
    //虾米音乐歌曲搜索
    $api = array(
        "from"      => CROM_XIAMI,
        "method"    => CROM_SEARCH,
        "value"     => array(
            "key"     => "许嵩 天使" ,
            "page"    =>  1
        )
    );
    $crom = Crom::getInstance($api);
    if($crom === false){
        die("参数不正确,创建对象失败");
    }
    $arr = $crom -> getInfo();
    echo json_encode($arr);
    
    //酷狗音乐搜索
    $api = array(
        "from"      => CROM_KUGOU,
        "method"    => CROM_SEARCH,
        "value"     => array(
            "key"     => "许嵩 天使" ,
            "page"    =>  1
        )
    );
    $crom = Crom::getInstance($api);
    if($crom === false){
        die("参数不正确,创建对象失败");
    }
    $arr = $crom -> getInfo();
    echo json_encode($arr);
    //酷狗音乐专辑
    $api = array(
        "from"      => CROM_KUGOU,
        "method"    => CROM_ALBUM,
        "value"     => array(
            "albumId"     => "973251" ,
        )
    );
    $crom = Crom::getInstance($api);
    if($crom === false){
        die("参数不正确,创建对象失败");
    }
    $arr = $crom -> getInfo();
    echo json_encode($arr);
    //酷狗音乐信息
    $api = array(
        "from"      => CROM_KUGOU,
        "method"    => CROM_MUSICINFO,
        "value"     => array(
            "songId"     => "8CE1AD6015430A4A05A6E2CB7B6ED541" ,
        )
    );
    $crom = Crom::getInstance($api);
    if($crom === false){
        die("参数不正确,创建对象失败");
    }
    $arr = $crom -> getInfo();
    echo json_encode($arr);
    //酷狗音乐歌单
    $api = array(
        "from"      => CROM_KUGOU,
        "method"    => CROM_LIST,
        "value"     => array(
            "listId"     => "6666" ,
            "page"       => "1"
        )
    );
    $crom = Crom::getInstance($api);
    if($crom === false){
        die("参数不正确,创建对象失败");
    }
    $arr = $crom -> getInfo();
    echo json_encode($arr);
    
    //QQ音乐搜索
    $api = array(
        "from"      => CROM_QQ,
        "method"    => CROM_SEARCH,
        "value"     => array(
            "key"        => "许嵩 天使" ,
            "page"       => "1"
        )
    );
    $crom = Crom::getInstance($api);
    if($crom === false){
        die("参数不正确,创建对象失败");
    }
    $arr = $crom -> getInfo();
    echo json_encode($arr);
    //QQ音乐歌曲获取
    $api = array(
        "from"      => CROM_QQ,
        "method"    => CROM_MUSICINFO,
        "value"     => array(
            "songId"        => "228771359" ,
        )
    );
    $crom = Crom::getInstance($api);
    if($crom === false){
        die("参数不正确,创建对象失败");
    }
    $arr = $crom -> getInfo();
    echo json_encode($arr);
    //qq音乐歌单获取
    $api = array(
        "from"      => CROM_QQ,
        "method"    => CROM_LIST,
        "value"     => array(
            "listId"        => "6549175773" ,
        )
    );
    $crom = Crom::getInstance($api);
    if($crom === false){
        die("参数不正确,创建对象失败");
    }
    $arr = $crom -> getInfo();
    echo json_encode($arr);
    //qq音乐专辑获取
    $api = array(
        "from"      => CROM_QQ,
        "method"    => CROM_ALBUM,
        "value"     => array(
            "albumId"        => "001esgZv3aiCpZ" ,
        )
    );
    $crom = Crom::getInstance($api);
    if($crom === false){
        die("参数不正确,创建对象失败");
    }
    $arr = $crom -> getInfo();
    echo json_encode($arr);
    
    //百度音乐歌曲信息
    $api = array(
        "from"      => CROM_BAIDU,
        "method"    => CROM_MUSICINFO,
        "value"     => array(
            "songId"        => "591470374" ,
        )
    );
    $crom = Crom::getInstance($api);
    if($crom === false){
        die("参数不正确,创建对象失败");
    }
    $arr = $crom -> getInfo();
    echo json_encode($arr);
    //百度歌单
    $api = array(
        "from"      => CROM_BAIDU,
        "method"    => CROM_LIST,
        "value"     => array(
            "listId"        => "566071906" ,
        )
    );
    $crom = Crom::getInstance($api);
    if($crom === false){
        die("参数不正确,创建对象失败");
    }
    $arr = $crom -> getInfo();
    echo json_encode($arr);
    //百度搜索
    $api = array(
        "from"      => CROM_BAIDU,
        "method"    => CROM_SEARCH,
        "value"     => array(
            "key"        => "许嵩 天使" ,
            "page"       => "1"
        )
    );
    $crom = Crom::getInstance($api);
    if($crom === false){
        die("参数不正确,创建对象失败");
    }
    $arr = $crom -> getInfo();
    echo json_encode($arr);
    //百度专辑
    $api = array(
        "from"      => CROM_BAIDU,
        "method"    => CROM_ALBUM,
        "value"     => array(
            "albumId"        => "608296302" ,
        )
    );
    $crom = Crom::getInstance($api);
    if($crom === false){
        die("参数不正确,创建对象失败");
    }
    $arr = $crom -> getInfo();
    echo json_encode($arr);
?>
