<?php

namespace mon\util;

use mon\util\Instance;

/**
 * 公共工具类库(数据处理)
 *
 * @author Mon <985558837@qq.com>
 * @version v1.0.0
 */
class Common
{
    use Instance;

    /**
     * 字符串编码过滤（中文、英文、数字不过滤，只过滤特殊字符）
     *
     * @param  string $src 安全转码的字符串
     * @return string
     */
    public function encodeEX($src)
    {
        $result = '';
        $len = mb_strlen($src);
        $encode_buf = '';
        for ($i = 0; $i < $len; $i++) {
            $sChar = mb_substr($src, $i, 1);
            switch ($sChar) {
                case "~":
                case "`":
                case "!":
                case "@":
                case "#":
                case "$":
                case "%":
                case "^":
                case "&":
                case "*":
                case "(":
                case ")":
                case "-":
                case "_":
                case "+":
                case "=":
                case "{":
                case "}":
                case "[":
                case "]":
                case "|":
                case "\\":
                case ";":
                case ":":
                case "\"":
                case ",":
                case "<":
                case ">":
                case ".":
                case "?":
                case "/":
                case " ":
                case "'":
                case "\"":
                case "\n":
                case "\r":
                case "\t":
                    $encode_buf = sprintf("%%%s", bin2hex($sChar));
                    $result .= $encode_buf;
                    break;
                default:
                    $result .= $sChar;
                    break;
            }
        }

        return $result;
    }

    /**
     * 字符串解码（对应encodeEX）
     *
     * @param  string $src 安全解码的字符串
     * @return string
     */
    public function decodeEX($src)
    {
        $result = '';
        $len = mb_strlen($src);
        for ($i = 0; $i < $len; $i++) {
            $sChar = mb_substr($src, $i, 1);
            if ($sChar == '%' && $i < ($len - 2) && $this->IsXDigit(mb_substr($src, $i + 1, 1)) && $this->IsXDigit(mb_substr($src, $i + 2, 1))) {
                $chDecode = mb_substr($src, $i + 1, 2);
                $result .= pack("H*", $chDecode);
                $i += 2;
            } else {
                $result .= $sChar;
            }
        }

        return $result;
    }

    /**
     * 字符串加密方法
     *
     * @param  string $str  加密的字符串
     * @param  string $salt 加密盐
     * @return string
     */
    public function encryption($str, $salt)
    {
        $str = base64_encode($this->randString(4, 5) . "." . $str . "." . $this->randString(4, 5));
        $key = base64_encode($salt);
        $str = base64_encode($str);
        $mix = mb_strlen($key) >= mb_strlen($str) ? ceil(mb_strlen($key) / mb_strlen($str)) : ceil(mb_strlen($str) / mb_strlen($key));
        $temp = str_split($str);
        $ftmp = str_split($key);
        foreach ($ftmp as $k => $v) {
            isset($temp[$k * $mix]) && $temp[$k * $mix] .= $v;
        }
        $str = str_replace(array("=", "+", "/"), array("i00i", "k00k", "z00z"), implode($temp));
        return base64_encode($str);
    }

    /**
     * 字符串解密方法
     *
     * @param  string $str  解密的字符串
     * @param  string $salt 解密的盐
     * @return string
     */
    public function decryption($str, $salt)
    {
        $str = base64_decode($str);
        if (empty($str)) {
            return '';
        };
        $key = base64_encode($salt);
        $str = str_replace(array("i00i", "k00k", "z00z"), array("=", "+", "/"), $str);
        $mix = mb_strlen($key) >= mb_strlen($str) ? ceil(mb_strlen($key) / mb_strlen($str)) : ceil(mb_strlen($str) / mb_strlen($key));
        $temp = str_split($str);
        for ($k = 0; $k < mb_strlen($key); $k++) {
            if (!isset($temp[$k * $mix + 1])) {
                break;
            }
            unset($temp[$k * $mix + 1]);
        }
        $str = base64_decode(base64_decode(implode($temp)));
        $_arr = explode(".", $str);
        return isset($_arr[1]) ? $_arr[1] : null;
    }

    /**
     * 判断是否为16进制，由于PHP没有相关的API，所以折中处理
     *
     * @param  string  $src 验证的字符串
     * @return boolean
     */
    public function isXDigit($src)
    {
        if (mb_strlen($src) < 1) {
            return false;
        }
        if (($src >= '0' && $src <= '9') || ($src >= 'A' && $src <= 'F') || ($src >= 'a' && $src <= 'f')) {
            return true;
        }

        return false;
    }

    /**
     * 检查字符串是否是UTF8编码
     *
     * @param string $string 验证的字符串
     * @return boolean
     */
    public function isUtf8($str)
    {
        $c = 0;
        $b = 0;
        $bits = 0;
        $len = mb_strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($str[$i]);
            if ($c > 128) {
                if (($c >= 254)) {
                    return false;
                } elseif ($c >= 252) {
                    $bits = 6;
                } elseif ($c >= 248) {
                    $bits = 5;
                } elseif ($c >= 240) {
                    $bits = 4;
                } elseif ($c >= 224) {
                    $bits = 3;
                } elseif ($c >= 192) {
                    $bits = 2;
                } else {
                    return false;
                }

                if (($i + $bits) > $len) {
                    return false;
                }
                while ($bits > 1) {
                    $i++;
                    $b = ord($str[$i]);
                    if ($b < 128 || $b > 191) {
                        return false;
                    }
                    $bits--;
                }
            }
        }
        return true;
    }

    /**
     * 获取余数
     *
     * @param  integer $bn 被除数
     * @param  integer $sn 除数
     * @return integer 余
     */
    public function mod($bn, $sn)
    {
        $mod = intval(fmod(floatval($bn), $sn));
        return abs($mod);
    }

    /**
     * 返回正数的ip2long值
     *
     * @param  string $ip ip
     * @return integer
     */
    public function ip2long_positive($ip)
    {
        return sprintf("%u", $this->ip2long_mon($ip));
    }

    /**
     * IP地址转为数字地址
     * php 的 ip2long 这个函数有问题
     * 133.205.0.0 ==>> 2244804608
     *
     * @param string $ip 要转换的 ip 地址
     * @return integer 转换完成的数字
     */
    public function ip2long_mon($ip)
    {
        $ip_arr = explode('.', $ip);
        $iplong = (16777216 * intval($ip_arr[0])) + (65536 * intval($ip_arr[1])) + (256 * intval($ip_arr[2])) + intval($ip_arr[3]);

        return $iplong;
    }

    /**
     * XML转数组
     *
     * @param  string $xml XML内容
     * @return array
     */
    public function xml2array($xml)
    {
        $p = xml_parser_create();
        xml_parse_into_struct($p, $xml, $vals, $index);
        xml_parser_free($p);
        $data = [];
        foreach ($index as $key => $value) {
            if (strtolower($key) == 'xml') {
                continue;
            }
            $tag = $vals[$value[0]]['tag'];
            $value = $vals[$value[0]]['value'];
            $data[$tag] = $value;
        }
        return $data;
    }

    /**
     * URI字符串转数组
     *
     * @param  string $str 入参，待转换的字符串
     * @return array 字符数组
     */
    public function strToMap($str)
    {
        $str = trim($str);
        $infoMap = array();
        $strArr = explode("&", $str);
        for ($i = 0; $i < count($strArr); $i++) {
            $infoArr = explode("=", $strArr[$i]);
            if (count($infoArr) != 2) {
                continue;
            }
            $infoMap[$infoArr[0]] = $infoArr[1];
        }
        return $infoMap;
    }

    /**
     * 数组转字符串
     *
     * @param  array $map 入参，待转换的数组
     * @return string
     */
    public function mapToStr(array $map)
    {
        $str = "";
        if (!empty($map)) {
            foreach ($map as $k => $v) {
                $str .= "&" . $k . "=" . $v;
            }
        }

        return $str;
    }

    /**
     * 二维数组去重(键&值不能完全相同)
     *
     * @param  array $arr    需要去重的数组
     * @return array
     */
    public function array_2D_unique(array $arr)
    {
        foreach ($arr as $v) {
            // 降维,将一维数组转换为用","连接的字符串.
            $v = implode(",", $v);
            $result[] = $v;
        }
        // 去掉重复的字符串,也就是重复的一维数组
        $result = array_unique($result);

        // 重组数组
        foreach ($result as $k => $v) {
            // 再将拆开的数组重新组装
            $result[$k] = explode(",", $v);
        }
        sort($result);

        return $result;
    }

    /**
     * 二维数组去重(值不能相同)
     *
     * @param  array $arr    需要去重的数组
     * @return array
     */
    public function array_2D_value_unique(array $arr)
    {
        $tmp = array();
        foreach ($arr as $k => $v) {
            // 搜索$v[$key]是否在$tmp数组中存在，若存在返回true
            if (in_array($v, $tmp)) {
                unset($arr[$k]);
            } else {
                $tmp[] = $v;
            }
        }
        sort($arr);

        return $arr;
    }

    /**
     * 是否为关联数组
     *
     * @param  array   $array 验证码的数组
     * @return boolean
     */
    public function isAssoc(array $array)
    {
        $keys = array_keys($array);
        return array_keys($keys) !== $keys;
    }

    /**
     * 二维数组排序
     *
     * @param array $array  排序的数组
     * @param string $keys  排序的键名
     * @param integer $sort 排序方式，默认值：SORT_DESC
     * @return array
     */
    public function array2DSort($array, $keys, $sort = SORT_DESC)
    {
        $keysValue = [];
        foreach ($array as $k => $v) {
            $keysValue[$k] = $v[$keys];
        }
        array_multisort($keysValue, $sort, $array);
        return $array;
    }

    /**
     * php获取中文字符拼音首字母
     *
     * @param  string $str 中文字符串
     * @return string
     */
    public function getFirstChar($str)
    {
        if (empty($str)) {
            return '';
        }
        $fchar = ord($str[0]);
        if ($fchar >= ord('A') && $fchar <= ord('z')) {
            return strtoupper($str[0]);
        }
        $s1 = iconv('UTF-8', 'gb2312', $str);
        $s2 = iconv('gb2312', 'UTF-8', $s1);
        $s = $s2 == $str ? $s1 : $str;
        if (empty($s[1])) {
            return '';
        }
        $asc = ord($s[0]) * 256 + ord($s[1]) - 65536;
        if ($asc >= -20319 && $asc <= -20284) return 'A';
        if ($asc >= -20283 && $asc <= -19776) return 'B';
        if ($asc >= -19775 && $asc <= -19219) return 'C';
        if ($asc >= -19218 && $asc <= -18711) return 'D';
        if ($asc >= -18710 && $asc <= -18527) return 'E';
        if ($asc >= -18526 && $asc <= -18240) return 'F';
        if ($asc >= -18239 && $asc <= -17923) return 'G';
        if ($asc >= -17922 && $asc <= -17418) return 'H';
        if ($asc >= -17417 && $asc <= -16475) return 'J';
        if ($asc >= -16474 && $asc <= -16213) return 'K';
        if ($asc >= -16212 && $asc <= -15641) return 'L';
        if ($asc >= -15640 && $asc <= -15166) return 'M';
        if ($asc >= -15165 && $asc <= -14923) return 'N';
        if ($asc >= -14922 && $asc <= -14915) return 'O';
        if ($asc >= -14914 && $asc <= -14631) return 'P';
        if ($asc >= -14630 && $asc <= -14150) return 'Q';
        if ($asc >= -14149 && $asc <= -14091) return 'R';
        if ($asc >= -14090 && $asc <= -13319) return 'S';
        if ($asc >= -13318 && $asc <= -12839) return 'T';
        if ($asc >= -12838 && $asc <= -12557) return 'W';
        if ($asc >= -12556 && $asc <= -11848) return 'X';
        if ($asc >= -11847 && $asc <= -11056) return 'Y';
        if ($asc >= -11055 && $asc <= -10247) return 'Z';
        return null;
    }

    /**
     * 生成UUID 单机使用
     *
     * @return string
     */
    public function uuid()
    {
        $charid = md5(uniqid(mt_rand(), true));
        $hyphen = chr(45); // "-"
        $uuid = mb_substr($charid, 0, 8) . $hyphen
            . mb_substr($charid, 8, 4) . $hyphen
            . mb_substr($charid, 12, 4) . $hyphen
            . mb_substr($charid, 16, 4) . $hyphen
            . mb_substr($charid, 20, 12);

        return $uuid;
    }

    /**
     * 生成Guid主键
     *
     * @return string
     */
    public function keyGen()
    {
        return str_replace('-', '', mb_substr($this->uuid(), 1, -1));
    }

    /**
     * 字符串截取，支持中文和其他编码
     *
     * @param string $str       需要转换的字符串
     * @param string $start     开始位置
     * @param string $length    截取长度
     * @param string $charset   编码格式
     * @param string $suffix    截断显示字符
     * @return string
     */
    public function mSubstr($str, $length, $start = 0, $charset = "utf-8", $suffix = true)
    {
        if (function_exists("mb_substr")) {
            $slice = mb_substr($str, $start, $length, $charset);
        } elseif (function_exists('iconv_substr')) {
            $slice = iconv_substr($str, $start, $length, $charset);
        } else {
            $re['utf-8']  = '/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/';
            $re['gb2312'] = '/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/';
            $re['gbk']    = '/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/';
            $re['big5']   = '/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/';
            preg_match_all($re[$charset], $str, $match);
            $slice = join('', array_slice($match[0], $start, $length));
        }

        return $suffix ? $slice . '...' : $slice;
    }

    /**
     * 产生随机字串，可用来自动生成密码
     * 默认长度6位 字母和数字混合 支持中文
     *
     * @param string $len       长度
     * @param string $type      字串类型，0:字母;1:数字;2:大写字母;3:小写字母;4:中文;5:字母数字混合;othor:过滤掉混淆字符的字母数字组合
     * @param string $addChars  额外字符
     * @return string
     */
    public function randString($len = 6, $type = '', $addChars = '')
    {
        $str = '';
        switch ($type) {
            case '0':
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            case '1':
                $chars = str_repeat('0123456789' . $addChars, 3);
                break;
            case '2':
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
                break;
            case '3':
                $chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
                break;
            case '4':
                $chars = '们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗泥辟告卵箱掌氧恩爱停曾溶营终纲孟钱待尽俄缩沙退陈讨奋械载胞幼哪剥迫旋征槽倒握担仍呀鲜吧卡粗介钻逐弱脚怕盐末阴丰雾冠丙街莱贝辐肠付吉渗瑞惊顿挤秒悬姆烂森糖圣凹陶词迟蚕亿矩康遵牧遭幅园腔订香肉弟屋敏恢忘编印蜂急拿扩伤飞露核缘游振操央伍域甚迅辉异序免纸夜乡久隶缸夹念兰映沟乙吗儒杀汽磷艰晶插埃燃欢铁补咱芽永瓦倾阵碳演威附牙芽永瓦斜灌欧献顺猪洋腐请透司危括脉宜笑若尾束壮暴企菜穗楚汉愈绿拖牛份染既秋遍锻玉夏疗尖殖井费州访吹荣铜沿替滚客召旱悟刺脑措贯藏敢令隙炉壳硫煤迎铸粘探临薄旬善福纵择礼愿伏残雷延烟句纯渐耕跑泽慢栽鲁赤繁境潮横掉锥希池败船假亮谓托伙哲怀割摆贡呈劲财仪沉炼麻罪祖息车穿货销齐鼠抽画饲龙库守筑房歌寒喜哥洗蚀废纳腹乎录镜妇恶脂庄擦险赞钟摇典柄辩竹谷卖乱虚桥奥伯赶垂途额壁网截野遗静谋弄挂课镇妄盛耐援扎虑键归符庆聚绕摩忙舞遇索顾胶羊湖钉仁音迹碎伸灯避泛亡答勇频皇柳哈揭甘诺概宪浓岛袭谁洪谢炮浇斑讯懂灵蛋闭孩释乳巨徒私银伊景坦累匀霉杜乐勒隔弯绩招绍胡呼痛峰零柴簧午跳居尚丁秦稍追梁折耗碱殊岗挖氏刃剧堆赫荷胸衡勤膜篇登驻案刊秧缓凸役剪川雪链渔啦脸户洛孢勃盟买杨宗焦赛旗滤硅炭股坐蒸凝竟陷枪黎救冒暗洞犯筒您宋弧爆谬涂味津臂障褐陆啊健尊豆拔莫抵桑坡缝警挑污冰柬嘴啥饭塑寄赵喊垫丹渡耳刨虎笔稀昆浪萨茶滴浅拥穴覆伦娘吨浸袖珠雌妈紫戏塔锤震岁貌洁剖牢锋疑霸闪埔猛诉刷狠忽灾闹乔唐漏闻沈熔氯荒茎男凡抢像浆旁玻亦忠唱蒙予纷捕锁尤乘乌智淡允叛畜俘摸锈扫毕璃宝芯爷鉴秘净蒋钙肩腾枯抛轨堂拌爸循诱祝励肯酒绳穷塘燥泡袋朗喂铝软渠颗惯贸粪综墙趋彼届墨碍启逆卸航衣孙龄岭骗休借' . $addChars;
                break;
            case '5':
                $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890' . $addChars;
                break;
            default:
                // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
                $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
                break;
        }
        if ($len > 10) {
            //位数过长重复字符串一定次数
            $chars = ($type == 1) ? str_repeat($chars, $len) : str_repeat($chars, 5);
        }
        if ($type != 4) {
            $chars = str_shuffle($chars);
            $str = mb_substr($chars, 0, $len);
        } else {
            // 中文随机字
            for ($i = 0; $i < $len; $i++) {
                $str .= $this->msubstr($chars, floor(mt_rand(0, mb_strlen($chars, 'utf-8') - 1)), 1, 'utf-8', false);
            }
        }

        return $str;
    }

    /**
     * 递归转换字符集
     *
     * @param  mixed  $data         要转换的数据
     * @param  string $out_charset  输出编码
     * @param  string $in_charset   输入编码
     * @return mixed
     */
    public function iconv_recursion($data, $out_charset, $in_charset)
    {
        switch (gettype($data)) {
            case 'integer':
            case 'boolean':
            case 'float':
            case 'double':
            case 'NULL':
                return $data;
            case 'string':
                if (empty($data) || is_numeric($data)) {
                    return $data;
                } elseif (function_exists('mb_convert_encoding')) {
                    $data = mb_convert_encoding($data, $out_charset, $in_charset);
                } elseif (function_exists('iconv')) {
                    $data = iconv($in_charset, $out_charset, $data);
                }

                return $data;
            case 'object':
                $vars = array_keys(get_object_vars($data));
                foreach ($vars as $key) {
                    $data->$key = $this->iconv_recursion($data->$key, $out_charset, $in_charset);
                }
                return $data;
            case 'array':
                foreach ($data as $k => $v) {
                    $data[$this->iconv_recursion($k, $out_charset, $in_charset)] = $this->iconv_recursion($v, $out_charset, $in_charset);
                }
                return $data;
            default:
                return $data;
        }
    }

    /**
     * 笛卡尔积生成规格
     *
     * @param array $arr1   要进行笛卡尔积的二维数组
     * @param array $arr2   最终实现的笛卡尔积组合,可不传
     * @return array
     */
    public function specCartesian($arr1, $arr2 = [])
    {
        $result = [];
        if (!empty($arr1)) {
            // 去除第一个元素
            $first = array_splice($arr1, 0, 1);
            // 判断是否是第一次进行拼接
            if (count($arr2) > 0) {
                foreach ($arr2 as $v) {
                    foreach ($first[0]['value'] as $vs) {
                        $result[] = $v . ',' . $vs;
                    }
                }
            } else {
                foreach ($first[0]['value'] as $vs) {
                    $result[] = $vs;
                }
            }
            // 递归进行拼接
            if (count($arr1) > 0) {
                $result = $this->specCartesian($arr1, $result);
            }
        }
        return $result;
    }

    /**
     * 字符串转Ascii码
     *
     * @param string $str 字符串  
     * @return string
     */
    public function str2ascii($str)
    {
        $change_after = '';
        if (!empty($str)) {
            // 编码处理
            $encode = mb_detect_encoding($str);
            if ($encode != 'UTF-8') {
                $str = mb_convert_encoding($str, 'UTF-8', $encode);
            }
            // 开始转换
            for ($i = 0, $l = mb_strlen($str); $i < $l; $i++) {
                $temp_str = dechex(ord($str[$i]));
                if (isset($temp_str[1])) {
                    $change_after .= $temp_str[1];
                }
                if (isset($temp_str[0])) {
                    $change_after .= $temp_str[0];
                }
            }
        }
        return strtoupper($change_after);
    }

    /**
     * Ascii码转字符串
     *
     * @param string $ascii Ascii码
     * @return string
     */
    public function ascii2str($ascii)
    {
        $str = '';
        if (!empty($ascii)) {
            // 开始转换
            $asc_arr = str_split(strtolower($ascii), 2);
            for ($i = 0; $i < count($asc_arr); $i++) {
                $str .= chr(hexdec($asc_arr[$i][1] . $asc_arr[$i][0]));
            }
            // 编码处理
            $encode = mb_detect_encoding($str);
            if ($encode != 'UTF-8') {
                $str = mb_convert_encoding($str, 'UTF-8', $encode);
            }
        }
        return $str;
    }

    /**
     * 删除字符串中的空格
     *
     * @param $str 要删除空格的字符串
     * @return $str 返回删除空格后的字符串
     */
    public function trimall($str)
    {
        $str = str_replace(" ", '', $str);
        $str = str_ireplace(array("\r", "\n", '\r', '\n'), '', $str);

        return $str;
    }

    /**
     * 将一个字符串部分字符用$re替代隐藏
     *
     * @param string    $string   待处理的字符串
     * @param integer   $start    规定在字符串的何处开始，
     *                            正数 - 在字符串的指定位置开始
     *                            负数 - 在从字符串结尾的指定位置开始
     *                            0 - 在字符串中的第一个字符处开始
     * @param integer   $length   可选。规定要隐藏的字符串长度。默认是直到字符串的结尾。
     *                            正数 - 从 start 参数所在的位置隐藏
     *                            负数 - 从字符串末端隐藏
     * @param string    $re       替代符
     * @return string   处理后的字符串
     */
    public function hidestr($string, $start = 0, $length = 0, $re = '*')
    {
        if (empty($string)) {
            return false;
        }
        $strarr = [];
        $mb_strlen = mb_strlen($string);
        while ($mb_strlen) {
            $strarr[] = mb_substr($string, 0, 1, 'utf8');
            $string = mb_substr($string, 1, $mb_strlen, 'utf8');
            $mb_strlen = mb_strlen($string);
        }
        $strlen = count($strarr);
        $begin  = $start >= 0 ? $start : ($strlen - abs($start));
        $end    = $last   = $strlen - 1;
        if ($length > 0) {
            $end  = $begin + $length - 1;
        } elseif ($length < 0) {
            $end -= abs($length);
        }

        for ($i = $begin; $i <= $end; $i++) {
            $strarr[$i] = $re;
        }
        if ($begin >= $end || $begin >= $last || $end > $last) {
            return false;
        }

        return implode('', $strarr);
    }
}
