<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$skin_url.'/style.css" media="screen">', 0);

if($header_skin)
	include_once('./header.php');

?>

<div style="max-width:340px; margin:0 auto;">
	<div class="form-box">
		<div class="form-header">
			<h2><b><i class="fa fa-smile-o"></i> Have a Nice Day!</b></h2>
		</div>
		<div class="form-body">
			<form class="form" role="form" name="flogin" action="<?php echo $login_action_url ?>" onsubmit="return flogin_submit(this);" method="post">
			<input type="hidden" name="url" value='<?php echo $login_url ?>'>
				<div class="form-group has-feedback">
					<label for="login_id"><b>아이디</b><strong class="sound_only"> 필수</strong></label>
					<input type="text" name="mb_id" id="login_id" required class="form-control input-sm" size="20" maxLength="20">
					<span class="fa fa-user form-control-feedback"></span>
				</div>
				<div class="form-group has-feedback">
					<label for="login_pw"><b>비밀번호</b><strong class="sound_only"> 필수</strong></label>
					<input type="password" name="mb_password" id="login_pw" required class="form-control input-sm" size="20" maxLength="20">
					<span class="fa fa-lock form-control-feedback"></span>
				</div>
				<div class="row">
					<div class="col-xs-6">
						<label class="checkbox-inline remember-me">
							<input type="checkbox" name="auto_login" id="login_auto_login"> 자동로그인
						</label>
					</div>
					<div class="col-xs-6">
						<button type="submit" class="btn btn-color pull-right"><i class="fa fa-sign-in fa-lg"></i> 로그인</button>
					</div>
				</div>
			</form>
			<?php if($config['cf_social_login_use']) { //소셜 로그인 사용시
				$social_pop_once = false;

				$self_url = G5_BBS_URL."/login.php";

				//새창을 사용한다면
				if( G5_SOCIAL_USE_POPUP ) {
					$self_url = G5_SOCIAL_LOGIN_URL.'/popup.php';
				}
			?>
				<div id="sns_login" class="sns-wrap-over">
					<div class="sns-wrap">
						<?php if( social_service_check('naver') ) {     //네이버 로그인을 사용한다면 ?>
						<a href="<?php echo $self_url;?>?provider=naver&amp;url=<?php echo $urlencode;?>" class="sns-icon social_link sns-naver" title="네이버">
							<span class="ico"></span>
							<span class="txt">네이버<i> 로그인</i></span>
						</a>
						<?php }     //end if ?>
						<?php if( social_service_check('kakao') ) {     //카카오 로그인을 사용한다면 ?>
						<a href="<?php echo $self_url;?>?provider=kakao&amp;url=<?php echo $urlencode;?>" class="sns-icon social_link sns-kakao" title="카카오">
							<span class="ico"></span>
							<span class="txt">카카오<i> 로그인</i></span>
						</a>
						<?php }     //end if ?>
						<?php if( social_service_check('facebook') ) {     //페이스북 로그인을 사용한다면 ?>
						<a href="<?php echo $self_url;?>?provider=facebook&amp;url=<?php echo $urlencode;?>" class="sns-icon social_link sns-facebook" title="페이스북">
							<span class="ico"></span>
							<span class="txt">페이스북<i> 로그인</i></span>
						</a>
						<?php }     //end if ?>
						<?php if( social_service_check('google') ) {     //구글 로그인을 사용한다면 ?>
						<a href="<?php echo $self_url;?>?provider=google&amp;url=<?php echo $urlencode;?>" class="sns-icon social_link sns-google" title="구글">
							<span class="ico"></span>
							<span class="txt">구글+<i> 로그인</i></span>
						</a>
						<?php }     //end if ?>
						<?php if( social_service_check('twitter') ) {     //트위터 로그인을 사용한다면 ?>
						<a href="<?php echo $self_url;?>?provider=twitter&amp;url=<?php echo $urlencode;?>" class="sns-icon social_link sns-twitter" title="트위터">
							<span class="ico"></span>
							<span class="txt">트위터<i> 로그인</i></span>
						</a>
						<?php }     //end if ?>
						<?php if( social_service_check('payco') ) {     //페이코 로그인을 사용한다면 ?>
						<a href="<?php echo $self_url;?>?provider=payco&amp;url=<?php echo $urlencode;?>" class="sns-icon social_link sns-payco" title="페이코">
							<span class="ico"></span>
							<span class="txt">페이코<i> 로그인</i></span>
						</a>
						<?php }     //end if ?>

						<?php if( G5_SOCIAL_USE_POPUP && !$social_pop_once ){
						$social_pop_once = true;
						?>
						<script>
							jQuery(function($){
								$(".sns-wrap").on("click", "a.social_link", function(e){
									e.preventDefault();

									var pop_url = $(this).attr("href");
									var newWin = window.open(
										pop_url, 
										"social_sing_on", 
										"location=0,status=0,scrollbars=1,width=600,height=500"
									);

									if(!newWin || newWin.closed || typeof newWin.closed=='undefined')
										 alert('브라우저에서 팝업이 차단되어 있습니다. 팝업 활성화 후 다시 시도해 주세요.');

									return false;
								});
							});
						</script>
						<?php } ?>

					</div>
				</div>
			<?php } ?>
		</div>
		<div class="form-footer">
			<p class="text-center">
				<a href="./register.php"><i class="fa fa-sign-in"></i> 회원가입</a>
				<a href="<?php echo G5_BBS_URL ?>/password_lost.php" target="_blank" id="login_password_lost"><i class="fa fa-search"></i> 정보찾기</a>
			</p>
		</div>
	</div>

<?php if ($default['de_level_sell'] == 1) { // 상품구입 권한 ?>

	<!-- 주문하기, 신청하기 -->
	<?php if (preg_match("/orderform.php/", $url)) { ?>

		<div class="form-box">
			<div class="form-header">
				<h2><b>비회원 구매</b></h2>
			</div>
			<div class="form-body">
				<p><i class="fa fa-caret-right text-muted"></i> 비회원 주문은 포인트를 지급하지 않습니다.</p>
				<p><i class="fa fa-caret-right text-muted"></i> <b>개인정보처리방침안내</b></p>
				<table class="table">
				<colgroup>
					<col width="30%">
					<col width="30%">
				</colgroup>
				<thead>
				<tr>
					<th>목적</th>
					<th>항목</th>
					<th>보유기간</th>
				</tr>
				</thead>
				<tbody>
				<tr>
					<td>이용자 식별 및 본인 확인</td>
					<td>이름, 비밀번호</td>
					<td>5년(전자상거래등에서의 소비자보호에 관한 법률)</td>
				</tr>
				<tr>
					<td>배송 및 CS대응을 위한 이용자 식별</td>
					<td>주소, 연락처(이메일, 휴대전화번호)</td>
					<td>5년(전자상거래등에서의 소비자보호에 관한 법률)</td>
				</tr>
				</tbody>
				<tfoot>
				<tr>
					<td colspan="3">
						<label for="agree" class="checkbox-inline"><input type="checkbox" id="agree" value="1"> 개인정보처리방침안내의 내용에 동의합니다.</label>
					</td>
				</tr>
				</tfoot>
				</table>
				
				<a href="javascript:guest_submit(document.flogin);" class="btn btn-color btn-block">비회원으로 구매하기</a>

				<script>
				function guest_submit(f) {
					if (document.getElementById('agree')) {
						if (!document.getElementById('agree').checked) {
							alert("개인정보처리방침안내의 내용에 동의하셔야 합니다.");
							return;
						}
					}

					f.url.value = "<?php echo $url; ?>";
					f.action = "<?php echo $url; ?>";
					f.submit();
				}
				</script>
			</div>
		</div>

	<?php } else if (preg_match("/orderinquiry.php$/", $url)) { ?>

		<div class="form-box">
			<div class="form-header">
				<h2><b>비회원 주문조회</b></h2>
			</div>
			<div class="form-body">
				<p>메일로 발송해드린 주문서의 <strong>주문번호</strong> 및 주문 시 입력하신 <strong>비밀번호</strong>를 정확히 입력해주십시오.</p>

				<form class="form" role="form" name="forderinquiry" method="post" action="<?php echo urldecode($url); ?>" autocomplete="off">
					<div class="form-group has-feedback">
						<label for="od_id" class="od_id"><b>주문서번호</b><strong class="sound_only"> 필수</strong></label>
						<input type="text" name="od_id" value="<?php echo $od_id; ?>" id="od_id" required class="form-control input-sm" size="20">
						<span class="fa fa-user form-control-feedback"></span>
					</div>
					<div class="form-group has-feedback">
						<label for="id_pwd" class="od_pwd"><b>비밀번호</b><strong class="sound_only"> 필수</strong></label>
						<input type="password" name="od_pwd" size="20" id="od_pwd" required class="form-control input-sm">
						<span class="fa fa-lock form-control-feedback"></span>
					</div>

					<button type="submit" class="btn btn-color btn-block">확인하기</button>

				</form>
			</div>
		</div>

	<?php } ?>
<?php } ?>

	<div class="text-center" style="margin:30px 0px;">
		<a href="<?php echo G5_URL ?>/" class="btn btn-black btn-sm" role="button">메인으로</a>
	</div>
</div>

<script>
$(function(){
    $("#login_auto_login").click(function(){
        if (this.checked) {
            this.checked = confirm("자동로그인을 사용하시면 다음부터 회원아이디와 비밀번호를 입력하실 필요가 없습니다.\n\n공공장소에서는 개인정보가 유출될 수 있으니 사용을 자제하여 주십시오.\n\n자동로그인을 사용하시겠습니까?");
        }
    });
});

function flogin_submit(f) {
    return true;
}
</script>
<!-- } 로그인 끝 -->