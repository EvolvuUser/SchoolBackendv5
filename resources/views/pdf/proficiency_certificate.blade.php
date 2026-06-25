{{-- @php
$school = getSchoolDetails();

$bgImageGold = getProficiencyGoldBgImage();
$bgImageSilver = getProficiencySilverBgImage();
$bgImageBronze = getProficiencyBronzeBgImage();

$goldBgPath = (!empty($bgImageGold) && !empty($bgImageGold['file_path']))
    ? asset($bgImageGold['file_path'])
    : asset('health3_bg.jpg');

$silverBgPath = (!empty($bgImageSilver) && !empty($bgImageSilver['file_path']))
    ? asset($bgImageSilver['file_path'])
    : asset('health3_bg.jpg');

$bronzeBgPath = (!empty($bgImageBronze) && !empty($bgImageBronze['file_path']))
    ? asset($bgImageBronze['file_path'])
    : asset('health3_bg.jpg');
@endphp --}}


@php
$school = getSchoolDetails();

$bgImageGold = getProficiencyGoldBgImage();
$bgImageSilver = getProficiencySilverBgImage();
$bgImageBronze = getProficiencyBronzeBgImage();

/*
|--------------------------------------------------------------------------
| Determine Certificate Type
|--------------------------------------------------------------------------
| You must already be passing $type = Gold/Silver/Bronze
*/
$type = $type ?? 'Gold';

/*
|--------------------------------------------------------------------------
| SWITCH LOGIC FOR BACKGROUND + PAGE TYPE
|--------------------------------------------------------------------------
*/
switch ($type) {

    case 'Gold':
        $bgImage = $bgImageGold;
        $classType = 'Gold';
        break;

    case 'Silver':
        $bgImage = $bgImageSilver;
        $classType = 'Silver';
        break;

    case 'Bronze':
        $bgImage = $bgImageBronze;
        $classType = 'Bronze';
        break;

    default:
        $bgImage = $bgImageGold;
        $classType = 'Gold';
        break;
}

/*
|--------------------------------------------------------------------------
| Background Path
|--------------------------------------------------------------------------
*/
$bgPath = (!empty($bgImage) && !empty($bgImage['file_path']))
    ? $bgImage['file_path']
    : 'health3_bg.jpg';

/*
|--------------------------------------------------------------------------
| Page Type (FROM DB)
|--------------------------------------------------------------------------
*/
$pageType = $bgImage['page_type'] ?? 'A4 landscape';

/*
|--------------------------------------------------------------------------
| Page Size and Top Margin
|--------------------------------------------------------------------------
*/
switch ($pageType) {

    case 'A4 landscape':
        $pageSize = 'A4 landscape';
        $contentMarginTop = '35%';
        break;

    case 'A5 portrait':
        $pageSize = 'A5 portrait';
        $contentMarginTop = '45%';
        break;

    case 'A5 landscape':
        $pageSize = 'A5 landscape';
        $contentMarginTop = '45%';
        break;

    case 'Letter portrait':
        $pageSize = 'letter portrait';
        $contentMarginTop = '15%';
        break;

    case 'Letter landscape':
        $pageSize = 'letter landscape';
        $contentMarginTop = '20%';
        break;

    case 'A4 portrait':
    default:
        $pageSize = 'A4 portrait';
        $contentMarginTop = '45%';
        break;
}
@endphp

<!DOCTYPE html>
<html>
<head>
    
    <style>
@page {
    margin: 0;
    padding: 0;
    size: {{ $pageSize }};
}

html, body {
    margin: 0;
    padding: 0;
    width: 100%;
    height: 100%;
}

/* Prevent automatic page breaks */
body {
    position: relative;
    overflow: hidden;
}

/* Background images for each certificate type */
.Gold {
    background-image: url('{{ asset($bgImageGold['file_path']) }}');
    background-size: 100% 100%;
    background-repeat: no-repeat;
    background-position: center;
}

.Silver {
    background-image: url('{{ asset($bgImageSilver['file_path']) }}');
    background-size: 100% 100%;
    background-repeat: no-repeat;
    background-position: center;
}

.Bronze {
    background-image: url('{{ asset($bgImageBronze['file_path']) }}');
    background-size: 100% 100%;
    background-repeat: no-repeat;
    background-position: center;
}

/* Text and table styling */
tr td {
    padding-top: 3px;
    padding-bottom: 3px;
    font-size: 20px;
    text-align: left;
    font-family: DejaVu Sans, sans-serif;
}

.statistics_line {
    width: 100%;
    border-bottom: 1px solid #000;
    padding: 3px;
    display: inline-block;
    text-align: center;
}

/* Main container */
.pdfdiv {
    width: 100%;
    height: 100%;
    position: relative;
}

/* Content positioning */
.content {
    position: absolute;
    top: {{ $contentMarginTop }};
    left: 10%;
    right: 10%;
    text-align: center;
}

/* Signature positioning */
.signature {
    position: absolute;
    bottom: 8%;
    left: 45%;
}

/* Optional image quality improvements */
img {
    max-width: 100%;
    height: auto;
}
</style>


</head>
<body class="{{ $type }}">
<div class="pdfdiv">
  <div class="content">
    <table style="width:100%; margin: auto;">
      <tr>
        <td>
          <table style="width:100%;">
            <tr>
              <td style="font-style:italic; font-size:20px; width:35%;">Awarded to Master / Miss</td>
              <td style="font-size:20px; text-align:center;">
                <div class="statistics_line">{{ $student_name }}</div>
              </td>
            </tr>
          </table>
          <br>
        </td>
      </tr>

      <tr>
        <td>
          <table style="width:100%;">
            <tr>
              <td style="font-style:italic; font-size:20px;">of std</td>
              <td style="font-size:20px; text-align:center;">
                <div class="statistics_line">{{ $class }}</div>
              </td>
              <td style="font-style:italic; font-size:20px;">Div</td>
              <td style="font-size:20px; text-align:center;">
                <div class="statistics_line">{{ $section }}</div>
              </td>
            </tr>
          </table>
          <br>
        </td>
      </tr>

      <tr>
        <td>
          <table style="width:100%;">
            <tr>
              <td style="font-style:italic; font-size:20px;">for</td>
              <td style="font-size:20px; text-align:center;">
                <div class="statistics_line">EXCELLENT PERFORMANCE</div>
              </td>
            </tr>
          </table>
          <br>
        </td>
      </tr>

      <tr>
        <td>
          <table style="width:100%;">
            <tr>
              <td style="font-style:italic; font-size:20px;">in</td>
              <td style="font-size:20px; text-align:center;">
                <div class="statistics_line">{{ $term_name }} {{ $term_label }}</div>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </div>

  <div class="signature">
    <img src="https://sms.evolvu.in/public/Principal Cert Signature.png" width="70" height="50">
  </div>
</div>
</body>
</html>



