<style>
@page {
    size: A4;
    margin-top:0;
    margin-bottom:0;
    margin-left:1;
    margin-right:1;
    padding: 0;
  }
    body{
    background-image: url('http://103.159.85.174/SchoolBackendv5/public/bonafide.jpg');
    -webkit-background-size: cover;
    -moz-background-size: cover;
    -o-background-size: cover;
    background-size: cover;
    object-fit: cover;
    background-repeat:no-repeat;

}
 tr td{
	padding-top: 3px; 
	padding-bottom:3px;
	word-wrap:break-word;
	font-size:14px;
 }
 
 
</style>
<html>
<div class="pdfdiv"> <!--Ends Here -->
    <center>
<!--	<div style="width:100%;height:95%;margin: auto;text-align:center;border-style:groove;border:4px groove grey;">-->


					
	<br/><center>
    <div style="width:95%;margin-top:20%;display: inline-block">
        	<img src=""  class="image_thumbnail studimg" width="100" height="100" style="padding-left: 70%;"/>
<center><p style="font-size:20px"><b>BONAFIDE CERTIFICATE</b></p></center>
<center><p style="font-size:20px"><b>To whomsoever it may concern</b></p></center>

<p style="font-size:15px;"> <span style="margin-left:10px;"><b> Ref. No : {{$data->sr_no}}</b></span></p>

<!--<p style="font-size:15px"> <b></span></b></p>-->

<p style="font-size:15px"><span style="margin-left:20px;">This is to certify that Mst/Miss.<b>{{$data->stud_name}},</b> son /daughter of <b>Mr.{{$data->father_name}}</b>is/was studying in our school in class- {{$data->class_division}} , for the academic year {{$data->academic_yr}}. </span></p>
<p style="font-size:15px"><span style="">According to our record her date of birth is {{$data->dob}} {{$data->dob_words}}. </span></p>
<p style="font-size:15px"><span style=""> {{$data->purpose}}.</span></p>
<br>
<p style="font-size:18px"><span style="">Date : {{$data->issue_date_bonafide}}<span style="margin-left:50%"> Fr. Sunil Memezes </span></p>

</div>
</div>
</html>