<?php
if (!defined('_GNUBOARD_')) exit;

function get_social_skin_path(){
    global $config, $member_skin_path, $member_skin_url;

    static $skin_path = '';

    if( $skin_path ){
        return $skin_path;
    }

	$dir = G5_SOCIAL_LOGIN_DIR;

    if( USE_G5_THEME && defined('G5_THEME_PATH') && $config['cf_theme'] ){
        $cf_theme = trim($config['cf_theme']);

        $theme_path = G5_PATH.'/'.G5_THEME_DIR.'/'.$cf_theme;

        if(G5_IS_MOBILE) {
            $skin_path = $theme_path.'/'.G5_MOBILE_DIR.'/'.G5_SKIN_DIR.'/'.$dir;
            if(!is_dir($skin_path))
                $skin_path = $theme_path.'/'.G5_SKIN_DIR.'/'.$dir;
        } else {
            $skin_path = $theme_path.'/'.G5_SKIN_DIR.'/'.$dir;
        }
	}

    if( ! ($skin_path && is_dir($skin_path)) ){
		list($member_skin_path, $member_skin_url) = apms_skin_thema('member', $member_skin_path, $member_skin_url); 
		$skin_path = $member_skin_path;
    }

    return $skin_path;
}

function get_social_skin_url(){

    $skin_path = get_social_skin_path();

    return str_replace(G5_PATH, G5_URL, $skin_path);
}

function get_social_convert_id($identifier, $service)
{
    return strtolower($service).'_'.hash('adler32', md5($identifier));
}

function get_social_callbackurl($provider, $no_domain=false, $no_params=false){

    $base_url = G5_SOCIAL_LOGIN_BASE_URL;

    if ( $provider === 'twitter' || ($provider === 'payco' && $no_params) ){
        return $base_url;
    }

    $base_url = $base_url . ( strpos($base_url, '?') ? '&' : '?' ).G5_SOCIAL_LOGIN_DONE_PARAM.'='.$provider;

    return $base_url;
}

function social_return_from_provider_page( $provider, $login_action_url, $mb_id, $mb_password, $url, $use_popup=2 ){

    $ref = $_SERVER['HTTP_REFERER'];

    if( !G5_SOCIAL_USE_POPUP || strpos($ref, 'login_check.php') !== false ){
        if( get_session('social_login_redirect') ){
            unset($_SESSION['social_login_redirect']);
            goto_url(G5_BBS_URL.'/login.php?url='.urlencode($url));
        } else {
            set_session('social_login_redirect', 1);
        }
    }
    
    $img_url = G5_SOCIAL_LOGIN_URL.'/img/';
    include_once(G5_SOCIAL_LOGIN_PATH.'/includes/loading.php');
}

/**
* Returns hybriauth idp adapter.
*/
function social_login_get_provider_adapter( $provider )
{
    global $g5;

	if( ! class_exists( 'Hybrid_Auth', false ) )
	{
		include_once G5_SOCIAL_LOGIN_PATH . "/Hybrid/Auth.php";
	}

    if( ! is_object($g5['hybrid_auth']) ){
        $setting = social_build_provider_config($provider);
        $g5['hybrid_auth'] = new Hybrid_Auth( $setting );
    }

    //$newsession  = $g5['hybrid_auth']->getSessionData();

    if( defined('G5_SOCIAL_LOGIN_START_PARAM') && G5_SOCIAL_LOGIN_START_PARAM === 'hauth.start' && G5_SOCIAL_LOGIN_DONE_PARAM === 'hauth.done' ){
        return $g5['hybrid_auth']->authenticate($provider);
    }
    
    $base_url = G5_SOCIAL_LOGIN_BASE_URL;
    $hauth_time = time();

    $connect_data = array(
            'login_start' => $base_url . ( strpos($base_url, '?') ? '&' : '?' ) . G5_SOCIAL_LOGIN_START_PARAM.'='.$provider.'&hauth.time='.$hauth_time,
            'login_done'  => $base_url . ( strpos($base_url, '?') ? '&' : '?' ) . G5_SOCIAL_LOGIN_DONE_PARAM.'='.$provider,
    );

    return $g5['hybrid_auth']->authenticate($provider, $connect_data);
}

function social_before_join_check($url=''){
    global $g5, $config;

    if( $provider_name = social_get_request_provider() ){
        //????????? ??????
        if( $user_profile = social_session_exists_check() ){

            $sql = sprintf("select * from {$g5['social_profile_table']} where provider = '%s' and identifier = '%s' ", $provider_name, $user_profile->identifier);

            $is_exist = false;

            $row = sql_fetch($sql);

            if( $row['provider'] ){
                $is_exist = true;

                $time = time() - (86400 * (int) G5_SOCIAL_DELETE_DAY);
                
                if( empty($row['mb_id']) && ( 0 == G5_SOCIAL_DELETE_DAY || strtotime($row['mp_latest_day']) < $time) ){

                    $sql = "delete from {$g5['social_profile_table']} where mp_no =".$row['mp_no'];

                    sql_query($sql);

                    $is_exist = false;
                }
            }

            if( $is_exist ){
                $msg = sprintf("?????? %s ID ??? ?????? ?????? ????????? ????????? ?????? ????????? ?????? ???????????? ????????????. ??????????????? ????????? ??? ?????? ???????????? ?????? ????????? ??? ?????????.", social_get_provider_service_name($provider_name) );

                $url = $url ? $url : G5_URL;
                alert($msg, $url);
                return false;
            }
        }

        return true;
    }

    return false;
}

function social_get_data($by='provider', $provider, $user_profile){
    global $g5;

    // ?????? ????????? ?????? ????????? ??????
    if( $by == 'provider' ){
        
        $sql = sprintf("select * from {$g5['social_profile_table']} where provider = '%s' and identifier = '%s' order by mb_id desc ", $provider, $user_profile->identifier);

        $row = sql_fetch($sql);

        if( !empty($row['mb_id']) ){
            return $row;    //mb_id ??? ?????? ???????????? ???????????? ???????????????.
        }

        return false;

    } else if ( $by == 'member' ){  // ????????? ?????? ??????????????? ???????????? ?????? ???????????? ????????? ??????

        $email = ($user_profile->emailVerified) ? $user_profile->emailVerified : $user_profile->email;
        $sid = preg_match("/[^0-9a-z_]+/i", "", $user_profile->sid);
        $nick = social_relace_nick($user_profile->displayName);
        if( !$nick ){
            $tmp = explode("@", $email);
            $nick = $tmp[0];
        }

        $sql = "select mb_nick, mb_email from {$g5['member_table']} where mb_nick = '".$nick."' ";

        if( !empty($email) ){
            $sql .= sprintf(" or mb_email = '%s' ", $email);
        }

        $result = sql_query($sql);

        $exists = array();

        while($row=sql_fetch_array($result)){
            if($row['mb_nick'] && $row['mb_nick'] == $nick){
                $exists['mb_nick'] = $nick;
            }
            if($row['mb_email'] && $row['mb_email'] == $email){
                $exists['mb_email'] = $email;
            }
        }

        return $exists;

    }

    return null;
}

function social_user_profile_replace( $mb_id, $provider, $profile ){
    global $g5;

    if( !$mb_id )
        return;

    // $profile ??? ??????, ??????, ?????? ?????? ????????? ???????????? ????????????.

    //????????? ????????? ????????? ??????
    $object_sha = sha1( serialize( $profile ) );
    
    $provider = strtolower($provider);

    $sql = sprintf("SELECT mp_no, mb_id from {$g5['social_profile_table']} where provider= '%s' and identifier = '%s' ", $provider, $profile->identifier);
    $result = sql_query($sql);
    for($i=0;$row=sql_fetch_array($result);$i++){   //?????? ?????? ?????? ???????????? ????????? ???????????????.
        if( $row['mb_id'] != $mb_id ){
           sql_query(sprintf("DELETE FROM {$g5['social_profile_table']} where mp_no=%d", $row['mp_no']));
        }
    }
    
    $sql = sprintf("SELECT mp_no, object_sha, mp_register_day from {$g5['social_profile_table']} where mb_id= '%s' and provider= '%s' and identifier = '%s' ", $mb_id, $provider, $profile->identifier);

    $row = sql_fetch($sql);

    $table_data = array(
        "mp_no"    =>  ! empty($row) ? $row['mp_no'] : 'NULL',
        'mb_id' =>  "'". $mb_id. "'",
        'provider'  => "'".  $provider . "'",
        'object_sha'    => "'". $object_sha . "'",
        'mp_register_day' => ! empty($row) ? "'".$row['mp_register_day']."'" : "'". G5_TIME_YMDHIS . "'",
        'mp_latest_day' => "'". G5_TIME_YMDHIS . "'",
    );

    $fields = array( 
        'identifier',
        'profileurl',
        'photourl',
        'displayname',
        'description',
    );

    foreach( (array) $profile as $key => $value ){
        $key = strtolower($key);

        if( in_array( $key, $fields ) )
        {
            $value = (string) $value;
            $table_data[ $key ] = "'". sql_real_escape_string($value). "'";
        }
    }
    
    $fields  = '`' . implode( '`, `', array_keys( $table_data ) ) . '`';
    $values = implode( ", ", array_values( $table_data )  );

    $sql = "REPLACE INTO {$g5['social_profile_table']} ($fields) VALUES ($values) ";

    sql_query($sql);

    return sql_insert_id();

}

function social_build_provider_config($provider){
    $setting = array(
        'base_url'  =>  https_url(G5_PLUGIN_DIR.'/'.G5_SOCIAL_LOGIN_DIR).'/',
        'providers' =>  array(
            $provider   =>  array(
                    'enabled'   => true,
                    'keys'  =>  array( 'id' => null, 'key' => null, 'secret' => null )
                )
            ),
        );

    if( function_exists('social_extends_get_keys') ){
        $setting['providers'][$provider] = social_extends_get_keys($provider);
    }

    if(defined('G5_SOCIAL_IS_DEBUG') && G5_SOCIAL_IS_DEBUG){
        $setting['debug_mode'] = true;
        $setting['debug_file'] = G5_DATA_PATH.'/tmp/social_'.md5($_SERVER['SERVER_SOFTWARE'].$_SERVER['SERVER_ADDR']).'_'.date('ymd').'.log';
    }

    return $setting;
}

function social_extends_get_keys($provider){

    global $config;

    static $r = array();

    if ( empty($r) ) {

        // Naver
        $r['Naver'] = array(
                    "enabled" => option_array_checked('naver', $config['cf_social_servicelist']) ? true : false,
                    "redirect_uri" => get_social_callbackurl('naver'),
                    "keys" => array(
                        "id" => $config['cf_naver_clientid'],
                        "secret" => $config['cf_naver_secret'],
                    ),
                );

        // Kakao
        $r['Kakao'] = array(
                    "enabled" => option_array_checked('kakao', $config['cf_social_servicelist']) ? true : false,
                    "keys" => array("id" => $config['cf_kakao_rest_key'],
                                    "secret" => $config['cf_kakao_client_secret'] ? $config['cf_kakao_client_secret'] : $config['cf_kakao_rest_key']
                    ),
                    "redirect_uri" => get_social_callbackurl('kakao')
                );

        // Facebook
        $r['Facebook'] = array(
                    "enabled" => option_array_checked('facebook', $config['cf_social_servicelist']) ? true : false,
                    "keys" => array("id" => $config['cf_facebook_appid'], "secret" => $config['cf_facebook_secret']),
                    "display"   =>  "popup",
                    "redirect_uri" => get_social_callbackurl('facebook'),
                    "scope"   => 'email', // optional
                    "trustForwarded" => false
                );

        // Google
        $r['Google'] = array(
                    "enabled" => option_array_checked('google', $config['cf_social_servicelist']) ? true : false,
                    "keys" => array("id" => $config['cf_google_clientid'],
                    "secret" => $config['cf_google_secret']),
                    "redirect_uri" => get_social_callbackurl('google'),
                    "scope"   => "https://www.googleapis.com/auth/userinfo.profile "."https://www.googleapis.com/auth/userinfo.email",
                    /*
                    "scope"   => "https://www.googleapis.com/auth/plus.login ". // optional
                                    "https://www.googleapis.com/auth/plus.me ". // optional
                                    "https://www.googleapis.com/auth/plus.profile.emails.read", // optional
                    */
                    //"access_type"     => "offline",   // optional
                    //"approval_prompt" => "force",     // optional
                );

        // Twitter
        $r['Twitter'] = array(
                    "enabled" => option_array_checked('twitter', $config['cf_social_servicelist']) ? true : false,
                    "keys" => array("key" => $config['cf_twitter_key'], "secret" => $config['cf_twitter_secret']),
                    "redirect_uri" => get_social_callbackurl('twitter'),
                    "trustForwarded" => false
                );

        // Payco
        $r['Payco'] = array(
                    "enabled" => option_array_checked('payco', $config['cf_social_servicelist']) ? true : false,
                    "keys" => array("id" => $config['cf_payco_clientid'], "secret" => $config['cf_payco_secret']),
                    "redirect_uri" => get_social_callbackurl('payco'),
                    "trustForwarded" => false
                );
    }

    return $r[$provider];
}

function social_escape_request($request){
    return clean_xss_tags( strip_tags($request) );
}

function social_get_request_provider(){
    $provider_name = isset($_REQUEST['provider']) ? ucfirst(social_escape_request($_REQUEST['provider'])) : '';

    return $provider_name;
}

function social_login_session_clear($mycf=0){
	$_SESSION["HA::STORE"]        = array(); // used by hybridauth library. to clear as soon as the auth process ends.
	$_SESSION["HA::CONFIG"]       = array(); // used by hybridauth library. to clear as soon as the auth process ends.
    set_session('sl_userprofile', '');
    set_session('social_login_redirect', '');
    if(!$mycf){
        set_session('ss_social_provider', '');
    }
}

function social_session_exists_check(){

    $provider_name = social_get_request_provider();

    if(!$provider_name){
        return false;
    }

    if( $provider_name && isset($_SESSION['HA::STORE']['hauth_session.'.strtolower($provider_name).'.is_logged_in']) && !empty($_SESSION['sl_userprofile'][$provider_name]) ){
        $decode_value = function_exists('get_string_decrypt') ? json_decode(get_string_decrypt($_SESSION['sl_userprofile'][$provider_name])) : json_decode($_SESSION['sl_userprofile'][$provider_name]);
        return $decode_value;
    }

    return false;
}

function social_relace_nick($nick=''){

    if( empty($nick) ) return '';

    return preg_replace("/[ #\&\+\-%@=\/\\\:;,\.'\"\^`~\_|\!\?\*$#<>()\[\]\{\}]/i", "", $nick);
}

function social_get_error_msg($type){
    ob_start();

    switch( $type ){
      case 0 : echo "???????????? ?????? ???????????????."; break;
      case 1 : echo "?????? ???????????????."; break;
      case 2 : echo "?????? provider ?????? ???????????????."; break;
      case 3 : echo "?????? ????????? ???????????? ??? provider ?????????."; break;
      case 4 : echo "?????? ???????????? ???????????? ?????? ????????? ????????????."; break;
      case 5 : echo "????????? ?????????????????????.. "
                  . "???????????? ????????? ???????????????, ???????????? ????????? ??????????????????.";
               break;
      case 6 : echo "????????? ????????? ????????? ??????????????????.???????????? ?????? ???????????? ???????????? ?????? ?????? ????????? ????????????. "
                  . "??? ?????? ?????? ?????? ????????? ?????? ?????????.";
               break;
      case 7 : echo "???????????? ?????? ???????????? ???????????? ?????? ????????????.";
               break;
      case 8 : echo "?????? ???????????? ????????? ???????????? ????????????."; break;
    }
    
    $get_error = ob_get_clean();

    return $get_error;
}

if( !function_exists('replaceQueryParams') ){
    function replaceQueryParams($url, $params)
    {
        $query = parse_url($url, PHP_URL_QUERY);
        parse_str($query, $oldParams);

        if (empty($oldParams)) {
            return rtrim($url, '?') . '?' . http_build_query($params);
        }

        $params = array_merge($oldParams, $params);

        return preg_replace('#\?.*#', '?' . http_build_query($params), $url);
    }
}

function social_loading_provider_page( $provider ){
    
	social_login_session_clear(1);

    define('G5_SOCIAL_IS_LOADING', TRUE );

    $login_action_url = G5_URL;

    $img_url = G5_SOCIAL_LOGIN_URL.'/img/';
    include_once(G5_SOCIAL_LOGIN_PATH.'/includes/loading.php');
}

function social_check_login_before($p_service=''){
    global $is_member, $member;

    $action = isset( $_REQUEST['action'] ) ? social_escape_request($_REQUEST['action']) : '';
    $provider_name = $p_service ? $p_service : social_get_request_provider();
    $url = isset($_REQUEST['url']) ? $_REQUEST['url'] : G5_URL;
    $mode = isset($_REQUEST['mode']) ? $_REQUEST['mode'] : 'login';
    $use_popup = G5_SOCIAL_USE_POPUP ? 1 : 2;
    $ss_social_provider = get_session('ss_social_provider');

    if( $provider_name ){

        if( ! isset( $_REQUEST["redirect_to_idp"] ) )
        {
            return social_loading_provider_page( $provider_name );
        }

        try
        {
            $adapter = social_login_get_provider_adapter( $provider_name );
            
            // then grab the user profile 
            $user_profile = $adapter->getUserProfile();

            if( ! (isset($_SESSION['sl_userprofile']) && is_array($_SESSION['sl_userprofile'])) ){ 
                $_SESSION['sl_userprofile'] = array(); 
            }

            if( ! $is_member ){
                $encode_value = function_exists('get_string_encrypt') ? get_string_encrypt(json_encode($user_profile)) : json_encode($user_profile);
                $_SESSION['sl_userprofile'][$provider_name] = $encode_value;
            }
        }

        catch( Exception $e )
        {
            $get_error = social_get_error_msg( $e->getCode() );

            if( is_object( $adapter ) ){
                $adapter->logout();
            }

            include_once(G5_SOCIAL_LOGIN_PATH.'/error.php');
            exit;
        }

        $register_url = G5_BBS_URL.'/register_form.php?provider='.$provider_name;
        $register_action_url = G5_BBS_URL.'/register_form_update.php';

        $login_action_url = G5_HTTPS_BBS_URL."/login_check.php";
        $mylink = (isset($_REQUEST['mylink']) && !empty($_REQUEST['mylink'])) ? 1 : 0;

        //????????? ?????? ?????? ????????? ????????? ?????? ?????????.
        if( $user_provider = social_get_data('provider', $provider_name, $user_profile) ){

            if( $is_member ){

                $msg = "?????? ????????? ???????????? ????????? ???????????????.";
                
                if( $mylink ){
                    $msg = "?????? ????????? ???????????? ?????????, ????????? ???????????????.";
                }

                if( $use_popup == 1 || ! $use_popup ){   //????????????
                    alert_close( $msg );
                } else {
                    alert( $msg );
                }

                if( is_object( $adapter ) ){    //??????????????? ?????? ?????? ?????? ??????????????????.
                    social_logout_with_adapter($adapter);
                }
                exit;
            }

            //???????????? ????????? ???????????? ?????? ??? ????????? ?????? ?????????.

            $mb_id = $user_provider['mb_id'];
            //?????? ????????? ????????? ???????????? ????????? password??? ???????????? ?????????, ??????????????? ????????? ???????????? ????????????.
            $mb_password = sha1( str_shuffle( "0123456789abcdefghijklmnoABCDEFGHIJ" ) );

            echo social_return_from_provider_page( $provider_name, $login_action_url, $mb_id, $mb_password, $url, $use_popup );
            exit;

        //?????? ???????????? ?????????????????? ?????? ?????? ?????? ????????? ????????????, ?????? ????????? ????????? ???????????????.
        } else {

            if( $is_member && !empty($user_profile) ){   //????????????
                
                if( $mylink ){

                    social_user_profile_replace($member['mb_id'], $provider_name, $user_profile);

                    if( is_object( $adapter ) ){    //??????????????? ?????? ?????? ?????? ??????????????????.
                        social_logout_with_adapter($adapter);
                    }
                    
                    // ????????? ??????????????? ????????? ????????? ?????????????????? ???????????????.
                    if( ! get_session('ss_social_provider') ){
                        set_session('ss_social_provider', $provider_name);
                    }

                    if( $use_popup == 1 || ! $use_popup ){   //????????????
                    ?>
                    <script>
                        if( window.opener )
                        {
                            window.close();
                            if (typeof window.opener.social_link_fn != 'undefined')
                            {
                                window.opener.social_link_fn("<?php echo $provider_name; ?>");
                            }
                        }
                    </script>
                    <?php
                    } else {
                        if( $url ){
                            $social_token = social_nonce_create($provider_name);
                            set_session('social_link_token', $social_token);
                            
                            $params = array('provider'=>$provider_name);

                            $url = replaceQueryParams($url, $params);
                            goto_url($url);
                        } else {
                            goto_url(G5_URL);
                        }
                    }
                    exit;
                }

                goto_url(G5_URL);
            }

            if( !( property_exists($user_profile, 'sid') && !empty($user_profile->sid) ) ){
                $msg = '?????? ????????? ??????';
                if( $use_popup == 1 || ! $use_popup ){   //????????????
                    alert_close($msg);
                } else {
                    alert($msg);
                }
            }

            /*
             * ????????? ?????? ???????????? ?????? ??????
            */
            $register_url = G5_SOCIAL_LOGIN_URL.'/register_member.php?provider='.$provider_name;

            if( $url ){
                $register_url .= '&url='.urlencode($url);
            }

            if( $use_popup == 1 || ! $use_popup ){   //????????????
            ?>
                <script>
                    if( window.opener )
                    {
                        window.close();

                        if (typeof window.opener.social_link_fn != 'undefined')
                        {
                            window.opener.social_link_fn("<?php echo $provider_name; ?>");
                        } else {
                            window.opener.location.href = "<?php echo $register_url; ?>";
                        }
                    }
                </script>
            <?php
            } else {
                goto_url( $register_url );
            }

            return '';

        }
    }
}

function social_register_member_check($member){
    
    //?????? ????????? ????????? ???????????? ???????????????.
    if( $user_profile = social_session_exists_check() ){

        $member['mb_nick'] = social_relace_nick($user_profile->displayName);
        $member['mb_sex'] = $user_profile->gender;
        $member['mb_email'] = ($user_profile->emailVerified) ? $user_profile->emailVerified : $user_profile->email;

    }

    return $member;
}

function social_profile_img_resize($path, $file_url, $width, $height){
    
    // getimagesize ?????? php.ini ?????? allow_url_fopen ??? ????????? ?????? ????????? ?????????????????? ???????????? ????????????.
    list($w, $h, $ext) = @getimagesize($file_url);
    if( $w && $h && $ext ){
        $ratio = max($width/$w, $height/$h);
        $h = ceil($height / $ratio);
        $x = ($w - $width / $ratio) / 2;
        $w = ceil($width / $ratio);

        $tmp = imagecreatetruecolor($width, $height);
        
        if($ext == 1){
            $image = imagecreatefromgif($file_url);
        } else if($ext == 3) {
            $image = imagecreatefrompng($file_url);
        } else {
            $image = imagecreatefromjpeg($file_url);
        }
        imagecopyresampled($tmp, $image,
        0, 0,
        $x, 0,
        $width, $height,
        $w, $h);

        switch ($ext) {
        case '2':
          imagejpeg($tmp, $path, 100);
          break;
        case '3':
          imagepng($tmp, $path, 0);
          break;
        case '1':
          imagegif($tmp, $path);
          break;
        }
        
        chmod($path, G5_FILE_PERMISSION);

        /* cleanup memory */
        imagedestroy($image);
        imagedestroy($tmp);
    }
}

function social_is_login_check(){

    //?????? ???????????? ????????? ???????????????.
    if( social_session_exists_check() ){
        return true;
    }

    return false;
}

function social_logout_with_adapter($adapter=null){
    if( is_object( $adapter ) ){
        $adapter->logout();
    }
    social_login_session_clear(1);
}

function social_member_provider_manage(){
    global $member;

    return social_login_link_account($member['mb_id'], false, 'mb_form');
}

function social_member_comfirm_redirect(){
    global $is_member;

    if( !$is_member ){
        return;
    }

    $provider_name = get_session('ss_social_provider');

    if( social_get_provider_service_name($provider_name) ){

        try
        {
            $adapter = social_login_get_provider_adapter( $provider_name );
            
            // then grab the user profile 
            $user_profile = $adapter->getUserProfile();
        }

        catch( Exception $e )
        {
            $get_error = social_get_error_msg( $e->getCode() );

            if( is_object( $adapter ) ){
                social_logout_with_adapter($adapter);
            }

            alert('SNS ????????? ????????? ?????????????????????.', G5_URL);
        }

        if( $user_provider = social_get_data('provider', $provider_name, $user_profile) ){
            
            social_login_session_clear(1);

            $url = G5_BBS_URL.'/register_form.php';

            $social_token = social_nonce_create($provider_name);
            set_session('social_link_token', $social_token);
            
            $params = array('provider'=>$provider_name);

            $url = replaceQueryParams($url, $params);
            goto_url($url);

        }

        set_session('ss_social_provider', '');
        alert('????????? ???????????????.', G5_URL);
    }
}

function social_is_edit_page($url=''){
    global $is_member;

    if( !$is_member ) return false;

    if($url){
        $p = @parse_url($url);
        $host = preg_replace('/:[0-9]+$/', '', $_SERVER['HTTP_HOST']);

        if ( isset($p['host']) && ($p['host'] === $host) && preg_match('/register_form\.php$/i', $url) ){
            return true;
        }
    }

    return false;
}

function social_is_login_password_check($mb_id){
    global $g5;

    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $provider_name = social_get_request_provider();

    if(!$mb_id || $action === 'link'){ //???????????? ?????????, ?????? ????????????
        if($action === 'link'){    //?????????????????? ?????? ??????????????? ?????? ??????

            $sql = sprintf("select count(*) as num from {$g5['social_profile_table']} where provider = '%s' and mb_id = '%s' ", $provider_name, $mb_id);

            $row = sql_fetch($sql);
            if( $row['num'] ){
                alert("?????? ????????? ?????? $provider_name ID ??? ???????????? ????????????. ????????? ?????? ??? ?????? ????????? ?????????.");
            }
        }
        return false;
    }

    //?????? ???????????? ????????? ???????????????.
    if( $user_profile = social_session_exists_check() ){

        // db??? ?????? ?????? ????????? ???????????? ????????????
        if( $user_provider = social_get_data('provider', $provider_name, $user_profile) ){

            if($user_provider['mb_id'] == $mb_id)
                return true;
        }
    }

    return false;
}

//?????? ????????? ??? ?????? ????????????
function social_login_success_after($mb, $link='', $mode='', $tmp_create_info=array()){
    global $g5, $config;

    $provider = social_get_request_provider();

    if( isset($mb['mb_id']) && !empty($mb['mb_id']) && $provider && $user_profile = social_session_exists_check() ){
        
        $mb_id = $mb['mb_id'];
        //???????????? ?????? ?????????  ?????? ???????????? ???????????? ?????? ?????? ????????? ???????????? ?????????.
        social_user_profile_replace($mb_id, $provider, $user_profile);

        //?????????????????? provider ??????( naver, kakao, facebook ?????? ?????? ) ????????? ????????? ????????? ???????????????.
        set_session('ss_social_provider', $provider);

        //??????????????? ?????? ????????? ????????? ????????? ?????? ???????????????.
        if( isset($_SESSION['sl_userprofile']) && isset($_SESSION['sl_userprofile'][$provider]) ){
            unset($_SESSION['sl_userprofile'][$provider]);
        }

        if($mode=='register'){   //???????????? ?????????
            return;
        }

    }

    return $link;
}

function social_login_link_account($mb_id, $is_buffer=false, $is_type=''){
    global $g5, $is_admin, $is_guest, $member, $config;

    if( !$mb_id )
        return;

    $sql = "select * from {$g5['social_profile_table']} where mb_id = '".$mb_id."' ";

    $result = sql_query($sql);

    $my_social_accounts = array();

    for($i=0;$row=sql_fetch_array($result);$i++){
        $my_social_accounts[] = $row;
    }

    if( $is_type === 'get_data' ){
        return $my_social_accounts;
    }

    ob_start();

    if( $is_type === 'mb_form' ) {

        global $urlencode;

        static $social_pop_once;

        $my_provides = array();

        foreach( $my_social_accounts as $account ){
            $my_provides[] = strtolower($account['provider']);
        }

        $self_url = G5_BBS_URL."/login.php";

        //????????? ???????????????
        if( G5_SOCIAL_USE_POPUP )
            $self_url = G5_SOCIAL_LOGIN_URL.'/popup.php';

        include(get_social_skin_path().'/social_u_register_form.skin.php');
    }

    $html = ob_get_clean();

    if($is_buffer){
        return $html;
    } else {
        echo $html;
    }
}

function social_get_provider_service_name($provider='', $all=''){

    $services = array(
        'naver' =>  '?????????',
        'kakao'  =>  '?????????',
        'daum'  =>  '??????',
        'facebook'  =>  '????????????',
        'google'    =>  '??????',
        'twitter'  =>  '?????????',
        'payco'  =>  '?????????',
        );

    if( $all ){
        return $services;
    }

    $provider = $provider ? strtolower($provider) : '';

    return ($provider && isset($services[$provider])) ? $services[$provider] : '';
}

function social_provider_logout($provider='', $session_delete=1){
    
    $provider = $provider ? $provider : get_session('ss_social_provider');

    if( $provider ){

        try
        {
            if( ! class_exists( 'Hybrid_Auth', false ) )
            {
                include_once G5_SOCIAL_LOGIN_PATH . "/Hybrid/Auth.php";
            }

            Hybrid_Auth::logoutAllProviders();
            
            /*
            if( $adapter = social_login_get_provider_adapter( $provider ) ){
                $adapter->logout();
            }
            */
            if( $session_delete )
                set_session('ss_social_provider', '');
        }

        catch( Exception $e ){
            if( is_object( $adapter ) ){
                social_logout_with_adapter($adapter);
            }
        }
    }
}

//?????? ????????? ??????????????? ?????? ?????????
function social_member_link_delete($mb_id, $mp_no=''){

    global $g5;

    if(!$mb_id)
        return;

    $mp_no = (int) $mp_no;

    if( G5_SOCIAL_DELETE_DAY > 0 ){

        //mb_id??? ?????? ?????? ????????? ?????? ?????? ????????? ????????? db ???????????? ???????????????.
        $time = date("Y-m-d H:i:s", time() - (86400 * (int) G5_SOCIAL_DELETE_DAY));

        $sql = "delete from {$g5['social_profile_table']} where mb_id = '' and mp_latest_day < '$time' ";
        sql_query($sql);

        $sql = "update {$g5['social_profile_table']} set mb_id='', object_sha='', profileurl='', photourl='', displayname='', mp_latest_day = '".G5_TIME_YMDHIS."' where mb_id= '".$mb_id."'";
    } else {
        $sql = "delete from {$g5['social_profile_table']} where mb_id= '".$mb_id."'"; //?????? ???????????????.
    }

    if($mp_no){
        $sql .= " and mp_no=$mp_no";
    }

    sql_query($sql, false);
}

function social_service_check($provider){
    global $config;

    if( $config['cf_social_servicelist'] && option_array_checked($provider, $config['cf_social_servicelist']) ) {
        return true;
    }
    
    return false;
}

function exist_mb_id_recursive($mb_id){
    static $count = 0;

    $mb_id_add = ($count > 0) ? $mb_id.(string)$count : $mb_id;

    if( ! exist_mb_id($mb_id_add) ){
        return $mb_id_add;
    }
    
    $count++;
    return exist_mb_id_recursive($mb_id);
}

function exist_mb_nick_recursive($mb_nick){
    static $count = 0;

    $mb_nick_add = ($count > 0) ? $mb_nick.(string)$count : $mb_nick;

    if( ! exist_mb_nick($mb_nick_add, '') ){
        return $mb_nick_add;
    }
    
    $count++;
    return exist_mb_nick_recursive($mb_nick);
}

function social_get_nonce($key=''){
    if( $key == 'd' ){  //nonce_duration
        return 7200;
    } else if ($key == 'n' ){   //nonce_name
        return '_nonce';
    } else {

        if( empty($key) )
            $key = social_get_request_provider();

        $setting = social_build_provider_config($key);
        try{
            return sha1($setting['providers'][$key]['secret']);
        } catch(Exception $e) {
            return '';
        }
    }

    return '';
}

function social_nonce_create_query_string( $action = '' , $user = '', $provider = '' ){
    if($nonce_key=social_get_nonce('n')){
        return $nonce_key."=".social_nonce_create( $action , $user, $provider );
    }
    return '';
}

function social_nonce_create( $action = '' , $user='' , $provider = '' ){
    return substr( social_nonce_generate_hash( $action , $user, $provider ), -12, 10);
}

function social_nonce_is_valid( $nonce , $action = '' , $user='' , $provider = '' ){
    // Nonce generated 0-12 hours ago
    if ( substr(social_nonce_generate_hash( $action , $user, $provider ), -12, 10) == $nonce ){
        return true;
    }
    return false;
}

function social_nonce_generate_hash( $action='' , $user='', $provider = '' ){
    $i = ceil( time() / ( social_get_nonce('d') / 2 ) );
    return md5( $i . $action . $user . social_get_nonce($provider) );
}
?>