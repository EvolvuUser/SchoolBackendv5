<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

<style type = "text/css">
@page {
    size: A4;
    margin-top:0;
    margin-bottom:0;
    margin-left:-2;
    margin-right:0;
    padding: 0;
  }
    @media print {
    .element-that-contains-table {
        overflow: visible !important;
    }
}
  body {
    background-image: url('{{ str_replace('\\', '/', public_path('reportcard/JPS/reportcard.png')) }}');
   -webkit-background-size: cover;
    -moz-background-size: cover;
    -o-background-size: cover;
    background-size: cover;
    object-fit: cover;
    background-repeat:no-repeat;
}
    p{
        color:blue;
        font-family: Comic Sans MS;
    }
    h4{
        color:red;
        font-family: 'Comic Sans MS' !important;
    }


    .th{
        vertical-align:middle;
        text-align:center;
        height:30px;
		font-size:12px;
        border:1px solid grey;
        text-transform: uppercase;
        padding-top: 8px;
    }

    .th1{
        vertical-align:middle;
        text-align:center;
        height:30px;
		font-size:12px;
        border:1px solid grey;
        text-transform: uppercase;
        color:red;
        padding-top: 8px;
    }
	.thc{
        vertical-align:middle;
        text-align:center;
        height:30px;
        border:1px solid grey;
    }
    .statistics_line {
        width:100%;
        border-bottom:1px solid #000;
        padding:3px;
    }
    .td1{
        vertical-align:middle;
        text-align:center;
        height:30px;
        border:1px solid grey;
        text-transform: uppercase;
    }
    .td2{
        vertical-align:middle;
        text-align:left;
        height:30px;
        border:1px solid grey;
        text-transform: uppercase;
        padding-left:18px;
        background-color: orange;
    }
    .td{
        vertical-align:middle;
        height:30px;
        border:1px solid grey;
		font-size:12px;
    }
    .td3{
        vertical-align:middle;
        text-align:left;
        height:30px;
        border:1px solid grey;
        color:#0000A0;
        font-size:21px;
        padding-left:30px;
    }
    .td4{
        vertical-align:middle;
        text-align:left;
        height:30px;
        border:1px solid grey;
        text-transform: uppercase;
        color:#0000A0;
        background-color:silver;
        padding-left:30px;
        font-size:21px;
    }
    .lasttd{
        text-align:center;
        border:1px solid grey;
		font-size:14px;
    }
	.pdfdiv {
	   page-break-after: always;
	}
	.pdfdiv:last-child{
		page-break-after: avoid;
		page-break-inside: avoid;
		margin-bottom: 0px;
	}
</style>
</head>
<body>
@foreach ($reports ?? [$reportCardData] as $reportCardData)
<?php
$row1 = (array) $reportCardData['student'];
$class_name = $row1['class_name'];
$section_name = $row1['section_name'];
$settings = $reportCardData['settings'];
$term_list = array_map(
    fn ($block) => (object) ['term_id' => $block['term_number'], 'name' => $block['exam']->name],
    $reportCardData['blocks']
);
$gradeFor = static function ($value, $maximum, $type = 'Scholastic') use ($reportCardData) {
    if ($value === 'Ab') {
        return 'Ab';
    }
    if (!is_numeric($value) || $maximum <= 0) {
        return '';
    }
    $ranges = collect($reportCardData['grades'][$type] ?? []);
    $mark = round($value / $maximum * (float) $ranges->max('mark_upto'), 0, PHP_ROUND_HALF_UP);
    foreach ($ranges as $range) {
        if ($mark >= $range->mark_from && $mark <= $range->mark_upto) {
            return $range->name;
        }
    }
    return '';
};
?>
<div class="col-md-12 pdfdiv">
<div class="col-md-2"></div>
	<div class="col-md-8 table-responsive bgimg" style="text-align:center;">
        <table border="0" style="width:85%;margin-left:5%;margin-right: auto;border-spacing: 0px;background-color:white;margin-top: 21%;">
			<tr>
				<td style="width:30%;text-align: left;font-size:14px;" >
					UDISE No. - 27251207823
				</td>
				<td style="width:40%;text-align: center;">
					<h4 >ACADEMIC SESSION <?php echo $row1['academic_yr']; ?></h4>
					<h3><font color="#000000">REPORT CARD</font></h3>
				</td>
				<td style="width:30%;text-align: left;font-size:14px;margin-left: 30px;" >

				</td>
			</tr>
		</table>
		<table border="0"  class="table-responsive" style="width:85%;margin-left:5%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="1" cellspacing="10">
			<tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:16px;width: 25%; padding-top: 6px; padding-bottom:6px;	word-wrap:break-word;">Student's Name : </td>
						<td style="font-size:16px;text-align: center;width: auto"><div class="statistics_line"><?php echo $row1['first_name'] . ' ' . $row1['mid_name'] . ' ' . $row1['last_name']; ?></div> </td>
						<td style="font-size:16px;width: 1%;"></td>
						<td style="font-size:16px;width: 15%;margin-left: 10px;padding-top: 8px; padding-bottom:8px;	word-wrap:break-word;">Roll No. : </td>
						<td style="font-size:18px;width: 8%;text-align: center;"><div class="statistics_line"> <?php echo $row1['roll_no']; ?></div></td>
                    </table>
                </td>
			</tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:16px;padding:5px;width: 38%;padding-top: 8px; padding-bottom:8px;  word-wrap:break-word;">Mother's/Father's / Guardian's Name : </td>
                        <td style="font-size:16px;padding:5px;width: 42%;text-align: center;"><div class="statistics_line"><?php echo ($row1['father_name'] ?? ''); ?></div></td>
						<td style="width: 1%;"></td>
						<td style="font-size:16px;margin-left: 10px;word-wrap:break-word;width:10%">GR No. : </td>
						<td style="font-size:16px;margin-left: 10px;word-wrap:break-word;width:auto;text-align: center;width:auto"><div class="statistics_line"> <?php echo $row1['reg_no']; ?></div></td>
                    </table>

                </td>

            </tr>
            <tr>
                <td>
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
                        <td style="font-size:16px;padding:5px;width: 17%;padding-top: 8px; padding-bottom:8px;  word-wrap:break-word;">Date of Birth : </td>
                        <td style="width:25%;text-align: center;"><div class="statistics_line"><?php echo date_format(date_create($row1['dob']), 'd-m-Y'); ?></div></td>
						<td style="width: 5%;"></td>
                        <td style="font-size:16px;padding:5px;width: 20%;padding-top: 8px; padding-bottom:8px;  word-wrap:break-word;">Class / Section : </td>
						<td style="width: auto;text-align: center;"><div class="statistics_line"><?php echo $class_name . ' ' . $section_name; ?></div></td>
                    </table>

                </td>

            </tr>
		</table>
		                <?php
        $grandTotals = array_fill(0, count($reportCardData['blocks']), ['total' => 0, 'maximum' => 0]);
        $assessmentColumnCounts = array_fill(0, count($reportCardData['blocks']), 0);
        foreach ($reportCardData['groups'] as $group) {
            foreach ($group['schema'] as $index => $schema) {
                $assessmentColumnCounts[$index] = max(
                    $assessmentColumnCounts[$index],
                    count($schema) + count($group['periodic_schema'][$index])
                );
            }
        }
        ?>
        <table class="table-responsive" style="width:85%; margin-left: 5%; margin-right: auto; border-spacing: 0px; background-color:white; " cellpadding="0" cellspacing="0">
            <tr>
                <td style="vertical-align:middle;" cellpadding="0" cellspacing="0">
                    <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;border: 1px solid grey !important;" cellpadding="0" cellspacing="0">
                        <tr>
                            <th class="col-md-3 col-sm-3 col-xs-3 th" style="word-wrap: break-word; font-size:10px"><b>Scholastic Areas</b></th>
                            @foreach ($reportCardData['blocks'] as $index => $block)
                                <th class="col-md-1 th1" style="text-align:center;height:30px;" colspan="{{ $assessmentColumnCounts[$index] + 2 }}">{{ $block['exam']->name }}</th>
                            @endforeach
                        </tr>
                        @forelse ($reportCardData['groups'] as $group)
                            {{-- Repeat only the assessment headings when a subject uses a different scheme. --}}
                            <tr>
                                <td class="col-md-3 td" style="text-align:center;height:30px;">SUBJECT</td>
                                @foreach ($reportCardData['blocks'] as $index => $block)
                                    @foreach ($group['periodic_schema'][$index] as [$name, $maximum])
                                        <td class="col-md-1 td" style="vertical-align:middle;text-align:center;height:30px;">@if ($block['term_number'] === 1){{ $block['source_label'] }}<br/>@endif{{ $name }}<br/>({{ $maximum + 0 }})</td>
                                    @endforeach
                                    @foreach ($group['schema'][$index] as [$name, $maximum])
                                        <td class="col-md-1 td" style="vertical-align:middle;text-align:center;height:30px;">{{ $block['exam']->name }}<br/>{{ $name }}<br/>({{ $maximum + 0 }})</td>
                                    @endforeach
                                    @php($padding = $assessmentColumnCounts[$index] - count($group['schema'][$index]) - count($group['periodic_schema'][$index]))
                                    @if ($padding > 0)
                                        <td class="col-md-1 td" style="vertical-align:middle;text-align:center;height:30px;" colspan="{{ $padding }}">&nbsp;</td>
                                    @endif
                                    <td class="col-md-1 td" style="vertical-align:middle;text-align:center;height:30px;">Total<br/>({{ $group['rows'][0]['terms'][$index]['maximum'] }})</td>
                                    <td class="col-md-1 td" style="vertical-align:middle;text-align:center;height:30px;">Grade</td>
                                @endforeach
                            </tr>
                            @foreach ($group['rows'] as $row)
                                <tr>
                                    <td class="col-md-1 td" style="text-align:center;height:30px;">{{ $row['name'] }}</td>
                                    @foreach ($row['terms'] as $index => $term)
                                        @foreach ($term['periodic'] as $heading)
                                            <td class="col-md-1 td" style="vertical-align:middle;text-align:center;height:30px;">{{ $heading['value'] }}</td>
                                        @endforeach
                                        @foreach ($term['components'] as $component)
                                            <td class="col-md-1 td" style="vertical-align:middle;text-align:center;height:30px;">{{ $component['value'] }}</td>
                                        @endforeach
                                        <?php $padding = $assessmentColumnCounts[$index] - count($term['components']) - count($term['periodic']); ?>
                                        @if ($padding > 0)
                                            <td class="col-md-1 td" style="vertical-align:middle;text-align:center;height:30px;" colspan="{{ $padding }}">&nbsp;</td>
                                        @endif
                                        <td class="col-md-1 td" style="vertical-align:middle;text-align:center;height:30px;">{{ $term['total'] }}</td>
                                        <td class="col-md-1 td" style="vertical-align:middle;text-align:center;height:30px;">{{ $gradeFor($term['total'], $term['maximum']) }}</td>
                                        <?php
                                        if (is_numeric($term['total'])) {
                                            $grandTotals[$index]['total'] += $term['total'];
                                            $grandTotals[$index]['maximum'] += $term['maximum'];
                                        }
                                        ?>
                                    @endforeach
                                </tr>
                            @endforeach
                        @empty
                            <tr><td class="td" style="text-align:center;height:30px;" colspan="{{ 1 + array_sum($assessmentColumnCounts) + count($reportCardData['blocks']) * 2 }}">No published Term I or Term II assessments are available.</td></tr>
                        @endforelse
                        @if ($reportCardData['groups'])
                            <tr>
                                <td class="td" style="text-align:center;height:45px;">TOTAL</td>
                                @foreach ($reportCardData['blocks'] as $index => $block)
                                    @for ($column = 0; $column < $assessmentColumnCounts[$index]; $column++)
                                        <td class="td" style="border:1px solid grey;text-align:center;height:45px;">&nbsp;</td>
                                    @endfor
                                    <td class="td" style="text-align:center;height:45px;">{{ $grandTotals[$index]['total'] }}/{{ $grandTotals[$index]['maximum'] }}</td>
                                    <td class="td" style="text-align:center;height:45px;">{{ $gradeFor($grandTotals[$index]['total'], $grandTotals[$index]['maximum']) }}</td>
                                @endforeach
                            </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

<table class="table-responsive" style="width:90%;margin-left: 4%;margin-right: auto;border-spacing: 0px;background-color:white;">
			 <tr>
				 <td style="" cellpadding="0" cellspacing="0">
                    <table class="table-responsive" style="width:auto;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="0" cellspacing="0">
						<tr>

                            <td class="" style="vertical-align:middle;" cellpadding="0" cellspacing="0">
                                                        <table class="table-responsive" style="width:100%;margin-left: auto;margin-right: auto;border-spacing: 0px;background-color:white; font-size:15px;" cellpadding="2" cellspacing="0">
                                <tr>
                                    <th class="td" cellpadding="0" colspan="{{ count($term_list) + 1 }}">CO- SCHOLASTICS AREA (Graded on 5 point Scale)</th>
                                </tr>
                                <tr>
                                    <th class="col-md-3 th" style="height:25px;">Subjects</th>
                                    @foreach ($term_list as $term)
                                        <th class="col-md-1 th1" style="height:25px;" width="">{{ $term->name }}</th>
                                    @endforeach
                                </tr>
                                @foreach ($reportCardData['co_scholastic'] as $row)
                                    <tr>
                                        <td class="col-md-1 td" style="text-align:center;height:20px;">{{ $row['name'] }}</td>
                                        @foreach ($row['terms'] as $term)
                                            <td class="td" style="text-align:center;height:20px;">{{ $term['grade'] }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </table>
                          </td>
				</tr>
			</table>
                 </td>
                 <td ></td>
				<td >
                     <table border="1" class="table-responsive" style="width:auto;margin-left: 3%; margin-right: auto;border-spacing: 0px;background-color:white;margin-top: 0%;border-color:grey" cellpadding="0" cellspacing="0">
                         @php($scholasticGradeScale = collect($reportCardData['grades']['Scholastic'] ?? []))
                         <tr>
                            <th class="col-md-3 lasttd" colspan="2" style="text-align:center;"> <div style="font-size:15px"> GRADING SCALE FOR SCHOLASTIC AREAS </div><div style="font-size:12px">Grades are awarded on a {{ $scholasticGradeScale->count() }} Point grading scale as follows</div></th>
                        </tr>
                        <tr>
                            <td class="col-md-1 lasttd" style="">MARKS RANGE</td>
                            <td class="col-md-1 lasttd">GRADE</td>
                        </tr>
                        @forelse ($scholasticGradeScale as $range)
                            <tr>
                                <td class="col-md-1 lasttd">{{ $range->mark_from + 0 }}-{{ $range->mark_upto + 0 }}</td>
                                <td class="col-md-1 lasttd">{{ $range->name }}</td>
                            </tr>
                        @empty
                            <tr><td class="col-md-1 lasttd" colspan="2">Grading scale is not configured.</td></tr>
                        @endforelse
                     </table>
                 </td>
             </tr>
        </table>
			<table class="table-responsive" style="width:90%;margin-left:4%;margin-right: auto;border-spacing: 0px;background-color:white;" cellpadding="4" cellspacing="0" border="0">
			<tr>
			   <!--td-->
				<?php
                $remarkParts = [];
                $width = '100%';
                foreach ($term_list as $term) {
                    $remark = $reportCardData['remarks'][$term->term_id]->remark ?? '';
                    if ($remark !== '') {
                        $remarkParts[] = e('Term ' . $term->term_id . ' - ' . $remark);
                    }
                }
                $remark_string = $remarkParts ? implode('<br/>', $remarkParts) : '<br>';
                ?>
				<td style="font-size:14px;width: 28%;"><b>Class Teacher's Remark :</b></td>
				<td style="font-size:12px;width: 70%;">
				<div class="statistics_line">
				<?php
                    echo $remark_string;
                    ?>
				</div>
				</td>
        </tr>
		<?php

        $date_from = $reportCardData['date_from'];
        $date_to = $reportCardData['date_to'];
        ?>
        <tr>
            <td colspan="2">
				<table class="table-responsive" border=0 style="width:100%;margin-left:auto;margin-right: auto;border-spacing: 0px;background-color:white;">
                    <tr>
						<td style="font-size:14px;text-align:left;white-space:nowrap;width: 10% !important" ><b> Attendance : </b></td>
					<td style="font-size:14px;white-space:nowrap;width:20%!important;margin-right:2%;text-align:center;" ><div class="statistics_line">
						<?php
                        if (($reportCardData['attendance']->present ?? '') <> '') {
                            echo ($reportCardData['attendance']->present ?? '') . '/' . ($reportCardData['attendance']->working ?? '');
                        }
                        ?>&nbsp;
							</div>
						</td>
					<?php
                    if (count($term_list) > 1) {
                        ?>
                        <td style="font-size:14px;;text-align:left;white-space:nowrap;width: 15%;"><b> Promoted To :</b></td>
						<?php
                        $promote_to = '';
                        if (isset($term_list[1]->term_id))
                            $promote_to = ($reportCardData['remarks'][$term_list[1]->term_id]->promot ?? '');

                        ?>
                        <td style="margin-left:5%;text-align:center;font-size:14px;width:20%!important;">
						<div class="statistics_line"><?php echo $promote_to; ?>&nbsp;</div> </td>
                        <td style="text-align:center;font-size:14px;width:15%;"><b> Date Of Reopening :</b></td>
                        <td style="width:auto;text-align:center;" ><div class="statistics_line">
							<?php
                            $reopen_date = $reportCardData['reopen_date'];
                            if ($reopen_date <> NULL && $reopen_date <> '0000-00-00')
                                echo date_format(date_create($reopen_date), 'd-m-Y');
                            ?>
						&nbsp;</div></td>
					<?php } else { ?>
						<td style="width: auto"> </td>
					<?php } ?>
                    </tr>
                </table>

			 </td>

        </tr>
		</table>
	    <table class="table-responsive" style="width:86%;margin-left:5%;margin-right: auto;margin-top:8px;margin-bottom:18px;border-spacing: 0px;background-color:white;overflow: visible !important;page-break-inside:avoid;" cellpadding="1" cellspacing="0">
			<tr>
				<td style="width:33%;">
					<table class="table-responsive" width="90%" cellspacing="0">
						<tr>
							<td style="width:30%;text-align: center;"><div class="statistics_line"> </div></td>
						</tr>
					</table>
				</td>
				<td style="width:33%;">
					<table class="table-responsive" width="90%" cellspacing="0">
						<tr>
							<td style="width:30%;text-align: center;"><div class="statistics_line"> </div></td>
						</tr>
					</table>
				</td>
				<td style="width:33%;">
					<table class="table-responsive" width="90%" cellspacing="0">
						 <tr>
							<td style="width:30%;text-align: center;"><div class="statistics_line"> </div></td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td style="width:33%;">
					<table class="table-responsive" width="100%" cellspacing="0">
						<tr>
							<td style="font-size:13px;" width="30%;text-align: center;"><b>Class Teacher's Sign.</b></td>
						</tr>
					</table>
				</td>
				<td style="width:33%;">
					<table class="table-responsive" width="90%" cellspacing="0">
						<tr>
							<th style="font-size:10px;text-align:center;" width="15%" ></th>
							<td style="font-size:13px;text-align:left;width:30%;"><b>Parent's Sign.</b></td>
						</tr>
					</table>
				</td>
				<td style="width:33%;">
					<table class="table-responsive" width="90%" cellspacing="0">
						<tr>
							<th style="font-size:10px;text-align:center;" width="15%" ></th>
							<td style="font-size:13px;text-align:left;width:30%;"><b>Principal's Sign.</b></td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
    </div>
</div>
@endforeach
</body>
</html>
