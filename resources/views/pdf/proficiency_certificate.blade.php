<!DOCTYPE html>
<html>
<head>
<style>
@page {
  margin: 0;
  padding: 0;
  size: A4 landscape;
}

/* Prevents automatic page breaks */
body {
  margin: 0;
  padding: 0;
  width: 297mm;
  height: 210mm;
  position: relative;
  overflow: hidden;
}

/* Background images for each certificate type */
.Gold {
  background-image: url("https://sms.evolvu.in/public/proficiencycertificategold.jpg");
  background-size: cover;
  background-repeat: no-repeat;
  background-position: center;
}
.Silver {
  background-image: url("https://sms.evolvu.in/public/proficiencycertificatesilver.jpg");
  background-size: cover;
  background-repeat: no-repeat;
  background-position: center;
}
.Bronze {
  background-image: url("https://sms.evolvu.in/public/proficiencycertificatebronze.jpg");
  background-size: cover;
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

/* Container positioning */
.pdfdiv {
  width: 100%;
  height: 100%;
  position: relative;
}

.content {
  position: absolute;
  top: 35%;
  left: 10%;
  right: 10%;
  text-align: center;
}

.signature {
  position: absolute;
  bottom: 8%;
  left: 45%;
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



