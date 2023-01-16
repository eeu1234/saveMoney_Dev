<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// input의 name을 wset[배열키] 형태로 등록
// 모바일 설정값은 동일 배열키에 배열변수만 wmset으로 지정 → wmset[배열키]

if(!$wset['new']) $wset['new'] = 'red';
if(!$wset['tab']) $wset['tab'] = 'red';

?>

<div class="tbl_head01 tbl_wrap">
	<table>
	<caption>스킨설정</caption>
	<colgroup>
		<col class="grid_2">
		<col>
	</colgroup>
	<thead>
	<tr>
		<th scope="col">구분</th>
		<th scope="col">설정</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td align="center">탭라인</td>
		<td>
			<select name="wset[tab]">
				<?php echo apms_color_options($wset['btn1']);?>
			</select>
		</td>
	</tr>
	<tr>
		<td align="center">썸네일</td>
		<td>
			<?php echo help('기본 400x540 - 미입력시 기본값 적용');?>
			<input type="text" name="wset[thumb_w]" value="<?php echo $wset['thumb_w']; ?>" class="frm_input" size="4">
			x
			<input type="text" name="wset[thumb_h]" value="<?php echo $wset['thumb_h']; ?>" class="frm_input" size="4">
			px 
			&nbsp;
			<select name="wset[shadow]">
				<?php echo apms_shadow_options($wset['shadow']);?>
			</select>
			&nbsp;
			<label><input type="checkbox" name="wset[inshadow]" value="1"<?php echo get_checked('1', $wset['inshadow']); ?>> 내부그림자</label>
		</td>
	</tr>
	<tr>
		<td align="center">이미지</td>
		<td>
			<input type="text" name="wset[gap]" value="<?php echo $wset['gap']; ?>" class="frm_input" size="4"> px 좌우간격(기본 15)
			&nbsp;	
			<input type="text" name="wset[gapb]" value="<?php echo $wset['gapb']; ?>" class="frm_input" size="4"> px 상하간격(기본 30)
		</td>
	</tr>
	<tr>
		<td align="center">가로수</td>
		<td>
			<input type="text" name="wset[item]" value="<?php echo $wset['item']; ?>" class="frm_input" size="4"> 개
			(기본 4개, 반응형 기본 lg 3개, md 3개, sm 2개, xs 2개)
			<div class="h10"></div>
			<table>
			<thead>
			<tr>
				<th scope="col">구분</th>
				<th scope="col">lg(1199px~)</th>
				<th scope="col">md(991px~)</th>
				<th scope="col">sm(767px~)</th>
				<th scope="col">xs(480px~)</th>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td align="center">가로(개)</td>
				<td align="center">
					<input type="text" name="wset[lg]" value="<?php echo $wset['lg']; ?>" class="frm_input" size="4">
				</td>
				<td align="center">
					<input type="text" name="wset[md]" value="<?php echo $wset['md']; ?>" class="frm_input" size="4">
				</td>
				<td align="center">
					<input type="text" name="wset[sm]" value="<?php echo $wset['sm']; ?>" class="frm_input" size="4">
				</td>
				<td align="center">
					<input type="text" name="wset[xs]" value="<?php echo $wset['xs']; ?>" class="frm_input" size="4">
				</td>
			</tr>
			</tbody>
			</table>
		</td>
	</tr>
	<tr>
		<td align="center">출력설정</td>
		<td>
			<input type="text" name="wset[line]" value="<?php echo $wset['line']; ?>" class="frm_input" size="4"> 줄(기본 2)
			&nbsp;
			<label><input type="checkbox" name="wset[hit]" value="1"<?php echo get_checked('1', $wset['hit']);?>> 조회</label>
			&nbsp;
			<label><input type="checkbox" name="wset[cmt]" value="1"<?php echo get_checked('1', $wset['cmt']);?>> 댓글</label>
			&nbsp;
			<label><input type="checkbox" name="wset[buy]" value="1"<?php echo get_checked('1', $wset['buy']);?>> 구매</label>
			&nbsp;
			<label><input type="checkbox" name="wset[sns]" value="1"<?php echo get_checked('1', $wset['sns']);?>> SNS</label>
			&nbsp;
			<select name="wset[star]">
				<option value="">별점</option>
				<?php echo apms_color_options($wset['star']);?>
			</select>
		</td>
	</tr>
	<tr>
		<td align="center">새아이템</td>
		<td>
			<input type="text" name="wset[newtime]" value="<?php echo ($wset['newtime']);?>" size="3" class="frm_input"> 시간 이내 등록 아이템
			&nbsp;
			색상
			<select name="wset[new]">
				<?php echo apms_color_options($wset['new']);?>
			</select>
		</td>
	</tr>
	</tbody>
	</table>
</div>